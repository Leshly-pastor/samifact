<?php

namespace App\CoreFacturalo;

use Exception;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use App\Traits\KardexTrait;
use Illuminate\Support\Str;
use App\Models\Tenant\Voided;
use App\Models\Tenant\Company;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Summary;
use Mpdf\Config\FontVariables;
use App\Models\Tenant\Dispatch;
use App\Models\Tenant\Document;
use App\Models\Tenant\Retention;
use Mpdf\Config\ConfigVariables;
use App\Models\Tenant\Perception;
use App\Mail\Tenant\DocumentEmail;
use App\Models\Tenant\DispatchItem;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\Establishment;
use Modules\Inventory\Models\Warehouse;
use Modules\Finance\Traits\FinanceTrait;
use App\CoreFacturalo\WS\Client\WsClient;
use App\Models\Tenant\PurchaseSettlement;
use App\CoreFacturalo\Helpers\Xml\XmlHash;
use App\CoreFacturalo\WS\Signed\XmlSigned;
use App\CoreFacturalo\Helpers\Xml\XmlFormat;
use Modules\Finance\Traits\FilePaymentTrait;
use App\CoreFacturalo\WS\Services\BillSender;
use App\CoreFacturalo\WS\Services\ExtService;
use App\CoreFacturalo\Requests\Inputs\Functions;
use App\CoreFacturalo\WS\Services\SummarySender;
use App\Http\Controllers\Tenant\EmailController;
use App\CoreFacturalo\WS\Services\SunatEndpoints;
use App\CoreFacturalo\Helpers\QrCode\QrCodeGenerate;
use App\CoreFacturalo\WS\Services\ConsultCdrService;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\Services\Helpers\SendDocumentPse;
use App\CoreFacturalo\WS\Validator\XmlErrorCodeProvider;
use App\Models\Tenant\Catalogs\UnitType;
use App\Models\Tenant\DispatchRelated;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\Item;
use App\Models\Tenant\ItemSeller;
use App\Models\Tenant\ItemUnitType;
use App\Models\Tenant\ItemWarehousePrice;
use App\Services\PseService;
use App\Models\Tenant\NoStockDocument;
use App\Services\PseServiceDispatch;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\GlobalPayment;
use Modules\Suscription\Models\Tenant\SuscriptionPayment;

/**
 * Class Facturalo
 *
 * @package App\CoreFacturalo
 */
class Facturalo
{
    use StorageDocument, FinanceTrait, KardexTrait, FilePaymentTrait;

    const REGISTERED = '01';
    const SENT = '03';
    const ACCEPTED = '05';
    const OBSERVED = '07';
    const REJECTED = '09';
    const CANCELING = '13';
    const VOIDED = '11';

    protected $configuration;
    protected $company;
    protected $isDemo;
    protected $isOse;
    protected $signer;
    protected $wsClient;
    protected $document;
    protected $documents;
    protected $type;
    protected $actions;
    protected $xmlUnsigned;
    protected $xmlSigned;
    protected $pathCertificate;
    protected $soapUsername;
    protected $soapPassword;
    protected $endpoint;
    protected $response;
    protected $apply_change;
    protected $sendDocumentPse;

    public function __construct($company = null)
    {
        $this->configuration = Configuration::first();
        if ($company) {
            $this->company = $company;
        } else {
            $this->company = Company::active();
        }
        $this->isDemo = ($this->company->soap_type_id === '01') ? true : false;
        $this->isOse = ($this->company->soap_send_id === '02') ? true : false;
        $this->signer = new XmlSigned();
        $this->wsClient = new WsClient();
        $this->sendDocumentPse = new SendDocumentPse($this->company);
        $this->setDataSoapType();
    }
    public function sendPseNew()
    {
        if ($this->configuration->send_auto) {
            $new_pse = new PseService($this->document);
            $this->response = $new_pse->sendToPse();
        }
    }

    public function sendPseNewDispatch()
    {
        $new_pse = new PseServiceDispatch($this->document);
        $this->response = $new_pse->sendToPse();
    }
    public function sendPseDispatch()
    {
        $new_pse = new PseServiceDispatch($this->document);
        $this->response = $new_pse->sendToPse();
    }
    public function sendPse()
    {

        // $pse = new PseService($this->document);
        // $xml_string = $pse->create_as_xmlcab();
    }
    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function setXmlUnsigned($xmlUnsigned)
    {
        $this->xmlUnsigned = $xmlUnsigned;
    }

