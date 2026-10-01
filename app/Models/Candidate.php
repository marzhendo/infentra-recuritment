<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_key',
        'name',
        'nim',
        'whatsapp',
        'angkatan',
        'pilihan_1_id',
        'pilihan_2_id',
        'file_cert_pkkmb',
        'file_cert_wpi',
        'file_cv',
        'file_portfolio',
        'form_timestamp',
        'form_data',
        'status',
        'is_hmif',
    ];

    protected function casts(): array
    {
        return [
            'status' => CandidateStatus::class,
            'is_hmif' => 'boolean',
            'form_timestamp' => 'datetime',
            'form_data' => 'array',
        ];
    }

    public function pilihan1(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'pilihan_1_id');
    }

    public function pilihan2(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'pilihan_2_id');
    }

    public function slot(): HasOne
    {
        return $this->hasOne(InterviewSlot::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function placement(): HasOne
    {
        return $this->hasOne(Placement::class);
    }
}
