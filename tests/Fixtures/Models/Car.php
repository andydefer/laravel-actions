<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $table = 'cars';

    protected $fillable = [
        'brand', 'model', 'year', 'color', 'price', 'is_available',
    ];

    protected $casts = [
        'year' => 'integer',
        'price' => 'float',
        'is_available' => 'boolean',
    ];

    protected $visible = [
        'id', 'brand', 'model', 'year', 'color', 'price', 'is_available',
        'created_at', 'updated_at',
    ];
}
