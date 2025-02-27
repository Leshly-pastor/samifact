<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UserRequest;
use App\Http\Resources\Tenant\UserCollection;
use App\Http\Resources\Tenant\UserResource;
use App\Models\Tenant\Cash;
use App\Models\Tenant\Catalogs\DocumentType;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Module;
use App\Models\Tenant\Series;
use App\Models\Tenant\User;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\Zone;
use App\Models\Tenant\Catalogs\IdentityDocumentType;
use App\Models\Tenant\CustomInitRoute;
use Illuminate\Http\Request;
use Modules\Finance\Helpers\UploadFileHelper;
use Illuminate\Support\Facades\DB;
use Modules\BusinessTurn\Models\BusinessTurn;

class UserController extends Controller
{
    public function unlock(Request $request){
        $user_id = $request->input('user_id');
        $user = User::findOrFail($user_id);
        $user->is_locked = false;
        $user->msg_locked = null;
        $user->save();
        return [
            'success' => true,
            'message' => 'Usuario desbloqueado'
        ];
    }
    public function lock(Request $request){
        $user_id = $request->input('user_id');
        $message = $request->input('message');
        $message = trim($message);
        $user = User::findOrFail($user_id);
        $user->is_locked = true;
        if($message == null || $message == "" || $message == " "){
            $message = "Su cuenta ha sido bloqueada. Comuníquese con el administrador";
        }
        $user->msg_locked = $message;
        $user->save();
        return [
            'success' => true,
            'message' => 'Usuario bloqueado'
        ];
    }
    public function cambiarContrasena(Request $request)
    {
        $url = explode('.', $request->getHttpHost());
        $part = $url[0];
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');


        if ($password != $password_confirmation) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ];
        }
        if ($part == "demo") {
            $password = "123456";
        }
        $user_id = auth()->user()->id;
        $user = User::findOrFail($user_id);
        $user->password = bcrypt($password);
        $user->save();
        auth()->logout();
        return redirect()->guest('login');
    }
    public function index()
    {
        return view('tenant.users.index');
    }

    public function record($id)
    {
        $record = new UserResource(User::findOrFail($id));

        return $record;
    }

    private function prepareModules(Module $module): Module
    {
        $levels = [];
        foreach ($module->levels as $level) {
            array_push($levels, [
                'id' => "{$module->id}-{$level->id}",
                'description' => $level->description,
                'module_id' => $level->module_id,
                'is_parent' => false,
            ]);
        }
        unset($module->levels);
        $module->is_parent = true;
        $module->childrens = $levels;
        return $module;
    }

    public function tables()
    {
        /** @var User $user */
        $user = User::find(1);
        $modulesTenant = $user->getCurrentModuleByTenant()
            ->pluck('module_id')
            ->all();

        $levelsTenant = $user->getCurrentModuleLevelByTenant()
            ->pluck('module_level_id')
            ->toArray();


        $modules = Module::with(['levels' => function ($query) use ($levelsTenant) {
            $query->whereIn('id', $levelsTenant);
        }])
            ->orderBy('order_menu')
            ->whereIn('id', $modulesTenant)
            ->get()
            ->each(function ($module) {
                return $this->prepareModules($module);
            });
        $establishments = Establishment::orderBy('description')->get();
        $documents = DocumentType::OnlyAvaibleDocuments()->get();
        $series = Series::FilterEstablishment()->FilterDocumentType()->get();
        $types = [
            ['type' => 'admin', 'description' => 'Administrador'],
            ['type' => 'seller', 'description' => 'Vendedor'],
        ];

        $configuration = Configuration::select(['package_handlers', 'permission_to_edit_cpe', 'regex_password_user'])->first();
        if ($configuration->package_handlers) {
            $types = [
                ['type' => 'admin', 'description' => 'Administrador'],
                ['type' => 'seller', 'description' => 'Cajero'],
            ];
        }
        $config_permission_to_edit_cpe = $configuration->permission_to_edit_cpe;
        $config_regex_password_user = $configuration->regex_password_user;
        $zones = Zone::all();
        $routes = CustomInitRoute::all();
        $identity_document_types = IdentityDocumentType::filterDataForPersons()->get();

        return compact('modules','routes', 'establishments', 'types', 'documents', 'series', 'config_permission_to_edit_cpe', 'zones', 'identity_document_types', 'config_regex_password_user');
    }

    public function regenerateToken(User $user)
    {
        $data = [
            'api_token' => $user->api_token,
            'success' => false,
            'message' => 'No puedes cambiar el token'
        ];
        if (auth()->user()->isAdmin()) {
            $user->updateToken()->push();
            $data['api_token'] = $user->api_token;
            $data['success'] = true;
            $data['message'] = 'Token cambiado';
        }
        return $data;
    }


    public function store(UserRequest $request)
    {
        // 
        $id = $request->input('id');
      
        if (!$id) { //VALIDAR EMAIL DISPONIBLE
            $verify = User::where('email', $request->input('email'))->first();
            if ($verify) {
                return [
                    'success' => false,
                    'message' => 'Email no disponible. Ingrese otro Email'
                ];
            }
        }

        DB::connection('tenant')->transaction(function () use ($request, $id) {
            $is_integrate_system = BusinessTurn::isIntegrateSystem();
            $integrate_user_type_id = $request->input('integrate_user_type_id');
            /** @var User $user */
            $user = User::firstOrNew(['id' => $id]);
    
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->establishment_id = $request->input('establishment_id');
            $user->type = $request->input('type');
            $user->integrate_user_type_id = $request->input('integrate_user_type_id');
            $init_route = $request->input('init_route');
            if ($init_route == null) {
                $init_route = '/documents/create';
            }
            $user->init_route = $init_route;

            // Zona por usuario
            // $user->zone_id = $request->input('zone_id');

            if (!$id) {
                $user->api_token = str_random(50);
                $user->password = bcrypt($request->input('password'));
            } elseif ($request->has('password')) {
                if (config('tenant.password_change')) {
                    $user->password = bcrypt($request->input('password'));
                }
            }

            $user->setDocumentId($request->input('document_id'))
                ->setSeriesId($request->input('series_id'));
            $user->establishment_id = $request->input('establishment_id');

            $user->recreate_documents = $request->input('recreate_documents');
            $user->permission_edit_cpe = $request->input('permission_edit_cpe');
            $user->create_payment = $request->input('create_payment');
            $user->delete_payment = $request->input('delete_payment');
            $user->auditor = $request->input('auditor') ? $request->input('auditor') : false;

            $user->edit_purchase = $request->input('edit_purchase');
            $user->annular_purchase = $request->input('annular_purchase');
            $user->delete_purchase = $request->input('delete_purchase');

            $user->permission_force_send_by_summary = $request->input('permission_force_send_by_summary');

            if ($user->isDirty('password')) $user->last_password_update = date('Y-m-d H:i:s');

            $this->setAdditionalData($user, $request);

            $user->save();
            if ($id == null) {
                $cash = new Cash;
                $cash->user_id = $user->id;
                $cash->date_opening = date('Y-m-d');
                $cash->time_opening = date('H:i:s');
                $cash->state = 1;
                $cash->save();
            }
            $this->savePhoto($user, $request);
            $this->saveDefaultDocumentTypes($user, $request);

            if ($user->id != 1) {
                $user->setModuleAndLevelModule($request->modules, $request->levels);
            }
            if($is_integrate_system && $integrate_user_type_id){
                if($id){
                    DB::connection('tenant')
                    ->table('module_user')
                    ->where('user_id', $user->id)
                    ->delete();
        
                    DB::connection('tenant')
                    ->table('module_level_user')
                    ->where('user_id', $user->id)
                    ->delete();
                   }
        
                $user->setIntegrateUserType($integrate_user_type_id);
            }
        });

        return [
            'success' => true,
            'message' => ($id) ? 'Usuario actualizado' : 'Usuario registrado'
        ];
    }

  
    /**
     * 
     * Asignar datos
     *
     * @param  User $user
     * @param  UserRequest $request
     * @return void
     */
    private function setAdditionalData(User &$user, $request)
    {
        $user->edit_purchase = $request->input('edit_purchase');

        $user->identity_document_type_id = $request->identity_document_type_id;
        $user->number = $request->number;
        $user->address = $request->address;
        $user->names = $request->names;
        $user->last_names = $request->last_names;
        $user->personal_email = $request->personal_email;
        $user->corporate_email = $request->corporate_email;
        $user->personal_cell_phone = $request->personal_cell_phone;
        $user->corporate_cell_phone = $request->corporate_cell_phone;
        $user->date_of_birth = $request->date_of_birth;
        $user->contract_date = $request->contract_date;
        $user->position = $request->position;
        $user->photo_filename = $request->photo_filename;

        $user->multiple_default_document_types = $request->multiple_default_document_types;
    }


    /**
     * 
     * Guardar imágen
     *
     * @param  User $user
     * @param  UserRequest $request
     * @return void
     */
    public function savePhoto(&$user, $request)
    {
        $temp_path = $request->photo_temp_path;

        if ($temp_path) {
            $old_filename = $request->photo_filename;
            $user->photo_filename = UploadFileHelper::uploadImageFromTempFile('users', $old_filename, $temp_path, $user->id, true);
            $user->save();
        }
    }


    /**
     * 
     * Guardar documentos por defecto
     *
     * @param  User $user
     * @param  UserRequest $request
     * @return void
     */
    public function saveDefaultDocumentTypes(User $user, UserRequest $request)
    {
        $user->default_document_types()->delete();

        foreach ($request->default_document_types as $row) {
            $user->default_document_types()->create($row);
        }
    }


    public function records_lite()
    {
        $records = User::where('type', '!=', 'integrator')
        ->where('type', '!=', 'superadmin')
        ->get()
            ->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            });

        return $records;
    }
    public function records()
    {
        $records = User::where('type', '!=', 'integrator')
        ->where('type', '!=', 'superadmin')
        ->get();

        return new UserCollection(collect($records));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return [
            'success' => true,
            'message' => 'Usuario eliminado con éxito'
        ];
    }
}
