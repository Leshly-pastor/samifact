<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Requests\Tenant\PersonRequest;
use App\Http\Resources\Tenant\PersonCollection;
use App\Http\Resources\Tenant\PersonResource;
use App\Imports\PersonsImport;
use App\Models\Tenant\Catalogs\Country;
use App\Models\Tenant\Catalogs\Department;
use App\Models\Tenant\Catalogs\District;
use App\Models\Tenant\Catalogs\IdentityDocumentType;
use App\Models\Tenant\Catalogs\Province;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Person;
use App\Models\Tenant\PersonType;
use App\Models\Tenant\Zone;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Modules\Finance\Helpers\UploadFileHelper;
use Carbon\Carbon;
use App\Exports\ClientExport;
use App\Exports\ClientMigrationExport;
use App\Models\System\Configuration;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Suscription\Models\Tenant\SuscriptionNames;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class PersonController extends Controller
{

    public function ubigeo(Request $request)
    {
        $id = $request->id;
        $district = District::where('id', $id)->first();
        if ($district) {
            return [
                'success' => false,
                'message' => 'El distrito ya existe',

            ];
        }
        $district = new District();
        $district->id = $id;
        $district->description = $request->description;
        $district->province_id = $request->province_id;
        $district->save();
        $locations = func_get_locations();
        return [
            'success' => true,
            'message' => 'Distrito registrado con éxito',
            'data' => $district,
            'locations' => $locations
        ];
    }
    public function savePhoto(&$user, $request)
    {
        $temp_path = $request->photo_temp_path;

        if ($temp_path) {
            $old_filename = $request->photo_filename;
            $user->photo_filename = UploadFileHelper::uploadImageFromTempFile('users', $old_filename, $temp_path, $user->id, true);
            $user->save();
        }
    }
    public function drivers()
    {
        $is_comercial  = auth()->user()->integrate_user_type_id == 2;
        $type = 'customers';
        $api_service_token = \App\Models\Tenant\Configuration::getApiServiceToken();
        $driver = true;
        $suscriptionames = SuscriptionNames::create_new();
        return view('tenant.persons.index', compact(
            'is_comercial',
            'type',
            'driver',
            'api_service_token',
            'suscriptionames'
        ));
    }
    public function index($type)
    {
        $is_comercial  = auth()->user()->integrate_user_type_id == 2;

        // $configuration = Configuration::first();
        // $api_service_token = $configuration->token_apiruc =! '' ? $configuration->token_apiruc : config('configuration.api_service_token');
        $api_service_token = \App\Models\Tenant\Configuration::getApiServiceToken();
        $driver = false;
        $suscriptionames = SuscriptionNames::create_new();
        return view('tenant.persons.index', compact(
            'type',
            'is_comercial',
            'driver',
            'api_service_token',
            'suscriptionames'
        ));
    }

    public function columns()
    {
        return [
            'name' => 'Nombre',
            'internal_code' => 'Código interno',
            'barcode' => 'Código de barras',
            'number' => 'Número',
            'document_type' => 'Tipo de documento',
            'person_type_id' => 'Tipo de cliente',
            'zone_id' => 'Zona',
            'department_id' => 'Departamento',
            'province_id' => 'Provincia',
            'district' => 'Distrito',
            // 'observation' => 'Observaci',
        ];
    }
    function get_ids($column, $value)
    {
        $ids = [];
        switch ($column) {
            case 'zone_id':
                $ids = Zone::where('name', 'like', '%' . $value . '%')->pluck('id')->toArray();
                break;
            case 'department_id':
                $ids = Department::where('description', 'like', '%' . $value . '%')->pluck('id')->toArray();
                break;
            case 'province_id':
                $ids = Province::where('description', 'like',  '%' . $value . '%')->pluck('id')->toArray();
                break;
            case 'district_id':
                $ids = District::where('description', 'like', '%' . $value . '%')->pluck('id')->toArray();
                break;
        }

        return $ids;
    }
    public function records($type, Request $request)
    {
        $column = $request->column;
        $value = $request->value;
        $order = $request->order ?? 'asc';
        $driver = filter_var($request->driver ?? "false", FILTER_VALIDATE_BOOLEAN);
        $records = Person::where('type', $type);
        if ($column == 'zone_id' || $column == 'department_id' || $column == 'province_id' || $column == 'district_id') {
            $ids = $this->get_ids($column, $value);
            $records = $records->whereIn($column, $ids);
        } else {
            $records = $records->where($column, 'like', "%{$value}%");
        }
        if ($driver) {
            $records = $records->where('is_driver', true);
        } else {
            $records = $records->where('is_driver', false);
        }


        $records = $records->whereFilterCustomerBySeller($type);
        if ($column == 'name' || $column == 'internal_code' && $order) {
            $records = $records->orderBy($column, $order);
        } else {

            $records = $records->orderBy('name');
        }

        return new PersonCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function create()
    {
        return view('tenant.customers.form');
    }

    public function getLastDocument()
    {
        $last_no_document = Person::where('identity_document_type_id', '0')
            ->whereRaw('number REGEXP "^[0-9]{7}$"')
            ->max('number');
        if ($last_no_document == null) {
            $last_no_document = "0000001";
        } else {
            $last_no_document = $last_no_document + 1;
            $last_no_document = str_pad($last_no_document, 7, "0", STR_PAD_LEFT);
        }
        return [
            'success' => true,
            'data' => $last_no_document

        ];
    }
    public function tables()
    {
        $departments = Department::whereActive()->orderByDescription()->get();
        $provinces = Province::whereActive()->orderByDescription()->get();
        $districts = District::whereActive()->orderByDescription()->get();


        $countries = Country::whereActive()->orderByDescription()->get();
        $identity_document_types = IdentityDocumentType::whereActive()->get();
        $person_types = PersonType::get();
        $locations = func_get_locations();
        $zones = Zone::all();
        $sellers = $this->getSellers();
        $api_service_token = \App\Models\Tenant\Configuration::getApiServiceToken();

        return compact(
            'departments',
            'provinces',
            'districts',
            'countries',
            'identity_document_types',
            'locations',
            'person_types',
            'api_service_token',
            'zones',
            'sellers'
        );
    }

    public function record($id)
    {
        $record = new PersonResource(Person::findOrFail($id));

        return $record;
    }

    public function store(PersonRequest $request)
    {
        /* dd($request->all()); */

        if (!$request->barcode) {
            if ($request->internal_id) {
                $request->merge(['barcode' => $request->internal_id]);
            }
        }

        if ($request->state) {
            if ($request->state != "ACTIVO") {
                return [
                    'success' => false,
                    'message' => 'El estado del contribuyente no es activo, no puede registrarlo',
                ];
            }
        }

        $id = $request->input('id');
        $person = Person::firstOrNew(['id' => $id]);
        $data = $request->all();
        unset($data['optional_email'], $data['id']);
        $person->fill($data);

        $location_id = $request->input('location_id');
        if (is_array($location_id) && count($location_id) === 3) {
            $person->district_id = $location_id[2];
            $person->province_id = $location_id[1];
            $person->department_id = $location_id[0];
        }

        $person->save();
        $this->savePhoto($person, $request);
        $person->addresses()->delete();
        $person->dispatch_addresses()->delete();
        $addresses = $request->input('addresses');
        foreach ($addresses as $row) {
            $person->addresses()->updateOrCreate(['id' => $row['id']], $row);
        }
        $dispatch_addresses = $request->input('dispatch_addresses')  ?? [];
        foreach ($dispatch_addresses as $row) {
            $person->dispatch_addresses()->updateOrCreate(['id' => $row['id']], $row);
        }

        $optional_email = $request->optional_email;
        if (!empty($optional_email)) {
            $person->setOptionalEmailArray($optional_email)->push();
        }

        $msg = '';
        if ($request->type === 'suppliers') {
            $msg = ($id) ? 'Proveedor editado con éxito' : 'Proveedor registrado con éxito';
        } else {
            $msg = ($id) ? 'Cliente editado con éxito' : 'Cliente registrado con éxito';
        }
        return [
            'success' => true,
            'message' => $msg,
            'id' => $person->id
        ];
    }

    public function destroy($id)
    {
        try {

            $person = Person::findOrFail($id);
            $person_type = ($person->type == 'customers') ? 'Cliente' : 'Proveedor';
            $person->delete();

            return [
                'success' => true,
                'message' => $person_type . ' eliminado con éxito'
            ];
        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false, 'message' => "El {$person_type} esta siendo usado por otros registros, no puede eliminar"] : ['success' => false, 'message' => "Error inesperado, no se pudo eliminar el {$person_type}"];
        }
    }

    public function import(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $import = new PersonsImport();
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

    public function getLocationCascade()
    {
        $locations = [];
        $departments = Department::where('active', true)->get();
        foreach ($departments as $department) {
            $children_provinces = [];
            foreach ($department->provinces as $province) {
                $children_districts = [];
                foreach ($province->districts as $district) {
                    $children_districts[] = [
                        'value' => $district->id,
                        'label' => $district->id . " - " . $district->description
                    ];
                }
                $children_provinces[] = [
                    'value' => $province->id,
                    'label' => $province->description,
                    'children' => $children_districts
                ];
            }
            $locations[] = [
                'value' => $department->id,
                'label' => $department->description,
                'children' => $children_provinces
            ];
        }

        return $locations;
    }


    public function enabled($type, $id)
    {

        $person = Person::findOrFail($id);
        $person->enabled = $type;
        $person->save();

        $type_message = ($type) ? 'habilitado' : 'inhabilitado';

        return [
            'success' => true,
            'message' => "Cliente {$type_message} con éxito"
        ];
    }
    public function export_migration($type, Request $request)
    {

        $d_start = null;
        $d_end = null;
        $period = $request->period;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($request->month_start . '-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_start . '-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($request->month_start . '-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_end . '-01')->endOfMonth()->format('Y-m-d');
                break;
        }

        if ($period == 'all') {
            $records = Person::where('type', $type)->get();
        } elseif ($period == 'seller') {
            $records = Person::where(['type' => $type, 'seller_id' => $request->seller_id,])->get();
        } else {
            $records = Person::where('type', $type)->whereBetween('created_at', [$d_start, $d_end])->get();
        }

        $filename = ($type == 'customers') ? 'Reporte_Clientes_' : 'Reporte_Proveedores_';

        return (new ClientMigrationExport)
            ->records($records)
            ->type($type)
            ->download($filename . Carbon::now() . '.xlsx');
    }
    public function export($type, Request $request)
    {

        $d_start = null;
        $d_end = null;
        $period = $request->period;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($request->month_start . '-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_start . '-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($request->month_start . '-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_end . '-01')->endOfMonth()->format('Y-m-d');
                break;
        }

        if ($period == 'all') {
            $records = Person::where('type', $type)->get();
        } elseif ($period == 'seller') {
            $records = Person::where(['type' => $type, 'seller_id' => $request->seller_id,])->get();
        } else {
            $records = Person::where('type', $type)->whereBetween('created_at', [$d_start, $d_end])->get();
        }

        $filename = ($type == 'customers') ? 'Reporte_Clientes_' : 'Reporte_Proveedores_';

        return (new ClientExport)
            ->records($records)
            ->type($type)
            ->download($filename . Carbon::now() . '.xlsx');
    }

    public function clientsForGenerateCPEById($id)
    {

        $persons = Person::without(['identity_document_type', 'country', 'department', 'province', 'district'])
            ->select('id', 'name', 'identity_document_type_id', 'number')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $persons,
        ], 200);
    }
    public function suppliersForGenerateCPE(){
        $typeFile = request('type');
        $filter = request('name');
        $persons = Person::without(['identity_document_type', 'country', 'department', 'province', 'district'])
            ->select('id', 'name', 'identity_document_type_id', 'number')
            ->where('type', 'suppliers')
            ->orderBy('name');
        if ($filter && $typeFile) {
            if ($typeFile === 'document') {
                $persons = $persons->where('number', 'like', "{$filter}%");
            }
            if ($typeFile === 'name') {
                $persons = $persons->where('name', 'like', "%{$filter}%");
            }
        }
        $persons = $persons->take(10)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $persons,
        ], 200);
    }
    public function clientsForGenerateCPE()
    {
        $typeFile = request('type');
        $filter = request('name');
        $persons = Person::without(['identity_document_type', 'country', 'department', 'province', 'district'])
            ->select('id', 'name', 'identity_document_type_id', 'number')
            ->where('type', 'customers')
            ->orderBy('name');
        if ($filter && $typeFile) {
            if ($typeFile === 'document') {
                $persons = $persons->where('number', 'like', "{$filter}%");
            }
            if ($typeFile === 'name') {
                $persons = $persons->where('name', 'like', "%{$filter}%");
            }
        }
        $persons = $persons->take(10)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $persons,
        ], 200);
    }

    public function printBarCode(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");
        $id = $request->id;

        $record = Person::find($id);


        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [
                104.1,
                24
            ],
            'margin_top' => 2,
            'margin_right' => 2,
            'margin_bottom' => 0,
            'margin_left' => 2
        ]);
        $html = view('tenant.persons.exports.persons-barcode-id', compact('record'))->render();

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_clientes_' . now()->format('Y_m_d') . '.pdf', 'I');
    }

    public function generateBarcode($id)
    {

        $person = Person::findOrFail($id);

        $colour = [150, 150, 150];

        $generator = new BarcodeGeneratorPNG();

        $temp = tempnam(sys_get_temp_dir(), 'person_barcode');

        file_put_contents($temp, $generator->getBarcode($person->barcode, $generator::TYPE_CODE_128, 5, 70, $colour));

        $headers = [
            'Content-Type' => 'application/png',
        ];

        return response()->download($temp, "{$person->barcode}.png", $headers);
    }

    public function getPersonByBarcode($request)
    {
        /* dd($request); */
        $value = $request;

        $customers = Person::with('addresses')->whereType('customers')
            ->where('id', $value)->get()->transform(function ($row) {
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


    /**
     *
     * Obtener puntos acumulados por cliente
     *
     * @param int $id
     * @return float
     */
    public function getAccumulatedPoints($id)
    {
        return Person::getOnlyAccumulatedPoints($id);
    }
}
