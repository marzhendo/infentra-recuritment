<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'starts_at',
        'ends_at',
        'room',
        'candidate_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'slot_id');
    }
}
