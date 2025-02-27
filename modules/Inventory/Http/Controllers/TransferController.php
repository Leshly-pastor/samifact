<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchItemController;
use App\Models\Tenant\Company;
use App\Models\Tenant\Item;
use App\Models\Tenant\Series;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Exports\InventoryTransferExport;
use Modules\Inventory\Http\Resources\TransferCollection;
use Modules\Inventory\Http\Resources\TransferResource;
use Modules\Inventory\Traits\InventoryTrait;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Inventory\Models\InventoryTransferItem;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\InventoryTransfer;
use Modules\Inventory\Http\Requests\InventoryRequest;
use Modules\Inventory\Http\Requests\TransferRequest;

use Modules\Item\Models\ItemLot;
use Exception;
use Modules\Inventory\Models\InventoryConfiguration;
use Modules\Inventory\Models\InventoryTransferToAccept;
use Modules\Item\Models\ItemLotsGroup;

class TransferController extends Controller
{
    use InventoryTrait;

    public function index()
    {
        $inventory_configuration = InventoryConfiguration::first();
        return view('inventory::transfers.index', compact('inventory_configuration'));
    }

    public function create()
    {
        // $establishment_id = auth()->user()->establishment_id;
        //$current_warehouse = Warehouse::where('establishment_id', $establishment_id)->first();
        return view('inventory::transfers.form');
    }

    public function columns()
    {
        return [
            'created_at' => 'Fecha de emisión',
        ];
    }

    /**
     * It returns a collection of records from the database
     *
     * @param Request request
     *
     * @return A collection of records.
     */
    public function records(Request $request)
    {
        if ($request->column) {
            $records = InventoryTransfer::with(['warehouse', 'warehouse_destination', 'inventory'])->where('created_at', 'like', "%{$request->value}%")->latest();
        } else {
            $records = InventoryTransfer::with(['warehouse', 'warehouse_destination', 'inventory'])->latest();
        }
        //return json_encode( $records );
        /*$records = Inventory::with(['item', 'warehouse', 'warehouse_destination'])
                            ->where('type', 2)
                            ->whereHas('warehouse_destination')
                            ->whereHas('item', function($query) use($request) {
                                $query->where('description', 'like', '%' . $request->value . '%');

                            })
                            ->latest();*/


        return new TransferCollection($records->paginate(config('tenant.items_per_page')));
    }


    public function tables()
    {
        return [
            'inventory_configuration' => InventoryConfiguration::first(),
            //'items' => $this->optionsItemWareHouse(),
            'warehouses' => $this->optionsWarehouse()
        ];
    }

    public function record($id)
    {
        $record = new TransferResource(Inventory::findOrFail($id));

        return $record;
    }




    /* public function store(Request $request)
     {

         $result =  DB::connection('tenant')->transaction(function () use ($request) {

             $id = $request->input('id');
             $item_id = $request->input('item_id');
             $warehouse_id = $request->input('warehouse_id');
             $warehouse_destination_id = $request->input('warehouse_destination_id');
             $stock = $request->input('stock');
             $quantity = $request->input('quantity');
             $detail = $request->input('detail');

             if($warehouse_id === $warehouse_destination_id) {
                 return  [
                     'success' => false,
                     'message' => 'El almacén destino no puede ser igual al de origen'
                 ];
             }
             if($stock < $quantity) {
                 return  [
                     'success' => false,
                     'message' => 'La cantidad a trasladar no puede ser mayor al que se tiene en el almacén.'
                 ];
             }

             $re_it_warehouse = ItemWarehouse::where([['item_id',$item_id],['warehouse_id', $warehouse_destination_id]])->first();

             if(!$re_it_warehouse) {
                 return  [
                     'success' => false,
                     'message' => 'El producto no se encuentra registrado en el almacén destino.'
                 ];
             }


             $inventory = Inventory::findOrFail($id);

             //proccess stock
             $origin_inv_kardex = $inventory->inventory_kardex->first();
             $origin_item_warehouse = ItemWarehouse::where([['item_id',$origin_inv_kardex->item_id],['warehouse_id', $origin_inv_kardex->warehouse_id]])->first();
             $origin_item_warehouse->stock += $inventory->quantity;
             $origin_item_warehouse->stock -= $quantity;
             $origin_item_warehouse->update();


             $destination_inv_kardex = $inventory->inventory_kardex->last();
             $destination_item_warehouse = ItemWarehouse::where([['item_id',$destination_inv_kardex->item_id],['warehouse_id', $destination_inv_kardex->warehouse_id]])->first();
             $destination_item_warehouse->stock -= $inventory->quantity;
             $destination_item_warehouse->update();


             $new_item_warehouse = ItemWarehouse::where([['item_id',$item_id],['warehouse_id', $warehouse_destination_id]])->first();
             $new_item_warehouse->stock += $quantity;
             $new_item_warehouse->update();

             //proccess stock

             //proccess kardex
             $origin_inv_kardex->quantity = -$quantity;
             $origin_inv_kardex->update();

             $destination_inv_kardex->quantity = $quantity;
             $destination_inv_kardex->warehouse_id = $warehouse_destination_id;
             $destination_inv_kardex->update();
             //proccess kardex

             $inventory->warehouse_destination_id = $warehouse_destination_id;
             $inventory->quantity = $quantity;
             $inventory->detail = $detail;


             $inventory->update();

             return  [
                 'success' => true,
                 'message' => 'Traslado actualizado con éxito'
             ];
         });

         return $result;
     }*/


