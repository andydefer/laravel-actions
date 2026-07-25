<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'gender', 'slug',
        'languages', 'user_type', 'user_status',
    ];

    protected $casts = [
        'languages' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'full_name',
        'is_active',
        'created_at_formatted',
        'updated_at_formatted',
    ];

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    // Accessor avec suffixe Attribute
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return 'Dr. '.$this->name;
            }
        );
    }

    // Accessor avec suffixe Attribute
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->user_status === 'active';
            }
        );
    }

    // Accessor avec get{Attribute}Attribute (ancienne syntaxe)
    public function getCreatedAtFormattedAttribute(): string
    {
        if ($this->created_at instanceof \DateTime) {
            return $this->created_at->format('Y-m-d\TH:i:sP');
        }

        return (string) $this->created_at;
    }

    // Accessor avec get{Attribute}Attribute (ancienne syntaxe)
    public function getUpdatedAtFormattedAttribute(): string
    {
        if ($this->updated_at instanceof \DateTime) {
            return $this->updated_at->format('Y-m-d\TH:i:sP');
        }

        return (string) $this->updated_at;
    }
}
