@php
$establishment = $document->establishment;
$establishment__ = \App\Models\Tenant\Establishment::find($document->establishment_id);
$logo = $establishment__->logo ?? $company->logo;
if ($logo === null && !file_exists(public_path("$logo}"))) {
    $logo = "{$company->logo}";
}

if ($logo) {
    $logo = "storage/uploads/logos/{$logo}";
    $logo = str_replace("storage/uploads/logos/storage/uploads/logos/", "storage/uploads/logos/", $logo);
}

    
$customer = $document->customer;
    $invoice = $document->invoice;
    //$path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
    $document_number = $document->series.'-'.str_pad($document->number, 8, '0', STR_PAD_LEFT);
    $accounts = \App\Models\Tenant\BankAccount::where('show_in_documents', true)->get();
    $document_base = ($document->note) ? $document->note : null;
    $payments = $document->payments;

    if($document_base) {
        $affected_document_number = ($document_base->affected_document) ? $document_base->affected_document->series.'-'.str_pad($document_base->affected_document->number, 8, '0', STR_PAD_LEFT) : $document_base->data_affected_document->series.'-'.str_pad($document_base->data_affected_document->number, 8, '0', STR_PAD_LEFT);

    } else {
        $affected_document_number = null;
    }
    $document->load('reference_guides');

    $total_payment = $document->payments->sum('payment');
    $balance = ($document->total - $total_payment) - $document->payments->sum('change');


$establishment__ = \App\Models\Tenant\Establishment::find($document->establishment_id);
$logo = $establishment__->logo ?? $company->logo;

if ($logo === null && !file_exists(public_path("$logo}"))) {
    $logo = "{$company->logo}";
}

if ($logo) {
    $logo = "storage/uploads/logos/{$logo}";
    $logo = str_replace("storage/uploads/logos/storage/uploads/logos/", "storage/uploads/logos/", $logo);
}


@endphp
<html>
<head>
    {{--<title>{{ $document_number }}</title>--}}
    {{--<link href="{{ $path_style }}" rel="stylesheet" />--}}
</head>
<body class="ticket">

@if($logo)
    <div class="text-center company_logo_box pt-4">
        <img src="data:{{mime_content_type(public_path("{$logo}"))}};base64, {{base64_encode(file_get_contents(public_path("{$logo}")))}}" alt="{{$company->name}}" class="company_logo_ticket contain">
    </div>
{{--@else--}}
    {{--<div class="text-center company_logo_box pt-5">--}}
        {{--<img src="{{ asset('logo/logo.jpg') }}" class="company_logo_ticket contain">--}}
    {{--</div>--}}
@endif

