<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'is_duplicate',
        'name_override',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => CandidateStatus::class,
            'is_hmif' => 'boolean',
            'is_duplicate' => 'boolean',
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

    public function notes(): HasMany
    {
        return $this->hasMany(CandidateNote::class);
    }
    
    public function getPrimaryScorerIdsAttribute(): array
    {
        $headId = \App\Models\User::where('is_head_interviewer', true)->value('id');
        
        $pilihan1KoorId = \App\Models\User::where('division_id', $this->pilihan_1_id)
            ->where('role', \App\Enums\Role::Koor)
            ->value('id');
            
        $pilihan2KoorId = \App\Models\User::where('division_id', $this->pilihan_2_id)
            ->where('role', \App\Enums\Role::Koor)
            ->value('id');

        return array_filter([$headId, $pilihan1KoorId, $pilihan2KoorId]);
    }

    public function getAveragePrimaryAttribute(): ?float
    {
        if (!$this->slot) return null;
        $primaryIds = $this->primary_scorer_ids;
        if (empty($primaryIds)) return null;

        $avg = \App\Models\Score::where('slot_id', $this->slot->id)
            ->whereIn('interviewer_id', $primaryIds)
            ->avg('value');
            
        return $avg !== null ? (float) round($avg, 2) : null;
    }

    public function getAverageOtherPohAttribute(): ?float
    {
        if (!$this->slot) return null;
        $primaryIds = $this->primary_scorer_ids;

        $query = \App\Models\Score::where('slot_id', $this->slot->id);
        if (!empty($primaryIds)) {
            $query->whereNotIn('interviewer_id', $primaryIds);
        }
        $avg = $query->avg('value');
            
        return $avg !== null ? (float) round($avg, 2) : null;
    }

    public function getAverageAllAttribute(): ?float
    {
        if (!$this->slot) return null;

        $avg = \App\Models\Score::where('slot_id', $this->slot->id)->avg('value');
            
        return $avg !== null ? (float) round($avg, 2) : null;
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if (! empty($attributes['name_override'])) {
                    return preg_replace('/\s+/', ' ', trim($attributes['name_override']));
                }

                $name = $attributes['name'] ?? '';
                $name = preg_replace('/\s+/', ' ', trim($name));

                // Check if all upper or all lower
                if (mb_strtoupper($name) === $name || mb_strtolower($name) === $name) {
                    // Title case it
                    $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

                    // Fix apostrophes and hyphens
                    $name = preg_replace_callback("/(['-])(.)/", function ($m) {
                        return $m[1].mb_strtoupper($m[2]);
                    }, $name);

                    // Fix particles
                    $particles = ['bin', 'binti', 'al'];
                    $words = explode(' ', $name);
                    foreach ($words as &$word) {
                        if (in_array(mb_strtolower($word), $particles)) {
                            $word = mb_strtolower($word);
                        }
                    }
                    $name = implode(' ', $words);
                }

                return $name;
            }
        );
    }
}
