<?php

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Company;
use App\Models\Tenant\EmailSendLog;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function ($row, $key) {
            $has_xml = true;
            $has_pdf = true;
            $has_cdr = false;
            $btn_note = false;
            $btn_guide = true; // Boton para generar guia
            $btn_resend = false;
            $btn_voided = false;
            $btn_pdf_voided = false;
            $btn_consult_cdr = false;
            $btn_delete_doc_type_03 = false;
            $btn_constancy_detraction = false;



            $affected_document = null;

            if ($row->group_id === '01') {
                if ($row->state_type_id === '01') {
                    $btn_resend = true;
                }

                if ($row->state_type_id === '05') {
                    $has_cdr = true;
                    $btn_note = true;
                    $btn_resend = false;
                    $btn_voided = true;
                    $btn_consult_cdr = true;
                }
                if ($row->state_type_id === '11') {
                    $btn_pdf_voided = true;
                }

                if (in_array($row->document_type_id, ['07', '08'])) {
                    $btn_note = false;
                }
            }

            if ($row->group_id === '02') {
                if ($row->state_type_id === '05') {
                    $btn_note = true;
                    $btn_voided = true;

                    // envio individual
                    if ($row->isSingleDocumentShipment()) $has_cdr = true;
                    // envio individual

                }

                // envio individual reenviar
                if ($row->state_type_id === '01' && $row->isSingleDocumentShipment()) {
                    $btn_resend = true;
                }
                // envio individual reenviar


                if (in_array($row->document_type_id, ['07', '08'])) {
                    $btn_note = false;
                }

                if ($row->document_type_id === '03' && config('tenant.delete_document_type_03')) {

                    if ($row->state_type_id === '01' && $row->doesntHave('summary_document')) {
                        $btn_delete_doc_type_03 = true;
                    }
                }
            }
            $btn_guide = $btn_note;
            if ($btn_guide === false && ($row->state_type_id === '01')) {
                // #750
                $btn_guide = true;
            }
            if ($row->no_stock) {
                $no_stock_document = $row->no_stock_document;
                if ($no_stock_document && $no_stock_document->completed) {
                    $btn_guide = false;
                }
            }
            if (in_array($row->document_type_id, ['01', '03'])) {
                $btn_constancy_detraction = ($row->detraction) ? true : false;
            }

            // $btn_recreate_document = config('tenant.recreate_document');
            $btn_recreate_document = auth()->user()->recreate_documents;

            $btn_change_to_registered_status = false;
            if ($row->state_type_id === '01') {
                $btn_change_to_registered_status = config('tenant.change_to_registered_status');
            }

            $total_payment = $row->payments->sum('payment');
            if ($row->bill_of_exchange_id) {
                $total_payment += $row->bill_of_exchange_document->total;
            }

            $balance = number_format($row->total - $total_payment, 2, ".", "");
            if($row->sale_note_id) {
                $sale_note = $row->sale_note;
                if($sale_note->payments && ($sale_note->paid == 1||$sale_note->total_canceled == 1)) {
                    $total_payment += $sale_note->payments->sum('payment');
                }
            }
            if ($row->retention) {
                $balance = number_format($row->total - $row->retention->amount - $total_payment, 2, ".", "");
            } else {
                $balance = number_format($row->total - $total_payment, 2, ".", "");
            }
            if($balance < 0) {
                $balance = number_format(0, 2, ".", "");
            }
            $message_regularize_shipping = null;

            if ($row->regularize_shipping) {
                $message_regularize_shipping = "Por regularizar: {$row->response_regularize_shipping->code} - {$row->response_regularize_shipping->description}";
            }
            $nvs = $row->getNvCollection();

            $order_note = $row->getOrderNoteCollection();
            // Regresa si se hn enviado correos
            $email_send_it = false;
            $email_send_it_array = [];
            $send_it = EmailSendLog::Document()->FindRelationId($row->id)->get();
            if (count($send_it) > 0) {
                /** @var EmailSendLog $log*/
                foreach ($send_it as $log) {
                    $email_send_it_array[] = [
                        'email' => $log->email,
                        'send_it' => $log->sendit,
                        'send_date' => $log->created_at->format('Y-m-d H:i'),
                    ];
                    if ($email_send_it == false) {
                        $email_send_it = $log->sendit;
                    }
                }
            }
            $date_pay = $row->payments;
            $payment = '';
            if (count($date_pay) > 0) {
                foreach ($date_pay as $pay) {
                    $payment = $pay->date_of_payment->format('Y-m-d');
                }
            }

            $btn_retention = !is_null($row->retention);
            $company = Company::active();
            $btn_send_pse = false;
            $btn_check_voided_pse = false;
            $btn_check_pse = false;
            $btn_voided_pse = false;
            if ($row->state_type_id === '03'  && $row->soap_type_id === '02' && $company->pse) {
                // if($row->state_type_id === '03'  && $company->pse){
                $btn_check_pse = true;
            }

            if ($row->state_type_id === '01'  && $row->soap_type_id === '02' && $company->pse) {
                // if($row->state_type_id === '03'  && $company->pse){
                $btn_send_pse = true;
            }
            if ($row->state_type_id === '05'  && $row->soap_type_id === '02' && $company->pse) {
                // if($row->state_type_id === '03'  && $company->pse){
                $btn_voided_pse = true;
                $btn_voided = false;
            }

            if ($row->state_type_id == '13' && $row->soap_type_id === '02' && $company->pse) {
                $btn_check_voided_pse = true;
            }
            return [
                'sent_it_email' => $email_send_it,
                'alter_company' => $row->alter_company,
                'btn_pdf_voided' => $btn_pdf_voided,
                'bill_of_exchange_id' => $row->bill_of_exchange_id,
                'btn_check_voided_pse' => $btn_check_voided_pse,
                'btn_voided_pse' => $btn_voided_pse,
                'btn_send_pse' => $btn_send_pse,
                'btn_check_pse' => $btn_check_pse,
                'appendix_2' => (bool) $row->appendix_2,
                'appendix_3' => (bool) $row->appendix_3,
                'appendix_4' => (bool) $row->appendix_4,
                'appendix_5' => (bool) $row->appendix_5,
                'id' => $row->id,
                'group_id' => $row->group_id,
                'soap_type_id' => $row->soap_type_id,
                'soap_type_description' => $row->soap_type->description,
                'date_of_issue' => $row->date_of_issue->format('Y-m-d'),
                'date_of_due' => (in_array($row->document_type_id, ['01', '03'])) ? $row->invoice->date_of_due->format('Y-m-d') : null,
                'number' => $row->number_full,
                'customer_name' => $row->customer->name,
                'customer_number' => $row->customer->number,
                'customer_telephone' => $row->customer->telephone,
                'customer_email' => optional($row->customer)->email,
                'currency_type_id' => $row->currency_type_id,
                'exchange_rate_sale' => $row->exchange_rate_sale,
                'total_exportation' => $row->total_exportation,
                'total_free' => $row->total_free,
                'total_unaffected' => $row->total_unaffected,
                'total_exonerated' => $row->total_exonerated,
                'total_taxed' => $row->total_taxed,
                'total_igv' => $row->total_igv,
                'total' => $row->total,
                'state_type_id' => $row->state_type_id,
                'state_type_description' => $row->state_type->description,
                'document_type_description' => $row->document_type->description,
                'document_type_id' => $row->document_type->id,
                'has_xml' => $has_xml,
                'has_pdf' => $has_pdf,
                'has_cdr' => $has_cdr,
                'download_xml' => $row->download_external_xml,
                'download_pdf' => $row->download_external_pdf,
                'download_cdr' => $row->download_external_cdr,
                'btn_voided' => $btn_voided,
                'btn_note' => $btn_note,
                'btn_guide' => $btn_guide,
                //                'btn_ticket' => $btn_ticket,
                'btn_resend' => $btn_resend,
                'btn_consult_cdr' => $btn_consult_cdr,
                'btn_constancy_detraction' => $btn_constancy_detraction,
                'btn_recreate_document' => $btn_recreate_document,
                'btn_change_to_registered_status' => $btn_change_to_registered_status,
                'btn_delete_doc_type_03' => $btn_delete_doc_type_03,
                'send_server' => (bool) $row->send_server,
                //                'voided' => $voided,
                'affected_document' => $affected_document,
                //                'has_xml_voided' => $has_xml_voided,
                //                'has_cdr_voided' => $has_cdr_voided,
                //                'download_xml_voided' => $download_xml_voided,
                //                'download_cdr_voided' => $download_cdr_voided,
                'shipping_status' => json_decode($row->shipping_status),
                'sunat_shipping_status' => json_decode($row->sunat_shipping_status),
                'query_status' => json_decode($row->query_status),
                'created_at' => $row->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $row->updated_at->format('Y-m-d H:i:s'),
                'user_name' => ($row->user) ? $row->user->name : '',
                'user_email' => ($row->user) ? $row->user->email : '',
                'user_id' => $row->user_id,
                'email_send_it' => $email_send_it,
                'email_send_it_array' => $email_send_it_array,
                'external_id' => $row->external_id,
                'ticket_single_shipment' => (bool) $row->ticket_single_shipment,
                'force_send_by_summary' => (bool) $row->force_send_by_summary,

                'notes' => (in_array($row->document_type_id, ['01', '03'])) ? $row->affected_documents->transform(function ($row) {
                    return [
                        'id' => $row->id,
                        'document_id' => $row->document_id,
                        'note_type_description' => ($row->note_type == 'credit') ? 'NC' : 'ND',
                        'description' => $row->document->number_full,
                    ];
                }) : null,
                'affected_documents' => (in_array($row->document_type_id, ['07', '08'])) ? $row->affected_documents2->transform(function ($row) {
                    return [
                        'document_type_id' => isset($row->affected_document) ?  $row->affected_document->document_type_id
                            : (isset($row->data_affected_document) ?
                                $row->data_affected_document->document_type_id
                                : null
                            )
                    ];
                }) : [],
                'auditor_state' => (bool)$row->auditor_state,
                'sales_note' => $nvs,
                'order_note' => $order_note,
                'balance' => $balance,
                'guides' => !empty($row->guides) ? (array)$row->guides : null,
                'message_regularize_shipping' => $message_regularize_shipping,
                'regularize_shipping' => (bool) $row->regularize_shipping,
                'purchase_order' => $row->purchase_order,
                'is_editable' => $row->is_editable,
                'dispatches' => $this->getDispatches($row),
                'soap_type' => $row->soap_type,
                'plate_numbers' => $row->getPlateNumbers(),
                'total_charge' => $row->total_charge,
                'filename' => $row->filename,
                'date_of_payment' => $payment,
                'btn_force_send_by_summary' => $row->isAvailableForceSendBySummary(),
                'btn_retention' => $btn_retention
            ];
        });
    }


    private function getDispatches($row)
    {

        $dispatches = [];

        if (in_array($row->document_type_id, ['01', '03'])) {

            $dispatches = $row->reference_guides->transform(function ($row) {
                return [
                    'description' => $row->number_full,
                ];
            });

            if ($row->dispatch) {
                $dispatches = $dispatches->push([
                    'description' => $row->dispatch->number_full,
                ]);
            }
        }

        return $dispatches;
    }

    private function getVoided($row, $document_id)
    {
        $voided_data = [];

        if (in_array($row->document_type_id, ['01', '03'])) {
            $voided = \App\Models\Tenant\VoidedDocument::where("document_id", "=", $document_id)->orderBy('id', 'desc')->first();
            if ($voided) {
                $voided_data = \App\Models\Tenant\Voided::where("id", $voided["voided_id"])->orderBy('id', 'desc')->get()->transform(function ($row) {
                    return [
                        'external_id' => $row->external_id,
                        'ticket' => $row->ticket,
                    ];
                });
            }
        }

        return $voided_data;
    }
}
