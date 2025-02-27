<?php

namespace App\CoreFacturalo\Requests\Inputs;

use App\CoreFacturalo\Requests\Inputs\Common\ActionInput;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use App\CoreFacturalo\Requests\Inputs\Common\LegendInput;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\Models\Tenant\Catalogs\IdentityDocumentType;
use App\Models\Tenant\Company;
use App\Models\Tenant\Dispatch;
use App\Models\Tenant\DispatchOrder;
use App\Models\Tenant\Item;
use Illuminate\Support\Str;
use Modules\BusinessTurn\Models\BusinessTurn;
use Modules\Dispatch\Models\DispatchAddress;
use Modules\Dispatch\Models\Dispatcher;
use Modules\Dispatch\Models\DispatchPerson;
use Modules\Dispatch\Models\Driver;
use Modules\Dispatch\Models\Receiver;
use Modules\Dispatch\Models\ReceiverAddress;
use Modules\Dispatch\Models\Sender;
use Modules\Dispatch\Models\SenderAddress;
use Modules\Dispatch\Models\Transport;

class DispatchInput
{
    public static function set($inputs)
    {
        $document_type_id = $inputs['document_type_id'];
        $series = $inputs['series'];
        $number = $inputs['number'];
        $company_id = Functions::valueKeyInArray($inputs, 'company_id');
        $company = Company::active();
        $soap_type_id = $company->soap_type_id;
        $number = Functions::newNumber($soap_type_id, $document_type_id, $series, $number, Dispatch::class);
        
        if (is_null($inputs['id'])) {
            Functions::validateUniqueDocument($soap_type_id, $document_type_id, $series, $number, Dispatch::class,$company_id);
        }
        
        $filename = Functions::filename($company, $document_type_id, $series, $number);
        $establishment = EstablishmentInput::set($inputs['establishment_id']);
        $alter_establishment = Functions::valueKeyInArray($inputs, 'establishment');
        if ($alter_establishment) {
            $establishment = $alter_establishment;
        }
        
        $customer = PersonInput::set($inputs['customer_id']);
        $reference_sale_note_id = Functions::valueKeyInArray($inputs, 'reference_sale_note_id');
        if ($reference_sale_note_id) {
            if(BusinessTurn::isIntegrateSystem()){
                $dispatch_order = DispatchOrder::where('sale_note_id', $reference_sale_note_id)->first();
                if($dispatch_order){
                    $inputs['reference_dispatch_order_id'] = $dispatch_order->id;
                }
            }
        } 
        $alter_company = [];
        if ($company_id ) {
            $company_found = Company::where('website_id', $company_id)->first();
            $alter_company = [
                'id' => $company_found->id,
                'name' => $company_found->name,
                'number' => $company_found->number,
                'trade_name' => $company_found->trade_name,
                'website_id' => $company_found->website_id,
            ];
            $document_found = Dispatch::where('series', $series)
                ->where('document_type_id', $document_type_id)
                ->where('alter_company->website_id', $company_found->website_id)
                ->orderBy('number', 'desc')
                ->first();
            if ($document_found) {
                $document_number = $document_found->number;
                $document_number = $document_number + 1;
                // if ($document_number > $number) {
                $number = $document_number;
                // }
            }else{
                if(!is_numeric($number)){
                    $number = 1;
                }
            }
            
        }
        $inputs['type'] = 'dispatch';
    
        $data = [
            'alter_company' => $alter_company,
            'purchase_order' => Functions::valueKeyInArray($inputs, 'purchase_order'),
            'id' => Functions::valueKeyInArray($inputs, 'id'),
            'type' => $inputs['type'],
            'inventory_reference_id' => Functions::valueKeyInArray($inputs, 'inventory_reference_id'),
            'user_id' => auth()->id(),
            'external_id' => Str::uuid()->toString(),
            'establishment_id' => $inputs['establishment_id'],
            'establishment' => $establishment,
            'soap_type_id' => $soap_type_id,
            'state_type_id' => '01',
            'ubl_version' => '2.0',
            'filename' => $filename,
            'document_type_id' => $document_type_id,
            'series' => $series,
            'number' => $number,
            'date_of_issue' => $inputs['date_of_issue'],
            'time_of_issue' => $inputs['time_of_issue'],
            'customer_id' => $inputs['customer_id'],
            'customer' => $customer,
            'observations' => $inputs['observations'],
            'dispatches_related' => Functions::valueKeyInArray($inputs, 'dispatches_related'),
            'transport_mode_type_id' => Functions::valueKeyInArray($inputs, 'transport_mode_type_id'),
            'transfer_reason_type_id' => Functions::valueKeyInArray($inputs, 'transfer_reason_type_id'),
            'transfer_reason_description' => Functions::valueKeyInArray($inputs, 'transfer_reason_description'),
            'date_of_shipping' => $inputs['date_of_shipping'],
            'transshipment_indicator' => Functions::valueKeyInArray($inputs, 'transshipment_indicator', false),
            'port_code' => $inputs['port_code'],
            'unit_type_id' => $inputs['unit_type_id'],
            'total_weight' => $inputs['total_weight'],
            'packages_number' => $inputs['packages_number'],
            'container_number' => $inputs['container_number'],
            //            'license_plate' => (isset($inputs['license_plate'])) ? func_str_to_upper_utf8($inputs['license_plate']) : null,
            'origin' => self::origin($inputs),
            'delivery' => self::delivery($inputs),
            'dispatcher' => self::dispatcher($inputs),
            'driver' => self::driver($inputs),
            'transport_data' => self::transport($inputs),
            'items' => self::items($inputs),
            'legends' => LegendInput::set($inputs),
            'optional' => Functions::valueKeyInArray($inputs, 'optional'),
            'actions' => ActionInput::set($inputs),
            'reference_document_id' => Functions::valueKeyInArray($inputs, 'reference_document_id'),
            'reference_quotation_id' => Functions::valueKeyInArray($inputs, 'reference_quotation_id'),
            'reference_order_note_id' => Functions::valueKeyInArray($inputs, 'reference_order_note_id'),
            'reference_order_form_id' => Functions::valueKeyInArray($inputs, 'reference_order_form_id'),
            'reference_sale_note_id' => Functions::valueKeyInArray($inputs, 'reference_sale_note_id'),
            'reference_dispatch_order_id' => Functions::valueKeyInArray($inputs, 'reference_dispatch_order_id'),
            'secondary_license_plates' => self::secondary_license_plates($inputs),
            'related' => self::related($inputs),
            'order_form_external' => Functions::valueKeyInArray($inputs, 'order_form_external'),
            'additional_data' => Functions::valueKeyInArray($inputs, 'additional_data'),
            'origin_address_id' => Functions::valueKeyInArray($inputs, 'origin_address_id', 0),
            'delivery_address_id' => Functions::valueKeyInArray($inputs, 'delivery_address_id', 0),
            'driver_id' => self::getDriverId($inputs), //Functions::valueKeyInArray($inputs, 'driver_id'),
            'dispatcher_id' => self::getDispatcherId($inputs), //Functions::valueKeyInArray($inputs, 'dispatcher_id'),
            'transport_id' => self::getTransportId($inputs), // Functions::valueKeyInArray($inputs, 'transport_id'),
            'receiver_id' => self::getReceiverId($inputs),
            'sender_address_id' => self::getSenderAddressId($inputs),
            'receiver_address_id' => self::getReceiverAddressId($inputs),
            'sender_data' => self::senderData($inputs),
            'sender_id' => self::getSenderId($inputs),
            'receiver_data' => self::receiverData($inputs),
            'sender_address_data' => self::senderAddressData($inputs),
            'receiver_address_data' => self::receiverAddressData($inputs),
            'tracto_carreta' => Functions::valueKeyInArray($inputs, 'tracto_carreta'),
        ];

        if (isset($inputs['data_affected_document'])) {
            $data['data_affected_document'] = $inputs['data_affected_document'];
        }
        return $data;
    }


