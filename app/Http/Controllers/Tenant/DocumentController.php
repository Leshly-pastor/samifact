<?php

namespace App\Http\Controllers\Tenant;

use App\CoreFacturalo\Facturalo;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\Helpers\Template\ReportHelper;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use App\Exports\PaymentExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchItemController;
use App\Http\Requests\Tenant\DocumentEmailRequest;
use App\Http\Requests\Tenant\DocumentRequest;
use App\Http\Requests\Tenant\DocumentUpdateRequest;
use App\Http\Resources\Tenant\DocumentCollection;
use App\Http\Resources\Tenant\DocumentResource;
use App\Imports\DocumentImportExcelFormat;
use App\Imports\DocumentsImport;
use App\Imports\DocumentsImportTwoFormat;
use App\Mail\Tenant\DocumentEmail;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\AttributeType;
use App\Models\Tenant\Catalogs\CatColorsItem;
use App\Models\Tenant\Catalogs\CatItemMoldCavity;
use App\Models\Tenant\Catalogs\CatItemMoldProperty;
use App\Models\Tenant\Catalogs\CatItemPackageMeasurement;
use App\Models\Tenant\Catalogs\CatItemProductFamily;
use App\Models\Tenant\Catalogs\CatItemStatus;
use App\Models\Tenant\Catalogs\CatItemUnitBusiness;
use App\Models\Tenant\Catalogs\CatItemUnitsPerPackage;
use App\Models\Tenant\Catalogs\ChargeDiscountType;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\DocumentType;
use App\Models\Tenant\Catalogs\NoteCreditType;
use App\Models\Tenant\Catalogs\NoteDebitType;
use App\Models\Tenant\Catalogs\OperationType;
use App\Models\Tenant\Catalogs\PriceType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\CatItemSize;
use App\Models\Tenant\Company;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\Dispatch;
use App\Models\Tenant\Document;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Item;
use App\Models\Tenant\PaymentCondition;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\Person;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\Series;
use App\Models\Tenant\StateType;
use App\Models\Tenant\User;
use App\Models\Tenant\NameDocument;
use App\Traits\OfflineTrait;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Modules\BusinessTurn\Models\BusinessTurn;
use Modules\Finance\Helpers\UploadFileHelper;
use Modules\Finance\Traits\FinanceTrait;
use App\Traits\PrinterTrait;
use Modules\Inventory\Models\Warehouse as ModuleWarehouse;
use Modules\Item\Http\Requests\BrandRequest;
use Modules\Item\Http\Requests\CategoryRequest;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Category;
use Modules\Document\Helpers\DocumentHelper;
use App\CoreFacturalo\Requests\Inputs\Functions;
use App\Models\System\Client as SystemClient;
use App\Models\Tenant\CashDocument;
use App\Models\Tenant\CashDocumentCredit;
use App\Models\Tenant\DocumentFee;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\ItemSeller;
use App\Models\Tenant\ItemSizeStock;
use App\Models\Tenant\ItemWarehouse;
use App\Models\Tenant\Kardex;
use App\Models\Tenant\Note;
use App\Models\Tenant\SummaryDocument;
use App\Models\Tenant\VoidedDocument;
use App\Models\Tenant\NameQuotations;
use App\Models\Tenant\Voided;
use App\Providers\InventoryServiceProvider;
use App\Services\PseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BusinessTurn\Models\DocumentHotel;
use Modules\BusinessTurn\Models\DocumentTransport;
use Modules\Hotel\Models\HotelRent;
use Modules\Inventory\Models\{
    InventoryConfiguration
};
use Modules\Inventory\Providers\InventoryKardexServiceProvider;
use Modules\Inventory\Traits\InventoryTrait;
use Modules\Item\Models\ItemLot;
use Modules\Item\Models\ItemLotsGroup;
use Modules\Suscription\Models\Tenant\SuscriptionNames;
use Modules\Suscription\Models\Tenant\SuscriptionPayment;

class DocumentController extends Controller
{
    use FinanceTrait;
    use OfflineTrait;
    use StorageDocument;
    use PrinterTrait;
    use InventoryTrait;
    private $max_count_payment = 0;
    protected $document;
    protected $apply_change;
    public function __construct()
    {
        $this->middleware('input.request:document,web', ['only' => ['store']]);
        $this->middleware('input.request:documentUpdate,web', ['only' => ['update']]);
    }

    public function updateUser($user_id, $document_id)
    {
        $document = Document::find($document_id);
        $document->user_id = $user_id;
        $document->save();
        return [
            'success' => true,
            'message' => 'Usuario cambiado'
        ];
    }

