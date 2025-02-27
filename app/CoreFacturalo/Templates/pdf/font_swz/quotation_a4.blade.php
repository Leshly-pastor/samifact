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
    //$path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
    $tittle = $document->prefix.'-'.str_pad($document->number ?? $document->id, 8, '0', STR_PAD_LEFT);
@endphp
<html>
<head>
    {{--<title>{{ $tittle }}</title>--}}
    {{--<link href="{{ $path_style }}" rel="stylesheet" />--}}
</head>
<body>
<table class="full-width">
    <tr>
        <td width="65%">
            @if($company->logo)
                <div class="company_logo_box">
                    <img src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo" style="max-height: 150px;">
                </div>
            @endif
        </td>
        <td width="35%" class="border-box p-box-info text-center">
            <div class="text-left">
                <h5>{{ 'RUC '.$company->number }}</h5>
                <h5 class="text-center">{{ get_document_name('quotation', 'Cotización') }}</h5>
                <h3 class="text-center">{{ $tittle }}</h3>
            </div>
        </td>
    </tr>
</table>
<table class="full-width">
    <tr>
        <td class="pl-3">
            <div class="text-left">
                <p class="font-bold text-upp">{{ $company->name }}</p>
                <p style="text-transform: uppercase;">
                    {{ ($establishment->address !== '-')? $establishment->address : '' }}
                    {{ ($establishment->district_id !== '-')? ', '.$establishment->district->description : '' }}
                    {{ ($establishment->province_id !== '-')? ', '.$establishment->province->description : '' }}
                    {{ ($establishment->department_id !== '-')? '- '.$establishment->department->description : '' }}
                </p>
                <p>{{ ($establishment->email !== '-')? $establishment->email : '' }}</p>
                <p>{{ ($establishment->telephone !== '-')? $establishment->telephone : '' }}</p>
            </div>
        </td>
    </tr>
</table>

<table class="full-width mt-5">
    <tr>
        <td><p class="font-bold text-upp">Adquiriente</p></td>
        <td></td>
    </tr>
    <tr>
        <td width="65%">
            <table class="full-width">
                <tr>
                    <td>{{ $customer->identity_document_type->description }}:{{$customer->number}}</td>
                </tr>
                <tr>
                    <td>{{ $customer->name }}</td>
                </tr>
                @if ($customer->address !== '')
                    <tr>
                        <td class="align-top">Dirección:</td>
                        <td colspan="3">
                            {{ $customer->address }}
                            {{ ($customer->district_id !== '-')? ', '.$customer->district->description : '' }}
                            {{ ($customer->province_id !== '-')? ', '.$customer->province->description : '' }}
                            {{ ($customer->department_id !== '-')? '- '.$customer->department->description : '' }}
                        </td>
                    </tr>
                @endif
            </table>
        </td>
        <td>
            <table class="full-width">
                <tr>
                    <td>Fecha de emisión:</td>
                    <td>{{$document->date_of_issue->format('Y-m-d')}}</td>
                </tr>
                @if ($document->purchase_order)
                    <tr>
                        <td>Orden de compra:</td>
                        <td>{{ $document->purchase_order }}</td>
                    </tr>
                @endif
                @if ($document->guides)
                    @foreach($document->guides as $guide)
                        <tr>
                            <td>{{ \App\Models\Tenant\Catalogs\Code::byCatalogAndCode('01', $guide->document_type_code)->description }}</td>
                            <td>{{ $guide->number }}</td>
                        </tr>
                    @endforeach
                @endif
            </table>
        </td>
    </tr>
</table>

<table class="mt-10 mb-10" style="border-collapse: collapse;border-top: 1px solid #333;">
    <tr class="bg-grey">
        <th class="text-center py-2" width="8%">Cant.</th>
        <th class="text-center py-2" width="8%">Unidad</th>
        <th class="text-left py-2">Descripción</th>
        <th class="text-right py-2" width="12%">P.Unit</th>
        <th class="text-right py-2" width="8%">Dto.</th>
        <th class="text-right py-2" width="12%">Total</th>
    </tr>
    <tbody>
    @foreach($document->items as $row)
        <tr>
            <td class="text-center align-top">
                @if(((int)$row->quantity != $row->quantity))
                    <p>{{ $row->quantity }}</p>
                @else
                    <p>{{ number_format($row->quantity, 0) }}</p>
                @endif
            </td>
            <td class="text-center align-top">
                <p>{{ $row->item->unit_type_id }}</p>
            </td>
            <td class="text-left">
                <p>@if($row->item->name_product_pdf ?? false) {!!$row->item->name_product_pdf ?? ''!!} @else {!!$row->item->description!!} @endif   </p>
                @if (!empty($row->item->presentation))
                    <p>{!!$row->item->presentation->description!!}</p>
                @endif
                @if($row->attributes)
                    @foreach($row->attributes as $attr)
                        <br/><span style="font-size: 9px">{!! $attr->description !!} : {{ $attr->value }}</span>
                    @endforeach
                @endif
                @if($row->discounts)
                    @foreach($row->discounts as $dtos)
                        <br/><span style="font-size: 9px">{{ $dtos->factor * 100 }}% {{$dtos->description }}</span>
                    @endforeach
                @endif
            </td>
            <td class="text-right align-top">
                <p>{{ number_format($row->unit_price, 2) }}</p>
            </td>
            <td class="text-right align-top">
                @if($row->discounts)
                    @php
                        $total_discount_line = 0;
                        foreach ($row->discounts as $disto) {
                            $total_discount_line = $total_discount_line + $disto->amount;
                        }
                    @endphp
                    <p>{{ number_format($total_discount_line, 2) }}</p>
                @else
                    <p>0</p>
                @endif
            </td>
            <td class="text-right align-top">
                <p>{{ number_format($row->total, 2) }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="6" class="border-bottom"></td>
        </tr>
    @endforeach
        @if($document->total_exportation > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Op. Exportación: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exportation, 2) }}</td>
            </tr>
        @endif
        @if($document->total_free > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Op. Gratuitas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_free, 2) }}</td>
            </tr>
        @endif
        @if($document->total_unaffected > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Op. Inafectas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_unaffected, 2) }}</td>
            </tr>
        @endif
        @if($document->total_exonerated > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Op. Exoneradas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_exonerated, 2) }}</td>
            </tr>
        @endif
        @if($document->total_taxed > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">Op. Gravadas: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_taxed, 2) }}</td>
            </tr>
        @endif
        @if($document->total_discount > 0)
            <tr>
                <td colspan="5" class="text-right font-bold">{{(($document->total_prepayment > 0) ? 'Anticipo':'Descuento TOTAL')}}: {{ $document->currency_type->symbol }}</td>
                <td class="text-right font-bold">{{ number_format($document->total_discount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="5" class="text-right font-bold">IGV: {{ $document->currency_type->symbol }}</td>
            <td class="text-right font-bold">{{ number_format($document->total_igv, 2) }}</td>
        </tr>
        <tr>
            <td colspan="5" class="text-right font-bold">Total a pagar: {{ $document->currency_type->symbol }}</td>
            <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
        </tr>
    </tbody>
</table>
<table class="full-width">
    <tr>
        {{-- <td width="65%">
            @foreach($document->legends as $row)
                <p>Son: <span class="font-bold">{{ $row->value }} {{ $document->currency_type->description }}</span></p>
            @endforeach
            <br/>
            <strong>Información adicional</strong>
            @foreach($document->additional_information as $information)
                <p>{{ $information }}</p>
            @endforeach
        </td> --}}
    </tr>
</table>
</body>
</html>