    public function getXmlSigned()
    {
        return $this->xmlSigned;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function save($inputs, $duplicate = false)
    {
        if (isset($inputs['actions']) == true) {
            $this->actions = array_key_exists('actions', $inputs) ? $inputs['actions'] : [];
        } else {
            $this->actions = [];
        }
        if (isset($inputs['type']) == true) {
            $this->type = $inputs['type'];
        } else {
            $this->type = 'invoice';
        }

        switch ($this->type) {
            case 'debit':
            case 'credit':
                $document = Document::create($inputs);
                $document->note()->create($inputs['note']);
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                if ($this->type === 'credit') $this->saveFee($document, $inputs['fee']);
                $this->document = Document::find($document->id);
                break;
            case 'invoice':

                if ($duplicate == false) {
                    $document = Document::create($inputs);
                    $this->savePayments($document, $inputs['payments']);
                    $this->saveFee($document, $inputs['fee']);
                    foreach ($inputs['items'] as $row) {
                        $update_price = Functions::valueKeyInArray($row, 'update_price', false);
                        if ($update_price) {
                            try {
                                $price = floatval($row["unit_price"]);
                                if (isset($row["item"]["presentation"]["id"])) {
                                    $id = $row["item"]["presentation"]["id"];
                                    $unit_type = ItemUnitType::find($id);
                                    if ($unit_type) {
                                        switch ($unit_type->price_default) {
                                            case 1:
                                                $unit_type->price1 = $price;
                                                break;
                                            case 2:
                                                $unit_type->price2 = $price;
                                                break;
                                            default:
                                                $unit_type->price3 = $price;
                                                break;
                                        }

                                        $unit_type->save();
                                    }
                                } else {

                                    $item_id = $row["item_id"];
                                    Item::where('id', $item_id)->update(["sale_unit_price" => $price]);
                                    if (isset($row["warehouse_id"]) && $row["warehouse_id"] != null) {
                                        ItemWarehousePrice::where('item_id', $item_id)
                                            ->where('warehouse_id', $row["warehouse_id"])
                                            ->update(["sale_unit_price" => $price]);
                                    }
                                }
                            } catch (Exception $e) {
                                Log::error($e->getMessage());
                            }
                        }
                        $document_item = $document->items()->create($row);
                        if ((bool)$this->configuration->multi_sellers) {
                            $seller_id = Functions::valueKeyInArray($row, 'seller_id');

                            if ($seller_id == null) {
                                $seller_id = $document->seller_id;
                                if ($seller_id == null) {
                                    $seller_id = auth()->user()->id;
                                }
                            }
                            ItemSeller::create([
                                'seller_id' => $seller_id,
                                'document_item_id' => $document_item->id
                            ]);
                        }
                    }
                    $this->updatePrepaymentDocuments($inputs);
                    if ($inputs['hotel']) $document->hotel()->create($inputs['hotel']);
                    if ($inputs['transport']) $document->transport()->create($inputs['transport']);
                    if ($inputs['transport_dispatch']) $document->transport_dispatch()->create($inputs['transport_dispatch']);
                    $document->invoice()->create($inputs['invoice']);

                    $this->document = Document::find($document->id);
                    if ($document->no_stock) {
                        NoStockDocument::create([
                            'document_id' => $document->id,
                            'completed' => false
                        ]);
                    }
                } else {
                    $obj = Document::find($inputs->id);
                    $this->documents = $obj->replicate();
                    $doc = Document::select('number')
                        ->where('soap_type_id',  $obj->soap_type_id)
                        ->where('document_type_id', $obj->document_type_id)
                        ->where('series', $obj->series)
                        ->orderBy('number', 'desc')
                        ->first();
                    $this->documents->external_id = Str::uuid()->toString();
                    $this->documents->ticket_single_shipment = 0;
                    $this->documents->number = (int) $doc->number + 1;
                    $this->documents->hash = null;
                    // $this->documents->qr = null;
                    $this->documents->state_type_id = '01';
                    $company = Company::first();
                    $filename = Functions::filename($company, $this->documents->document_type_id, $this->documents->series, $this->documents->number);
                    $this->documents->unique_filename = $filename;
                    $this->documents->filename = $filename;
                    $this->documents->save();
                    $this->savePayments_duplicate($this->documents, $this->documents->payments);
                    $this->saveFee($this->documents, $this->documents->fee);
                    foreach ($inputs->items as $row) {
                        DocumentItem::create([
                            'document_id' => $this->documents->id,
                            'item_id' => $row->item_id,
                            'item'    => $row->item,
                            'quantity' => $row->quantity,
                            'unit_value' => $row->unit_value,
                            'affectation_igv_type_id' => $row->affectation_igv_type_id,
                            'total_base_igv' => $row->total_base_igv,
                            'percentage_igv' => $row->percentage_igv,
                            'total_igv' => $row->total_igv,
                            'system_isc_type_id' => $row->system_isc_type_id,
                            'total_base_isc' => $row->total_base_isc,
                            'percentage_isc' => $row->percentage_isc,
                            'total_isc'      => $row->total_isc,
                            'total_base_other_taxes' => $row->total_base_other_taxes,
                            'percentage_other_taxes' => $row->percentage_other_taxes,
                            'total_other_taxes' => $row->total_other_taxes,
                            'total_plastic_bag_taxes' => $row->total_plastic_bag_taxes,
                            'total_taxes' => $row->total_taxes,
                            'price_type_id' => $row->price_type_id,
                            'unit_price' => $row->unit_price,
                            'total_value' => $row->total_value,
                            'total_charge' => $row->total_charge,
                            'total_discount' => $row->total_discount,
                            'total' => $row->total,
                            'attributes' => $row->attributes,
                            'discounts' => $row->discounts,
                            'charges' => $row->charges,
                            'additional_information' => "",
                            'warehouse_id' => $row->warehouse_id,
                            'name_product_pdf' => $row->name_product_pdf,
                            'total_discount' => $row->total_discount,
                            'additional_data' => $row->additional_data,
                            'name_product_xml' => $row->name_product_xml
                        ]);
                    }
                    $this->updatePrepaymentDocuments($inputs);
                    if (isset($inputs->hotel)  && $inputs->hotel) $this->documents->hotel()->create($inputs->hotel);
                    if (isset($inputs->transport) && $inputs->transport) $this->documents->transport()->create($inputs->transport);
                    if (isset($inputs->transport_dispatch)  && $inputs->transport_dispatch) $this->documents->transport_dispatch()->create($inputs->transport_dispatch);
                    Invoice::create(
                        [
                            'document_id' => $this->documents->id,
                            'operation_type_id' => $inputs->invoice->operation_type_id,
                            'date_of_due' => $inputs->invoice->date_of_due
                        ]
                    );
                    $this->document = Document::find($this->documents->id);
                }

                break;
            case 'summary':
                $document = Summary::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Summary::find($document->id);
                break;
            case 'voided':
                $document = Voided::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Voided::find($document->id);
                break;
            case 'retention':
                $document = Retention::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Retention::find($document->id);
                break;
            case 'perception':
                $document = Perception::create($inputs);
                foreach ($inputs['documents'] as $row) {
                    $document->documents()->create($row);
                }
                $this->document = Perception::find($document->id);
                break;
            case 'purchase_settlement':
                $document = PurchaseSettlement::create($inputs);
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                $this->document = PurchaseSettlement::find($document->id);
                break;
            default:
                DispatchItem::query()->where('dispatch_id', $inputs['id'])->delete();
                DispatchRelated::query()->where('dispatch_id', $inputs['id'])->delete();
                $document = Dispatch::query()->updateOrCreate([
                    'id' => $inputs['id']
                ], $inputs);
                if ($inputs['dispatches_related']) {
                    foreach ($inputs['dispatches_related'] as $row) {
                        $serie = $row['serie_number'];
                        $company_number = $row['company_number'];
                        if ($serie && $company_number) {
                            DispatchRelated::create([
                                'dispatch_id' => $document->id,
                                'serie_number' => $serie,
                                'company_number' => $company_number,
                            ]);
                        }
                    }
                }
                foreach ($inputs['items'] as $row) {
                    $document->items()->create($row);
                }
                $this->document = Dispatch::find($document->id);
                break;
        }
        if ($duplicate == true) {
            return $this->document;
        } else {
            return $this;
        }
    }

    public function sendEmail()
    {
        $send_email = ($this->actions['send_email'] === true) ? true : false;

        if ($send_email) {

            $company = $this->company;
            $document = $this->document;
            $email = ($this->document->customer) ? $this->document->customer->email : $this->document->supplier->email;
            $mailable = new DocumentEmail($company, $document);
            $id =  $document->id;
            $model = __FILE__ . ";;" . __LINE__;
            $sendIt = EmailController::SendMail($email, $mailable, $id, $model);
            /*
            Configuration::setConfigSmtpMail();
            $array_email = explode(',', $email);
            if (count($array_email) > 1) {
                foreach ($array_email as $email_to) {
                    $email_to = trim($email_to);
                if(!empty($email_to)) {
                        Mail::to($email_to)->send(new DocumentEmail($company, $document));
                    }
                }
            } else {
                Mail::to($email)->send(new DocumentEmail($company, $document));
            }
            */
        }
    }

    public function createXmlUnsigned($id = null)
    {

        if ($id != null) {
            $this->document = Document::find($id);
        }
        $template = new Template();

        $this->xmlUnsigned = XmlFormat::format($template->xml($this->type, $this->company, $this->document));

        $this->uploadFile($this->xmlUnsigned, 'unsigned');
        return $this;
    }


    /**
     * Firma digital xml
     */
    public function signXmlUnsigned()
    {

        //validar si es que el documento se enviara al pse para la agregar la firma
        if ($this->sendToPse()) {
            $this->xmlSigned = $this->sendDocumentPse->signXml($this->xmlUnsigned, $this->document);
        } else {

            $this->setPathCertificate();
            $this->signer->setCertificateFromFile($this->pathCertificate);
            $this->xmlSigned = $this->signer->signXml($this->xmlUnsigned);
        }

        $this->uploadFile($this->xmlSigned, 'signed');

        return $this;
    }

    public function updateHash()
    {
        $this->document->update([
            'hash' => $this->getHash(),
        ]);
    }

    public function updateQr()
    {
        if (config('tenant.save_qrcode')) {
            $this->document->update([
                'qr' => $this->getQr(),
            ]);
        }
    }

    public function updateState($state_type_id)
    {

        $this->document->update([
            'state_type_id' => $state_type_id,
            'soap_shipping_response' => isset($this->response['sent']) ? $this->response : null
        ]);
    }

    public function updateSoap($soap_type_id, $type)
    {
        $this->document->update([
            'soap_type_id' => $soap_type_id
        ]);
        // if($type === 'invoice') {
        //     $invoice = Invoice::where('document_id', $this->document->id)->first();
        //     $invoice->date_of_due = $this->document->date_of_issue;
        //     $invoice->save();
        // }
    }

    public function updateStateDocuments($state_type_id)
    {
        foreach ($this->document->documents as $doc) {
            $doc->document->update([
                'state_type_id' => $state_type_id
            ]);
        }
    }

    private function getHash()
    {
        $helper = new XmlHash();
        return $helper->getHashSign($this->xmlSigned);
    }

    private function getQr()
    {
        $customer = $this->document->customer;
        $text = join('|', [
            $this->company->number,
            $this->document->document_type_id,
            $this->document->series,
            $this->document->number,
            $this->document->total_igv,
            $this->document->total,
            $this->document->date_of_issue->format('Y-m-d'),
            $customer->identity_document_type_id,
            $customer->number,
            $this->document->hash
        ]);
        if (substr($text, -1) !== '|') {
            $text .= '|';
        }

        $qrCode = new QrCodeGenerate();
        $qr = $qrCode->displayPNGBase64($text);
        return $qr;
    }

    public function createPdf($document = null, $type = null, $format = null, $output = 'pdf')
    {
        $configuration = Configuration::first();

        ini_set("pcre.backtrack_limit", "5000000");
        $template = new Template();
        $pdf = new Mpdf();
        $format_pdf = $this->actions['format_pdf'] ?? null;

        $this->document = ($document != null) ? $document : $this->document;
        if ($configuration->multi_companies &&  $this->document->alter_company) {
            $company = Company::where('website_id', $this->document->alter_company->website_id)->first();
            if ($company) {
                $this->company = $company;
            }
        }
        $format_pdf = ($format != null) ? $format : $format_pdf;
        $this->type = ($type != null) ? $type : $this->type;

        if (in_array($this->document->document_type_id, ['09', '31'])) {
            if ($this->document->qr_url) {
                $qrCode = new QrCodeGenerate();
                $this->document->qr = $qrCode->displayPNGBase64($this->document->qr_url);
            }
        }

        $base_pdf_template = Establishment::find($this->document->establishment_id)->template_pdf;
        if (($format_pdf === 'ticket') or
            ($format_pdf === 'ticket_58') or
            ($format_pdf === 'ticket_50')
        ) {
            $base_pdf_template = Establishment::find($this->document->establishment_id)->template_ticket_pdf;
        }

        $pdf_margin_top = 15;
        $pdf_margin_right = 15;
        $pdf_margin_bottom = 15;
        $pdf_margin_left = 15;

        if (in_array($base_pdf_template, ['personalizada_gonzalo_diseno', 'personalizada_famavet', 'famavet', 'personalizada_trujillosalud', 'hercold', 'personalizada_gonzalo_ultramix', 'personalizada_gonzalo_concremix', 'full_height', 'personalizada_drogueria', 'custom', 'personalizada_impacto', 'personalizada_default3_banks_valor', 'personalizada_default3_banks_precio', 'personalizada_pack_pro', 'personalizada_custom', 'perzonalizada_gonzalo_full_dorado', 'perzonalizada_gonzalo_full_negro', 'rounded'])) {
            $pdf_margin_top = 5;
            $pdf_margin_right = 5;
            $pdf_margin_bottom = 5;
            $pdf_margin_left = 5;
        }
        if ($base_pdf_template === 'blank' && in_array($this->document->document_type_id, ['09'])) {
            $pdf_margin_top = 15;
            $pdf_margin_right = 5;
            $pdf_margin_bottom = 15;
            $pdf_margin_left = 14;
        }
        if (substr($base_pdf_template, 0, 7) === 'facnova') {
            $pdf_margin_top = 10;
            $pdf_margin_right = 4;
            $pdf_margin_bottom = 5;
            $pdf_margin_left = 15;
        }

        $html = $template->pdf($base_pdf_template, $this->type, $this->company, $this->document, $format_pdf);

        if (($format_pdf === 'ticket') or
            ($format_pdf === 'ticket_58') or
            ($format_pdf === 'ticket_50')
        ) {
            $base_pdf_template = Establishment::find($this->document->establishment_id)->template_ticket_pdf;

            $width = ($format_pdf === 'ticket_58') ? 56 : 78;
            if (config('tenant.enabled_template_ticket_80')) $width = 76;
            if (config('tenant.enabled_template_ticket_70')) $width = 70;
            if ($format_pdf === 'ticket_50') $width = 45;

            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name = 0;
            $customer_address = 0;
            $customer_department_id = 0;
            if ($this->document->customer) {
                $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
                $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
                $customer_department_id  = ($this->document->customer->department_id == 16) ? 20 : 0;
            }
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';

            $total_prepayment = $this->document->total_prepayment != '' ? '10' : '0';
            $total_discount = $this->document->total_discount != '' ? '10' : '0';
            $was_deducted_prepayment = $this->document->was_deducted_prepayment ? '10' : '0';

            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $perception       = $this->document->perception != '' ? '10' : '0';
            $detraction       = $this->document->detraction != '' ? '50' : '0';
            $detraction       += ($this->document->detraction && $this->document->invoice->operation_type_id == '1004') ? 45 : 0;

            $total_plastic_bag_taxes       = $this->document->total_plastic_bag_taxes != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items) + $was_deducted_prepayment;
            $document_payments     = count($this->document->payments ?? []);
            $document_transport     = ($this->document->transport) ? 30 : 0;
            $document_transport_dispatch     = ($this->document->transport_dispatch) ? 30 : 0;
            $document_retention     = ($this->document->retention) ? 10 : 0;

            $extra_by_item_additional_information = 0;
            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {
                if (strlen($it->item->description) > 100) {

                    $extra_by_item_description += 24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
                if ($it->additional_information) {
                    $extra_by_item_additional_information += count($it->additional_information) * 5;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';

            $quotation_id = ($this->document->quotation_id) ? 15 : 0;

            //ajustes para footer amazonia

            if (
                $this->configuration->legend_footer
                and $format_pdf === 'ticket'
                and !in_array($base_pdf_template, ['ticket_c'])
            ) {
                $height_legend = 15;
            } elseif (
                $this->configuration->legend_footer
                and $format_pdf === 'ticket_58'
                and !in_array($base_pdf_template, ['ticket_c'])
            ) {
                $height_legend = 30;
            } elseif (
                $this->configuration->legend_footer
                and $format_pdf === 'ticket_50'
            ) {
                $height_legend = 10;
            } else {
                $height_legend = 10;
            }

            $append_height = 0;

            if ($this->type === 'dispatch') {
                $append_height = 15;
                $this->appendHeightFromDispatch($append_height, $format, $this->document);
            }

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    80 +
                        (($quantity_rows * 8) + $extra_by_item_description) +
                        ($document_payments * 8) +
                        ($discount_global * 8) +
                        $company_name +
                        $company_address +
                        $company_number +
                        $customer_name +
                        $customer_address +
                        $p_order +
                        $legends +
                        $total_exportation +
                        $total_free +
                        $total_unaffected +
                        $total_exonerated +
                        $perception +
                        $total_taxed +
                        $total_prepayment +
                        $total_discount +
                        $was_deducted_prepayment +
                        $customer_department_id +
                        $detraction +
                        $total_plastic_bag_taxes +
                        $quotation_id +
                        $extra_by_item_additional_information +
                        $height_legend +
                        $document_transport +
                        $document_transport_dispatch +
                        $append_height +
                        $document_retention
                ],
                'margin_top' => 0,
                'margin_right' => 1,
                'margin_bottom' => 0,
                'margin_left' => 1
            ]);
        } else if ($format_pdf === 'a5' && $this->type !== 'dispatch') {

            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
            $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';

            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $total_plastic_bag_taxes       = $this->document->total_plastic_bag_taxes != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items);

            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {

                if (strlen($it->item->description) > 100) {
                    $extra_by_item_description += 24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';


            $height = ($quantity_rows * 8) +
                ($discount_global * 3) +
                $company_name +
                $company_address +
                $company_number +
                $customer_name +
                $customer_address +
                $p_order +
                $legends +
                $total_exportation +
                $total_free +
                $total_unaffected +
                $total_exonerated +
                $total_taxed;
            $diferencia = 148 - (float)$height;

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    210,
                    $diferencia + $height
                ],
                'margin_top' => 2,
                'margin_right' => 5,
                'margin_bottom' => 0,
                'margin_left' => 5
            ]);
        } else if ($format_pdf === 'a5' && $this->type == 'dispatch') {

            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';


            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $total_plastic_bag_taxes       = $this->document->total_plastic_bag_taxes != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items);

            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {

                if (strlen($it->item->description) > 100) {
                    $extra_by_item_description += 24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';


            $height = ($quantity_rows * 8) +
                ($discount_global * 3) +
                $company_name +
                $company_address +
                $company_number +

                $legends +
                $total_exportation +
                $total_free +
                $total_unaffected +
                $total_exonerated +
                $total_taxed;
            $diferencia = 148 - (float)$height;

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    210,
                    $diferencia + $height
                ],
                'margin_top' => 2,
                'margin_right' => 5,
                'margin_bottom' => 0,
                'margin_left' => 5
            ]);
        } else {

            if ($base_pdf_template === 'brand') {
                $pdf_margin_top = 93.7;
                $pdf_margin_bottom = 74;
            }
            if ($base_pdf_template === 'blank' && in_array($this->document->document_type_id, ['09'])) {
                $pdf_margin_top = 110;
                $pdf_margin_bottom = 125;
            }

            $pdf_font_regular = config('tenant.pdf_name_regular');
            $pdf_font_bold = config('tenant.pdf_name_bold');

            if ($pdf_font_regular != false) {
                $defaultConfig = (new ConfigVariables())->getDefaults();
                $fontDirs = $defaultConfig['fontDir'];

                $defaultFontConfig = (new FontVariables())->getDefaults();
                $fontData = $defaultFontConfig['fontdata'];

                $pdf = new Mpdf([
                    'fontDir' => array_merge($fontDirs, [
                        app_path('CoreFacturalo' . DIRECTORY_SEPARATOR . 'Templates' .
                            DIRECTORY_SEPARATOR . 'pdf' .
                            DIRECTORY_SEPARATOR . $base_pdf_template .
                            DIRECTORY_SEPARATOR . 'font')
                    ]),
                    'fontdata' => $fontData + [
                        'custom_bold' => [
                            'R' => $pdf_font_bold . '.ttf',
                        ],
                        'custom_regular' => [
                            'R' => $pdf_font_regular . '.ttf',
                        ],
                    ],
                    'margin_top' => $pdf_margin_top,
                    'margin_right' => $pdf_margin_right,
                    'margin_bottom' => $pdf_margin_bottom,
                    'margin_left' => $pdf_margin_left,
                ]);
            } else {
                $pdf = new Mpdf([
                    'margin_top' => $pdf_margin_top,
                    'margin_right' => $pdf_margin_right,
                    'margin_bottom' => $pdf_margin_bottom,
                    'margin_left' => $pdf_margin_left
                ]);
            }
        }
        $pdf->shrink_tables_to_fit = 1;
        if (in_array($base_pdf_template, ['personalizada_gonzalo_diseno', 'hercold', 'maite'])) {

            $path_css = app_path('CoreFacturalo' . DIRECTORY_SEPARATOR . 'Templates' .
                DIRECTORY_SEPARATOR . 'pdf' .
                DIRECTORY_SEPARATOR . $base_pdf_template .
                DIRECTORY_SEPARATOR . 'fondo.css');
            //dd($path_css);
        } else {
            $path_css = app_path('CoreFacturalo' . DIRECTORY_SEPARATOR . 'Templates' .
                DIRECTORY_SEPARATOR . 'pdf' .
                DIRECTORY_SEPARATOR . $base_pdf_template .
                DIRECTORY_SEPARATOR . 'style.css');
        }

        $stylesheet = file_get_contents($path_css);


        // if (($format_pdf != 'ticket') AND ($format_pdf != 'ticket_58') AND ($format_pdf != 'ticket_50')) {
        // dd($base_pdf_template);// = config(['tenant.pdf_template'=> $configuration]);
        if (config('tenant.pdf_template_footer')) {
            $html_footer = '';
            if (($format_pdf != 'ticket') and ($format_pdf != 'ticket_58') and ($format_pdf != 'ticket_50')) {
                $html_footer = $template->pdfFooter($base_pdf_template, in_array($this->document->document_type_id, ['09']) ? null : $this->document);
                $html_footer_legend = "";
            }
            // dd($this->configuration->legend_footer && in_array($this->document->document_type_id, ['01', '03']));
            // se quiere visuzalizar ahora la legenda amazona en todos los formatos
            $html_footer_legend = '';
            if (
                $this->configuration->legend_footer
                && in_array($this->document->document_type_id, ['01', '03'])
                && !in_array($base_pdf_template, ['ticket_c'])
            ) {
                $html_footer_legend = $template->pdfFooterLegend($base_pdf_template, $document);
            }

            $pdf->SetHTMLFooter($html_footer . $html_footer_legend);
        }
        //            $html_footer = $template->pdfFooter();
        //            $pdf->SetHTMLFooter($html_footer);
        // }

        if ($base_pdf_template === 'brand') {

            $html_header = $template->pdfHeader($base_pdf_template, $this->company, in_array($this->document->document_type_id, ['09']) ? null : $this->document);
            $pdf->SetHTMLHeader($html_header);

            if (($format_pdf === 'ticket') || ($format_pdf === 'ticket_58') || ($format_pdf === 'ticket_50') || ($format_pdf === 'a5')) {
                $pdf->SetHTMLHeader("");
                $pdf->SetHTMLFooter("");
            }
        }

        if ($base_pdf_template === 'blank' && in_array($this->document->document_type_id, ['09'])) {

            $html_header = $template->pdfHeader($base_pdf_template, $this->company, $this->document);
            $pdf->SetHTMLHeader($html_header);

            $html_footer_blank = $template->pdfFooterBlank($base_pdf_template, $this->document);
            $pdf->SetHTMLFooter($html_footer_blank);
        }

        if ($base_pdf_template === 'default3_929' && in_array($this->document->document_type_id, ['03', '01'])) {
            // Solo boleta o factura #929
            $html_header = $template->pdfHeader($base_pdf_template, $this->company, $this->document);
            $pdf->SetHTMLHeader($html_header);
            $html_footer = $template->pdfFooter($base_pdf_template, $this->document);
            $pdf->SetHTMLFooter($html_footer);
        }

        if ($base_pdf_template === 'distpatch_pharmacy' && in_array($this->document->document_type_id, ['09'])) {
            // Solo para guia #1192
            $pdf->setAutoTopMargin = 'stretch'; //margen autommatico
            $pdf->autoMarginPadding  = 0;
            $pdf->setAutoBottomMargin = 'stretch';
            $html_header = $template->pdfHeader($base_pdf_template, $this->company, $this->document);
            $pdf->SetHTMLHeader($html_header);
            $html_footer = $template->pdfFooterDispatch($base_pdf_template, $this->document);
            $pdf->SetHTMLFooter($html_footer);
        }

        // para impresion automatica se requiere el resultado en html ya que es lo que se envia a las funciones de impresión
        if ($output == 'html') {
            $path_html = app_path('CoreFacturalo' . DIRECTORY_SEPARATOR . 'Templates' .
                DIRECTORY_SEPARATOR . 'pdf' .
                DIRECTORY_SEPARATOR . 'ticket_html.css');
            $ticket_html = file_get_contents($path_html);
            $pdf->WriteHTML($ticket_html, HTMLParserMode::HEADER_CSS);
            $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
            return "<style>" . $ticket_html . $stylesheet . "</style>" . $html;
        } else {
            $pdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
            $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

            $helper_facturalo = new HelperFacturalo();

            if ($helper_facturalo->isAllowedAddDispatchTicket($format_pdf, $this->type, $this->document)) {
                $helper_facturalo->addDocumentDispatchTicket($pdf, $this->company, $this->document, [
                    $template,
                    $base_pdf_template,
                    $width,
                    ($quantity_rows * 8) + $extra_by_item_description
                ]);
            }
        }
        // echo $html_header.$html.$html_footer; exit();
        $this->uploadFile($pdf->output('', 'S'), 'pdf');
        return $this;
    }



    /**
     *
     * Agregar altura para ticket de guia
     *
     * @param  float $append_height
     * @param  $document
     * @return void
     */
    private function appendHeightFromDispatch(&$append_height, $format, $document)
    {
        $base_height = 0;
        $observations = 0;
        $data_affected_document = 0;
        $transfer_reason_type = 0;
        $transport_mode_type = 0;
        $driver = 0;
        $license_plate = 0;
        $secondary_license_plates = 0;

        if ($format == 'ticket_58') {
            $base_height = 80;
            if ($document->data_affected_document) $data_affected_document = 25;
        } else {
            $base_height = 50;
            if ($document->data_affected_document) $data_affected_document = 20;
        }

        if ($document->observations) $observations = 30;
        if ($document->transfer_reason_type) $transfer_reason_type = 6;
        if ($document->transport_mode_type) $transport_mode_type = 6;
        if ($document->license_plate) $license_plate = 5;
        if ($document->secondary_license_plates) $secondary_license_plates = 5;

        if ($document->driver) {
            if ($document->driver->number)  $driver += 5;
            if ($document->driver->license)  $driver += 5;
        }

        $append_height += $base_height + $observations + $data_affected_document + $transfer_reason_type + $transport_mode_type + $driver
            + $license_plate + $secondary_license_plates;
    }


    public function loadXmlSigned()
    {
        $this->xmlSigned = $this->getStorage($this->document->filename, 'signed');
        //        dd($this->xmlSigned);
        return $this;
    }

    private function senderXmlSigned()
    {
        $this->setDataSoapType();
        $sender = in_array($this->type, ['summary', 'voided']) ? new SummarySender() : new BillSender();
        $sender->setClient($this->wsClient);
        $sender->setCodeProvider(new XmlErrorCodeProvider());

        return $sender->send($this->document->filename, $this->xmlSigned);
    }

    public function senderXmlSignedBill()
    {
        if (!$this->actions['send_xml_signed']) {
            $this->response = [
                'sent' => false,
            ];
            return;
        }
        $this->onlySenderXmlSignedBill();
    }


    /**
     *
     * Evaluar si se debe firmar el xml y enviar cdr al PSE
     * Disponible para facturas, boletas, anulaciones de facturas
     *
     * @return bool
     */
    public function sendToPse()
    {
        $send_to_pse = false;

        if ($this->company->send_document_to_pse) {
            if (in_array($this->type, ['invoice', 'dispatch', 'credit', 'debit'])) {
                $send_to_pse = true;
            } elseif (in_array($this->type, ['voided', 'summary'])) {
                $send_to_pse = $this->document->getSendToPse($this->sendDocumentPse);
            }
        }

        return $send_to_pse;
    }


    public function sendCdrToPse($cdr_zip, $document)
    {
        if ($this->sendToPse()) {
            $this->sendDocumentPse->sendCdr($cdr_zip, $document);
        }
    }

    public function onlySenderXmlSignedBill()
    {
        $res = $this->senderXmlSigned();

        //Log::info($res);
        if ($res->isSuccess()) {

            $cdrResponse = $res->getCdrResponse();
            $beta = $cdrResponse->getIsBeta();
            if ($beta && !$this->isDemo) {
                $this->response = [
                    'sent' => true,
                    'code' => "0000",
                    'description' => "El comprobante ha sido enviado a la SUNAT en modo de pruebas (BETA).",
                    'notes' => ""
                ];
                return;
            }
            $this->uploadFile($res->getCdrZip(), 'cdr');

            //enviar cdr a pse
            $this->sendCdrToPse($res->getCdrZip(), $this->document);
            //enviar cdr a pse

            $code = $cdrResponse->getCode();
            $description = $cdrResponse->getDescription();
            Log::info($code . " " . $description);
            $this->response = [
                'sent' => true,
                'code' => $cdrResponse->getCode(),
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes()
            ];

            $this->validationCodeResponse($code, $description);
        } else {
            $code = $res->getError()->getCode();
            $message = $res->getError()->getMessage();
            $this->response = [
                'sent' => true,
                'code' => $code,
                'description' => $message
            ];

            $this->validationCodeResponse($code, $message);
        }
    }

    public function validationCodeResponse($code, $message)
    {
        //Errors
        if (!is_numeric($code)) {

            if (in_array($this->type, ['retention', 'dispatch', 'perception', 'purchase_settlement'])) {
                throw new Exception("Code: {$code}; Description: {$message}");
            }

            $this->updateRegularizeShipping($code, $message);
            return;
        }
        //dd($message);
        // if($code === 'ERROR_CDR') {
        //     return;
        // }

        // if($code === 'HTTP') {
        //     // $message = 'La SUNAT no responde a su solicitud, vuelva a intentarlo.';

        //     if(in_array($this->type, ['retention', 'dispatch'])){
        //         throw new Exception("Code: {$code}; Description: {$message}");
        //     }

        //     $this->updateRegularizeShipping($code, $message);
        //     return;
        // }

        if ((int)$code === 0) {
            $this->updateState(self::ACCEPTED);
            try {
                $this->sendFilesToWebService('vida');
            } catch (Exception $e) {
                Log::error("No se envió");
                Log::error($e->getMessage());
            }
            return;
        }
        if ((int)$code < 2000) {
            //Excepciones

            if (in_array($this->type, ['retention', 'dispatch', 'perception', 'purchase_settlement'])) {
                // if(in_array($this->type, ['retention', 'dispatch'])){
                throw new Exception("Code: {$code}; Description: {$message}");
            }

            $this->updateRegularizeShipping($code, $message);
            return;
        } elseif ((int)$code < 4000) {
            //Rechazo
            $this->updateState(self::REJECTED);
        } else {
            $this->updateState(self::OBSERVED);
            //Observaciones
        }
        return;
    }


    public function updateRegularizeShipping($code, $description)
    {

        $this->document->update([
            'state_type_id' => self::REGISTERED,
            'regularize_shipping' => true,
            'response_regularize_shipping' => [
                'code' => $code,
                'description' => $description
            ]
        ]);
    }


    public function senderXmlSignedSummary()
    {
        $res = $this->senderXmlSigned();
        if ($res->isSuccess()) {
            $ticket = $res->getTicket();
            $this->updateTicket($ticket);
            $this->updateState(self::SENT);
            if ($this->type === 'summary') {
                // if($this->document->summary_status_type_id === '1') {
                if (in_array($this->document->summary_status_type_id, ['1', '2'])) {
                    $this->updateStateDocuments(self::SENT);
                } else {
                    $this->updateStateDocuments(self::CANCELING);
                }
            } else {
                $this->updateStateDocuments(self::CANCELING);
            }
            $this->response = [
                'sent' => true
            ];
        } else {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}");
        }
    }

    private function updateTicket($ticket)
    {
        $this->document->update([
            'ticket' => $ticket
        ]);
    }
    public function checkSignature($xmlString)
    {

        $pass = true;
        $xml = new \SimpleXMLElement($xmlString);
        $xml->registerXPathNamespace('ar', 'urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2');
        $xml->registerXPathNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $signatureValue = $xml->xpath('//ds:Signature/ds:SignatureValue');

        if (!empty($signatureValue)) {
            $signatureValue = (string)$signatureValue[0];
            Log::info($signatureValue);
            if (strpos($signatureValue, 'BetaPublicCert') !== false) {
                $pass = false;
            }
        }


        return $pass;
    }
    public function statusSummary($ticket)
    {
        $extService = new ExtService();
        $extService->setClient($this->wsClient);
        $extService->setCodeProvider(new XmlErrorCodeProvider());
        $res = $extService->getStatus($ticket);
        $beta = $res->getCdrResponse()->getIsBeta();
        if ($beta && !$this->isDemo) {
            throw new Exception("El CDR es de prueba, no se puede procesar.", 511); //custom exception code
        }
        if (!$res->isSuccess()) {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}", 511); //custom exception code
        } else {
            $cdrResponse = $res->getCdrResponse();
            $description = $cdrResponse->getDescription();
            $description_to_lower = strtolower($description);
            if (strpos($description_to_lower, 'error') !== false) {
                throw new Exception("Code: {$cdrResponse->getCode()}; Description: {$description}", 511); //custom exception code
            }
            $this->uploadFile($res->getCdrZip(), 'cdr');
            $this->response = [
                'sent' => true,
                'code' => $cdrResponse->getCode(),
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes(),
                'is_accepted' => $cdrResponse->isAccepted(),
                'status_code' => $extService->getCustomStatusCode(),
            ];

            $this->validationStatusCodeResponse($extService->getCustomStatusCode());
            // $this->updateState(self::ACCEPTED);

            if ($this->type === 'summary') {

                if ($extService->getCustomStatusCode() === 0) {

                    // if($this->document->summary_status_type_id === '1') {
                    if (in_array($this->document->summary_status_type_id, ['1', '2'])) {
                        $this->updateStateDocuments(self::ACCEPTED);
                    } else {
                        $this->updateStateDocuments(self::VOIDED);
                    }

                    //enviar cdr a pse
                    $this->sendCdrToPse($res->getCdrZip(), $this->document);
                    //enviar cdr a pse

                } else if ($extService->getCustomStatusCode() === 99) {

                    $this->updateStateDocuments(self::REGISTERED);
                }
            } else {

                //enviar cdr a pse
                $this->sendCdrToPse($res->getCdrZip(), $this->document);
                //enviar cdr a pse

                $this->updateStateDocuments(self::VOIDED);
            }
        }
    }

    public function validationStatusCodeResponse($status_code)
    {

        switch ($status_code) {
            case 0:
                $this->updateState(self::ACCEPTED);
                break;

            case 99:
                $this->updateState(self::REJECTED);
                break;
        }
    }

    public function consultCdr()
    {
        $consultCdrService = new ConsultCdrService();
        $consultCdrService->setClient($this->wsClient);
        $consultCdrService->setCodeProvider(new XmlErrorCodeProvider());
        $res = $consultCdrService->getStatusCdr(
            $this->company->number,
            $this->document->document_type_id,
            $this->document->series,
            $this->document->number
        );

        if (!$res->isSuccess()) {
            throw new Exception("Code: {$res->getError()->getCode()}; Description: {$res->getError()->getMessage()}");
        } else {
            $cdrResponse = $res->getCdrResponse();
            $this->uploadFile($res->getCdrZip(), 'cdr');
            $this->updateState(self::ACCEPTED);
            $this->response = [
                'sent' => true,
                'code' => $cdrResponse->getCode(),
                // para carga de voucher
                'description' => $cdrResponse->getDescription(),
                'notes' => $cdrResponse->getNotes()
            ];
        }
    }

    public function uploadFile($file_content, $file_type)
    {
        $this->uploadStorage($this->document->filename, $file_content, $file_type);
    }

    private function setDataSoapType()
    {
        $this->setSoapCredentials();
        $this->wsClient->setCredentials($this->soapUsername, $this->soapPassword);
        $this->wsClient->setService($this->endpoint);
    }

    private function setPathCertificate()
    {
        if ($this->isOse) {
            $this->pathCertificate = storage_path('app' . DIRECTORY_SEPARATOR .
                'certificates' . DIRECTORY_SEPARATOR . $this->company->certificate);
        } else {
            if ($this->isDemo) {
                $this->pathCertificate = app_path('CoreFacturalo' . DIRECTORY_SEPARATOR .
                    'WS' . DIRECTORY_SEPARATOR .
                    'Signed' . DIRECTORY_SEPARATOR .
                    'Resources' . DIRECTORY_SEPARATOR .
                    'certificate.pem');
            } else {
                $this->pathCertificate = storage_path('app' . DIRECTORY_SEPARATOR .
                    'certificates' . DIRECTORY_SEPARATOR . $this->company->certificate);
            }
        }

        //        if($this->isDemo) {
        //            $this->pathCertificate = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.
        //                'WS'.DIRECTORY_SEPARATOR.
        //                'Signed'.DIRECTORY_SEPARATOR.
        //                'Resources'.DIRECTORY_SEPARATOR.
        //                'certificate.pem');
        //        } else {
        //            $this->pathCertificate = storage_path('app'.DIRECTORY_SEPARATOR.
        //                'certificates'.DIRECTORY_SEPARATOR.$this->company->certificate);
        //        }
    }

    private function setSoapCredentials()
    {

        if ($this->isOse) {

            $this->soapUsername = $this->company->soap_username;
            $this->soapPassword = $this->company->soap_password;
        } else {

            if ($this->isDemo) {
                $this->soapUsername = $this->company->number . 'MODDATOS';
                $this->soapPassword = 'moddatos';
            } else {
                $this->soapUsername = $this->company->soap_username;
                $this->soapPassword = $this->company->soap_password;
            }
        }


        //        $this->soapUsername = ($this->isDemo)?$this->company->number.'MODDATOS':$this->company->soap_username;
        //        $this->soapPassword = ($this->isDemo)?'moddatos':$this->company->soap_password;

        if ($this->isOse) {
            $this->endpoint = $this->company->soap_url;
            //            dd($this->soapPassword);
        } else {
            switch ($this->type) {
                case 'perception':
                case 'retention':
                    $this->endpoint = ($this->isDemo) ? SunatEndpoints::RETENCION_BETA : SunatEndpoints::RETENCION_PRODUCCION;
                    break;
                case 'dispatch':
                    $this->endpoint = ($this->isDemo) ? SunatEndpoints::GUIA_BETA : SunatEndpoints::GUIA_PRODUCCION;
                    break;
                default:
                    // $this->endpoint = ($this->isDemo)?SunatEndpoints::FE_BETA:SunatEndpoints::FE_PRODUCCION;
                    $this->endpoint = ($this->isDemo) ? SunatEndpoints::FE_BETA : ($this->configuration->sunat_alternate_server ? SunatEndpoints::FE_PRODUCCION_ALTERNATE : SunatEndpoints::FE_PRODUCCION);
                    break;
            }
        }
    }

    private function updatePrepaymentDocuments($inputs)
    {
        // dd($inputs);

        if (isset($inputs['prepayments'])) {

            foreach ($inputs['prepayments'] as $row) {

                $fullnumber = explode('-', $row['number']);
                $series = $fullnumber[0];
                $number = $fullnumber[1];

                $doc = Document::where([['series', $series], ['number', $number]])->first();

                if ($doc) {

                    $total = $row['total'];
                    $balance = $doc->pending_amount_prepayment - $total;
                    $doc->pending_amount_prepayment = $balance;

                    if ($balance <= 0) {
                        $doc->was_deducted_prepayment = true;
                    }

                    $doc->save();
                }
            }
        }
    }

    public function updateResponse()
    {

        // if($this->response['sent']) {
        //     return

        //     $this->document->update([
        //         'soap_shipping_response' => $this->response
        //     ]);

        // }

    }

    private function savePayments_duplicate($document, $payments)
    {

        $total = $document->total;
        $balance = $total - collect($payments)->sum('payment');
        $search_cash = ($balance < 0) ? collect($payments)->firstWhere('payment_method_type_id', '01') : null;
        $this->apply_change = false;
        $configuration = Configuration::first();
        if ($balance < 0 && $search_cash) {
            $payments = collect($payments)->map(function ($row) use ($balance) {

                $change = null;
                $payment = $row['payment'];

                if ($row['payment_method_type_id'] == '01' && !$this->apply_change) {
                    $change = abs($balance);
                    $payment = $row['payment'] - abs($balance);
                    $this->apply_change = true;
                }

                return [
                    "id" => null,
                    "document_id" => null,
                    "sale_note_id" => null,
                    "date_of_payment" => $row['date_of_payment'],
                    "payment_method_type_id" => $row['payment_method_type_id'],
                    "reference" => $row['reference'],
                    "glosa" => isset($row['glosa']) ? $row['glosa'] : null,
                    "payment_destination_id" => isset($row['payment_destination_id']) ? $row['payment_destination_id'] : null,
                    "change" => $change,
                    "payment" => $payment,
                    "payment_received" => isset($row['payment_received']) ? $row['payment_received'] : null,
                ];
            });
        }
        foreach ($payments as $row) {

            if ($balance < 0 && !$this->apply_change) {
                $row['change'] = abs($balance);
                $row['payment'] = $row['payment'] - abs($balance);
                $this->apply_change = true;
            }

            $record = $document->payments()->create(
                [
                    'document_id' => $row->document_id,
                    'date_of_payment' => $row->date_of_payment,
                    'payment_method_type_id' => $row->payment_method_type_id,
                    'has_card' => $row->has_card,
                    'payment_received'  => $row->payment_received,
                    'payment'  => $row->payment,
                ]
            );

            // para carga de voucher
            $this->saveFilesFromPayments($row, $record, 'documents');

            //considerar la creacion de una caja chica cuando recien se crea el cliente
            if (isset($row['payment_destination_id'])) {
                $this->createGlobalPayment($record, $row);
            } else {
                if ($configuration->current_cash_destination_duplicate) {
                    $row['payment_destination_id'] = 'cash';
                    $this->createGlobalPayment($record, $row);
                } else {
                    $global_payment = GlobalPayment::where('payment_id', $row['id'])
                        ->where('payment_type', DocumentPayment::class)
                        ->first();
                    if ($global_payment) {
                        $global_payment_replicate = $global_payment->replicate();
                        $global_payment_replicate->payment_id = $record->id;
                        $global_payment_replicate->save();
                    }
                }
            }
        }
    }

    private function savePayments($document, $payments)
    {
        $total = $document->total;
        $balance = $total - collect($payments)->sum('payment');

        $search_cash = ($balance < 0) ? collect($payments)->firstWhere('payment_method_type_id', '01') : null;
        $this->apply_change = false;

        if ($balance < 0 && $search_cash) {

            $payments = collect($payments)->map(function ($row) use ($balance) {

                $change = null;
                $payment = $row['payment'];

                if ($row['payment_method_type_id'] == '01' && !$this->apply_change) {
                    $change = abs($balance);
                    $payment = $row['payment'] - abs($balance);
                    $this->apply_change = true;
                }

                return [
                    "id" => null,
                    "document_id" => null,
                    "sale_note_id" => null,
                    "date_of_payment" => $row['date_of_payment'],
                    "payment_method_type_id" => $row['payment_method_type_id'],
                    "reference" => $row['reference'],
                    "glosa" => isset($row['glosa']) ? $row['glosa'] : null,
                    "payment_destination_id" => isset($row['payment_destination_id']) ? $row['payment_destination_id'] : null,
                    "change" => $change,
                    "payment" => $payment,
                    "payment_received" => isset($row['payment_received']) ? $row['payment_received'] : null,
                ];
            });
        }

        foreach ($payments as $row) {
            if ($balance < 0 && !$this->apply_change) {
                $row['change'] = abs($balance);
                $row['payment'] = $row['payment'] - abs($balance);
                $this->apply_change = true;
            }
            $record = $document->payments()->create($row);
            $this->saveFilesFromPayments($row, $record, 'documents');

            // $customer_id = $document->customer_id;
            // $row['person_id'] = $customer_id;
            if (isset($row['payment_destination_id'])) {
                $this->createGlobalPayment($record, $row);
            }
        }
    }

    /**
     * @param array $inputs
     * @param int   $id
     */
    public function update($inputs, $id)
    {
        $this->actions = array_key_exists('actions', $inputs) ? $inputs['actions'] : [];
        $this->type = @$inputs['type'];
        // dd($inputs);
        switch ($this->type) {
            case 'invoice':
                $document = Document::find($id);
                // si cambia la serie
                if ($inputs['series'] !== $document->series) {
                    // se consulta el ultimo numero de la nueva serie
                    $last_number = Document::getLastNumberBySerie($inputs['series']);
                    // se actualiza el numero actual en $imputs
                    $inputs['number'] = $last_number + 1;
                    // cambiamos el filename
                    $inputs['filename'] = Functions::filename(Company::active(), $inputs['document_type_id'], $inputs['series'], $inputs['number']);
                }
                $document->fill($inputs);
                $document->save();

                $document->payments()->delete();
                $this->savePayments($document, $inputs['payments']);

                $document->fee()->delete();
                $this->saveFee($document, $inputs['fee']);

                $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();

                foreach ($document->items as $it) {
                    //se usa el evento deleted del modelo - InventoryKardexServiceProvider document_item_delete
                    ItemSeller::where('document_item_id', $it->id)->delete();
                    $it->delete();
                    // $this->restoreStockInWarehpuse($it->item_id, $warehouse->id, $it->quantity);
                }

                // Al editar el item, borra los registros anteriores
                // foreach ($document->items()->get() as $item) {
                //     /** @var \App\Models\Tenant\DocumentItem $item */
                //     DocumentItem::UpdateItemWarehous($item,'deleted');
                //     $item->delete();
                // }
                // $document->items()->delete();

                foreach ($inputs['items'] as $row) {
                    $document_item =  $document->items()->create($row);
                    if ((bool)$this->configuration->multi_sellers) {
                        $seller_id = Functions::valueKeyInArray($row, 'seller_id');

                        if ($seller_id == null) {
                            $seller_id = $document->seller_id;
                            if ($seller_id == null) {
                                $seller_id = auth()->user()->id;
                            }
                        }
                        ItemSeller::create([
                            'seller_id' => $seller_id,
                            'document_item_id' => $document_item->id
                        ]);
                    }
                }

                $this->updatePrepaymentDocuments($inputs);

                if ($inputs['hotel']) {
                    $document->hotel()->update($inputs['hotel']);
                }

                $document->invoice()->update($inputs['invoice']);
                $this->document = Document::find($document->id);
                $configuration = Configuration::first();
                if ($configuration->college) {
                    $document_id = $id;
                    $periods = $inputs['months'];
                    $client_id = $inputs['customer_id'];
                    $child_id = $inputs['child_id'];
                    if ($client_id && $child_id && $periods) {
                        SuscriptionPayment::where('document_id', $document_id)->delete();
                        foreach ($periods as  $period) {
                            $date = Carbon::createFromDate($period['year'], $period['value'], 1);
                            SuscriptionPayment::create([
                                'child_id' => $child_id,
                                'client_id' => $client_id,
                                'document_id' => $document_id,
                                'period' => $date,
                            ]);
                        }
                    }
                }

                break;
        }
    }
    public function sendFilesToWebService($type = null)
    {
        $company = Company::active();
        if ($company->isSmart() == false) {
            return false;
        }
        $data = [
            'xml' => $this->document->download_external_xml,
            'pdf' => $this->document->download_external_pdf,
            'cdr' => $this->document->download_external_cdr,
            'date_of_issue' => $this->document->date_of_issue->format('Y-m-d'),
            'series' => $this->document->series,
            'number' => $this->document->number,
            'state_type_id' => $this->document->state_type_id,
            'document_type_id' => $this->document->document_type_id,
            'ruc' => $company->number,
            'company_name' => $company->name,
            'type'  => $type,
        ];
        $pse_url = $company->pse_url;

        $send = new Client();
        if (substr($pse_url, -1) != '/') {
            $pse_url = $pse_url . '/';
        }
        $send->post(
            $pse_url . 'api/pse/download_files_others',
            [
                'form_params' => $data,

            ],
        );


        return true;
    }

    /**
     * @param array $actions
     *
     * @return $this
     */
    public function setActions($actions = [])
    {
        $this->actions = $actions;;
        return $this;
    }
    /**
     * Carga los elementos segun corresponda.
     *
     * @todo Falta determinar Document para credit e invoice
     *
     * @param      $id
     * @param null $type
     *
     * @return \App\CoreFacturalo\Facturalo
     */
    public function loadDocument($id, $type = null)
    {
        $this->type = $type;
        switch ($this->type) {
            case 'debit':
            case 'credit':
                $this->document = Document::find($id);
                break;
            case 'invoice':
                $this->document = Document::find($id);
                break;
            case 'summary':
                $this->document = Summary::find($id);
                break;
            case 'voided':
                $this->document = Voided::find($id);
                break;
            case 'retention':
                $this->document = Retention::find($id);
                break;
            case 'perception':
                $this->document = Perception::find($id);
                break;
            default:
                $this->document = Dispatch::find($id);
                break;
        }
        return $this;
    }

    private function saveFee($document, $fee)
    {
        foreach ($fee as $row) {
            $document->fee()->create($row);
        }
    }
}
