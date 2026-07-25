<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorProfile extends Model
{
    protected $table = 'doctor_profiles';

    protected $fillable = [
        'user_id', 'license_number', 'bio', 'practice_since',
        'website', 'verification_date', 'is_accepting_new_patients',
        'is_verified', 'years_of_experience',
    ];

    protected $casts = [
        'verification_date' => 'datetime',
        'is_accepting_new_patients' => 'boolean',
        'is_verified' => 'boolean',
    ];

    protected $visible = [
        'id', 'user_id', 'license_number', 'bio', 'practice_since',
        'website', 'verification_date', 'is_accepting_new_patients',
        'is_verified', 'years_of_experience', 'created_at', 'updated_at',
        'full_name',
    ];

    protected $appends = [
        'full_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function degrees(): HasMany
    {
        return $this->hasMany(Degree::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }
}