    public function destroy($id)
    {

        DB::connection('tenant')->transaction(function () use ($id) {

            $record = Inventory::findOrFail($id);

            $origin_inv_kardex = $record->inventory_kardex->first();
            $destination_inv_kardex = $record->inventory_kardex->last();

            $destination_item_warehouse = ItemWarehouse::where([['item_id', $destination_inv_kardex->item_id], ['warehouse_id', $destination_inv_kardex->warehouse_id]])->first();
            $destination_item_warehouse->stock -= $record->quantity;
            $destination_item_warehouse->update();

            $origin_item_warehouse = ItemWarehouse::where([['item_id', $origin_inv_kardex->item_id], ['warehouse_id', $origin_inv_kardex->warehouse_id]])->first();
            $origin_item_warehouse->stock += $record->quantity;
            $origin_item_warehouse->update();

            $record->inventory_kardex()->delete();
            $record->delete();
        });


        return [
            'success' => true,
            'message' => 'Traslado eliminado con éxito'
        ];
    }

    public function stock($item_id, $warehouse_id)
    {

        $row = ItemWarehouse::where([['item_id', $item_id], ['warehouse_id', $warehouse_id]])->first();

        return [
            'stock' => ($row) ? $row->stock : 0
        ];
    }


    /**
     *
     * Validar si existe la serie
     *
     * @param  Series $series
     * @param  string $document_type_id
     * @return void
     */
    public function checkIfExistSerie($series, $document_type_id)
    {
        if (is_null($series)) {
            $document_type_description = $this->generalGetDocumentTypeDescription($document_type_id);

            throw new Exception("No se encontró una serie para el tipo de documento {$document_type_id} - {$document_type_description}, registre la serie en Establecimientos/Series");
        }
    }
    public function acceptTransfer($id)
    {
        DB::connection('tenant')->beginTransaction();
        try {
            $this->restoreStockTransfer($id);
            $transfer_to_accept = InventoryTransferToAccept::where('inventory_transfer_id', $id)->get();
            $inventory_transfer = InventoryTransfer::findOrFail($id);
            $warehouse_id = $inventory_transfer->warehouse_id;
            $warehouse_destination_id = $inventory_transfer->warehouse_destination_id;
            foreach ($transfer_to_accept as $it) {
                $inventory = new Inventory();
                $inventory->type = 2;
                $inventory->description = 'Traslado';
                $inventory->item_id = $it->item_id;
                $inventory->warehouse_id = $warehouse_id;
                $inventory->warehouse_destination_id = $warehouse_destination_id;
                $inventory->quantity = $it->quantity;
                $inventory->inventories_transfer_id = $id;
                $inventory->save();
                $series_lots = json_decode($it->series_lots, true);

                if (isset($series_lots['lots'])) {
                    foreach ($series_lots['lots'] as $lot) {
                        if ($lot['has_sale']) {
                            $item_lot = ItemLot::findOrFail($lot['id']);
                            $item_lot->warehouse_id = $inventory->warehouse_destination_id;
                            $item_lot->update();

                            // historico de item para traslado
                            InventoryTransferItem::query()->create([
                                'inventory_transfer_id' => $id,
                                'item_lot_id' => $lot['id'],
                            ]);
                        }
                    }
                }

                if (isset($series_lots['lot_groups'])) {
                    foreach ($series_lots['lot_groups'] as $lot) {
                        InventoryTransferItem::query()->create([
                            'inventory_transfer_id' => $id,
                            'item_lots_group_id' => $lot['id'],
                        ]);
                    }
                }
            }

            $inventory_transfer->state = 2;
            $inventory_transfer->update();
            InventoryTransferToAccept::where('inventory_transfer_id', $id)->delete();
            DB::connection('tenant')->commit();
            return [
                'success' => true,
                'message' => 'Traslado aceptado con éxito'
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    public function rejectTransfer($id)
    {
        // $transfer_to_accept = InventoryTransferToAccept::where('inventory_transfer_id', $id)->get();
        $this->restoreStockTransfer($id);
        InventoryTransferToAccept::where('inventory_transfer_id', $id)->delete();
        $inventory_transfer = InventoryTransfer::findOrFail($id);
        $inventory_transfer->state = 3;
        $inventory_transfer->update();
        return [
            'success' => true,
            'message' => 'Traslado rechazado con éxito'
        ];
    }

    function restoreStockTransfer($id)
    {
        $transfers_to_accept = InventoryTransferToAccept::where('inventory_transfer_id', $id)->get();
        foreach ($transfers_to_accept as $transfer_to_accept) {
            $item_id = $transfer_to_accept->item_id;
            $series_lots = json_decode($transfer_to_accept->series_lots, true);
            $item = Item::findOrFail($item_id);
            $item->stock += $transfer_to_accept->quantity;
            $item->update();
            $item_warehouse = ItemWarehouse::where([['item_id', $item_id], ['warehouse_id', $transfer_to_accept->inventory_transfer->warehouse_id]])->first();
            $item_warehouse->stock += $transfer_to_accept->quantity;
            $item_warehouse->update();

            if (isset($series_lots['lots'])) {
                foreach ($series_lots['lots'] as $lot) {
                    $item_lot = ItemLot::findOrFail($lot['id']);
                    $item_lot->has_sale = 0;
                    $item_lot->update();
                }
            }

            if (isset($series_lots['lot_groups'])) {
                foreach ($series_lots['lot_groups'] as $lot_group) {
                    $item_lots_group = ItemLotsGroup::findOrFail($lot_group['id']);
                    $item_lots_group->quantity += $lot_group['compromise_quantity'];
                    $item_lots_group->update();
                }
            }
        }
    }
    public function storeToAccept(Request $request)
    {
        DB::connection('tenant')->beginTransaction();
        try {
            $document_type_id = 'U4';
            $warehouse_id = $request->input('warehouse_id');

            $warehouse = Warehouse::query()
                ->select('id', 'establishment_id')
                ->where('id', $warehouse_id)
                ->first();

            $series = Series::query()
                ->select('number')
                ->where('establishment_id', $warehouse->establishment_id)
                ->where('document_type_id', 'U4')
                ->first();

            $this->checkIfExistSerie($series, $document_type_id);

            $row = InventoryTransfer::query()
                ->create([
                    'description' => $request->description,
                    'warehouse_id' => $request->warehouse_id,
                    'warehouse_destination_id' => $request->warehouse_destination_id,
                    'quantity' => count($request->items),
                    'document_type_id' => $document_type_id,
                    'series' => $series->number,
                    'number' => '#',
                    'state' => 1,
                ]);

            foreach ($request->items as $it) {
                $inventoryToAccept = new InventoryTransferToAccept();
                $inventoryToAccept->inventory_transfer_id = $row->id;
                $inventoryToAccept->item_id = $it['id'];
                $inventoryToAccept->quantity = $it['quantity'];
                $series_lots = ['lots' =>  $it['lots']];
                foreach ($it['lots'] as $lot) {
                    $item_lot = ItemLot::findOrFail($lot['id']);
                    $item_lot->has_sale = 1;
                    $item_lot->update();
                }
                $lot_groups = $this->searchLotGroup($request->lot_groups_total, $it['id']);
                if ($lot_groups) {
                    $series_lots['lot_groups'] = $lot_groups;
                }
                $inventoryToAccept->series_lots = json_encode($series_lots);
                $inventoryToAccept->save();
                $this->restStock($it['id'], $warehouse_id, $it['quantity']);
            }


            DB::connection('tenant')->commit();

            return [
                'success' => true,
                'message' => 'Traslado por aceptar creado con éxito'
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    function searchLotGroup($lot_groups, $item_id)
    {
        $lot_group_result = [];
        foreach ($lot_groups as $lot_group) {
            $lote_group_id = $lot_group['id'];
            $item_lots_group = ItemLotsGroup::findOrFail($lote_group_id);
            $lot_group_item_id = $item_lots_group->item_id;
            if ($lot_group_item_id == $item_id) {
                $quantity = $lot_group['compromise_quantity'];
                $item_lots_group->quantity -= $quantity;
                $item_lots_group->update();
                $lot_group_result[] = $lot_group;
            }
        }
        if (count($lot_group_result) == 0) {
            return null;
        }
        return  $lot_group_result;
    }
    function restStock($item_id, $warehouse_id, $quantity)
    {
        $row = ItemWarehouse::where([['item_id', $item_id], ['warehouse_id', $warehouse_id]])->first();
        $row->stock -= $quantity;
        $row->update();
        $item = Item::findOrFail($item_id);
        $item->stock -= $quantity;
        $item->update();
    }
    public function store(TransferRequest $request)
    {
        DB::connection('tenant')->beginTransaction();
        try {
            $document_type_id = 'U4';
            $warehouse_id = $request->input('warehouse_id');

            $warehouse = Warehouse::query()
                ->select('id', 'establishment_id')
                ->where('id', $warehouse_id)
                ->first();

            $series = Series::query()
                ->select('number')
                ->where('establishment_id', $warehouse->establishment_id)
                ->where('document_type_id', 'U4')
                ->first();

            $this->checkIfExistSerie($series, $document_type_id);

            $row = InventoryTransfer::query()
                ->create([
                    'description' => $request->description,
                    'warehouse_id' => $request->warehouse_id,
                    'warehouse_destination_id' => $request->warehouse_destination_id,
                    'quantity' => count($request->items),
                    'document_type_id' => $document_type_id,
                    'series' => $series->number,
                    'number' => '#',
                ]);

            foreach ($request->items as $it) {
                $inventory = new Inventory();
                $inventory->type = 2;
                $inventory->description = 'Traslado';
                $inventory->item_id = $it['id'];
                $inventory->warehouse_id = $request->warehouse_id;
                $inventory->warehouse_destination_id = $request->warehouse_destination_id;
                $inventory->quantity = $it['quantity'];
                $inventory->inventories_transfer_id = $row->id;
                $inventory->save();

                foreach ($it['lots'] as $lot) {
                    if ($lot['has_sale']) {
                        $item_lot = ItemLot::findOrFail($lot['id']);
                        $item_lot->warehouse_id = $inventory->warehouse_destination_id;
                        $item_lot->update();

                        // historico de item para traslado
                        InventoryTransferItem::query()->create([
                            'inventory_transfer_id' => $row->id,
                            'item_lot_id' => $lot['id'],
                        ]);
                    }
                }
            }

            if ($request->lot_groups_total) {
                foreach ($request->lot_groups_total as $lot) {
                    InventoryTransferItem::query()->create([
                        'inventory_transfer_id' => $row->id,
                        'item_lots_group_id' => $lot['id'],
                    ]);
                }
            }

            DB::connection('tenant')->commit();

            return [
                'success' => true,
                'message' => 'Traslado creado con éxito'
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function searchItems(Request $request)
    {
        $items = SearchItemController::getItemToTrasferWithSearch($request);
        return compact('items');
    }

    public function items($warehouse_id)
    {
        return ['items' => SearchItemController::getItemToTrasferWithoutSearch($warehouse_id)];
        return [
            'items' => $this->optionsItemWareHousexId($warehouse_id),
        ];
    }


    /**
     * No se implementa
     *
     * @param \Modules\Inventory\Models\InventoryTransfer $inventoryTransfer
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function excel(InventoryTransfer $inventoryTransfer)
    {
        return null;
        $export = new InventoryTransferExport();
        $export->setInventory($inventoryTransfer);
        return $export->download('Reporte_Traslado_' . $inventoryTransfer->id . '_' . date('YmdHis') . '.xlsx');
    }

    public function getInventoryTransferData(InventoryTransfer $inventoryTransfer)
    {
        return null;
        // return $this->excel(($inventoryTransfer));
        $data = $inventoryTransfer->getPdfData();
        $pdf = PDF::loadView('inventory::transfers.export.pdf', compact('data'));
        $pdf->setPaper('A4', 'landscape');
        $filename = 'Reporte_Traslado_' . $inventoryTransfer->id . '_' . date('YmdHis');
        return $pdf->download($filename . '.pdf');
    }


    /**
     * Genera un pdf para nota de traslado
     *
     * @param \Modules\Inventory\Models\InventoryTransfer $inventoryTransfer
     *
     * @return \Illuminate\Http\Response
     */
    public function getPdf(InventoryTransfer $inventoryTransfer): \Illuminate\Http\Response
    {
        $data = $inventoryTransfer->getPdfData();
        // dd($inventoryTransfer->getPdfData());
        // return View('inventory::transfers.export.pdf', compact('data'));
        $pdf = PDF::loadView('inventory::transfers.export.pdf', compact('data'));
        $pdf->setPaper('A4', 'portrait');
        // $pdf->setPaper('A4', 'landscape');
        $filename = 'Reporte_Traslado_' . $inventoryTransfer->id . '_' . date('YmdHis');

        return $pdf->stream($filename . '.pdf');
    }
}
