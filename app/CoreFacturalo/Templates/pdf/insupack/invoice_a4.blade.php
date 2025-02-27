@php
$establishment = $document->establishment;
    $customer = $document->customer;
    $invoice = $document->invoice;
    $document_base = $document->note ? $document->note : null;
    
    //$path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
    $document_number = $document->series. '-' . str_pad($document->number, 8, '0', STR_PAD_LEFT);
    $accounts = \App\Models\Tenant\BankAccount::where('show_in_documents', true)->get();
    
    if ($document_base) {
        $affected_document_number = $document_base->affected_document ? $document_base->affected_document->series. '-' . str_pad($document_base->affected_document->number, 8, '0', STR_PAD_LEFT) : $document_base->data_affected_document->series. '-' . str_pad($document_base->data_affected_document->number, 8, '0', STR_PAD_LEFT);
    } else {
        $affected_document_number = null;
    }
    
    $payments = $document->payments;
    
    $document->load('reference_guides');
    
    $total_payment = $document->payments->sum('payment');
    $balance = $document->total - $total_payment - $document->payments->sum('change');
    
    //calculate items
    $allowed_items = 70;
    $quantity_items = $document->items()->count();
    $cycle_items = $allowed_items - $quantity_items * 3;
    $total_weight = 0;
    
    // $marca_agua = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'custom_multisaba'.DIRECTORY_SEPARATOR.'marca_agua.png');
    
    // $titulo = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'custom_multisaba'.DIRECTORY_SEPARATOR.'logo_titulo.png');
    
@endphp
<html>

<head>
    {{-- <title>{{ $document_number }}</title> --}}
    {{-- <link href="{{ $path_style }}" rel="stylesheet" /> --}}
</head>