@if($document->state_type->id == '11')
    <div class="company_logo_box" style="position: absolute; text-align: center; top:500px">
        <img src="data:{{mime_content_type(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png"))}};base64, {{base64_encode(file_get_contents(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png")))}}" alt="anulado" class="" style="opacity: 0.6;">
    </div>
@endif
<table class="full-width">
    <tr>
        <td class="text-center" style="text-transform:uppercase;">{{ $company->name }}</td>
    </tr>
    <tr>
        <td class="text-center">{{ 'RUC '.$company->number }}</td>
    </tr>
    <tr>
        <td class="text-center" style="text-transform: capitalize;">
            {{ ($establishment->address !== '-')? $establishment->address : '' }}
            {{ ($establishment->district_id !== '-')? ', '.$establishment->district->description : '' }}
            {{ ($establishment->province_id !== '-')? ', '.$establishment->province->description : '' }}
            {{ ($establishment->department_id !== '-')? ', '.$establishment->department->description : '' }}
        </td>
    </tr>

    @isset($establishment->trade_address)
    <tr>
        <td class="text-center ">{{  ($establishment->trade_address !== '-')? 'D. Comercial: '.$establishment->trade_address : ''  }}</td>
    </tr>
    @endisset
    <tr>
        <td class="text-center ">{{ ($establishment->telephone !== '-')? 'Central telefónica: '.$establishment->telephone : '' }}</td>
    </tr>
    <tr>
        <td class="text-center">{{ ($establishment->email !== '-')? 'Email: '.$establishment->email : '' }}</td>
    </tr>
    @isset($establishment->web_address)
        <tr>
            <td class="text-center">{{ ($establishment->web_address !== '-')? 'Web: '.$establishment->web_address : '' }}</td>
        </tr>
    @endisset

    @isset($establishment->aditional_information)
        <tr>
            <td class="text-center pb-3">{{ ($establishment->aditional_information !== '-')? $establishment->aditional_information : '' }}</td>
        </tr>
    @endisset

    <br>
    <br>

    <tr>
        <td style="vertical-align: top; text-transform: capitalize;" class="font-bold text-center"><h5>{{ $document->document_type->description }}</h5></td>
    </tr>
    <tr>
        <td class="font-bold text-center"><h5>{{ $document_number }}</h5></td>
    </tr>
</table>
<table class="full-width">
    <tr >
        <td width="" class="pt-3"><p class="desc">F. Emisión:</p></td>
        <td width="" class="pt-3"><p class="desc">{{ $document->date_of_issue->format('Y-m-d') }}</p></td>
    </tr>
    <tr>
        <td width="" ><p class="desc">H. Emisión:</p></td>
        <td width="" ><p class="desc">{{ $document->time_of_issue }}</p></td>
    </tr>
    @isset($invoice->date_of_due)
    <tr>
        <td><p class="desc">F. Vencimiento:</p></td>
        <td><p class="desc">{{ $invoice->date_of_due->format('Y-m-d') }}</p></td>
    </tr>
    @endisset

    <tr>
        <td class="align-top"><p class="desc">Cliente:</p></td>
        <td><p class="desc">{{ $customer->name }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">{{ $customer->identity_document_type->description }}:</p></td>
        <td><p class="desc">{{ $customer->number }}</p></td>
    </tr>

    @if ($customer->address !== '')
    <tr>
        <td class="align-top"><p class="desc">Dirección:</p></td>
        <td class="desc" style="text-transform: capitalize;">
           
            {{ $customer->address }}
            {{ ($customer->district_id !== '-')? ', '.$customer->district->description : '' }}
            {{ ($customer->province_id !== '-')? ', '.$customer->province->description : '' }}
            {{ ($customer->department_id !== '-')? ', '.$customer->department->description : '' }}
        </td>
    </tr>
    @endif

    @if ($document->reference_data)
        <tr>
            <td class="align-top"><p class="desc">D. Referencia:</p></td>
            <td>
                <p class="desc">
                    {{ $document->reference_data }}
                </p>
            </td>
        </tr>
    @endif

    @if ($document->detraction)
    {{--<strong>Operación sujeta a detracción</strong>--}}
        <tr>
            <td  class="align-top"><p class="desc">N. Cta detracciones:</p></td>
            <td><p class="desc">{{ $document->detraction->bank_account}}</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">B/S Sujeto a detracción:</p></td>
            @inject('detractionType', 'App\Services\DetractionTypeService')
            <td><p class="desc">{{$document->detraction->detraction_type_id}} - {{ $detractionType->getDetractionTypeDescription($document->detraction->detraction_type_id ) }}</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">Método de Pago:</p></td>
            <td><p class="desc">{{ $detractionType->getPaymentMethodTypeDescription($document->detraction->payment_method_id ) }}</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">Porcentaje detracción:</p></td>
            <td><p class="desc">{{ $document->detraction->percentage}}%</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">Monto detracción:</p></td>
            <td><p class="desc">S/ {{ $document->detraction->amount}}</p></td>
        </tr>
        @if($document->detraction->pay_constancy)
        <tr>
            <td  class="align-top"><p class="desc">Constancia de Pago:</p></td>
            <td><p class="desc">{{ $document->detraction->pay_constancy}}</p></td>
        </tr>
        @endif


        @if($invoice->operation_type_id == '1004')
        <tr class="mt-2">
            <td colspan="2"></td>
        </tr>
        <tr class="mt-2">
            <td colspan="2">Detalle - Servicios de transporte de carga</td>
        </tr>
        <tr>
            <td class="align-top"><p class="desc">Ubigeo origen:</p></td>
            <td><p class="desc">{{ $document->detraction->origin_location_id[2] }}</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">Dirección origen:</td>
            <td><p class="desc">{{ $document->detraction->origin_address }}</td>
        </tr>
        <tr>
            <td class="align-top"><p class="desc">Ubigeo destino:</p></td>
            <td><p class="desc">{{ $document->detraction->delivery_location_id[2] }}</p></td>
        </tr>
        <tr>

            <td  class="align-top"><p class="desc">Dirección destino:</p></td>
            <td><p class="desc">{{ $document->detraction->delivery_address }}</p></td>
        </tr>
        <tr>
            <td class="align-top"><p class="desc">Valor referencial servicio de transporte:</p></td>
            <td><p class="desc">{{ $document->detraction->reference_value_service }}</p></td>
        </tr>
        <tr>

            <td  class="align-top"><p class="desc">Valor referencia carga efectiva:</p></td>
            <td><p class="desc">{{ $document->detraction->reference_value_effective_load }}</p></td>
        </tr>
        <tr>
            <td class="align-top"><p class="desc">Valor referencial carga útil:</p></td>
            <td><p class="desc">{{ $document->detraction->reference_value_payload }}</p></td>
        </tr>
        <tr>
            <td  class="align-top"><p class="desc">Detalle del viaje:</p></td>
            <td><p class="desc">{{ $document->detraction->trip_detail }}</p></td>
        </tr>
        @endif

    @endif


    @if ($document->retention)
        <br>
        <tr>
            <td colspan="2">
                <p class="desc"><strong>Información de la retención</strong></p>
            </td>
        </tr>
        <tr>
            <td><p class="desc">Base imponible: </p></td>
            <td><p class="desc">{{ $document->currency_type->symbol}} {{ $document->retention->base }} </p></td>
        </tr>
        <tr>
            <td><p class="desc">Porcentaje:</p></td>
            <td><p class="desc">{{ $document->retention->percentage * 100 }}%</p></td>
        </tr>
        <tr>
            <td><p class="desc">Monto:</p></td>
            <td><p class="desc">{{ $document->currency_type->symbol}} {{ $document->retention->amount }}</p></td>
        </tr>
    @endif


    @if ($document->prepayments)
        @foreach($document->prepayments as $p)
        <tr>
            <td><p class="desc">Anticipo :</p></td>
            <td><p class="desc">{{$p->number}}</p></td>
        </tr>
        @endforeach
    @endif
    @if ($document->purchase_order)
        <tr>
            <td><p class="desc">Orden de compra:</p></td>
            <td><p class="desc">{{ $document->purchase_order }}</p></td>
        </tr>
    @endif
    @if ($document->quotation_id)
        <tr>
            <td><p class="desc">Cotización:</p></td>
            <td><p class="desc">{{ $document->quotation->identifier }}</p></td>
        </tr>
    @endif
    @isset($document->quotation->delivery_date)
        <tr>
            <td><p class="desc">F. Entrega</p></td>
            <td><p class="desc">{{ $document->date_of_issue->addDays($document->quotation->delivery_date)->format('d-m-Y') }}</p></td>
        </tr>
    @endisset
    @isset($document->quotation->sale_opportunity)
        <tr>
            <td><p class="desc">O. Venta</p></td>
            <td><p class="desc">{{ $document->quotation->sale_opportunity->number_full}}</p></td>
        </tr>
    @endisset
</table>

@if ($document->guides)
{{--<strong>Guías:</strong>--}}
<table>
    @foreach($document->guides as $guide)
        <tr>
            @if(isset($guide->document_type_description))
                <td class="desc">{{ $guide->document_type_description }}</td>
            @else
                <td class="desc">{{ $guide->document_type_id }}</td>
            @endif
            <td class="desc">:</td>
            <td class="desc">{{ $guide->number }}</td>
        </tr>
    @endforeach
</table>
@endif


@if ($document->transport)
<p class="desc"><strong>Transporte de pasajeros</strong></p>

@php
    $transport = $document->transport;
    $origin_district_id = (array)$transport->origin_district_id;
    $destinatation_district_id = (array)$transport->destinatation_district_id;
    $origin_district = Modules\Order\Services\AddressFullService::getDescription($origin_district_id[2]);
    $destinatation_district = Modules\Order\Services\AddressFullService::getDescription($destinatation_district_id[2]);
@endphp


<table class="full-width mt-3">
    <tr>
        <td><p class="desc">{{ $transport->identity_document_type->description }}:</p></td>
        <td><p class="desc">{{ $transport->number_identity_document }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">Nombre:</p></td>
        <td><p class="desc">{{ $transport->passenger_fullname }}</p></td>
    </tr>


    <tr>
        <td><p class="desc">N° Asiento:</p></td>
        <td><p class="desc">{{ $transport->seat_number }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">M. Pasajero:</p></td>
        <td><p class="desc">{{ $transport->passenger_manifest }}</p></td>
    </tr>

    <tr>
        <td><p class="desc">F. Inicio:</p></td>
        <td><p class="desc">{{ $transport->start_date }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">H. Inicio:</p></td>
        <td><p class="desc">{{ $transport->start_time }}</p></td>
    </tr>


    <tr>
        <td><p class="desc">U. Origen:</p></td>
        <td><p class="desc">{{ $origin_district }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">D. Origen:</p></td>
        <td><p class="desc">{{ $transport->origin_address }}</p></td>
    </tr>

    <tr>
        <td><p class="desc">U. Destino:</p></td>
        <td><p class="desc">{{ $destinatation_district }}</p></td>
    </tr>
    <tr>
        <td><p class="desc">D. Destino:</p></td>
        <td><p class="desc">{{ $transport->destinatation_address }}</p></td>
    </tr>

</table>
@endif


@if (count($document->reference_guides) > 0)
<br/>
<strong>Guias de remisión</strong>
<table>
    @foreach($document->reference_guides as $guide)
        <tr>
            <td>{{ $guide->series }}</td>
            <td>-</td>
            <td>{{ $guide->number }}</td>
        </tr>
    @endforeach
</table>
@endif

@if(!is_null($document_base))
<table>
    <tr>
        <td class="desc">Documento Afectado:</td>
        <td class="desc">{{ $affected_document_number }}</td>
    </tr>
    <tr>
        <td class="desc">Tipo de nota:</td>
        <td class="desc">{{ ($document_base->note_type === 'credit')?$document_base->note_credit_type->description:$document_base->note_debit_type->description}}</td>
    </tr>
    <tr>
        <td class="align-top desc">Descripción:</td>
        <td class="text-left desc">{{ $document_base->note_description }}</td>
    </tr>
</table>
@endif

<table class="full-width mt-10 mb-10">
    <thead class="">
    <tr>
        <th class="border-top-bottom desc text-left">Cant.</th>
        <th class="border-top-bottom desc text-center">Unidad</th>
        <th class="border-top-bottom desc text-left">Descripción</th>
        <th class="border-top-bottom desc text-right">P.Unit</th>
        <th class="border-top-bottom desc text-right">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($document->items as $row)
        <tr>
            <td class="text-center align-top desc ">
                @if(((int)$row->quantity != $row->quantity))
                    {{ $row->quantity }}
                @else
                    {{ number_format($row->quantity, 0) }}
                @endif
            </td>
            <td class="text-center align-top desc ">{{ symbol_or_code($row->item->unit_type_id) }}</td>
            <td class="text-left align-top desc ">
                @if($row->name_product_pdf)
                    {!!$row->name_product_pdf!!}
                @else
                    {!!$row->item->description!!}
                @endif

                @if($row->total_isc > 0)
                    <br/>ISC : {{ $row->total_isc }} ({{ $row->percentage_isc }}%)
                @endif

                  

                @foreach($row->additional_information as $information)
                    @if ($information)
                        <br/>{{ $information }}
                    @endif
                @endforeach

                @if($row->attributes)
                    @foreach($row->attributes as $attr)
                        <br/>{!! $attr->description !!} : {{ $attr->value }}
                    @endforeach
                @endif
                @if($row->discounts)
                    @foreach($row->discounts as $dtos)
                        <br/><small>{{ $dtos->factor * 100 }}% {{$dtos->description }}</small>
                    @endforeach
                @endif

                @if($row->charges)
                    @foreach($row->charges as $charge)
                        <br/><small>{{ $document->currency_type->symbol}} {{ $charge->amount}} ({{ $charge->factor * 100 }}%) {{$charge->description }}</small>
                    @endforeach
                @endif

                @if($row->item->is_set == 1)

                 <br>
                 @inject('itemSet', 'App\Services\ItemSetService')
                 @foreach ($itemSet->getItemsSet($row->item_id) as $item)
                     {{$item}}<br>
                 @endforeach
                 {{-- {{join( "-", $itemSet->getItemsSet($row->item_id) )}} --}}
                @endif

                @if($document->has_prepayment)
                    <br>
                    *** Pago Anticipado ***
                @endif
            </td>
            <td  class="text-right align-top desc ">{{ number_format($row->unit_price, 2) }}</td>
            <td  class="text-right align-top desc ">{{ number_format($row->total, 2) }}</td>
        </tr>
        <tr>
            <td colspan="5" class="border-bottom"></td>
        </tr>
    @endforeach

    @if ($document->prepayments)
        @foreach($document->prepayments as $p)
        <tr>
            <td class="text-center desc-9 align-top">
                1
            </td>
            <td class="text-center desc-9 align-top">NIU</td>
            <td class="text-left desc-9 align-top">
                Anticipo: {{($p->document_type_id == '02')? 'Factura':'Boleta'}} Nro. {{$p->number}}
            </td>
            <td class="text-right  desc-9 align-top">-{{ number_format($p->total, 2) }}</td>
            <td class="text-right  desc-9 align-top">-{{ number_format($p->total, 2) }}</td>
        </tr>
        <tr>
            <td colspan="5" class="border-bottom"></td>
        </tr>
        @endforeach
    @endif

        @if($document->total_exportation > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Exportación: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_exportation, 2) }}</td>
            </tr>
        @endif
        @if($document->total_free > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Gratuitas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_free, 2) }}</td>
            </tr>
        @endif
        @if($document->total_unaffected > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Inafectas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_unaffected, 2) }}</td>
            </tr>
        @endif
        @if($document->total_exonerated > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Exoneradas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_exonerated, 2) }}</td>
            </tr>
        @endif

        @if ($document->document_type_id === '07')
            @if($document->total_taxed >= 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Gravadas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_taxed, 2) }}</td>
            </tr>
            @endif
        @elseif($document->total_taxed > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Op. Gravadas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_taxed, 2) }}</td>
            </tr>
        @endif

        @if($document->total_plastic_bag_taxes > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Icbper: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_plastic_bag_taxes, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="4" class="text-right font-bold desc">IGV: {{ $document->currency_type->symbol }}</td>
            <td class="text-right font-bold desc">{{ number_format($document->total_igv, 2) }}</td>
        </tr>

        @if($document->total_isc > 0)
        <tr>
            <td colspan="4" class="text-right font-bold desc">ISC: {{ $document->currency_type->symbol }}</td>
            <td class="text-right font-bold desc">{{ number_format($document->total_isc, 2) }}</td>
        </tr>
        @endif

        @if($document->total_discount > 0 && $document->subtotal > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Subtotal: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->subtotal, 2) }}</td>
            </tr>
        @endif

        @if($document->total_discount > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">Descuento total: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_discount, 2) }}</td>
            </tr>
        @endif

        @if($document->total_charge > 0)
            @if($document->charges)
                @php
                    $total_factor = 0;
                    foreach($document->charges as $charge) {
                        $total_factor = ($total_factor + $charge->factor) * 100;
                    }
                @endphp
                <tr>
                    <td colspan="4" class="text-right font-bold desc">Cargos ({{$total_factor}}%): {{ $document->currency_type->symbol }}</td>
                    <td class="text-right font-bold desc">{{ number_format($document->total_charge, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="4" class="text-right font-bold desc">Cargos: {{ $document->currency_type->symbol }}</td>
                    <td class="text-right font-bold desc">{{ number_format($document->total_charge, 2) }}</td>
                </tr>
            @endif
        @endif

        <tr>
            <td colspan="4" class="text-right font-bold desc">Total a pagar: {{ $document->currency_type->symbol }}</td>
            <td class="text-right font-bold desc">{{ number_format($document->total, 2) }}</td>
        </tr>

        @if(($document->retention || $document->detraction) && $document->total_pending_payment > 0)
            <tr>
                <td colspan="4" class="text-right font-bold desc">M. Pendiente: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold desc">{{ number_format($document->total_pending_payment, 2) }}</td>
            </tr>
        @endif

        @if($balance < 0)
           <tr>
               <td colspan="4" class="text-right font-bold desc">Vuelto: {{ $document->currency_type->symbol }}</td>
               <td class="text-right font-bold desc">{{ number_format(abs($balance),2, ".", "") }}</td>
           </tr>
        @endif
    </tbody>
</table>

<table class="full-width">
    @foreach(array_reverse((array) $document->legends) as $row)
        <tr>
            @if ($row->code == "1000")
                <td class="desc pt-3" colspan="2">Son: <span class="font-bold">{{ $row->value }} {{ $document->currency_type->description }}</span></td>
                @if (count((array) $document->legends)>1)
                <tr><td class="desc pt-3"><span class="font-bold">Leyendas</span></td></tr>
                @endif
            @else
                <td class="desc pt-3" colspan="2">{{$row->code}}: {{ $row->value }}</td>
            @endif
        </tr>
    @endforeach
    <tr>
        <td class="text-center pt-1">
            <img class="" style="max-width: 100px" src="data:image/png;base64, {{ $document->qr }}" />
        </td>
        <td>
            @if ($document->detraction)
                <p>Operación sujeta al Sistema de Pago de Obligaciones Tributarias</p>
            @endif
            @foreach($document->additional_information as $information)
                @if ($information)
                    @if ($loop->first)
                        <strong>Información adicional</strong>
                    @endif
                    <p class="desc">{{ $information }}</p>
                @endif
            @endforeach
            @if(in_array($document->document_type->id,['01','03']))
                @foreach($accounts as $account)
                    <p class="desc">
                        <small>
                            <span class="font-bold desc">{{$account->bank->description}}</span> {{$account->currency_type->description}}
                            <span class="font-bold desc">N°:</span> {{$account->number}}
                            @if($account->cci)
                            <span class="font-bold desc">CCI:</span> {{$account->cci}}
                            @endif
                        </small>
                    </p>
                @endforeach
            @endif

            <p class="desc"><strong>Código hash:</strong> {{ $document->hash }}</p>

            @php
                $paymentCondition = \App\CoreFacturalo\Helpers\Template\TemplateHelper::getDocumentPaymentCondition($document);
            @endphp
            {{-- Condicion de pago  Crédito / Contado --}}
            <p class="desc pt-5">
                <strong>Condición de Pago:</strong> {{ $paymentCondition }} 
            </p>

            @if($document->payment_method_type_id)
                <p class="desc pt-5">
                    <strong>Método de Pago: </strong>{{ $document->payment_method_type->description }}
                </p>
            @endif

            @if ($document->payment_condition_id === '01')

                @if($payments->count())
                        <p class="desc pt-5">
                            <strong>Pagos:</strong>
                        </p>
                    @foreach($payments as $row)
                        <p>
                            <span class="desc">&#8226; {{ $row->payment_method_type->description }} - {{ $row->reference ? $row->reference.' - ':'' }} {{ $document->currency_type->symbol }} {{ $row->payment + $row->change }}</span>
                        </p>
                    @endforeach
                @endif
            @else
                @foreach($document->fee as $key => $quote)
                    <p class="desc pt-5">
                        <span class="desc">&#8226; {{ (empty($quote->getStringPaymentMethodType()) ? 'Cuota #'.( $key + 1) : $quote->getStringPaymentMethodType()) }} / Fecha: {{ $quote->date->format('d-m-Y') }} / Monto: {{ $quote->currency_type->symbol }}{{ $quote->amount }}</span>
                    </p>
                @endforeach
            @endif

            <p class="desc pt-5"><strong>Vendedor:</strong> {{ $document->seller ? $document->seller->name : $document->user->name}}</p>

        </td>
    </tr>
</table>
<table class="full-width">
    @php
        $configuration = \App\Models\Tenant\Configuration::first();
        $establishment_data = \App\Models\Tenant\Establishment::find($document->establishment_id);
    @endphp
    <tbody>
        <tr>
            @if ($configuration->yape_qr_documents && $establishment_data->yape_logo)
                @php
                    $yape_logo = $establishment_data->yape_logo;
                @endphp
                <td class="text-center">
                    <table>
                        <tr>
                            <td>

                                     Yape

                            </td>
                        </tr>
                        <tr>
                            <td>
                                <img src="data:{{ mime_content_type(public_path("{$yape_logo}")) }};base64, {{ base64_encode(file_get_contents(public_path("{$yape_logo}"))) }}"
                                    alt="{{ $company->name }}" class="company_logo" style="max-width: 150px;">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                @if($establishment_data->yape_owner)

                                         {{ $establishment_data->yape_owner }}

                                @endif
                                @if($establishment_data->yape_number)
                                    <br>

                                         {{ $establishment_data->yape_number }}

                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            @endif
            @if ($configuration->plin_qr_documents && $establishment_data->plin_logo)
                @php
                    $plin_logo = $establishment_data->plin_logo;
                @endphp
                <td class="text-center">
                    <table>
                        <tr>
                            <td>

                                    Plin

                            </td>
                        </tr>
                        <tr>
                            <td>
                                <img src="data:{{ mime_content_type(public_path("{$plin_logo}")) }};base64, {{ base64_encode(file_get_contents(public_path("{$plin_logo}"))) }}"
                                    alt="{{ $company->name }}" class="company_logo" style="max-width: 150px;">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                @if($establishment_data->plin_owner)

                                         {{ $establishment_data->plin_owner }}

                                @endif
                                @if($establishment_data->plin_number)
                                    <br>

                                         {{ $establishment_data->plin_number }}

                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            @endif
        </tr>
    </tbody>
</table>
<table class="full-width">
    @if ($customer->department_id == 16)
        <tr>
            <td class="text-center desc pt-5">
                Representación impresa del Comprobante de Pago Electrónico.
                <br/>Esta puede ser consultada en:
                <br/> <a href="{!! route('search.index', ['external_id' => $document->external_id]) !!}" style="text-decoration: none; font-weight: bold;color:black;">{!! url('/buscar') !!}</a>
                <br/> "Bienes transferidos en la Amazonía
                <br/>para ser consumidos en la misma
            </td>
        </tr>
    @endif
    @if ($document->terms_condition)
        <tr>
            <td class="desc">
                <br>
                <h6 style="font-size: 10px; font-weight: bold;">Términos y condiciones del servicio</h6>
                {!! $document->terms_condition !!}
            </td>
        </tr>
    @endif
    @if ($company->footer_logo)
    @php
        $footer_logo = "storage/uploads/logos/{$company->footer_logo}";
    @endphp
    <tr>
        <td class="text-center pt-5">
            <img  style="max-height: 35px;" src="data:{{mime_content_type(public_path("{$footer_logo}"))}};base64, {{base64_encode(file_get_contents(public_path("{$footer_logo}")))}}" alt="{{$company->name}}">
        </td>
    </tr>
    @endif
    @if($company->footer_text_template)
    <tr>
        <td class="text-center desc pt-2">

            
            {!!func_str_find_url($company->footer_text_template)!!}
        </td>
    </tr>
    @endif
    <tr>
        <td class="text-center desc pt-5">Representación impresa del Comprobante de Pago Electrónico. Esta puede ser consultada en <a href="{!! route('search.index', ['external_id' => $document->external_id]) !!}" style="text-decoration: none; font-weight: bold;color:black;">{!! url('/buscar') !!}</a></td>
    </tr>
</table>

</body>
</html>
