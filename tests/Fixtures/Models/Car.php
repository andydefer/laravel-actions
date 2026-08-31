<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    protected $table = 'cars';

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'year',
        'color',
        'price',
        'is_available',
    ];

    protected $casts = [
        'year' => 'integer',
        'price' => 'float',
        'is_available' => 'boolean',
    ];

    protected $visible = [
        'id',
        'user_id',
        'brand',
        'model',
        'year',
        'color',
        'price',
        'is_available',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'user_in_other_form',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor that returns the user relation.
     * This is an attribute that will be included in normalization.
     */
    protected function userInOtherForm(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->user;
            }
        );
    }
}