    /**
     *
     * Documento relacionado (DAM), usado para exportación
     *
     * @param  $inputs
     * @return array|null
     */
    private static function related($inputs)
    {
        if (array_key_exists('related', $inputs)) {
            $related = $inputs['related'];

            if (!empty($related)) return $related;
        }

        return null;
    }

    private static function origin($inputs)
    {
        if ($inputs['document_type_id'] === '09') {
            if (array_key_exists('origin', $inputs)) {
                $origin = $inputs['origin'];
// dd($origin['location_id']);
                $country_id = key_exists('country_id', $origin) ? $origin['country_id'] : 'PE';
                $address = $origin['address'];
                $location_id = is_array($origin['location_id']) ? $origin['location_id'][2] : $origin['location_id'];
                // $location_id = $origin['location_id'][2] == '0' ? $origin['location_id'] : $origin['location_id'][2];
                $code = key_exists('code', $origin) ? $origin['code'] : '0000';
                return [
                    'country_id' => $country_id,
                    'location_id' => $location_id,
                    'address' => $address,
                    'code' => $code,
                ];
            }
        }

        return null;
    }

    private static function delivery($inputs)
    {
        if ($inputs['document_type_id'] === '09') {
            if (array_key_exists('delivery', $inputs)) {
                $delivery = $inputs['delivery'];
                $country_id = key_exists('country_id', $delivery) ? $delivery['country_id'] : 'PE';
                $address = $delivery['address'];
                $location_id = is_array($delivery['location_id']) ? $delivery['location_id'][2] : $delivery['location_id'];
                $code = key_exists('code', $delivery) ? $delivery['code'] : '0000';

                return [
                    'country_id' => $country_id,
                    'location_id' => $location_id,
                    'address' => $address,
                    'code' => $code,
                ];
            }
        }

        return null;
    }