<body>

    @if ($document->state_type->id == '11')
        <div class="company_logo_box" style="position: absolute; text-align: center; top:30%;">
            <img src="data:{{ mime_content_type(public_path('status_images' . DIRECTORY_SEPARATOR . 'anulado.png')) }};base64, {{ base64_encode(file_get_contents(public_path('status_images' . DIRECTORY_SEPARATOR . 'anulado.png'))) }}"
                alt="anulado" class="" style="opacity: 0.6;">
        </div>
    @else
        <div class="item_watermark" style="position: absolute; text-align: center; top:30%;">
            <img style="width: 100%"
                src="data:{{ mime_content_type(public_path("storage/uploads/logos/{$company->logo}")) }};base64, {{ base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}"))) }}"
                alt="anulado" class="" style="opacity: 0.1;width: 95%">
        </div>
    @endif
    <table class="full-width">

        <tr>
            @if ($company->logo)
                <td width="20%">
                    <div class="company_logo_box">
                        <img src="data:{{ mime_content_type(public_path("storage/uploads/logos/{$company->logo}")) }};base64, {{ base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}"))) }}"
                            alt="{{ $company->name }}" class="company_logo" style="max-width: 250px;">
                    </div>
                </td>
            @else
                <td width="20%">
                    {{-- <img src="{{ asset('logo/logo.jpg') }}" class="company_logo" style="max-width: 150px"> --}}
                </td>
            @endif
            <td width="40%" class="pl-3">
                <div class="text-left">
                    <h4 class="">{{ $company->name }}</h4>
                    <h5>{{ 'RUC ' . $company->number }}</h5>
                    @isset($establishment->aditional_information)
                        <h6>{{ $establishment->aditional_information !== '-' ? $establishment->aditional_information : '' }}
                        </h6>
                    @endisset
                    <h6 style="text-transform: uppercase;">
                        {{ $establishment->address !== '-' ? $establishment->address : '' }}
                        {{ $establishment->district_id !== '-' ? ', ' . $establishment->district->description : '' }}
                        {{ $establishment->province_id !== '-' ? ', ' . $establishment->province->description : '' }}
                        {{ $establishment->department_id !== '-' ? '- ' . $establishment->department->description : '' }}
                    </h6>

                    @isset($establishment->trade_address)
                        <h6>{{ $establishment->trade_address !== '-' ? 'D. Comercial: ' . $establishment->trade_address : '' }}
                        </h6>
                    @endisset

                    <h6>{{ $establishment->telephone !== '-' ? 'Central telefónica: ' . $establishment->telephone : '' }}
                    </h6>

                    <h6>{{ $establishment->email !== '-' ? 'Email: ' . $establishment->email : '' }}</h6>

                    @isset($establishment->web_address)
                        <h6>{{ $establishment->web_address !== '-' ? 'Web: ' . $establishment->web_address : '' }}</h6>
                    @endisset


                </div>
            </td>
            <td width="40%" class="border-box py-2 px-2 text-center">
                <h3 class="font-bold">{{ 'R.U.C. ' . $company->number }}</h3>
                <h3 class="text-center font-bold">{{ $document->document_type->description }}</h3>
                <br>
                <h3 class="text-center font-bold">{{ $document_number }}</h3>
            </td>
        </tr>
    </table>
    <table class="full-width mt-3">
        <tr>
            <td width="47%" class="border-box pl-3">
                <table class="full-width">
                    <tr>
                        <td class="font-sm" width="80px">
                            <strong>Razón Social</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            {{ $customer->name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-sm" width="80px">
                            <strong>{{ $customer->identity_document_type->description }}</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            {{ $customer->number }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-sm" width="80px">
                            <strong>Dirección</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            @if ($customer->address !== '')
                                <span style="text-transform: uppercase;">
                                    {{ $customer->address }}
                                    {{ $customer->district_id !== '-' ? ', ' . $customer->district->description : '' }}
                                    {{ $customer->province_id !== '-' ? ', ' . $customer->province->description : '' }}
                                    {{ $customer->department_id !== '-' ? '- ' . $customer->department->description : '' }}
                                </span>
                            @endif
                        </td>
                    </tr>

                    @if (!is_null($document_base))
                        <tr>
                            <td class="font-sm font-bold" width="80px">Doc. Afectado</td>
                            <td class="font-sm" width="8px">:</td>
                            <td class="font-sm">{{ $affected_document_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-sm font-bold" width="80px">Tipo de nota</td>
                            <td class="font-sm">:</td>
                            <td class="font-sm">
                                {{ $document_base->note_type === 'credit' ? $document_base->note_credit_type->description : $document_base->note_debit_type->description }}
                            </td>
                        </tr>
                        <tr>
                            <td class="font-sm font-bold" width="80px">Descripción</td>
                            <td class="font-sm">:</td>
                            <td class="font-sm">{{ $document_base->note_description }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td width="3%"></td>
            <td width="50%" class="border-box pl-1 ">
                <table class="full-width">


                    <tr>
                        <td class="font-sm" width="90px">
                            <strong>Fecha Emisión</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            {{ $document->date_of_issue->format('Y-m-d') }}
                        </td>
                        <td class="font-sm" width="70px">
                            <strong>H. Emisión</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            {{ $document->time_of_issue }}
                        </td>
                    </tr>

                    <tr>
                        @if ($invoice)
                            <td class="font-sm" width="90px">
                                <strong>Fecha de Vcto</strong>
                            </td>
                            <td class="font-sm" width="8px">:</td>
                            <td class="font-sm">
                                {{ $invoice->date_of_due->format('Y-m-d') }}
                            </td>
                        @endif

                        <td class="font-sm" width="70px">
                            <strong>Moneda</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">
                            {{ $document->currency_type->description }}
                        </td>
                    </tr>

                    <tr>
                        @if ($document->quotation_id)
                            <td class="font-sm" width="90px">
                                <strong>Cotización</strong>
                            </td>
                            <td class="font-sm" width="8px">:</td>
                            <td class="font-sm">
                                {{ $document->quotation->identifier }}
                            </td>
                        @endif
                        @php
                            $paymentCondition = \App\CoreFacturalo\Helpers\Template\TemplateHelper::getDocumentPaymentCondition($document);
                        @endphp
                        <td class="font-sm" width="70px">
                            <strong> Pago</strong>
                        </td>
                        <td class="font-sm" width="8px">:</td>
                        <td class="font-sm">{{ $paymentCondition }}</td>


                        {{--   @if ($document->payments()->count() > 0)
                    <td class="font-sm" width="70px">
                        <strong>F. Pago</strong>
                    </td>
                    <td class="font-sm" width="8px">:</td>
                    <td class="font-sm">
                        {{ $document->payments()->first()->payment_method_type->description }} - {{ $document->currency_type_id }} {{ $document->payments()->first()->payment }}
                    </td>
                    @endif
 --}}



                    </tr>

                    <tr>
                        @if ($document->guides)
                            <td class="font-sm" width="100px">
                                <strong>Guía de Remisión</strong>
                            </td>
                            <td class="font-sm" width="8px">:</td>
                            <td class="font-sm" colspan="4">
                                @foreach ($document->guides as $item)
                                    {{ $item->document_type_description }}: {{ $item->number }}<br>
                                @endforeach
                            </td>
                        @endif
                    </tr>


                </table>
            </td>
            {{-- <td width="5%" class="p-0 m-0">
            <img src="data:image/png;base64, {{ $document->qr }}" class="p-0 m-0" style="width: 120px;" />
        </td> --}}
        </tr>
    </table>
    <table class="full-width my-2 text-center" border="0">
        <tr>
            <td class="desc"></td>
        </tr>
    </table>


    <table class="full-width mt-0 mb-0">
        <thead>
            <tr class="">
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="12%">Código</th>
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="8%">Cant.</th>
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="8%">U.M.</th>
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="40%">Descripción
                </th>
                <th class="border-top-bottom text-right py-1 desc" class="cell-solid" width="12%">P.Unit</th>
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="8%">SIN IGV</th>
                <th class="border-top-bottom text-center py-1 desc" class="cell-solid" width="12%">Total</th>
            </tr>
        </thead>
        <tbody class="">
            @foreach ($document->items as $row)
                <tr>
                    <td class="p-1 text-center align-top desc cell-solid-rl">{{ $row->item->internal_id }}</td>
                    <td class="p-1 text-center align-top desc cell-solid-rl">
                        @if ((int) $row->quantity != $row->quantity)
                            {{ $row->quantity }}
                        @else
                            {{ number_format($row->quantity, 0) }}
                        @endif
                    </td>
                    <td class="p-1 text-center align-top desc cell-solid-rl">{{ symbol_or_code($row->item->unit_type_id) }}</td>
                    <td class="p-1 text-left align-top desc text-upp cell-solid-rl">
                        @if ($row->name_product_pdf)
                            {!! $row->name_product_pdf !!}
                        @else
                            {!! $row->item->description !!}
                        @endif

   

                        @if ($row->attributes)
                            @foreach ($row->attributes as $attr)
                                @if ($attr->attribute_type_id === '5032')
                                    @php
                                        $total_weight += $attr->value * $row->quantity;
                                    @endphp
                                @endif
                                <br /><span style="font-size: 9px">{!! $attr->description !!} :
                                    {{ $attr->value }}</span>
                            @endforeach
                        @endif
                        @if ($row->discounts)
                        @foreach ($row->discounts as $dtos)
                            <br/><span style="font-size: 9px">{{ $dtos->factor * 100 }}% {{$dtos->description ?? 'dscto' }}</span>
                        @endforeach
                    @endif

                        @if ($row->item->is_set == 1)
                            <br>
                            @inject('itemSet', 'App\Services\ItemSetService')
                            {{ join('-', $itemSet->getItemsSet($row->item_id)) }}
                        @endif
                    </td>
                    <td class="p-1 text-right align-top desc cell-solid-rl">{{ number_format($row->unit_price, 4) }}
                    </td>

                    @php
                        $total_discount_line = 0;
                        if ($row->discounts) {
                            foreach ($row->discounts as $disto) {
                                $total_discount_line = $total_discount_line + $disto->amount;
                            }
                        }
                    @endphp

                    <td class="p-1 text-right align-top desc cell-solid-rl">
                        {{ number_format($row->unit_value - $total_discount_line / $row->quantity, 4) }}</td>
                    <td class="p-1 text-right align-top desc cell-solid-rl">{{ number_format($row->total, 2) }}</td>
                </tr>
            @endforeach

            @for ($i = 0; $i < $cycle_items; $i++)
                <tr>
                    <td class="p-1 text-center align-top desc cell-solid-rl"></td>
                    <td class="p-1 text-center align-top desc cell-solid-rl">
                    </td>
                    <td class="p-1 text-center align-top desc cell-solid-rl"></td>
                    <td class="p-1 text-left align-top desc text-upp cell-solid-rl">
                    </td>
                    <td class="p-1 text-right align-top desc cell-solid-rl"></td>
                    <td class="p-1 text-right align-top desc cell-solid-rl">
                    </td>
                    <td class="p-1 text-right align-top desc cell-solid-rl"></td>
                </tr>
            @endfor

            <tr>
                <td class="p-1 text-left align-top desc cell-solid" colspan="3"><strong> VENDEDOR:</strong>
                    @if ($document->seller)
                        {{ $document->seller->name }}
                    @else
                        {{ $document->user->name }}
                    @endif
                </td>
                <td class="p-1 text-left align-top desc cell-solid font-bold">
                    SON:
                    @foreach (array_reverse((array) $document->legends) as $row)
                        @if ($row->code == '1000')
                            {{ $row->value }} {{ $document->currency_type->description }}
                        @else
                            {{ $row->code }}: {{ $row->value }}
                        @endif
                    @endforeach
                </td>
                <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                    OP. GRAVADA {{ $document->currency_type->symbol }}
                </td>
                <td class="p-1 text-right align-top desc cell-solid font-bold">
                    {{ number_format($document->total_taxed, 2) }}</td>
            </tr>

            <tr>
                <td class="p-1 text-left align-top desc cell-solid" colspan="3" rowspan="6">
                    @php
                        $total_packages = $document->items()->sum('quantity');
                        
                    @endphp

                    <strong> Total bultos:</strong>
                    @if ((int) $total_packages != $total_packages)
                        {{ $total_packages }}
                    @else
                        {{ number_format($total_packages, 0) }}
                    @endif
                    <br>

                    <strong> Total Peso:</strong>
                    {{ $total_weight }} KG
                    <br>

                    <strong> Observación:</strong>
                    @foreach ($document->additional_information as $information)
                        @if ($information)
                            {{ $information }} <br>
                        @endif
                    @endforeach


                    {{-- @if ($document->payments()->count() > 0)
        <table class="full-width mt-1">
            <tr>
                <td class="desc"><strong>F. Pago: </strong> {{ $document->payments()->first()->payment_method_type->description }} - {{ $document->currency_type_id }} {{ $document->payments()->first()->payment }}</td>
            </tr>
        </table>
@endif --}}

                    @if ($document->payment_condition_id === '01')
                        @if ($payments->count())
                            <table class="full-width mt-1">
                                <tr>
                                    <td class="desc"><strong>Pagos:</strong></td>
                                </tr>
                                @php $payment = 0; @endphp
                                @foreach ($payments as $row)
                                    <tr>
                                        <td class="desc">&#8226; {{ $row->payment_method_type->description }} -
                                            {{ $row->reference ? $row->reference . ' - ' : '' }}
                                            {{ $document->currency_type->symbol }} {{ $row->payment + $row->change }}
                                        </td>
                                    </tr>
                                @endforeach
            </tr>
    </table>
    @endif
@else
    <table class="full-width mt-1">
        @foreach ($document->fee as $key => $quote)
            <tr>
                <td class="desc">&#8226;
                    {{ empty($quote->getStringPaymentMethodType()) ? 'Cuota #' . ($key + 1) : $quote->getStringPaymentMethodType() }}
                    / Fecha: {{ $quote->date->format('d-m-Y') }} / Monto:
                    {{ $quote->currency_type->symbol }}{{ $quote->amount }}</td>
            </tr>
        @endforeach
        </tr>
    </table>
    @endif

    @if ($document->retention)
        <table class="full-width mt-1">
            <tr>
                <td colspan="3" class="desc">
                    <strong>Información de la retención</strong>
                </td>
            </tr>
            <tr>
                <td width="100px" class="desc">Base de Cálculo</td>
                <td width="8px" class="desc">:</td>
                <td class="desc">{{ $document->currency_type->symbol }} {{ $document->retention->base }}</td>
            </tr>{}
            <tr>
                <td width="" class="desc">Porcentaje</td>
                <td width="" class="desc">:</td>
                <td class="desc">{{ $document->retention->percentage * 100 }}%</td>
            </tr>
            <tr>
                <td width="" class="desc">Monto</td>
                <td width="" class="desc">:</td>
                <td class="desc">{{ $document->currency_type->symbol }} {{ $document->retention->amount }}</td>
            </tr>
        </table>
    @endif


    <br>
    </td>
    <td class="p-1 text-center align-top desc cell-solid " rowspan="6">

        <img src="data:image/png;base64, {{ $document->qr }}" class="p-0 m-0" style="width: 120px;" /><br>
        Código Hash: {{ $document->hash }}

    </td>

    <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
        I.G.V. {{ $document->currency_type->symbol }}
    </td>
    <td class="p-1 text-right align-top desc cell-solid font-bold">{{ number_format($document->total_igv, 2) }}</td>
    </tr>


    <tr>
        <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
            OP. EXONERADAS {{ $document->currency_type->symbol }}
        </td>
        <td class="p-1 text-right align-top desc cell-solid font-bold">
            {{ number_format($document->total_exonerated, 2) }}</td>
    </tr>


    @if ($document->retention)
        <tr>
            <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                TOTAL {{ $document->currency_type->symbol }}
            </td>
            <td class="p-1 text-right align-top desc cell-solid font-bold">
                {{ number_format($document->retention->base, 2) }}</td>
        </tr>
        <tr>
            <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                RETENCIÓN - {{ $document->retention->percentage * 100 }}% {{ $document->currency_type->symbol }}
            </td>
            <td class="p-1 text-right align-top desc cell-solid font-bold">
                {{ number_format($document->retention->amount, 2) }}</td>
        </tr>
    @else
        <tr>
            <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                OP. GRATUITAS {{ $document->currency_type->symbol }}
            </td>
            <td class="p-1 text-right align-top desc cell-solid font-bold">
                {{ number_format($document->total_free, 2) }}</td>
        </tr>
    @endif
    @if($document->total_discount > 0)
    <tr>
        <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">{{(($document->total_prepayment > 0) ? 'Anticipo':'Descuento TOTAL')}}
            : {{ $document->currency_type->symbol }}</td>
        <td class="p-1 text-right align-top desc cell-solid font-bold">{{ number_format($document->total_discount, 2) }}</td>
    </tr>
    @endif
    @if ($document->retention)
        <tr>
            <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                TOTAL A PAGAR. {{ $document->currency_type->symbol }}
            </td>
            <td class="p-1 text-right align-top desc cell-solid font-bold">
                {{ number_format($document->total - $document->retention->amount, 2) }}</td>
        </tr>
    @else
        <tr>
            <td class="p-1 text-right align-top desc cell-solid font-bold" colspan="2">
                TOTAL A PAGAR. {{ $document->currency_type->symbol }}
            </td>
            <td class="p-1 text-right align-top desc cell-solid font-bold">{{ number_format($document->total, 2) }}
            </td>
        </tr>
    @endif
    </tbody>

    </table>
    @if (in_array($document->document_type->id, ['01', '03']))
        @foreach ($accounts as $account)
            <p>
                <span class="font-bold">{{ $account->bank->description }}</span>
                {{ $account->currency_type->description }}
                <span class="font-bold">N°:</span> {{ $account->number }}
                @if ($account->cci)
                    <span class="font-bold">CCI:</span> {{ $account->cci }}
                @endif
            </p>
        @endforeach
    @endif


    <p style="color: red; text-align: center;">UNA VEZ SALIDA LA MERCADERÍA NO HAY OPCIÓN A CAMBIOS NI DEVOLUCIONES</p>

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
