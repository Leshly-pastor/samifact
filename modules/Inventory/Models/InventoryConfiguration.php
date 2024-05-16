<?php

namespace Modules\Inventory\Models;

use App\Models\Tenant\ModelTenant;
use Illuminate\Database\Eloquent\Builder;


class InventoryConfiguration extends ModelTenant
{

    protected $fillable = [ 
        'order_note_with_stock',
        'confirm_inventory_transaction',
        'stock_control',
        'generate_internal_id',
        'inventory_review',
        'validate_stock_add_item',
    ];
  

    protected $casts = [
        'order_note_with_stock' => 'boolean',
        'confirm_inventory_transaction' => 'boolean',
        'generate_internal_id' => 'boolean',
        'inventory_review' => 'boolean',
        'validate_stock_add_item' => 'boolean',
    ];
    

    /**
     * 
     * Obtener campo individual de la configuracion
     *
     * @param  Builder $query
     * @param  string $column
     * @return Builder
     */
    public function scopeGetRecordIndividualColumn($query, $column)
    {
        return $query->select($column)->firstOrFail()->{$column};
    }


    /**
     *
     * Obtener campos de configuracion para permisos en vista sidebar
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeGetSidebarPermissions($query)
    {
        return $query->select([
                    'inventory_review',
                ])
                ->firstOrFail();
    }

}