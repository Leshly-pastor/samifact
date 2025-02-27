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
$document_base = ($document->note) ? $document->note : null;

//$path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
$document_number = $document->series.'-'.str_pad($document->number, 8, '0', STR_PAD_LEFT);
$accounts = \App\Models\Tenant\BankAccount::all();

if($document_base) {

$affected_document_number = ($document_base->affected_document) ? $document_base->affected_document->series.'-'.str_pad($document_base->affected_document->number, 8, '0', STR_PAD_LEFT) : $document_base->data_affected_document->series.'-'.str_pad($document_base->data_affected_document->number, 8, '0', STR_PAD_LEFT);

} else {

$affected_document_number = null;
}

$payments = $document->payments;

$document->load('reference_guides');

$total_payment = $document->payments->sum('payment');
$balance = ($document->total - $total_payment) - $document->payments->sum('change');

@endphp
<html>

<head>
</head>

<body>

    @if($document->state_type->id == '11')
    <div class="company_logo_box" style="position: absolute; text-align: center; top:20%;">
        <img src="data:{{mime_content_type(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png"))}};base64, {{base64_encode(file_get_contents(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png")))}}" alt="anulado" class="" style="opacity: 0.6;">
    </div>
    @endif
    @if($document->soap_type_id == '01')
    <div class="company_logo_box" style="position: absolute; text-align: center; top:30%;">
        <img src="data:{{mime_content_type(public_path("status_images".DIRECTORY_SEPARATOR."demo.png"))}};base64, {{base64_encode(file_get_contents(public_path("status_images".DIRECTORY_SEPARATOR."demo.png")))}}" alt="anulado" class="" style="opacity: 0.6;">
    </div>
    @endif
    <table class="full-width">
        <tr>
            @if($company->logo)
            <td width="20%">
                <div class="company_logo_box">
                    <img src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo" style="max-width: 150px;">
                </div>
            </td>
            @else
            <td width="20%"></td>
            @endif
            <td width="50%" class="pl-3">
                <div class="text-left">
                    <h4 class="">{{ $company->name }}</h4>
                    <h5>{{ 'RUC '.$company->number }}</h5>
                    <h6 style="text-transform: uppercase;">
                        {{ ($establishment->address !== '-')? $establishment->address.', ' : '' }}
                        {{ ($establishment->district_id !== '-')? $establishment->district->description : '' }}
                        {{ ($establishment->province_id !== '-')? ', '.$establishment->province->description : '' }}
                        {{ ($establishment->department_id !== '-')? '- '.$establishment->department->description : '' }}
                    </h6>

                    @isset($establishment->trade_address)
                    <h6>{{ ($establishment->trade_address !== '-')? 'D. Comercial: '.$establishment->trade_address : '' }}</h6>
                    @endisset

                    <h6>{{ ($establishment->telephone !== '-')? 'Central telefónica: '.$establishment->telephone : '' }}</h6>

                    <h6>{{ ($establishment->email !== '-')? 'Email: '.$establishment->email : '' }}</h6>

                    @isset($establishment->web_address)
                    <h6>{{ ($establishment->web_address !== '-')? 'Web: '.$establishment->web_address : '' }}</h6>
                    @endisset

                    @isset($establishment->aditional_information)
                    <h6>{{ ($establishment->aditional_information !== '-')? $establishment->aditional_information : '' }}</h6>
                    @endisset
                </div>
            </td>
            <td width="30%" class="border-box py-4 px-2 text-center">
                <h5 class="text-center">{{ $document->document_type->description }}</h5>
                <h3 class="text-center">{{ $document_number }}</h3>
            </td>
        </tr>
    </table>
    <table class="full-width">
        <tr>
            <td width="90%" class="align-top">
                <table class="full-width mt-2">
                    <tr>
                        <td colspan="3">
                            <table class="full-width">
                                <tr>
                                    <td width="120px">Fecha de emisión</td>
                                    <td width="8px">:</td>
                                    <td width="220px">{{$document->date_of_issue->format('Y-m-d')}}</td>
                                    @if($invoice)
                                    <td width="130px">F. de vencimiento</td>
                                    <td width="8px">:</td>
                                    <td>{{$invoice->date_of_due->format('Y-m-d')}}</td>
                                    @else
                                    <td colspan="3"></td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="120px">Cliente</td>
                        <td width="8px">:</td>
                        <td>{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td>{{ $customer->identity_document_type->description }}</td>
                        <td>:</td>
                        <td>{{$customer->number}}</td>
                    </tr>
                    @if ($customer->address !== '')
                    <tr>
                        <td class="align-top">Dirección</td>
                        <td class="align-top">:</td>
                        <td style="text-transform: uppercase;">
                            {{ $customer->address }}
                            {{ ($customer->district_id !== '-')? ', '.$customer->district->description : '' }}
                            {{ ($customer->province_id !== '-')? ', '.$customer->province->description : '' }}
                            {{ ($customer->department_id !== '-')? '- '.$customer->department->description : '' }}
                        </td>
                    </tr>
                    @endif
                    @if ($document->prepayments)
                    @foreach($document->prepayments as $p)
                    <tr>
                        <td>Anticipo</td>
                        <td>:</td>
                        <td>{{$p->number}}</td>
                    </tr>
                    @endforeach
                    @endif
                    @if ($document->purchase_order)
                    <tr>
                        <td>Orden de compra</td>
                        <td>:</td>
                        <td>{{ $document->purchase_order }}</td>
                    </tr>
                    @endif
                    @if ($document->quotation_id)
                    <tr>
                        <td>Cotización</td>
                        <td>:</td>
                        <td>{{ $document->quotation->identifier }}</td>
                    </tr>
                    @isset($document->quotation->delivery_date)
                    <tr>
                        <td>F. ENTREGA</td>
                        <td>:</td>
                        <td>{{ $document->quotation->getStringDeliveryDate()}}</td>
                    </tr>
                    @endisset
                    @endif
                    @isset($document->quotation->sale_opportunity)
                    <tr>
                        <td>O. Venta</td>
                        <td>:</td>
                        <td>{{ $document->quotation->sale_opportunity->number_full}}</td>
                    </tr>
                    @endisset
                    @if(!is_null($document_base))
                    <tr>
                        <td>Doc. Afectado</td>
                        <td>:</td>
                        <td>{{ $affected_document_number }}</td>
                    </tr>
                    <tr>
                        <td>Tipo de nota</td>
                        <td>:</td>
                        <td>{{ ($document_base->note_type === 'credit')?$document_base->note_credit_type->description:$document_base->note_debit_type->description}}</td>
                    </tr>
                    <tr>
                        <td>Descripción</td>
                        <td>:</td>
                        <td>{{ $document_base->note_description }}</td>
                    </tr>
                    @endif
                    @if ($document->detraction)
                    <tr>
                        <td colspan="3">
                            <table class="full-width">
                                <tr>
                                    <td width="120px">N. Cta detracciones</td>
                                    <td width="8px">:</td>
                                    <td width="220px">{{ $document->detraction->bank_account}}</td>
                                    <td>B/S Sujeto a detracción</td>
                                    <td width="8px">:</td>
                                    <td>
                                        @inject('detractionType', 'App\Services\DetractionTypeService')
                                        {{$document->detraction->detraction_type_id}} - {{ $detractionType->getDetractionTypeDescription($document->detraction->detraction_type_id ) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Método de pago</td>
                                    <td>:</td>
                                    <td>{{ $detractionType->getPaymentMethodTypeDescription($document->detraction->payment_method_id ) }}</td>
                                    <td>P. Detracción</td>
                                    <td>:</td>
                                    <td>{{ $document->detraction->percentage}}%</td>
                                </tr>
                                <tr>
                                    <td>Monto detracción</td>
                                    <td>:</td>
                                    <td>S/ {{ $document->detraction->amount}}</td>
                                    @if($document->detraction->pay_constancy)
                                    <td>Constancia de pago</td>
                                    <td>:</td>
                                    <td>{{ $document->detraction->pay_constancy}}</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                    @if ($document->guides)
                    @foreach($document->guides as $guide)
                    <tr>
                        @if(isset($guide->document_type_description))
                        <td>{{ $guide->document_type_description }}</td>
                        @else
                        <td>{{ $guide->document_type_id }}</td>
                        @endif
                        <td>:</td>
                        <td>{{ $guide->number }}</td>
                    </tr>
                    @endforeach
                    @endif
                    @if (count($document->reference_guides) > 0)
                    <tr>
                        <td>GUIAS DE REMISIÓN</td>
                        <td>:</td>
                        <td>
                            @foreach($document->reference_guides as $guide)
                            {{ $guide->series }}-{{ $guide->number }}
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    @if($document->user)
                    <tr>
                        <td>Vendedor</td>
                        <td>:</td>
                        <td>{{ $document->user->name }}</td>
                    </tr>
                    @endif
                </table>
            </td>
            <td class="align-top">
                <img src="data:image/png;base64, {{ $document->qr }}" style="height:100px;" />
            </td>
        </tr>
    </table>
    <table class="full-width">
        <thead class="">
            <tr class="bg-grey">
                <th class="border-top-bottom text-center py-2 desc" width="8%">Cant.</th>
                <th class="border-top-bottom text-center py-2 desc" width="8%">Unidad</th>
                <th class="border-top-bottom text-left py-2 desc">Descripción</th>
                <th class="border-top-bottom text-center py-2 desc" width="10%">PAQUETES</th>
                <th class="border-top-bottom text-left py-2 desc" width="15%">Lote (Cant.)</th>
                <th class="border-top-bottom text-center py-2 desc" width="8%">Serie</th>
                <th class="border-top-bottom text-right py-2 desc" width="12%">P.Unit</th>
                <th class="border-top-bottom text-right py-2 desc" width="8%">Dto.</th>
                <th class="border-top-bottom text-right py-2 desc" width="12%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $row)
            <tr>
                <td class="text-center align-top desc">
                    @if(((int)$row->quantity != $row->quantity))
                    {{ $row->quantity }}
                    @else
                    {{ number_format($row->quantity, 0) }}
                    @endif
                </td>
                <td class="text-center align-top desc">{{ $row->item->unit_type_id }}</td>
                <td class="text-left align-top desc">
                    @if($row->name_product_pdf)
                    {!!$row->name_product_pdf!!}
                    @else
                    {!!$row->item->description!!}
                    @endif

                      

                    @if($row->attributes)
                    @foreach($row->attributes as $attr)
                    <br /><span style="font-size: 9px">{!! $attr->description !!} : {{ $attr->value }}</span>
                    @endforeach
                    @endif
                    {{-- @if($row->discounts)
                    @foreach($row->discounts as $dtos)
                        <br/><span style="font-size: 9px">{{ $dtos->factor * 100 }}% {{$dtos->description }}</span>
                    @endforeach
                    @endif --}}

                    @if($row->item->is_set == 1)
                    <br>
                    @inject('itemSet', 'App\Services\ItemSetService')
                    {{join( "-", $itemSet->getItemsSet($row->item_id) )}}
                    @endif
                </td>

                {{-- Lotes --}}
                @inject('itemLotGroup', 'App\Services\ItemLotsGroupService')

                <td class="text-center align-top desc">
                    {{ $itemLotGroup->getQuantityLotsSelected($row->item->IdLoteSelected) }}
                </td>

                <td class="text-left align-top desc">
                    {{-- {{ $itemLotGroup->getLote($row->item->IdLoteSelected) }} --}}
                    {!! $itemLotGroup->getItemLotGroupWithQuantity($row->item->IdLoteSelected) !!}
                </td>
                {{-- Lotes --}}

                <td class="text-center align-top desc">

                    @isset($row->item->lots)
                    @foreach($row->item->lots as $lot)
                    @if( isset($lot->has_sale) && $lot->has_sale)
                    <span style="font-size: 9px">{{ $lot->series }}</span><br>
                    @endif
                    @endforeach
                    @endisset

                </td>
                <td class="text-right align-top desc">{{ number_format($row->unit_price, 2) }}</td>
                <td class="text-right align-top desc">
                    @if($row->discounts)
                    @php
                    $total_discount_line = 0;
                    foreach ($row->discounts as $disto) {
                    $total_discount_line = $total_discount_line + $disto->amount;
                    }
                    @endphp
                    {{ number_format($total_discount_line, 2) }}
                    @else
                    0
                    @endif
                </td>
                <td class="text-right align-top desc">{{ number_format($row->total, 2) }}</td>
            </tr>
            @endforeach
            @if(count($document->items) < 5) <tr>
                <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                @endif
                <tr>
                    <td colspan="9" class="border-bottom"></td>
                </tr>

                @if ($document->prepayments)
                @foreach($document->prepayments as $p)
                <tr>
                    <td class="text-center align-top desc">
                        1
                    </td>
                    <td class="text-center align-top desc">NIU</td>
                    <td class="text-left align-top desc">
                        Anticipo: {{($p->document_type_id == '02')? 'Factura':'Boleta'}} Nro. {{$p->number}}
                    </td>
                    <td class="text-right align-top desc">-{{ number_format($p->total, 2) }}</td>
                    <td class="text-right align-top desc">
                        0
                    </td>
                    <td class="text-right align-top desc">-{{ number_format($p->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="8" class="border-bottom"></td>
                </tr>
                @endforeach
                @endif

                <tr>
                    <td colspan="5" class="align-top pt-2">
                        @foreach(array_reverse( (array) $document->legends) as $row)
                        @if ($row->code == "1000")
                        <p style="text-transform: uppercase;">Son: <span class="font-bold">{{ $row->value }} {{ $document->currency_type->description }}</span></p>
                        @if (count((array) $document->legends)>1)
                        <p><span class="font-bold">Leyendas</span></p>
                        @endif
                        @else
                        <p> {{$row->code}}: {{ $row->value }} </p>
                        @endif
                        @endforeach
                        @if ($document->detraction)
                        <p>
                            <span class="font-bold">
                                Operación sujeta al Sistema de Pago de Obligaciones Tributarias
                            </span>
                        </p>
                        @endif
                        @if ($customer->department_id == 16)
                        <div>
                            <center>
                                Representación impresa del Comprobante de Pago Electrónico.
                                <br />Esta puede ser consultada en:
                                <br /><a href="{!! route('search.index', ['external_id' => $document->external_id]) !!}" style="text-decoration: none; font-weight: bold;color:black;">{!! url('/buscar') !!}</a>
                                <br /> "Bienes transferidos en la Amazonía
                                <br />para ser consumidos en la misma".
                            </center>
                        </div>
                        @endif
                        @foreach($document->additional_information as $information)
                        @if ($information)
                        @if ($loop->first)
                        <strong>Información adicional</strong>
                        @endif
                        <p>{{ $information }}</p>
                        @endif
                        @endforeach
                        @if(in_array($document->document_type->id,['01','03']))
                        @foreach($accounts as $account)
                        <p>
                            <span class="font-bold">{{$account->bank->description}}</span> {{$account->currency_type->description}}
                            <span class="font-bold">N°:</span> {{$account->number}}
                            @if($account->cci)
                            <span class="font-bold">CCI:</span> {{$account->cci}}
                            @endif
                        </p>
                        @endforeach
                        @endif
                        @if($payments->count())
                        <table class="full-width">
                            <tr>
                                <td>
                                    <strong>Pagos:</strong>
                                </td>
                            </tr>
                            @php
                            $payment = 0;
                            @endphp
                            @foreach($payments as $row)
                            <tr>
                                <td>&#8226; {{ $row->payment_method_type->description }} - {{ $row->reference ? $row->reference.' - ':'' }} {{ $document->currency_type->symbol }} {{ $row->payment + $row->change }}</td>
                            </tr>
                            @php
                            $payment += (float) $row->payment;
                            @endphp
                            @endforeach
                </tr>
                <tr>
                    <td>
                        <strong>Saldo:</strong> {{ $document->currency_type->symbol }} {{ number_format($document->total - $payment, 2) }}
                    </td>
                </tr>
    </table>
    @endif
    </td>
    <td colspan="4" width="30%" class="align-top">
        <table class="full-width">
            @if($document->total_exportation > 0)
            <tr>
                <td class="text-right font-bold">Op. Exportación: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exportation, 2) }}</td>
            </tr>
            @endif
            @if($document->total_free > 0)
            <tr>
                <td class="text-right font-bold">Op. Gratuitas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_free, 2) }}</td>
            </tr>
            @endif
            @if($document->total_unaffected > 0)
            <tr>
                <td class="text-right font-bold">Op. Inafectas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_unaffected, 2) }}</td>
            </tr>
            @endif
            @if($document->total_exonerated > 0)
            <tr>
                <td class="text-right font-bold">Op. Exoneradas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exonerated, 2) }}</td>
            </tr>
            @endif
            @if($document->total_taxed > 0)
            <tr>
                <td class="text-right font-bold">Op. Gravadas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_taxed, 2) }}</td>
            </tr>
            @endif
            @if($document->total_discount > 0)
            <tr>
                <td class="text-right font-bold">{{(($document->total_prepayment > 0) ? 'Anticipo':'Descuento TOTAL')}}: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_discount, 2) }}</td>
            </tr>
            @endif
            @if($document->total_plastic_bag_taxes > 0)
            <tr>
                <td class="text-right font-bold">Icbper: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_plastic_bag_taxes, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-right font-bold">IGV: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_igv, 2) }}</td>
            </tr>

            @if($document->perception)
            <tr>
                <td class="text-right font-bold"> Importe total: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">Percepción: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->perception->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">Total a pagar: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format(($document->total + $document->perception->amount), 2) }}</td>
            </tr>
            @else
            <tr>
                <td class="text-right font-bold">Total a pagar: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
            </tr>
            @endif

            @if($balance < 0) <tr>
                <td class="text-right font-bold">Vuelto: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format(abs($balance),2, ".", "") }}</td>
                </tr>

                @endif
                @if($document->hash)
                <tr>
                    <td class="pt-10 desc text-right" colspan="2">Código Hash: {{ $document->hash }}</td>
                </tr>
                @endif
        </table>
    </td>
    </tr>
    </tbody>
    </table>

    <br>

    <!-- COPIA -->

    @if($document->state_type->id == '11')
    <div class="company_logo_box" style="position: absolute; text-align: center; top:62%;">
        <img src="data:{{mime_content_type(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png"))}};base64, {{base64_encode(file_get_contents(public_path("status_images".DIRECTORY_SEPARATOR."anulado.png")))}}" alt="anulado" class="" style="opacity: 0.6;">
    </div>
    @endif
    <table class="full-width">
        <tr>
            @if($company->logo)
            <td width="20%">
                <div class="company_logo_box">
                    <img src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo" style="max-width: 150px;">
                </div>
            </td>
            @else
            <td width="20%"></td>
            @endif
            <td width="50%" class="pl-3">
                <div class="text-left">
                    <h4 class="">{{ $company->name }}</h4>
                    <h5>{{ 'RUC '.$company->number }}</h5>
                    <h6 style="text-transform: uppercase;">
                        {{ ($establishment->address !== '-')? $establishment->address.', ' : '' }}
                        {{ ($establishment->district_id !== '-')? $establishment->district->description : '' }}
                        {{ ($establishment->province_id !== '-')? ', '.$establishment->province->description : '' }}
                        {{ ($establishment->department_id !== '-')? '- '.$establishment->department->description : '' }}
                    </h6>

                    @isset($establishment->trade_address)
                    <h6>{{ ($establishment->trade_address !== '-')? 'D. Comercial: '.$establishment->trade_address : '' }}</h6>
                    @endisset

                    <h6>{{ ($establishment->telephone !== '-')? 'Central telefónica: '.$establishment->telephone : '' }}</h6>

                    <h6>{{ ($establishment->email !== '-')? 'Email: '.$establishment->email : '' }}</h6>

                    @isset($establishment->web_address)
                    <h6>{{ ($establishment->web_address !== '-')? 'Web: '.$establishment->web_address : '' }}</h6>
                    @endisset

                    @isset($establishment->aditional_information)
                    <h6>{{ ($establishment->aditional_information !== '-')? $establishment->aditional_information : '' }}</h6>
                    @endisset
                </div>
            </td>
            <td width="30%" class="border-box py-4 px-2 text-center">
                <h5 class="text-center">{{ $document->document_type->description }}</h5>
                <h3 class="text-center">{{ $document_number }}</h3>
            </td>
        </tr>
    </table>
    <table class="full-width">
        <tr>
            <td width="90%" class="align-top">
                <table class="full-width mt-2">
                    <tr>
                        <td colspan="3">
                            <table class="full-width">
                                <tr>
                                    <td width="120px">Fecha de emisión</td>
                                    <td width="8px">:</td>
                                    <td width="220px">{{$document->date_of_issue->format('Y-m-d')}}</td>
                                    @if($invoice)
                                    <td width="130px">F. de vencimiento</td>
                                    <td width="8px">:</td>
                                    <td>{{$invoice->date_of_due->format('Y-m-d')}}</td>
                                    @else
                                    <td colspan="3"></td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="120px">Cliente</td>
                        <td width="8px">:</td>
                        <td>{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td>{{ $customer->identity_document_type->description }}</td>
                        <td>:</td>
                        <td>{{$customer->number}}</td>
                    </tr>
                    @if ($customer->address !== '')
                    <tr>
                        <td class="align-top">Dirección</td>
                        <td class="align-top">:</td>
                        <td style="text-transform: uppercase;">
                            {{ $customer->address }}
                            {{ ($customer->district_id !== '-')? ', '.$customer->district->description : '' }}
                            {{ ($customer->province_id !== '-')? ', '.$customer->province->description : '' }}
                            {{ ($customer->department_id !== '-')? '- '.$customer->department->description : '' }}
                        </td>
                    </tr>
                    @endif
                    @if ($document->prepayments)
                    @foreach($document->prepayments as $p)
                    <tr>
                        <td>Anticipo</td>
                        <td>:</td>
                        <td>{{$p->number}}</td>
                    </tr>
                    @endforeach
                    @endif
                    @if ($document->purchase_order)
                    <tr>
                        <td>Orden de compra</td>
                        <td>:</td>
                        <td>{{ $document->purchase_order }}</td>
                    </tr>
                    @endif
                    @if ($document->quotation_id)
                    <tr>
                        <td>Cotización</td>
                        <td>:</td>
                        <td>{{ $document->quotation->identifier }}</td>
                    </tr>
                    @isset($document->quotation->delivery_date)
                    <tr>
                        <td>F. ENTREGA</td>
                        <td>:</td>
                        <td>{{ $document->quotation->getStringDeliveryDate()}}</td>
                    </tr>
                    @endisset
                    @endif
                    @isset($document->quotation->sale_opportunity)
                    <tr>
                        <td>O. Venta</td>
                        <td>:</td>
                        <td>{{ $document->quotation->sale_opportunity->number_full}}</td>
                    </tr>
                    @endisset
                    @if(!is_null($document_base))
                    <tr>
                        <td>Doc. Afectado</td>
                        <td>:</td>
                        <td>{{ $affected_document_number }}</td>
                    </tr>
                    <tr>
                        <td>Tipo de nota</td>
                        <td>:</td>
                        <td>{{ ($document_base->note_type === 'credit')?$document_base->note_credit_type->description:$document_base->note_debit_type->description}}</td>
                    </tr>
                    <tr>
                        <td>Descripción</td>
                        <td>:</td>
                        <td>{{ $document_base->note_description }}</td>
                    </tr>
                    @endif
                    @if ($document->detraction)
                    <tr>
                        <td colspan="3">
                            <table class="full-width">
                                <tr>
                                    <td width="120px">N. Cta detracciones</td>
                                    <td width="8px">:</td>
                                    <td width="220px">{{ $document->detraction->bank_account}}</td>
                                    <td>B/S Sujeto a detracción</td>
                                    <td width="8px">:</td>
                                    <td>
                                        @inject('detractionType', 'App\Services\DetractionTypeService')
                                        {{$document->detraction->detraction_type_id}} - {{ $detractionType->getDetractionTypeDescription($document->detraction->detraction_type_id ) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Método de pago</td>
                                    <td>:</td>
                                    <td>{{ $detractionType->getPaymentMethodTypeDescription($document->detraction->payment_method_id ) }}</td>
                                    <td>P. Detracción</td>
                                    <td>:</td>
                                    <td>{{ $document->detraction->percentage}}%</td>
                                </tr>
                                <tr>
                                    <td>Monto detracción</td>
                                    <td>:</td>
                                    <td>S/ {{ $document->detraction->amount}}</td>
                                    @if($document->detraction->pay_constancy)
                                    <td>Constancia de pago</td>
                                    <td>:</td>
                                    <td>{{ $document->detraction->pay_constancy}}</td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                    @if ($document->guides)
                    @foreach($document->guides as $guide)
                    <tr>
                        @if(isset($guide->document_type_description))
                        <td>{{ $guide->document_type_description }}</td>
                        @else
                        <td>{{ $guide->document_type_id }}</td>
                        @endif
                        <td>:</td>
                        <td>{{ $guide->number }}</td>
                    </tr>
                    @endforeach
                    @endif
                    @if (count($document->reference_guides) > 0)
                    <tr>
                        <td>GUIAS DE REMISIÓN</td>
                        <td>:</td>
                        <td>
                            @foreach($document->reference_guides as $guide)
                            {{ $guide->series }}-{{ $guide->number }}
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    @if($document->user)
                    <tr>
                        <td>Vendedor</td>
                        <td>:</td>
                        <td>{{ $document->user->name }}</td>
                    </tr>
                    @endif
                </table>
            </td>
            <td class="align-top">
                <img src="data:image/png;base64, {{ $document->qr }}" style="height:100px;" />
            </td>
        </tr>
    </table>
    <table class="full-width">
        <thead class="">
            <tr class="bg-grey">
                <th class="border-top-bottom text-center py-2 desc" width="8%">Cant.</th>
                <th class="border-top-bottom text-center py-2 desc" width="8%">Unidad</th>
                <th class="border-top-bottom text-left py-2 desc">Descripción</th>
                <th class="border-top-bottom text-center py-2 desc" width="10%">PAQUETES</th>
                <th class="border-top-bottom text-left py-2 desc" width="15%">Lote (Cant.)</th>
                {{-- <th class="border-top-bottom text-center py-2 desc" width="8%">Lote</th> --}}
                <th class="border-top-bottom text-center py-2 desc" width="8%">Serie</th>
                <th class="border-top-bottom text-right py-2 desc" width="12%">P.Unit</th>
                <th class="border-top-bottom text-right py-2 desc" width="8%">Dto.</th>
                <th class="border-top-bottom text-right py-2 desc" width="12%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $row)
            <tr>
                <td class="text-center align-top desc">
                    @if(((int)$row->quantity != $row->quantity))
                    {{ $row->quantity }}
                    @else
                    {{ number_format($row->quantity, 0) }}
                    @endif
                </td>
                <td class="text-center align-top desc">{{ $row->item->unit_type_id }}</td>
                <td class="text-left align-top desc">
                    @if($row->name_product_pdf)
                    {!!$row->name_product_pdf!!}
                    @else
                    {!!$row->item->description!!}
                    @endif

                      

                    @if($row->attributes)
                    @foreach($row->attributes as $attr)
                    <br /><span style="font-size: 9px">{!! $attr->description !!} : {{ $attr->value }}</span>
                    @endforeach
                    @endif
                    {{-- @if($row->discounts)
                    @foreach($row->discounts as $dtos)
                        <br/><span style="font-size: 9px">{{ $dtos->factor * 100 }}% {{$dtos->description }}</span>
                    @endforeach
                    @endif --}}

                    @if($row->item->is_set == 1)
                    <br>
                    @inject('itemSet', 'App\Services\ItemSetService')
                    {{join( "-", $itemSet->getItemsSet($row->item_id) )}}
                    @endif
                </td>

                {{-- Lotes --}}
                @inject('itemLotGroup', 'App\Services\ItemLotsGroupService')

                <td class="text-center align-top desc">
                    {{ $itemLotGroup->getQuantityLotsSelected($row->item->IdLoteSelected) }}
                </td>

                <td class="text-left align-top desc">
                    {{-- {{ $itemLotGroup->getLote($row->item->IdLoteSelected) }} --}}
                    {!! $itemLotGroup->getItemLotGroupWithQuantity($row->item->IdLoteSelected) !!}
                </td>
                {{-- Lotes --}}

                <td class="text-center align-top desc">

                    @isset($row->item->lots)
                    @foreach($row->item->lots as $lot)
                    @if( isset($lot->has_sale) && $lot->has_sale)
                    <span style="font-size: 9px">{{ $lot->series }}</span><br>
                    @endif
                    @endforeach
                    @endisset

                </td>
                <td class="text-right align-top desc">{{ number_format($row->unit_price, 2) }}</td>
                <td class="text-right align-top desc">
                    @if($row->discounts)
                    @php
                    $total_discount_line = 0;
                    foreach ($row->discounts as $disto) {
                    $total_discount_line = $total_discount_line + $disto->amount;
                    }
                    @endphp
                    {{ number_format($total_discount_line, 2) }}
                    @else
                    0
                    @endif
                </td>
                <td class="text-right align-top desc">{{ number_format($row->total, 2) }}</td>
            </tr>
            @endforeach
            @if(count($document->items) < 5) <tr>
                <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="8">&nbsp;</td>
                </tr>
                @endif
                <tr>
                    <td colspan="9" class="border-bottom"></td>
                </tr>

                @if ($document->prepayments)
                @foreach($document->prepayments as $p)
                <tr>
                    <td class="text-center align-top desc">
                        1
                    </td>
                    <td class="text-center align-top desc">NIU</td>
                    <td class="text-left align-top desc">
                        Anticipo: {{($p->document_type_id == '02')? 'Factura':'Boleta'}} Nro. {{$p->number}}
                    </td>
                    <td class="text-right align-top desc">-{{ number_format($p->total, 2) }}</td>
                    <td class="text-right align-top desc">
                        0
                    </td>
                    <td class="text-right align-top desc">-{{ number_format($p->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="8" class="border-bottom"></td>
                </tr>
                @endforeach
                @endif

                <tr>
                    <td colspan="5" class="align-top pt-2">
                        @foreach(array_reverse( (array) $document->legends) as $row)
                        @if ($row->code == "1000")
                        <p style="text-transform: uppercase;">Son: <span class="font-bold">{{ $row->value }} {{ $document->currency_type->description }}</span></p>
                        @if (count((array) $document->legends)>1)
                        <p><span class="font-bold">Leyendas</span></p>
                        @endif
                        @else
                        <p> {{$row->code}}: {{ $row->value }} </p>
                        @endif
                        @endforeach
                        @if ($document->detraction)
                        <p>
                            <span class="font-bold">
                                Operación sujeta al Sistema de Pago de Obligaciones Tributarias
                            </span>
                        </p>
                        @endif
                        @if ($customer->department_id == 16)
                        <div>
                            <center>
                                Representación impresa del Comprobante de Pago Electrónico.
                                <br />Esta puede ser consultada en:
                                <br /><a href="{!! route('search.index', ['external_id' => $document->external_id]) !!}" style="text-decoration: none; font-weight: bold;color:black;">{!! url('/buscar') !!}</a>
                                <br /> "Bienes transferidos en la Amazonía
                                <br />para ser consumidos en la misma".
                            </center>
                        </div>
                        @endif
                        @foreach($document->additional_information as $information)
                        @if ($information)
                        @if ($loop->first)
                        <strong>Información adicional</strong>
                        @endif
                        <p>{{ $information }}</p>
                        @endif
                        @endforeach
                        @if(in_array($document->document_type->id,['01','03']))
                        @foreach($accounts as $account)
                        <p>
                            <span class="font-bold">{{$account->bank->description}}</span> {{$account->currency_type->description}}
                            <span class="font-bold">N°:</span> {{$account->number}}
                            @if($account->cci)
                            <span class="font-bold">CCI:</span> {{$account->cci}}
                            @endif
                        </p>
                        @endforeach
                        @endif
                        @if($payments->count())
                        <table class="full-width">
                            <tr>
                                <td>
                                    <strong>Pagos:</strong>
                                </td>
                            </tr>
                            @php
                            $payment = 0;
                            @endphp
                            @foreach($payments as $row)
                            <tr>
                                <td>&#8226; {{ $row->payment_method_type->description }} - {{ $row->reference ? $row->reference.' - ':'' }} {{ $document->currency_type->symbol }} {{ $row->payment + $row->change }}</td>
                            </tr>
                            @php
                            $payment += (float) $row->payment;
                            @endphp
                            @endforeach
                </tr>
                <tr>
                    <td>
                        <strong>Saldo:</strong> {{ $document->currency_type->symbol }} {{ number_format($document->total - $payment, 2) }}
                    </td>
                </tr>

    </table>
    @endif
    </td>
    <td colspan="4" width="30%" class="align-top">
        <table class="full-width">
            @if($document->total_exportation > 0)
            <tr>
                <td class="text-right font-bold">Op. Exportación: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exportation, 2) }}</td>
            </tr>
            @endif
            @if($document->total_free > 0)
            <tr>
                <td class="text-right font-bold">Op. Gratuitas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_free, 2) }}</td>
            </tr>
            @endif
            @if($document->total_unaffected > 0)
            <tr>
                <td class="text-right font-bold">Op. Inafectas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_unaffected, 2) }}</td>
            </tr>
            @endif
            @if($document->total_exonerated > 0)
            <tr>
                <td class="text-right font-bold">Op. Exoneradas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exonerated, 2) }}</td>
            </tr>
            @endif
            @if($document->total_taxed > 0)
            <tr>
                <td class="text-right font-bold">Op. Gravadas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_taxed, 2) }}</td>
            </tr>
            @endif
            @if($document->total_discount > 0)
            <tr>
                <td class="text-right font-bold">{{(($document->total_prepayment > 0) ? 'Anticipo':'Descuento TOTAL')}}: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_discount, 2) }}</td>
            </tr>
            @endif
            @if($document->total_plastic_bag_taxes > 0)
            <tr>
                <td class="text-right font-bold">Icbper: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_plastic_bag_taxes, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-right font-bold">IGV: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_igv, 2) }}</td>
            </tr>

            @if($document->perception)
            <tr>
                <td class="text-right font-bold"> Importe total: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">Percepción: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->perception->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">Total a pagar: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format(($document->total + $document->perception->amount), 2) }}</td>
            </tr>
            @else
            <tr>
                <td class="text-right font-bold">Total a pagar: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
            </tr>
            @endif

            @if($balance < 0) <tr>
                <td class="text-right font-bold">Vuelto: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format(abs($balance),2, ".", "") }}</td>
                </tr>

                @endif
                @if($document->hash)
                <tr>
                    <td class="pt-10 desc text-right" colspan="2">Código Hash: {{ $document->hash }}</td>
                </tr>
                @endif
        </table>
    </td>
    </tr>
    </tbody>
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
                                    <strong>
                                        Qr yape
                                    </strong>
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
                                        <strong>
                                            Nombre: {{ $establishment_data->yape_owner }}
                                        </strong>
                                    @endif
                                    @if($establishment_data->yape_number)
                                        <br>
                                        <strong>
                                            Número: {{ $establishment_data->yape_number }}
                                        </strong>
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
                                    <strong>
                                        Qr plin
                                    </strong>
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
                                        <strong>
                                            Nombre: {{ $establishment_data->plin_owner }}
                                        </strong>
                                    @endif
                                    @if($establishment_data->plin_number)
                                        <br>
                                        <strong>
                                            Número: {{ $establishment_data->plin_number }}
                                        </strong>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                @endif
            </tr>
        </tbody>
    </table>

    @if ($document->terms_condition)
    <br>
    <table class="full-width">
        <tr>
            <td>
                <h6 style="font-size: 12px; font-weight: bold;">Términos y condiciones del servicio</h6>
                {!! $document->terms_condition !!}
            </td>
        </tr>
    </table>
    @endif
</body>

</html>