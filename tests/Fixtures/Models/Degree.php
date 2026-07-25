<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Degree extends Model
{
    protected $table = 'degrees';

    protected $fillable = [
        'doctor_profile_id', 'title', 'institution', 'field_of_study',
        'year', 'country', 'city', 'is_verified',
        'verification_document_path', 'description',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }
}
