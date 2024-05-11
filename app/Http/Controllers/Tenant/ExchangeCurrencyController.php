<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\ExchangeCurrencyCollection;
use App\Http\Resources\Tenant\ExchangeCurrencyResource;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\ExchangeCurrency;
use Illuminate\Http\Request;

class ExchangeCurrencyController extends Controller
{


    public function index()
    {
        return view('tenant.exchange_currency.index');
    }

    public function tables()
    {
        $currency_types = CurrencyType::all();
        $currencies = $currency_types->transform(function ($row) {
            return [
                'id' => $row->id,
                'description' => $row->description,
                'symbol' => $row->symbol,
            ];
        });









        return compact('currencies');
    }

    public function records(Request $request)
    {   
        $date = $request->input('date');
        $currency_id = $request->input('currency_id');

        $records = ExchangeCurrency::query();

        if($date){
            $records = $records->where('date', $date);
        }

        if($currency_id){
            $records = $records->where('currency_id', $currency_id);
        }

        return new ExchangeCurrencyCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function exchange_date($date, $currency_id)
    {
        $exchange_rate   = ExchangeCurrency::where('currency_id', $currency_id)->where('date', $date)->first();
        if ($exchange_rate) {
            return ['success' => true,'id' => $exchange_rate->id, 'date'=>$exchange_rate->date,  'sale' => $exchange_rate->sale, 'purchase' => $exchange_rate->purchase];
        } else {
            return ['success' => false, 'message' => 'No se encontró el tipo de cambio para la fecha seleccionada', 'sale' => 1, 'purchase' => 1];
        }
    }
    public function record($id)
    {
        $record = new ExchangeCurrencyResource(ExchangeCurrency::findOrFail($id));

        return $record;
    }

    public function store(Request $request)
    {
        $id = $request->input('id');
        $currency_id = $request->input('currency_id');
        $date = $request->input('date');
        if(!$id){
            $exist = ExchangeCurrency::where('currency_id', $currency_id)->where('date', $date)->first();
            if($exist){
                return [
                    'success' => false,
                    'message' => 'Ya existe un tipo de cambio para la fecha seleccionada'
                ];
            }
        }
        $currency_type = ExchangeCurrency::firstOrNew(['id' => $id]);
        $currency_type->fill($request->all());

        $currency_type->save();

        return [
            'success' => true,
            'message' => ($id) ? 'Tipo de cambio editado con éxito' : 'Tipo de cambio registrado con éxito'
        ];
    }

    public function destroy($id)
    {


        $currency_type = ExchangeCurrency::findOrFail($id);
        $currency_type->delete();

        return [
            'success' => true,
            'message' => 'Tipo de cambio eliminado con éxito'
        ];
    }
}
