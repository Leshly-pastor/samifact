@php
    use App\CoreFacturalo\Helpers\Template\TemplateHelper;
    use App\Models\Tenant\Document;
	use App\Models\Tenant\Company;
    use App\Models\Tenant\SaleNote;
    if ($document != null) {
    $establishment = $document->establishment;
        $customer = $document->customer;
        $invoice = $document->invoice;
        $company = Company::first();
        $document_base = ($document->note) ? $document->note : null;

        //$path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
        $document_number = $document->series.'-'.str_pad($document->number, 8, '0', STR_PAD_LEFT);

        if($document_base) {

            $affected_document_number = ($document_base->affected_document) ? $document_base->affected_document->series.'-'.str_pad($document_base->affected_document->number, 8, '0', STR_PAD_LEFT) : $document_base->data_affected_document->series.'-'.str_pad($document_base->data_affected_document->number, 8, '0', STR_PAD_LEFT);

        } else {

            $affected_document_number = null;
        }

        $payments = $document->payments;

        // $document->load('reference_guides');

        if ($document->payments) {
            $total_payment = $document->payments->sum('payment');
            $balance = ($document->total - $total_payment) - $document->payments->sum('change');
        }


    }

    $accounts = \App\Models\Tenant\BankAccount::all();

    $path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');


    // Pago/Coutas detalladas
    $paymentDetailed= [];
	if(
		$document != null  && (
		get_class($document) == Document::class ||
		get_class($document) == SaleNote::class
        )
	){
        $paymentDetailed = TemplateHelper::getDetailedPayment($document);
	}
@endphp
<head>
    <link href="{{ $path_style }}" rel="stylesheet" />
</head>
<body>
@if($document != null)
    <table class="full-width border-box my-2">

        <tr>
            <td class="text-upp p-2">SON:
                @foreach(array_reverse( (array) $document->legends) as $row)
                    @if ($row->code == "1000")
                        {{ $row->value }} {{ $document->currency_type->description }}
                    @else
                        {{$row->code}}: {{ $row->value }}
                    @endif
                @endforeach
            </td>
        </tr>
       
    </table>
    
    <table class="full-width border-box my-2">
        @if ($document->retention)
        <tr>
            <td>
                <strong>Información de la retención:</strong>
            </td>
        </tr>
        <tr>
            <td>Base imponible de la retención:
                S/ {{ round($document->retention->amount / $document->retention->percentage, 2) }}</td>
        </tr>
        <tr>
            <td>Porcentaje de la retención {{ $document->retention->percentage * 100 }}%</td>
        </tr>
        <tr>
            <td>Monto de la retención S/ {{ $document->retention->amount_pen }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-upp p-2">OBSERVACIONES:
                @if($document->additional_information)
                    @foreach($document->additional_information as $information)
                        @if ($information)
                            {{ $information }}
                        @endif
                    @endforeach
                @endif
            </td>
        </tr>
    </table>
    <table class="full-width mt-10 mb-10 border-bottom">
        <tr>
            <th class="border-box text-center py-1 desc">Importe bruto</th>
            <th class="border-box text-center py-1 desc">Descuentos</th>
            <th class="border-box text-center py-1 desc">Total valor venta</th>
            <th class="border-box text-center py-1 desc">I.G.V. 18%</th>
            <th class="border-box text-center py-1 desc">Total precio venta</th>
            <th class="border-box text-center py-1 desc">Pago a cuenta</th>
            @if($document->retention)
            <th class="border-box text-center py-1 desc">
                RETENCIÓN {{ $document->retention->percentage * 100 }}%
            </th>
            @endif
            <th class="border-box text-center py-1 desc">Neto a pagar</th>
        </tr>
        <tr>
            <td class="border-box text-center py-1 desc">
                @if($document->total_taxed > 0)
                    {{ $document->currency_type->symbol }} {{ number_format($document->total_taxed, 2) }}
                @endif
            </td>
            <td class="border-box text-center py-1 desc">
                @if($document->total_discount > 0)
                    {{ $document->currency_type->symbol }} {{ number_format($document->total_discount, 2) }}
                @endif
            </td>
            <td class="border-box text-center py-1 desc">
                @if($document->total_taxed > 0)
                    {{ $document->currency_type->symbol }} {{ number_format($document->total_taxed, 2) }}
                @endif
            </td>
            <td class="border-box text-center py-1 desc">
                {{ $document->currency_type->symbol }} {{ number_format($document->total_igv, 2) }}
            </td>
            <td class="border-box text-center py-1 desc">
                {{ $document->currency_type->symbol }} {{ number_format($document->total, 2) }}
            </td>
            <td class="border-box text-center py-1 desc">
                @if(!empty($paymentDetailed))
                    @foreach($paymentDetailed as $detailed)
                        @foreach($detailed as $row)
                            {{--{{ isset($paymentDetailed['CUOTA'])?'Cuotas:':'Pagos:' }}--}}

                            {{ $row['description']  }} -
                            {{ $row['reference']  }}
                            {{ $row['symbol']  }}
                            {{ number_format( $row['amount'], 2) }}
                        @endforeach
                    <br>
                    @endforeach
                @endif
            </td>
            @if ($document->retention)
               
            <td class="border-box text-center py-1 desc">
                {{$document->currency_type->symbol}} {{number_format($document->retention->amount, 2)}}
            </td>
            @endif
            <td class="border-box text-center py-1 desc">
                @if($document->retention)
                {{ $document->currency_type->symbol }} {{ number_format($document->total - $document->retention->amount, 2) }}
                @else
                {{ $document->currency_type->symbol }} {{ number_format($document->total, 2) }}
                @endif
            </td>
        </tr>
    </table>
    <table class="full-width border-box my-2">
        <tr>
            <th class="p-1" width="25%">Banco</th>
            <th class="p-1">Moneda</th>
            <th class="p-1" width="30%">Código de Cuenta Interbancaria</th>
            <th class="p-1" width="25%">Código de Cuenta</th>
        </tr>
        @foreach($accounts as $account)
        <tr>
            <td class="text-center">{{$account->bank->description}}</td>
            <td class="text-center text-upp">{{$account->currency_type->description}}</td>
            <td class="text-center">
                @if($account->cci)
                {{$account->cci}}
                @endif
            </td>
            <td class="text-center">{{$account->number}}</td>
        </tr>
        @endforeach
    </table>
    @endif

<table class="full-width">
    <tr>
        @php
            $document_description = null;
             if (is_object($document)) {
                if ($document && $document->prefix == 'NV') {
                    $document_description = 'NOTA DE VENTA ELECTRÓNICA';
                }
                if ($document && $document->document_type_id) {
                    $document_description = $document->document_type->description;
                }
            }

        @endphp
        @if ($document_description)
            <td class="text-center desc">Representación impresa de la {{ $document_description }} <br />Esta puede
                ser consultada en {!! url('/buscar') !!}</td>
        @else
            <td class="text-center desc">Representación impresa del Comprobante de Pago Electrónico. <br />Esta
                puede ser consultada en {!! url('/buscar') !!}</td>
        @endif
    </tr>
</table>
</body>
