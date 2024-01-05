<?php

namespace Modules\Dispatch\Models;

use App\Models\Tenant\ModelTenant;

class Transport extends ModelTenant
{
    protected $fillable = [
        'model',
        'brand',
        'tuc',
        'plate_number', 'secondary_plate_number',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