    private static function dispatcher($inputs)
    {
        if ($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '01') {
            if (array_key_exists('dispatcher', $inputs)) {
                $dispatcher = $inputs['dispatcher'];
                $identity_document_type_id = $dispatcher['identity_document_type_id'];
                $number = $dispatcher['number'];
                $name = $dispatcher['name'];
                $number_mtc = (isset($dispatcher['number_mtc'])) ? $dispatcher['number_mtc'] : null;

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'number' => $number,
                    'name' => $name,
                    'number_mtc' => $number_mtc,
                ];
            }
        }
        return null;
    }

    private static function driver($inputs)
    {
        // if (($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '02') || $inputs['document_type_id'] === '31') {
        if ($inputs['document_type_id'] === '09' || $inputs['document_type_id'] === '31') {
            if (array_key_exists('driver', $inputs) && $inputs['driver'] != null) {
                $driver = $inputs['driver'];
                $identity_document_type_id = $driver['identity_document_type_id'];
                $number = $driver['number'];
                $name = $driver['name'];
                $license = $driver['license'];
                $telephone = $driver['telephone'];

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'number' => $number,
                    'name' => $name,
                    'license' => $license,
                    'telephone' => $telephone,
                ];
            }
        }

        return null;
    }

    private static function transport($inputs)
    {
        // if (($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '02') || $inputs['document_type_id'] === '31') {
        if ($inputs['document_type_id'] === '09' || $inputs['document_type_id'] === '31') {
            if (array_key_exists('transport', $inputs) && $inputs['transport'] != null) {
                $transport = $inputs['transport'];
                $plate_number = $transport['plate_number'];
                $secondary_plate_number = Functions::valueKeyInArray($transport, 'secondary_plate_number');
                $auth_plate_primary = Functions::valueKeyInArray($transport, 'auth_plate_primary');
                $auth_plate_secondary = Functions::valueKeyInArray($transport, 'auth_plate_secondary');
                $tuc = Functions::valueKeyInArray($transport, 'tuc');
                $tuc_secondary = Functions::valueKeyInArray($transport, 'tuc_secondary');
                $model = $transport['model'];
                $brand = $transport['brand'];

                return [
                    'plate_number' => $plate_number,
                    'model' => $model,
                    'brand' => $brand,
                    'auth_plate_secondary' => $auth_plate_secondary,
                    'auth_plate_primary' => $auth_plate_primary,
                    'secondary_plate_number' => $secondary_plate_number,
                    'tuc' => $tuc,
                    'tuc_secondary' => $tuc_secondary,
                ];
            }
        }

        return null;
    }

    private static function senderData($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (array_key_exists('sender_data', $inputs)) {
                $sender = $inputs['sender_data'];
                $identity_document_type = IdentityDocumentType::query()->find($sender['identity_document_type_id']);
                $identity_document_type_id = $sender['identity_document_type_id'];
                $identity_document_type_description = $identity_document_type->description;
                $number = $sender['number'];
                $name = $sender['name'];

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'identity_document_type_description' => $identity_document_type_description,
                    'number' => $number,
                    'name' => $name,
                ];
            }
        }

        return null;
    }

    private static function receiverData($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (array_key_exists('receiver_data', $inputs)) {
                $receiver = $inputs['receiver_data'];
                $identity_document_type_id = $receiver['identity_document_type_id'];
                $identity_document_type_description = Functions::valueKeyInArray($receiver, 'identity_document_type_description');
                if ($identity_document_type_description == null) {
                    $identity_document_type = IdentityDocumentType::find($identity_document_type_id);
                    if ($identity_document_type) {
                        $identity_document_type_description = $identity_document_type->description;
                    }
                }
                // $identity_document_type_description = $receiver['identity_document_type_description'];
                $number = $receiver['number'];
                $name = $receiver['name'];

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'identity_document_type_description' => $identity_document_type_description,
                    'number' => $number,
                    'name' => $name,
                ];
            }
        }

        return null;
    }

    private static function receiverAddressData($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (array_key_exists('receiver_address_data', $inputs)) {
                $address = $inputs['receiver_address_data'];
                $location_id = $address['location_id'][2];
                $address = $address['address'];

                return [
                    'location_id' => $location_id,
                    'address' => $address
                ];
            }
        }

        return null;
    }

    private static function senderAddressData($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (array_key_exists('sender_address_data', $inputs)) {
                $address = $inputs['sender_address_data'];
                $location_id = $address['location_id'][2];
                $address = $address['address'];

                return [
                    'location_id' => $location_id,
                    'address' => $address
                ];
            }
        }

        return null;
    }

    private static function items($inputs)
    {
        if (array_key_exists('items', $inputs)) {
            $items = [];
            foreach ($inputs['items'] as $row) {
                
                $item = Item::find($row['item_id']);
                $unit_type_id = isset($row['unit_type_id']) ? $row['unit_type_id'] : $item->unit_type_id;
                $weight = isset($row['weight']) ? $row['weight'] : 1;
                $itemDispatch = $row['item'] ?? [];
                $lots = [];
                $row['IdLoteSelected'] = $row['IdLoteSelected'] ?? $itemDispatch['IdLoteSelected'] ?? null;
                $itemReDispatch = isset($itemDispatch['item']) ? $itemDispatch['item'] : $itemDispatch;
                if(count($itemReDispatch) > 0){
                    $lots = isset($itemReDispatch['lots']) ? $itemReDispatch['lots'] : [];


                }
                $temp = [
                    'item_id' => $item->id,
                    'item' => [
                        'lots' => $lots,
                        'description' => $item->description,
                        'model' => $item->model,
                        'item_type_id' => $item->item_type_id,
                        'internal_id' => $item->internal_id,
                        'item_code' => $item->item_code,
                        'item_code_gs1' => $item->item_code_gs1,
                        'unit_type_id' => $unit_type_id,
                        'weight' => $weight,
                        'IdLoteSelected' => $row['IdLoteSelected'] ?? null,
                        'lot_group' => $row['lot_group'] ?? null,
                        'attributes' => $itemDispatch['attributes'] ?? Functions::valueKeyInArray($row, 'attributes')
                    ],
                    'quantity' => $row['quantity'],
                    'name_product_pdf' => Functions::valueKeyInArray($row, 'name_product_pdf'),
                    'additional_data' => Functions::valueKeyInArray($row, 'additional_data'),
                ];

                if (isset($temp['item']['lot_group']['date_of_due'])) {
                    $temp['item']['date_of_due'] = $temp['item']['lot_group']['date_of_due'];
                } else {
                    $temp['item']['date_of_due'] = $itemDispatch['date_of_due'] ?? null;
                }
                $items[] = $temp;
            }
            return $items;
        }
        return null;
    }

    private static function secondary_license_plates($inputs)
    {
        if (array_key_exists('secondary_license_plates', $inputs)) {
            $secondary_license_plates = $inputs['secondary_license_plates'];
            $semitrailer = $secondary_license_plates['semitrailer'];
            return [
                'semitrailer' => $semitrailer,
            ];
        }
        return null;
    }

    private static function getDispatcherId($inputs)
    {
        if ($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '01') {
            //            if (key_exists('dispatcher_id', $inputs)) {
            return $inputs['dispatcher_id'];
            //            }
            //            $dispatcher = $inputs['dispatcher'];
            //            $record = Dispatcher::query()
            //                ->firstOrCreate([
            //                    'identity_document_type_id' => $dispatcher['identity_document_type_id'],
            //                    'number' => $dispatcher['number']
            //                ], [
            //                    'name' => $dispatcher['name'],
            //                    'number_mtc' => $dispatcher['number_mtc'],
            //                    'address' => '-'
            //                ]);
            //
            //            return $record->id;
        }
        return null;
    }

    private static function getDriverId($inputs)
    {
        // if (($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '02') || $inputs['document_type_id'] === '31') {
        if ($inputs['document_type_id'] === '09'  || $inputs['document_type_id'] === '31') {
            //            if (key_exists('driver_id', $inputs)) {
            return $inputs['driver_id'];
            //            }
            //            $driver = $inputs['driver'];
            //            $record = Driver::query()
            //                ->firstOrCreate([
            //                    'identity_document_type_id' => $driver['identity_document_type_id'],
            //                    'number' => $driver['number']
            //                ], [
            //                    'name' => $driver['name'],
            //                    'license' => $driver['license'],
            //                    'telephone' => $driver['telephone']
            //                ]);
            //
            //            return $record->id;
        }
        return null;
    }

    private static function getTransportId($inputs)
    {
        // if (($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '02')  || $inputs['document_type_id'] === '31') {
        if ($inputs['document_type_id'] === '09'   || $inputs['document_type_id'] === '31') {
            //            if (key_exists('transport_id', $inputs)) {
            return $inputs['transport_id'];
            //            }
            //            $transport = $inputs['transport'];
            //            $record = Transport::query()
            //                ->firstOrCreate([
            //                    'plate_number' => $transport['plate_number']
            //                ], [
            //                    'model' => $transport['model'],
            //                    'brand' => $transport['brand']
            //                ]);
            //
            //            return $record->id;
        }
        return null;
    }

    private static function getSenderId($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (key_exists('sender_id', $inputs)) {
                return $inputs['sender_id'];
            }
            //            $sender = $inputs['sender'];
            //            $record = DispatchPerson::query()
            //                ->firstOrCreate([
            //                    'identity_document_type_id' => $sender['identity_document_type_id'],
            //                    'number' => $sender['number'],
            //                ], [
            //                    'name' => $sender['name']
            //                ]);
            //
            //            return $record->id;
        }
        return null;
    }

    private static function getReceiverId($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (key_exists('receiver_id', $inputs)) {
                return $inputs['receiver_id'];
            }
            //            $receiver = $inputs['receiver'];
            //            $record = DispatchPerson::query()
            //                ->firstOrCreate([
            //                    'identity_document_type_id' => $receiver['identity_document_type_id'],
            //                    'number' => $receiver['number'],
            //                ], [
            //                    'name' => $receiver['name']
            //                ]);
            //
            //            return $record->id;
        }
        return null;
    }

    private static function getReceiverAddressId($inputs)
    {
        if ($inputs['document_type_id'] === '31') {
            if (key_exists('receiver_address_id', $inputs)) {
                return $inputs['receiver_address_id'];
            }
            $address = $inputs['receiver_address'];
            $record = DispatchAddress::query()
                ->firstOrCreate([
                    'person_id' => $inputs['receiver_id'],
                    'location_id' => $address['location_id'],
                    'address' => $address['address']
                ]);

            return $record->id;
        }
        return null;
    }

    private static function getSenderAddressId($inputs)
    {
        // dd($inputs);
        if ($inputs['document_type_id'] === '31') {
            if (key_exists('sender_address_id', $inputs)) {
                return $inputs['sender_address_id'];
            }
            $address = $inputs['sender_address'];
            $record = DispatchAddress::query()
                ->firstOrCreate([
                    'person_id' => $inputs['sender_id'],
                    'location_id' => $address['location_id'],
                    'address' => $address['address']
                ]);

            return $record->id;
        }
        return null;
    }

    private static function getTransports($inputs)
    {
        $transports = [];
        if (($inputs['document_type_id'] === '09' && $inputs['transport_mode_type_id'] === '02') || $inputs['document_type_id'] === '31') {
            if(key_exists('transports', $inputs)) {
                return $inputs['transports'];
            }
            if(key_exists('transport_ids', $inputs)) {
                foreach ($inputs['transport_ids'] as $id) {
                    $tr = Transport::query()
                        ->select('id', 'plate_number', 'model', 'brand', 'tuce_number', 'authorization_entity_id', 'authorization_entity_number')
                        ->find($id);
                    $transports[] = [
                        'transport_id' => $id,
                        'transport_data' => $tr->toArray()
                    ];
                }
                return $transports;
            }
        }
        return null;
    }
}