    public function checkSeries(Request $request)
    {
        $lots = $request->lots;
        $errors = [];
        $lots_found = [];
        foreach ($lots as $lot) {
            $item_lot = ItemLot::where('series', $lot)->first();
            if ($item_lot) {
                if ($item_lot->has_sale == 1) {
                    $message = 'La serie ' . $lot . ' ya fue vendida.';
                    $errors[] = $message;
                } else {
                    $transformed = [
                        "date" => $item_lot->date,
                        "has_sale" => true,
                        "id" => $item_lot->id,
                        "item_id" => $item_lot->item_id,
                        "lot_code" => $item_lot->lot_code,
                        "series" => $item_lot->series,
                        "warehouse_id" => $item_lot->warehouse_id,
                    ];
                    $lots_found[] = $transformed;
                }
            } else {
                $message = 'La serie ' . $lot . ' no se encuentra registrada.';
                $errors[] = $message;
            }
        }
        return [
            'success' => (count($errors) === 0) ? true : false,
            'message' => (count($errors) === 0) ? 'Series verificadas' : $errors,
            'lots' => $lots_found,

        ];
    }
    public function voidedPdf($id)
    {
        $document = Document::find($id);
        $voided_document = VoidedDocument::where('document_id', $id)->first();
        $voided = Voided::where('id', $voided_document->voided_id)->first();
        $establishment = Establishment::find(auth()->user()->establishment_id);
        $company = Company::active();


        $pdf = Pdf::loadView('tenant.documents.voided_pdf', compact(
            "document",
            "company",
            "establishment",
            "voided",
            "voided_document"
        ))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("anulacion" . '.pdf');
    }
    public function changeSire($document_id, $appendix)
    {
        $document = Document::find($document_id);
        switch ($appendix) {

            case 2:
                $document->appendix_2 = !$document->appendix_2;
                break;
            case 3:
                $document->appendix_3 = !$document->appendix_3;
                break;
            case 4:
                $document->appendix_4 = !$document->appendix_4;
                break;
            default:
                $document->appendix_5 = !$document->appendix_5;
                break;
        }
        $document->save();
        return [
            'success' => true,
            'message' => 'Anexo ' . $appendix . ' cambiado'
        ];
    }
    private function document_item_restore(DocumentItem $document_item)
    {
        // si es nota credito tipo 13, no se asocia a inventario
        if ($document_item->document->isCreditNoteAndType13()) return;
        if ($document_item->document->no_stock) return;

        if (!$document_item->item->is_set) {
            $presentationQuantity = (!empty($document_item->item->presentation)) ? $document_item->item->presentation->quantity_unit : 1;
            $document = $document_item->document;
            $factor = ($document->document_type_id === '07') ? 1 : -1;
            $warehouse = ($document_item->warehouse_id) ? $this->findWarehouse($this->findWarehouseById($document_item->warehouse_id)->establishment_id) : $this->findWarehouse();
            //$this->createInventory($document_item->item_id, $factor * $document_item->quantity, $warehouse->id);
            $this->createInventoryKardex($document_item->document, $document_item->item_id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);

            if (!$document_item->document->sale_note_id && !$document_item->document->order_note_id && !$document_item->document->dispatch_id && !$document_item->document->sale_notes_relateds) {
                $this->updateStock($document_item->item_id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
            } else {
                if ($document_item->document->dispatch) {
                    if (!$document_item->document->dispatch->transfer_reason_type->discount_stock) {
                        $this->updateStock($document_item->item_id, ($factor * ($document_item->quantity * $presentationQuantity)), $warehouse->id);
                    }
                }
            }
        } else {

            $item = Item::findOrFail($document_item->item_id);

            foreach ($item->sets as $it) {
                /** @var Item $ind_item */
                $ind_item = $it->individual_item;
                $item_set_quantity = ($it->quantity) ? $it->quantity : 1;
                $presentationQuantity = 1;
                $document = $document_item->document;
                $factor = ($document->document_type_id === '07') ? 1 : -1;
                $warehouse = $this->findWarehouse();
                $this->createInventoryKardex($document_item->document, $ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity * $item_set_quantity)), $warehouse->id);

                if (!$document_item->document->sale_note_id && !$document_item->document->order_note_id && !$document_item->document->dispatch_id && !$document_item->document->sale_notes_relateds) {
                    $this->updateStock($ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity * $item_set_quantity)), $warehouse->id);
                } else {
                    if ($document_item->document->dispatch) {
                        if (!$document_item->document->dispatch->transfer_reason_type->discount_stock) {
                            $this->updateStock($ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity * $item_set_quantity)), $warehouse->id);
                        }
                    }
                }
            }
        }

        /*
         * Calculando el stock por lote por factor según la unidad
         */

        if (!$document->isGeneratedFromExternalRecord()) {

            if (isset($document_item->item->IdLoteSelected)) {
                if ($document_item->item->IdLoteSelected != null) {
                    if (is_array($document_item->item->IdLoteSelected)) {
                        // presentacion - factor de lista de precios
                        $quantity_unit = isset($document_item->item->presentation->quantity_unit) ? $document_item->item->presentation->quantity_unit : 1;
                        $lotesSelecteds = $document_item->item->IdLoteSelected;
                        $document_factor = ($document->document_type_id === '07') ? 1 : -1;
                        $inventory_configuration = InventoryConfiguration::first();
                        $inventory_configuration->stock_control;
                        foreach ($lotesSelecteds as $item) {
                            $lot = ItemLotsGroup::query()->find($item->id);
                            $compromise_quantity = isset($item->compromise_quantity) ? $item->compromise_quantity : 1;
                            $lot->quantity = $lot->quantity + ($quantity_unit * $compromise_quantity * $document_factor);
                            if ($inventory_configuration->stock_control) {
                                $this->validateStockLotGroup($lot, $document_item);
                            }
                            $lot->save();
                        }
                    } else {

                        $lot = ItemLotsGroup::query()->find($document_item->item->IdLoteSelected);
                        try {
                            $quantity_unit = $document_item->item->presentation->quantity_unit;
                        } catch (Exception $e) {
                            $quantity_unit = 1;
                        }
                        if ($document->document_type_id === '07') {
                            $quantity = $lot->quantity + ($quantity_unit * $document_item->quantity);
                        } else {
                            $quantity = $lot->quantity - ($quantity_unit * $document_item->quantity);
                        }
                        $lot->quantity = $quantity;
                        $lot->save();
                    }
                }
            }
        }
        if (isset($document_item->item->sizes_selected)) {
            foreach ($document_item->item->sizes_selected as $size) {

                $item_size = ItemSizeStock::where('item_id', $document_item->item_id)->where('size', $size->size)->first();
                if ($item_size) {
                    $item_size->stock = $item_size->stock - $size->qty;
                    $item_size->save();
                }
            }
        }
        if (isset($document_item->item->lots)) {
            foreach ($document_item->item->lots as $it) {

                if ($it->has_sale == true) {
                    $r = ItemLot::find($it->id);
                    // $r->has_sale = true;
                    $r->has_sale = ($document->document_type_id === '07') ? false : true;
                    $r->save();
                }
            }
            /*if($document_item->item->IdLoteSelected != null)
            {
                $lot = ItemLotsGroup::find($document_item->item->IdLoteSelected);
                $lot->quantity = ($lot->quantity - $document_item->quantity);
                $lot->save();
            }*/
        }
    }
    function recalculateStock($item_id)
    {
        $total = 0;
        $item_warehouses = ItemWarehouse::where('item_id', $item_id)->get();
        foreach ($item_warehouses as $item_warehouse) {
            $total += $item_warehouse->stock;
        }
        $item = Item::find($item_id);
        $item->stock = $total;
        $item->save();
    }
    public function change_state($state_id,  $document_id)

    {
        $document = Document::find($document_id);
        $document->state_type_id = $state_id;
        if ($state_id == '05') {
            $document_items = DocumentItem::where('document_id', $document_id)->get();
            foreach ($document_items as $item) {
                $this->document_item_restore($item);
                $this->recalculateStock($item->item_id);
            }
        }
        $document->auditor_state = 1;
        $document->save();
        return [
            'success' => true,
            'message' => 'Estado cambiado'
        ];
    }
    public function index(Request $request)
    {
        $is_optometry = BusinessTurn::isOptometry();
        $is_comercial  = auth()->user()->integrate_user_type_id == 2;
        $to_anulate = $request->input('to_anulate') ?? false;
        $is_client = $this->getIsClient();
        $import_documents = config('tenant.import_documents');
        $import_documents_second = config('tenant.import_documents_second_format');
        $document_import_excel = config('tenant.document_import_excel');
        $configuration = Configuration::getPublicConfig();
        $is_auditor = (bool) (auth()->user()->auditor ?? false);
        // apiperu
        // se valida cual api usar para validacion desde el listado de comprobantes
        $view_apiperudev_validator_cpe = config('tenant.apiperudev_validator_cpe');
        $view_validator_cpe = config('tenant.validator_cpe');
        $document_state_types = StateType::all();

        return view(
            'tenant.documents.index',
            compact(
                'is_optometry',
                'is_comercial',
                'document_state_types',
                'is_auditor',
                'to_anulate',
                'is_client',
                'import_documents',
                'import_documents_second',
                'document_import_excel',
                'configuration',
                'view_apiperudev_validator_cpe',
                'view_validator_cpe'
            )
        );
    }

    public function killDocument($id)
    {
        $document = Document::find($id);

        //GuideFile
        $document->guide_files()->delete();
        HotelRent::where('document_id', $id)->delete();
        //DocumentPayment
        DocumentPayment::where('document_id', $id)->delete();
        //Dispatch
        Dispatch::where('reference_document_id', $id)->delete();
        //DocumentFee
        DocumentFee::where('document_id', $id)->delete();
        // CashDocument
        CashDocument::where('document_id', $id)->delete();
        // CashDocumentCredit
        CashDocumentCredit::where('document_id', $id)->delete();
        // Kardex
        Kardex::where('document_id', $id)->delete();
        // Note
        Note::where('document_id', $id)->delete();
        // SummaryDocument
        SummaryDocument::where('document_id', $id)->delete();
        // VoidedDocument
        VoidedDocument::where('document_id', $id)->delete();
        // DocumentHotel
        DocumentHotel::where('document_id', $id)->delete();
        // DocumentTransport
        DocumentTransport::where('document_id', $id)->delete();
        // SuscriptionPayment
        SuscriptionPayment::where('document_id', $id)->delete();

        $items = DocumentItem::where('document_id', $id)->get();
        foreach ($items as $item) {
            $item->restoreStock();
            ItemSeller::where('document_item_id', $item->id)->delete();
            $item->delete();
        }
        $notes = $document->getNotes();
        foreach ($notes as $note) {
            $note->delete();
        }

        // CashDocument::where('document_id', $id)->delete();

        $document->inventory_kardex()->delete();
        $document->delete();
        return [
            'success' => true,
            'message' => 'Documento eliminado'
        ];
    }
    public function columns()
    {
        return [
            'number' => 'Número',
            'date_of_issue' => 'Fecha de emisión'
        ];
    }

    public function records(Request $request)
    {

        $records = $this->getRecords($request);

        return new DocumentCollection($records->paginate(config('tenant.items_per_page')));
    }

    /**
     * Devuelve los totales de la busqueda,
     *
     * Implementado en resources/js/views/tenant/documents/index.vue
     * @param Request $request
     *
     * @return array[]
     */
    public function recordsTotal(Request $request)
    {

        $FT_t = DocumentType::find('01');
        $BV_t = DocumentType::find('03');
        $NC_t = DocumentType::find('07');
        $ND_t = DocumentType::find('08');

        $BV = $this->getRecords($request)->where('state_type_id', '05')->where('document_type_id', $BV_t->id)->get()->sum(function ($row) {
            if ($row->currency_type_id !==  "PEN") {
                return $row->total * $row->exchange_rate_sale;
            } else {
                return $row->total;
            }
        });
        $FT = $this->getRecords($request)->where('state_type_id', '05')->where('document_type_id', $FT_t->id)->get()->sum(function ($row) {
            if ($row->currency_type_id !==  "PEN") {
                return $row->total * $row->exchange_rate_sale;
            } else {
                return $row->total;
            }
        });
        $NC = $this->getRecords($request)->where('state_type_id', '05')->where('document_type_id', $NC_t->id)->get()->sum(function ($row) {
            if ($row->currency_type_id !==  "PEN") {
                return $row->total * $row->exchange_rate_sale;
            } else {
                return $row->total;
            }
        });
        $ND = $this->getRecords($request)->where('state_type_id', '05')->where('document_type_id', $ND_t->id)->get()->sum(function ($row) {
            if ($row->currency_type_id !==  "PEN") {
                return $row->total * $row->exchange_rate_sale;
            } else {
                return $row->total;
            }
        });
        return [
            [
                'name' => $FT_t->description,
                'total' => "S/. " . ReportHelper::setNumber($FT),
            ],
            [
                'name' => $BV_t->description,
                'total' => "S/. " . ReportHelper::setNumber($BV),

            ],
            [
                'name' => $NC_t->description,
                'total' => "S/. " . ReportHelper::setNumber($NC),
            ],
            [
                'name' => $ND_t->description,
                'total' => "S/. " . ReportHelper::setNumber($ND),
            ],
        ];
    }

    public function searchCustomers(Request $request)
    {

        //tru de boletas en env esta en true filtra a los con dni   , false a todos
        $identity_document_type_id = $this->getIdentityDocumentTypeId($request->document_type_id, $request->operation_type_id);
        //        $operation_type_id_id = $this->getIdentityDocumentTypeId($request->operation_type_id);

        $customers = Person::where('number', 'like', "%{$request->input}%")
            ->orWhere('name', 'like', "%{$request->input}%")
            ->whereType('customers')->orderBy('name')
            ->whereIn('identity_document_type_id', $identity_document_type_id)
            ->whereIsEnabled()
            ->whereFilterCustomerBySeller('customers')
            ->get()->transform(function ($row) {
                /** @var  Person $row */
                return $row->getCollectionData();
                /* Movido al modelo */
                return [
                    'id' => $row->id,
                    'description' => $row->number . ' - ' . $row->name,
                    'name' => $row->name,
                    'number' => $row->number,
                    'identity_document_type_id' => $row->identity_document_type_id,
                    'identity_document_type_code' => $row->identity_document_type->code,
                    'addresses' => $row->addresses,
                    'address' => $row->address
                ];
            });

        return compact('customers');
    }


    public function create()
    {
        $api_token = \App\Models\Tenant\Configuration::getApiServiceToken();
        if (auth()->user()->type == 'integrator')
            return redirect('/documents');

        $establishment = Establishment::all();
        $establishment_auth = Establishment::where('id', auth()->user()->establishment_id)->get();
        $configuration = Configuration::first();
        $is_contingency = 0;
        $suscriptionames = SuscriptionNames::create_new();
        $data = NameQuotations::first();
        $quotations_optional =  $data != null ? $data->quotations_optional : null;
        $quotations_optional_value =  $data != null ? $data->quotations_optional_value : null;

        return view(
            'tenant.documents.form',
            compact('suscriptionames', 'is_contingency', 'configuration', 'establishment', 'establishment_auth', 'quotations_optional', 'quotations_optional_value', 'api_token')
        );
    }

    public function create_tensu()
    {
        if (auth()->user()->type == 'integrator')
            return redirect('/documents');

        $is_contingency = 0;
        return view('tenant.documents.form_tensu', compact('is_contingency'));
    }


    public function tablesCompany($id)
    {
        $company = Company::where('website_id', $id)->first();
        $company_active = Company::active();
        $document_number = $company->document_number;
        $website_id = $company->website_id;
        $user = auth()->user()->id;
        $user_to_save = User::find($user);
        $user_to_save->company_active_id = $website_id;
        $user_to_save->save();
        $key = "cash_" . $user;
        Cache::put($key, $website_id, 60);
        $payment_destinations = $this->getPaymentDestinations();
        if ($website_id && $company->id != $company_active->id) {
            $hostname = Hostname::where('website_id', $website_id)->first();
            $client = SystemClient::where('hostname_id', $hostname->id)->first();
            $tenancy = app(Environment::class);
            $tenancy->tenant($client->hostname->website);
        }
        $establishment = Establishment::find(1);
        $establishment_info = EstablishmentInput::set($establishment->id);
        $series = Series::where('establishment_id', $establishment->id)->get()
            ->transform(function ($row) {
                return [
                    'id' => $row->id,
                    'contingency' => (bool)$row->contingency,
                    'document_type_id' => $row->document_type_id,
                    'establishment_id' => $row->establishment_id,
                    'number' => $row->number,
                ];
            });
        // $series = Series::FilterSeries(1)
        //     ->get()
        //     ->transform(function ($row)  use ($document_number) {
        //         /** @var Series $row */
        //         return $row->getCollectionData2($document_number);
        //     })->where('disabled', false);
        return [
            'success' => true,
            'data' => $company,
            'payment_destinations' => $payment_destinations,
            'series' => $series,
            'establishment' => $establishment_info,
        ];
    }
    public function tables()
    {
        $customers = $this->table('customers');
        $user = new User();
        if (\Auth::user()) {
            $user = \Auth::user();
        }
        $companies = [];
        $document_id = $user->document_id;
        $series_id = $user->series_id;
        $establishment_id = $user->establishment_id;
        $userId = $user->id;
        $userType = $user->type;
        $series = $user->getSeries();
        // $prepayment_documents = $this->table('prepayment_documents');
        $establishments = Establishment::where('id', $establishment_id)->get(); // Establishment::all();
        $document_types_invoice = DocumentType::whereIn('id', ['01', '03'])->where('active', true)->get();
        $document_types_note = DocumentType::whereIn('id', ['07', '08'])->get();
        $note_credit_types = NoteCreditType::whereActive()->orderByDescription()->get();
        $note_debit_types = NoteDebitType::whereActive()->orderByDescription()->get();
        $currency_types = CurrencyType::whereActive()->get();
        $operation_types = OperationType::whereActive()->get();
        $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        $company = Company::active();
        $document_type_03_filter = config('tenant.document_type_03_filter');
        // $sellers = User::where('establishment_id',$establishment_id)->whereIn('type', ['seller', 'admin'])->orWhere('id', $userId)->get();
        $sellers = User::getSellersToNvCpe($establishment_id, $userId)
            ->transform(function (User $row) {
                return $row->getCollectionData();
            });
        $payment_method_types = $this->table('payment_method_types');
        $business_turns = BusinessTurn::where('active', true)->get();
        $enabled_discount_global = config('tenant.enabled_discount_global');
        $is_client = $this->getIsClient();
        $select_first_document_type_03 = config('tenant.select_first_document_type_03');
        $payment_conditions = PaymentCondition::all();

        $document_types_guide = DocumentType::whereIn('id', ['09', '31'])->get()->transform(function ($row) {
            return [
                'id' => $row->id,
                'active' => (bool)$row->active,
                'short' => $row->short,
                'description' => ucfirst(mb_strtolower(str_replace('REMITENTE ELECTRÓNICA', 'REMITENTE', $row->description))),
            ];
        });
        // $cat_payment_method_types = CatPaymentMethodType::whereActive()->get();
        // $detraction_types = DetractionType::whereActive()->get();

        //        return compact('customers', 'establishments', 'series', 'document_types_invoice', 'document_types_note',
        //                       'note_credit_types', 'note_debit_types', 'currency_types', 'operation_types',
        //                       'discount_types', 'charge_types', 'company', 'document_type_03_filter',
        //                       'document_types_guide');

        // return compact('customers', 'establishments', 'series', 'document_types_invoice', 'document_types_note',
        //                'note_credit_types', 'note_debit_types', 'currency_types', 'operation_types',
        //                'discount_types', 'charge_types', 'company', 'document_type_03_filter');

        $payment_destinations = $this->getPaymentDestinations();
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $user = $userType;
        $global_discount_types = ChargeDiscountType::whereIn('id', ['02', '03'])->whereActive()->get();
        $configuration = Configuration::select('multi_companies')->first();
        if ($configuration->multi_companies) {
            $companies = Company::all();
        }
        return compact(
            'companies',
            'document_id',
            'series_id',
            'customers',
            'establishments',
            'series',
            'document_types_invoice',
            'document_types_note',
            'note_credit_types',
            'note_debit_types',
            'currency_types',
            'operation_types',
            'discount_types',
            'charge_types',
            'company',
            'document_type_03_filter',
            'document_types_guide',
            'user',
            'sellers',
            'payment_method_types',
            'enabled_discount_global',
            'business_turns',
            'is_client',
            'select_first_document_type_03',
            'payment_destinations',
            'payment_conditions',
            'global_discount_types',
            'affectation_igv_types'
        );
    }

    public function item_tables()
    {
        // $items = $this->table('items');
        $items = SearchItemController::getItemsToDocuments();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $system_isc_types = SystemIscType::whereActive()->get();
        $price_types = PriceType::whereActive()->get();
        $operation_types = OperationType::whereActive()->get();
        $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $is_client = $this->getIsClient();
        $validate_stock_add_item = InventoryConfiguration::getRecordIndividualColumn('validate_stock_add_item');

        $configuration = Configuration::first();

        /** Informacion adicional */
        $colors = collect([]);
        $CatItemSize = $colors;
        $CatItemStatus = $colors;
        $CatItemUnitBusiness = $colors;
        $CatItemMoldCavity = $colors;
        $CatItemPackageMeasurement = $colors;
        $CatItemUnitsPerPackage = $colors;
        $CatItemMoldProperty = $colors;
        $CatItemProductFamily = $colors;
        if ($configuration->isShowExtraInfoToItem()) {

            $colors = CatColorsItem::all();
            $CatItemSize = CatItemSize::all();
            $CatItemStatus = CatItemStatus::all();
            $CatItemUnitBusiness = CatItemUnitBusiness::all();
            $CatItemMoldCavity = CatItemMoldCavity::all();
            $CatItemPackageMeasurement = CatItemPackageMeasurement::all();
            $CatItemUnitsPerPackage = CatItemUnitsPerPackage::all();
            $CatItemMoldProperty = CatItemMoldProperty::all();
            $CatItemProductFamily = CatItemProductFamily::all();
        }


        /** Informacion adicional */

        return compact(
            'items',
            'categories',
            'brands',
            'affectation_igv_types',
            'system_isc_types',
            'price_types',
            'operation_types',
            'discount_types',
            'charge_types',
            'attribute_types',
            'is_client',
            'colors',
            'CatItemSize',
            'CatItemMoldCavity',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemProductFamily',
            'validate_stock_add_item',
            'CatItemUnitsPerPackage'
        );
    }

    public function table($table)
    {
        if ($table === 'customers') {
            $customers = Person::with('addresses')
                ->whereType('customers')
                ->whereIsEnabled()
                ->whereFilterCustomerBySeller('customers')
                ->orderBy('name')
                ->take(20)
                ->get()->transform(function ($row) {
                    /** @var Person $row */
                    return $row->getCollectionData();
                    /** Se ha movido la salida, al modelo */
                    return [
                        'id' => $row->id,
                        'description' => $row->number . ' - ' . $row->name,
                        'name' => $row->name,
                        'number' => $row->number,
                        'identity_document_type_id' => $row->identity_document_type_id,
                        'identity_document_type_code' => $row->identity_document_type->code,
                        'addresses' => $row->addresses,
                        'address' => $row->address,
                        'internal_code' => $row->internal_code,
                    ];
                });
            return $customers;
        }

        if ($table === 'prepayment_documents') {
            $prepayment_documents = Document::whereHasPrepayment()->get()->transform(function ($row) {

                $total = round($row->pending_amount_prepayment, 2);
                $amount = ($row->affectation_type_prepayment == '10') ? round($total / 1.18, 2) : $total;

                return [
                    'id' => $row->id,
                    'description' => $row->series . '-' . $row->number,
                    'series' => $row->series,
                    'number' => $row->number,
                    'document_type_id' => ($row->document_type_id == '01') ? '02' : '03',
                    // 'amount' => $row->total_value,
                    // 'total' => $row->total,
                    'amount' => $amount,
                    'total' => $total,

                ];
            });
            return $prepayment_documents;
        }

        if ($table === 'payment_method_types') {

            return PaymentMethodType::getPaymentMethodTypes();
            /*
            $payment_method_types = PaymentMethodType::whereNotIn('id', ['05', '08', '09'])->get();
            $end_payment_method_types = PaymentMethodType::whereIn('id', ['05', '08', '09'])->get(); //by requirement
            return $payment_method_types->merge($end_payment_method_types);
            */
        }

        if ($table === 'items') {

            return SearchItemController::getItemsToDocuments();

            $establishment_id = auth()->user()->establishment_id;
            $warehouse = ModuleWarehouse::where('establishment_id', $establishment_id)->first();
            // $items_u = Item::whereWarehouse()->whereIsActive()->whereNotIsSet()->orderBy('description')->take(20)->get();
            $items_u = Item::with('warehousePrices')
                ->whereIsActive()
                ->orderBy('description');
            $items_s = Item::with('warehousePrices')
                ->where('items.unit_type_id', 'ZZ')
                ->whereIsActive()
                ->orderBy('description');
            $items_u = $items_u
                ->take(20)
                ->get();
            $items_s = $items_s
                ->take(10)
                ->get();
            $items = $items_u->merge($items_s);

            return collect($items)->transform(function ($row) use ($warehouse) {
                /** @var Item $row */
                return $row->getDataToItemModal($warehouse);
                $detail = $this->getFullDescription($row, $warehouse);
                return [
                    'id' => $row->id,
                    'full_description' => $detail['full_description'],
                    'model' => $row->model,
                    'brand' => $detail['brand'],
                    'warehouse_description' => $detail['warehouse_description'],
                    'category' => $detail['category'],
                    'stock' => $detail['stock'],
                    'internal_id' => $row->internal_id,
                    'description' => $row->description,
                    'currency_type_id' => $row->currency_type_id,
                    'currency_type_symbol' => $row->currency_type->symbol,
                    'sale_unit_price' => Item::getSaleUnitPriceByWarehouse($row, $warehouse->id),
                    'purchase_unit_price' => $row->purchase_unit_price,
                    'unit_type_id' => $row->unit_type_id,
                    'sale_affectation_igv_type_id' => $row->sale_affectation_igv_type_id,
                    'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                    'calculate_quantity' => (bool)$row->calculate_quantity,
                    'has_igv' => (bool)$row->has_igv,
                    'has_plastic_bag_taxes' => (bool)$row->has_plastic_bag_taxes,
                    'amount_plastic_bag_taxes' => $row->amount_plastic_bag_taxes,
                    'item_unit_types' => collect($row->item_unit_types)->transform(function ($row) {
                        return [
                            'id' => $row->id,
                            'description' => "{$row->description}",
                            'item_id' => $row->item_id,
                            'unit_type_id' => $row->unit_type_id,
                            'quantity_unit' => $row->quantity_unit,
                            'price1' => $row->price1,
                            'price2' => $row->price2,
                            'price3' => $row->price3,
                            'price_default' => $row->price_default,
                        ];
                    }),
                    'warehouses' => collect($row->warehouses)->transform(function ($row) use ($warehouse) {
                        return [
                            'warehouse_description' => $row->warehouse->description,
                            'stock' => $row->stock,
                            'warehouse_id' => $row->warehouse_id,
                            'checked' => ($row->warehouse_id == $warehouse->id) ? true : false,
                        ];
                    }),
                    'attributes' => $row->attributes ? $row->attributes : [],
                    'lots_group' => collect($row->lots_group)->transform(function ($row) {
                        return [
                            'id' => $row->id,
                            'code' => $row->code,
                            'quantity' => $row->quantity,
                            'date_of_due' => $row->date_of_due,
                            'checked' => false
                        ];
                    }),
                    'lots' => [],
                    'lots_enabled' => (bool)$row->lots_enabled,
                    'series_enabled' => (bool)$row->series_enabled,

                ];
            });
        }

        return [];
    }

    public function getFullDescription($row, $warehouse)
    {

        $desc = ($row->internal_id) ? $row->internal_id . ' - ' . $row->description : $row->description;
        $category = ($row->category) ? "{$row->category->name}" : "";
        $brand = ($row->brand) ? "{$row->brand->name}" : "";


        if ($row->unit_type_id != 'ZZ') {
            if (isset($row['stock'])) {
                $warehouse_stock = number_format($row['stock'], 2);
            } else {
                $warehouse_stock = ($row->warehouses && $warehouse) ?
                    number_format($row->warehouses->where('warehouse_id', $warehouse->id)->first()->stock, 2) :
                    0;
            }

            $stock = ($row->warehouses && $warehouse) ? "{$warehouse_stock}" : "";
        } else {
            $stock = '';
        }

        $desc = "{$desc} - {$brand}";

        return [
            'full_description' => $desc,
            'brand' => $brand,
            'category' => $category,
            'stock' => $stock,
            'warehouse_description' => $warehouse->description,
        ];
    }


    public function record($id)
    {
        $record = new DocumentResource(Document::findOrFail($id));

        return $record;
    }

    public function duplicate($id)
    {
        try {
            $document = Document::find($id);
            $res = $this->storeWithData_duplicate($document, true, 'invoice', 'a4');

            $document_id = $res['data']['id'];
            $documents = Document::find($document_id);
            //$this->associateDispatchesToDocument_duplicate( $documents, $document_id);
            //  $this->associateSaleNoteToDocument($documents, $document_id);
            if ($res['data']['document']->sale_note_id != null) {
                SaleNote::where('id', $document_id->sale_note_id)
                    ->update(['document_id' => $document_id]);
            }

            return $res;
        } catch (Exception $e) {
            $this->generalWriteErrorLog($e);
            return $this->generalResponse(false, 'Ocurrió un error: ' . $e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
        }
    }
    public function store(DocumentRequest $request)
    {
        try {
            $validate = $this->validateDocument($request);
            if (!$validate['success']) return $validate;
            $res = $this->storeWithData($request->all());
            $document_id = $res['data']['id'];
            $this->associateDispatchesToDocument($request, $document_id);
            $this->associateSaleNoteToDocument($request, $document_id);
            return $res;
        } catch (Exception $e) {
            $code = $e->getCode();
            $this->generalWriteErrorLog($e);
            dd($e->getMessage());
            Log::error($e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
            if ($code == '23000') {
                $message = "Debe eliminar los comprobantes de prueba antes de empezar a emitir comprobantes con valor legal.";
                return $this->generalResponse(false, 'Ocurrió un error: ' . $message);
            } else {

                return $this->generalResponse(false, 'Ocurrió un error: ' . $e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
            }
            // return $this->generalResponse(false, 'Ocurrió un error: ' . $e->getMessage());

        }
    }


    /**
     * Validaciones previas al proceso de facturacion
     *
     * @param array $request
     * @return array
     */
    public function validateDocument($request)
    {

        // validar nombre de producto pdf en xml - items
        foreach ($request->items as $item) {

            if ($item['name_product_xml']) {
                // validar error 2027 sunat
                if (mb_strlen($item['name_product_xml']) > 500) {
                    return [
                        'success' => false,
                        'message' => "El campo Nombre producto en PDF/XML no puede superar los 500 caracteres - Producto/Servicio: {$item['item']['description']}"
                    ];
                }
            }
        }

        return [
            'success' => true,
            'message' => ''
        ];
    }

    /**
     * Guarda los datos del hijo para el proceso de suscripcion. #952
     * Toma el valor de nota de venta y lo pasa para la boleta/factura
     *
     * @param $data
     */
    public static function setChildrenToData(&$data)
    {
        $request = request();
        if (
            $request != null &&
            $request->has('sale_note_id') &&
            $request->sale_note_id
        ) {
            $saleNote = SaleNote::find($request->sale_note_id);
            if ($saleNote != null && isset($data['customer'])) {
                $customer = $data['customer'];
                $customerNote = (array)$saleNote->customer;
                if (isset($customerNote['children'])) {
                    $customer['children'] = (array)$customerNote['children'];
                }
                $data['customer'] = $customer;
                $data['grade'] = $saleNote->getGrade();
                $data['section'] = $saleNote->getSection();
            }
        }
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Throwable
     */
    public function storeWithData($data, $duplicate = false, $type = 'invoice', $format = 'a4')
    {

        self::setChildrenToData($data);
        $fact =  DB::connection('tenant')->transaction(function () use ($data, $duplicate, $type, $format) {
            $company_id = $data['company_id'];
            if ($company_id) {
                $company = Company::where(
                    'website_id',
                    $company_id
                )->first();
                $facturalo = new Facturalo($company);
            } else {
                $facturalo = new Facturalo();
                $company = Company::active();
            }
            $result = $facturalo->save($data, $duplicate);

            if ($company_id) {

                $document_number = $company->document_number;
                $document_result = $result->getDocument();
                $series = $document_result->series;
                $number = $document_result->number;
                if ($document_number) {
                    $document_number->$series = $number + 1;
                } else {
                    $document_number = new \stdClass();
                    $document_number->$series = $number + 1;
                }
                $company->document_number = $document_number;
                $company->save();
            }
            $configuration = Configuration::first();
            if ($type == 'invoice' && $configuration->college) {
                $document_id = $result->getDocument()->id;
                $periods = $data['months'];
                $client_id = $data['customer_id'];
                $child_id = $data['child_id'];
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
            //aqui

            if (isset($result->id) == true) {

                $facturalo->createXmlUnsigned($result->id);
            } else {
                $facturalo->createXmlUnsigned();
            }
            if ($company->pse && $company->soap_type_id == '02') {

                $facturalo->sendPseNew();
            } else {
                $facturalo->signXmlUnsigned();
                if ($duplicate == true) {
                    $configuration = Configuration::first();
                    $facturalo->setActions(['send_xml_signed' => (bool) $configuration->send_auto]);
                }
                //hasta aqui
            }

            $facturalo->updateHash();
            $facturalo->updateQr();
            if ($duplicate == true) {
                $facturalo->createPdf($result, $type, $format);
            } else {
                $facturalo->createPdf();
            }
            //aqui
            $document_result = $result->getDocument();
            if ((!$company->pse || $company->soap_type_id != '02') && $document_result->state_type_id != '55') {
                $facturalo->senderXmlSignedBill();
            }
            //hasta aqui

            return $facturalo;
        });

        $document = $fact->getDocument();
        //generar response
        $response = $fact->getResponse();
        $base_url = url('/');
        $external_id = $document->external_id;
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        $print_format = $establishment->print_format ?? 'ticket';
        $url_print = "{$base_url}/print/document/{$external_id}/$print_format";
        return [
            'success' => true,
            'data' => [
                'document' => $document,
                'id' => $document->id,
                'number_full' => $document->number_full,
                'response' => $response,
                'url_print' => $url_print
            ]
        ];
    }
    public function anulatePse($id)
    {
        $document = Document::find($id);
        $pse = new PseService($document);
        $response = $pse->anulatePse();

        return $response;
    }
    public function anulatePseCheck($id)
    {
        $document = Document::find($id);
        $pse = new PseService($document);
        $response = $pse->check_anulate();
        return $response;
    }
    public function checkPse($id)
    {
        $document = Document::find($id);
        $pse = new PseService($document);
        $response = $pse->download_file();

        return $response;
    }
    public function jsonPse($id)
    {
        $document = Document::find($id);
        $filename = $document->getNumberFullAttribute() . '.json';
        $pse = new PseService($document);
        $response = $pse->payloadToJson();
        if ($response['success'] == false) {
            return $response;
        } else {
            $payload = $response['payload'];
            // Crear la respuesta con el contenido del archivo JSON
            $response = response()->make($payload);
            $response->header('Content-Disposition', 'attachment; filename=' . $filename);
            $response->header('Content-Type', 'application/json');

            return $response;
        }
    }
    public function sendPse($id)
    {
        $document = Document::find($id);
        $pse = new PseService($document);

        $response = $pse->sendToPse();

        return $response;
    }

    public function storeWithData_duplicate($data, $duplicate = false, $type = 'invoice', $format = 'a4')
    {
        self::setChildrenToData($data);
        $fact =  DB::connection('tenant')->transaction(function () use ($data, $duplicate, $type, $format) {
            $facturalo = new Facturalo();
            $result = $facturalo->save($data, $duplicate);
            $configuration = Configuration::first();
            if ($type == 'invoice' && $configuration->college) {
                $document_id = $result->getDocument()->id;
                $periods = $data['months'];
                $client_id = $data['customer_id'];
                $child_id = $data['child_id'];
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
            if (isset($result->id) == true) {
                $facturalo->createXmlUnsigned($result->id);
            } else {
                $facturalo->createXmlUnsigned();
            }
            $facturalo->signXmlUnsigned();
            if ($duplicate == true) {
                $configuration = Configuration::first();
                $facturalo->setActions(['send_xml_signed' => (bool) $configuration->send_auto]);
            }
            $facturalo->updateHash();
            // $facturalo->updateQr();
            if ($duplicate == true) {
                $facturalo->createPdf($result, $type, $format);
            } else {

                $facturalo->createPdf();
            }
            $facturalo->senderXmlSignedBill();

            return $facturalo;
        });

        $document = $fact->getDocument();
        $response = $fact->getResponse();

        return [
            'success' => true,
            'data' => [
                'document' => $document,
                'id' => $document->id,
                'number_full' => $document->number_full,
                'response' => $response
            ]
        ];
    }

    private function associateSaleNoteToDocument(Request $request, int $documentId)
    {
        if ($request->sale_note_id) {
            SaleNote::where('id', $request->sale_note_id)
                ->update(['document_id' => $documentId]);
        }
        $notes = $request->sale_notes_relateds;
        if ($notes) {
            foreach ($notes as $note) {
                $sale_note_id = $note['id'] ?? null;
                if ($sale_note_id) {
                    $sale_note = SaleNote::find($sale_note_id);
                    if (!empty($sale_note)) {
                        $sale_note->document_id = $documentId;
                        $sale_note->push();
                    }
                }
            }
        }
    }
    public function sendInd($id)
    {
        $document = Document::find($id);
        $document->ticket_single_shipment = true;
        $document->force_send_by_summary = false;
        $document->save();
        return [
            "success" => true,
            "message" => "Cambiado a envío individual"
        ];
    }
    public function sendRes($id)
    {
        $document = Document::find($id);
        $document->ticket_single_shipment = false;
        $document->force_send_by_summary = true;
        $document->save();
        return [
            "success" => true,
            "message" => "Cambiado a envío por resumen"
        ];
    }
    private function associateDispatchesToDocument_duplicate($document, int $documentId)
    {
        $dispatches_relateds = $request->dispatches_relateds;

        foreach ($dispatches_relateds as $dispatch) {
            $dispatchToArray = explode('-', $dispatch);
            if (count($dispatchToArray) === 2) {
                Dispatch::where('series', $dispatchToArray[0])
                    ->where('number', $dispatchToArray[1])
                    ->update([
                        'reference_document_id' => $documentId,
                    ]);

                $document = Dispatch::where('series', $dispatchToArray[0])
                    ->where('number', $dispatchToArray[1])
                    ->first();

                if ($document) {
                    $facturalo = new Facturalo();
                    $facturalo->createPdf($document, 'dispatch', 'a4');
                }
            }
        }
    }

    private function associateDispatchesToDocument(Request $request, int $documentId)
    {
        $dispatches_relateds = $request->dispatches_relateds;
        if ($dispatches_relateds) {
            foreach ($dispatches_relateds as $dispatch) {
                $dispatchToArray = explode('-', $dispatch);
                if (count($dispatchToArray) === 2) {
                    Dispatch::where('series', $dispatchToArray[0])
                        ->where('number', $dispatchToArray[1])
                        ->update([
                            'reference_document_id' => $documentId,
                        ]);

                    $document = Dispatch::where('series', $dispatchToArray[0])
                        ->where('number', $dispatchToArray[1])
                        ->first();

                    if ($document) {
                        $facturalo = new Facturalo();
                        $facturalo->createPdf($document, 'dispatch', 'a4');
                    }
                }
            }
        }
    }
    public function copy($documentId)
    {
        $configuration = Configuration::first();
        $is_contingency = 0;
        $isUpdate = false;

        $copy = true;
        return view('tenant.documents.copy', compact('is_contingency',  'configuration', 'documentId', 'isUpdate', 'copy'));
    }
    public function edit($documentId)
    {
        $api_token = \App\Models\Tenant\Configuration::getApiServiceToken();
        if (auth()->user()->type == 'integrator') {
            return redirect('/documents');
        }
        $suscriptionames = SuscriptionNames::create_new();
        $configuration = Configuration::first();
        $is_contingency = 0;
        $establishment = Establishment::all();
        $establishment_auth = Establishment::where('id', auth()->user()->establishment_id)->get();
        $isUpdate = true;
        $data = NameQuotations::first();
        $quotations_optional =  $data != null ? $data->quotations_optional : null;
        $quotations_optional_value =  $data != null ? $data->quotations_optional_value : null;


        return view('tenant.documents.form', compact('suscriptionames', 'quotations_optional', 'quotations_optional_value', 'is_contingency', 'establishment', 'establishment_auth', 'configuration', 'documentId', 'isUpdate', 'api_token'));
    }

    /**
     * @param \App\Http\Requests\Tenant\DocumentUpdateRequest $request
     * @param                                                 $id
     *
     * @return array
     * @throws \Throwable
     */
    public function update(DocumentUpdateRequest $request, $id)
    {
        $validate = $this->validateDocument($request);
        if (!$validate['success']) return $validate;

        $fact =  DB::connection('tenant')->transaction(function () use ($request, $id) {
            $facturalo = new Facturalo();
            $facturalo->update($request->all(), $id);

            $facturalo->createXmlUnsigned();
            $facturalo->signXmlUnsigned();
            $facturalo->updateHash();
            $facturalo->updateQr();
            $facturalo->createPdf();

            return $facturalo;
        });

        $document = $fact->getDocument();
        $response = $fact->getResponse();

        return [
            'success' => true,
            'data' => [
                'id' => $document->id,
                'response' => $response,
            ],
        ];
    }

    public function show($documentId)
    {
        $configuration = Configuration::first();
        $document = Document::findOrFail($documentId);
        if ($configuration->college) {
            $suscriptions = SuscriptionPayment::where('document_id', $documentId)->get();
            $document->periods = $suscriptions;
        }
        foreach ($document->items as &$item) {
            $discounts = [];
            if ($item->discounts) {
                foreach ($item->discounts as $discount) {
                    $discount_type = ChargeDiscountType::query()->find($discount->discount_type_id);
                    $discounts[] = [
                        'amount' => $discount->amount,
                        'base' => $discount->base,
                        'description' => $discount->description,
                        'discount_type_id' => $discount->discount_type_id,
                        'factor' => $discount->factor,
                        'percentage' => $discount->factor * 100,
                        'is_amount' => false,
                        'discount_type' => $discount_type
                    ];
                }
            }
            $item->discounts = $discounts;
            $item->stock = Item::find($item->item_id)->getStockByWarehouse($document->establishment_id);
        }

        return response()->json([
            'data' => $document,
            'success' => true,
        ], 200);
    }

    public function reStore($document_id)
    {
        $fact =  DB::connection('tenant')->transaction(function () use ($document_id) {
            $document = Document::find($document_id);

            $type = 'invoice';
            if ($document->document_type_id === '07') {
                $type = 'credit';
            }
            if ($document->document_type_id === '08') {
                $type = 'debit';
            }

            $facturalo = new Facturalo();
            $facturalo->setDocument($document);
            $facturalo->setType($type);
            $facturalo->createXmlUnsigned();
            $facturalo->signXmlUnsigned();
            $facturalo->updateHash();
            $facturalo->updateQr();
            $facturalo->updateSoap('02', $type);
            $facturalo->updateState('01');
            $facturalo->createPdf($document, $type, 'ticket');
            //            $facturalo->senderXmlSignedBill();
        });

        //        $document = $fact->getDocument();
        //        $response = $fact->getResponse();

        return [
            'success' => true,
            'message' => 'El documento se volvio a generar.',
        ];
    }

    public function email(DocumentEmailRequest $request)
    {
        $company = Company::active();
        $document = Document::find($request->input('id'));
        $customer_email = $request->input('customer_email');
        $email = $customer_email;
        $mailable = new DocumentEmail($company, $document);
        $id = (int)$request->input('id');
        $sendIt = EmailController::SendMail($email, $mailable, $id, 1);
        // Centralizar el envio de correos a Email Controller
        /*
        Configuration::setConfigSmtpMail();
        $array_customer = explode(',', $customer_email);
        if (count($array_customer) > 1) {
            foreach ($array_customer as $customer) {
                Mail::to($customer)->send(new DocumentEmail($company, $document));
            }
        } else {
            Mail::to($customer_email)->send(new DocumentEmail($company, $document));
        }
        */
        return [
            'success' => true
        ];
    }

    public function send($document_id)
    {
        $document = Document::find($document_id);

        $fact =  DB::connection('tenant')->transaction(function () use ($document) {
            $facturalo = new Facturalo();
            $facturalo->setDocument($document);
            $facturalo->loadXmlSigned();
            $facturalo->onlySenderXmlSignedBill();
            return $facturalo;
        });

        $response = $fact->getResponse();

        return [
            'success' => true,
            'message' => $response['description'],
        ];
    }

    public function consultCdr($document_id)
    {
        $document = Document::find($document_id);

        $fact =  DB::connection('tenant')->transaction(function () use ($document) {
            $facturalo = new Facturalo();
            $facturalo->setDocument($document);
            $facturalo->consultCdr();
        });

        $response = $fact->getResponse();

        return [
            'success' => true,
            'message' => $response['description'],
        ];
    }

    public function sendServer($document_id, $query = false)
    {
        $document = Document::find($document_id);
        // $bearer = config('tenant.token_server');
        // $api_url = config('tenant.url_server');
        $bearer = $this->getTokenServer();
        $api_url = $this->getUrlServer();
        $client = new Client(['base_uri' => $api_url, 'verify' => false]);

        // $zipFly = new ZipFly();
        if (!$document->data_json) throw new Exception("Campo data_json nulo o inválido - Comprobante: {$document->fullnumber}");

        $data_json = (array)$document->data_json;
        $data_json['numero_documento'] = $document->number;
        $data_json['external_id'] = $document->external_id;
        $data_json['hash'] = $document->hash;
        $data_json['qr'] = $document->qr;
        $data_json['query'] = $query;
        $data_json['file_xml_signed'] = base64_encode($this->getStorage($document->filename, 'signed'));
        $data_json['file_pdf'] = base64_encode($this->getStorage($document->filename, 'pdf'));
        // dd($data_json);
        $res = $client->post('/api/documents_server', [
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer,
                'Accept' => 'application/json',
            ],
            'form_params' => $data_json
        ]);

        $response = json_decode($res->getBody()->getContents(), true);

        if ($response['success']) {
            $document->send_server = true;
            $document->save();
        }

        return $response;
    }

    public function checkServer($document_id)
    {
        $document = Document::find($document_id);
        $bearer = $this->getTokenServer();
        $api_url = $this->getUrlServer();

        $client = new Client(['base_uri' => $api_url, 'verify' => false]);

        $res = $client->get('/api/document_check_server/' . $document->external_id, [
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer,
                'Accept' => 'application/json',
            ],
        ]);

        $response = json_decode($res->getBody()->getContents(), true);

        if ($response['success']) {
            $state_type_id = $response['state_type_id'];
            $document->state_type_id = $state_type_id;
            $document->save();

            if ($state_type_id === '05') {
                $this->uploadStorage($document->filename, base64_decode($response['file_cdr']), 'cdr');
            }
        }

        return $response;
    }

    public function searchCustomerById($id)
    {

        $customers = Person::with('addresses')->whereType('customers')
            ->where('id', $id)
            ->whereFilterCustomerBySeller('customers')
            ->get()->transform(function ($row) {
                /** @var  Person $row */
                return $row->getCollectionData();
                /* Movido al modelo */
                return [
                    'id' => $row->id,
                    'description' => $row->number . ' - ' . $row->name,
                    'name' => $row->name,
                    'number' => $row->number,
                    'identity_document_type_id' => $row->identity_document_type_id,
                    'identity_document_type_code' => $row->identity_document_type->code,
                    'addresses' => $row->addresses,
                    'address' => $row->address
                ];
            });

        return compact('customers');
    }

    public function getIdentityDocumentTypeId($document_type_id, $operation_type_id)
    {

        // if($operation_type_id === '0101' || $operation_type_id === '1001') {

        if (in_array($operation_type_id, ['0101', '1001', '1004'])) {

            if ($document_type_id == '01') {
                $identity_document_type_id = [6];
            } else {
                if (config('tenant.document_type_03_filter')) {
                    $identity_document_type_id = [1];
                } else {
                    $identity_document_type_id = [1, 4, 6, 7, 0];
                }
            }
        } else {
            $identity_document_type_id = [1, 4, 6, 7, 0];
        }

        return $identity_document_type_id;
    }

    public function changeToRegisteredStatus($document_id)
    {
        $document = Document::find($document_id);
        if ($document->state_type_id === '01') {
            $document->state_type_id = '05';
            $document->save();

            return [
                'success' => true,
                'message' => 'El estado del documento fue actualizado.',
            ];
        }
    }

    public function import(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new DocumentsImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' => __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' => __('app.actions.upload.error'),
        ];
    }

    public function importTwoFormat(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new DocumentsImportTwoFormat();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' => __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' => __('app.actions.upload.error'),
        ];
    }

    public function messageLockedEmission()
    {

        $exceed_limit = DocumentHelper::exceedLimitDocuments();

        if ($exceed_limit['success']) {
            return [
                'success' => false,
                'message' => $exceed_limit['message'],
            ];
        }

        // $configuration = Configuration::first();
        // $quantity_documents = Document::count();
        // $quantity_documents = $configuration->quantity_documents;

        // if($configuration->limit_documents !== 0 && ($quantity_documents > $configuration->limit_documents))
        //     return [
        //         'success' => false,
        //         'message' => 'Alcanzó el límite permitido para la emisión de comprobantes',
        //     ];


        return [
            'success' => true,
            'message' => '',
        ];
    }

    public function getRecords($request)
    {


        $d_end = $request->d_end;
        $d_start = $request->d_start;
        $date_of_issue = $request->date_of_issue;
        $document_type_id = $request->document_type_id;
        $state_type_id = $request->state_type_id;
        $number = $request->number;
        $series = $request->series;
        $pending_payment = ($request->pending_payment == "true") ? true : false;
        $customer_id = $request->customer_id;
        $item_id = $request->item_id;
        $category_id = $request->category_id;
        $purchase_order = $request->purchase_order;
        $guides = $request->guides;
        $plate_numbers = $request->plate_numbers;

        $records = Document::query();
        if ($d_start && $d_end) {
            $records->whereBetween('date_of_issue', [$d_start, $d_end]);
        }
        if ($date_of_issue) {
            $records = Document::where('date_of_issue', 'like', '%' . $date_of_issue . '%');
        }
        /** @var Builder $records */
        if ($document_type_id) {
            $records->where('document_type_id', 'like', '%' . $document_type_id . '%');
        }
        if ($series) {
            $records->where('series', 'like', '%' . $series . '%');
        }
        if ($number) {
            $records->where('number', $number);
        }
        if ($state_type_id) {
            $records->where('state_type_id', 'like', '%' . $state_type_id . '%');
        }
        if ($purchase_order) {
            $records->where('purchase_order', $purchase_order);
        }
        $records->whereTypeUser()->latest();

        if ($pending_payment) {
            $records->where('total_canceled', false);
        }

        if ($customer_id) {
            $records->where('customer_id', $customer_id);
        }

        if ($item_id) {
            $records->whereHas('items', function ($query) use ($item_id) {
                $query->where('item_id', $item_id);
            });
        }

        if ($category_id) {
            $records->whereHas('items', function ($query) use ($category_id) {
                $query->whereHas('relation_item', function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });
            });
        }
        if (!empty($guides)) {
            $records->where('guides', 'like', DB::raw("%\"number\":\"%") . $guides . DB::raw("%\"%"));
        }
        if ($plate_numbers) {
            $records->where('plate_number', 'like', '%' . $plate_numbers . '%');
        }
        return $records;
    }

    public function data_table()
    {

        $customers = $this->table('customers');
        $items = $this->getItems();
        $categories = Category::orderBy('name')->get();
        $state_types = StateType::get();
        $document_types = DocumentType::whereIn('id', ['01', '03', '07', '08'])->get();
        $series = Series::whereIn('document_type_id', ['01', '03', '07', '08'])->get();
        $establishments = Establishment::where('id', auth()->user()->establishment_id)->get(); // Establishment::all();

        return compact('customers', 'document_types', 'series', 'establishments', 'state_types', 'items', 'categories');
    }


    public function getItems()
    {

        $items = Item::orderBy('description')->take(20)->get()->transform(function ($row) {
            return [
                'id' => $row->id,
                'description' => ($row->internal_id) ? "{$row->internal_id} - {$row->description}" : $row->description,
            ];
        });

        return $items;
    }


    public function getDataTableItem(Request $request)
    {

        $items = Item::where('description', 'like', "%{$request->input}%")
            ->orWhere('internal_id', 'like', "%{$request->input}%")
            ->orderBy('description')
            ->get()->transform(function ($row) {
                return [
                    'id' => $row->id,
                    'description' => ($row->internal_id) ? "{$row->internal_id} - {$row->description}" : $row->description,
                ];
            });

        return $items;
    }


    private function updateMaxCountPayments($value)
    {
        if ($value > $this->max_count_payment) {
            $this->max_count_payment = $value;
        }
        // $this->max_count_payment = 20 ;//( $value > $this->max_count_payment) ? $value : $this->$max_count_payment;
    }

    private function transformReportPayment($resource)
    {

        $records = $resource->transform(function ($row) {

            $total_paid = collect($row->payments)->sum('payment');
            $total = $row->total;
            $total_difference = round($total - $total_paid, 2);

            $this->updateMaxCountPayments($row->payments->count());

            return (object)[

                'id' => $row->id,
                'ruc' => $row->customer->number,
                // 'date' =>  $row->date_of_issue->format('Y-m-d'),
                // 'date' =>  $row->date_of_issue,
                'date' => $row->date_of_issue->format('d/m/Y'),
                'invoice' => $row->number_full,
                'comercial_name' => $row->customer->trade_name,
                'business_name' => $row->customer->name,
                'zone' => $row->customer->department->description,
                'total' => number_format($row->total, 2, ".", ""),

                'payments' => $row->payments,

                /*'payment1' =>  ( isset($row->payments[0]) ) ?  number_format($row->payments[0]->payment, 2) : '',
                'payment2' =>  ( isset($row->payments[1]) ) ?  number_format($row->payments[1]->payment, 2) : '',
                'payment3' =>   ( isset($row->payments[2]) ) ?  number_format($row->payments[2]->payment, 2) : '',
                'payment4' =>   ( isset($row->payments[3]) ) ?  number_format($row->payments[3]->payment, 2) : '', */

                'balance' => $total_difference,
                'person_type' => isset($row->person->person_type->description) ? $row->person->person_type->description : '',
                'department' => $row->customer->department->description,
                'district' => $row->customer->district->description,

                /*'reference1' => ( isset($row->payments[0]) ) ?  $row->payments[0]->reference : '',
                'reference2' =>  ( isset($row->payments[1]) ) ?  $row->payments[1]->reference : '',
                'reference3' =>  ( isset($row->payments[2]) ) ?  $row->payments[2]->reference : '',
                'reference4' =>  ( isset($row->payments[3]) ) ?  $row->payments[3]->reference : '', */
            ];
        });

        return $records;
    }

    public function report_payments(Request $request)
    {
        // $month_format = Carbon::parse($month)->format('m');

        if ($request->anulled == 'true') {
            $records = Document::whereBetween('date_of_issue', [$request->date_start, $request->date_end])->get();
        } else {
            $records = Document::whereBetween('date_of_issue', [$request->date_start, $request->date_end])->where('state_type_id', '!=', '11')->get();
        }

        $source = $this->transformReportPayment($records);

        return (new PaymentExport)
            ->records($source)
            ->payment_count($this->max_count_payment)
            ->download('Reporte_Pagos_' . Carbon::now() . '.xlsx');
    }

    public function destroyDocument($document_id)
    {
        try {

            DB::connection('tenant')->transaction(function () use ($document_id) {

                $record = Document::findOrFail($document_id);
                $this->deleteAllPayments($record->payments);
                $record->delete();
            });

            return [
                'success' => true,
                'message' => 'Documento eliminado con éxito'
            ];
        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false, 'message' => 'El Documento esta siendo usada por otros registros, no puede eliminar'] : ['success' => false, 'message' => 'Error inesperado, no se pudo eliminar el Documento'];
        }
    }

    public function storeCategories(CategoryRequest $request)
    {
        $id = $request->input('id');
        $category = Category::firstOrNew(['id' => $id]);
        $category->fill($request->all());
        $category->save();


        return [
            'success' => true,
            'message' => ($id) ? 'Categoría editada con éxito' : 'Categoría registrada con éxito',
            'data' => $category

        ];
    }

    public function storeBrands(BrandRequest $request)
    {
        $id = $request->input('id');
        $brand = Brand::firstOrNew(['id' => $id]);
        $brand->fill($request->all());
        $brand->save();


        return [
            'success' => true,
            'message' => ($id) ? 'Marca editada con éxito' : 'Marca registrada con éxito',
            'data' => $brand
        ];
    }

    public function searchExternalId(Request $request)
    {
        return response()->json(Document::where('external_id', $request->external_id)->first());
    }

    public function importExcelFormat(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new DocumentImportExcelFormat();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();

                return [
                    'success' => true,
                    'message' =>  'Se importaron ' . $data['registered'] . ' de ' . $data['total_records'] . ' registros',
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function importExcelTables()
    {
        $document_types = DocumentType::query()
            ->whereIn('id', ['01', '03'])
            ->get();

        $series = Series::query()
            ->whereIn('document_type_id', ['01', '03'])
            ->where('establishment_id', auth()->user()->establishment_id)
            ->get();

        return [
            'document_types' => $document_types,
            'series' => $series,
        ];
    }

    public function retention($document_id)
    {
        $document = Document::query()
            ->select('id', 'series', 'number', 'retention')
            ->where('id', $document_id)->first();

        if ($document->retention) {
            $retention = $document->retention;
            $amount = $retention->amount;
            if ($retention->currency_type_id === 'USD') {
                $amount = $amount * $retention->exchange_rate;
            }
            $amount = round($amount, 0);
            return [
                'success' => true,
                'form' => [
                    'document_id' => $document_id,
                    'document_number' => $document->number_full,
                    'amount' => $amount,
                    'voucher_date_of_issue' => $retention->voucher_date_of_issue ?: null,
                    'voucher_number' => $retention->voucher_number ?: null,
                    'voucher_amount' => $retention->voucher_amount ?: $amount,
                    'voucher_filename' => $retention->voucher_filename ?: null,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'No existe retención'
        ];
    }

    public function retentionStore(Request $request)
    {
        try {
            $voucher_filename = $request->input('voucher_filename');
            $temp_path = $request->input('temp_path');

            if ($temp_path) {
                $file_name_old_array = explode('.', $voucher_filename);
                $file_content = file_get_contents($temp_path);
                $extension = $file_name_old_array[1];
                $voucher_filename = Str::slug('r_' . $file_name_old_array[0]) . '_' . date('YmdHis') . '.' . $extension;
                Storage::disk('tenant')->put('document_payment' . DIRECTORY_SEPARATOR . $voucher_filename, $file_content);
            }

            $document_id = $request->input('document_id');
            $voucher_number = $request->input('voucher_number');
            $voucher_date_of_issue = $request->input('voucher_date_of_issue');
            $voucher_amount = $request->input('voucher_amount');

            Document::query()
                ->where('id', $document_id)->update([
                    'retention->voucher_date_of_issue' => $voucher_date_of_issue,
                    'retention->voucher_number' => $voucher_number,
                    'retention->voucher_amount' => $voucher_amount,
                    'retention->voucher_filename' => $voucher_filename
                ]);

            return [
                'success' => true,
                'message' => 'Retención actualizada satisfactoriamente',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
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
            }
        }
    }
    public function retentionUpload(Request $request)
    {
        try {
            $validate_upload = UploadFileHelper::validateUploadFile($request, 'file');

            if (!$validate_upload['success']) {
                return $validate_upload;
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $temp = tempnam(sys_get_temp_dir(), 'document_retention');
                file_put_contents($temp, file_get_contents($file));

                return [
                    'success' => true,
                    'data' => [
                        'filename' => $file->getClientOriginalName(),
                        'temp_path' => $temp,
                    ]
                ];
            }
            return [
                'success' => false,
                'message' => __('app.actions.upload.error'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
