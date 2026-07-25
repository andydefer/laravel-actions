<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $table = 'experiences';

    protected $fillable = [
        'doctor_profile_id', 'position', 'institution', 'start_date',
        'end_date', 'is_current', 'description', 'is_verified',
        'achievements', 'reference_contacts',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_current' => 'boolean',
        'is_verified' => 'boolean',
        'achievements' => 'array',
        'reference_contacts' => 'array',
    ];

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }
}
