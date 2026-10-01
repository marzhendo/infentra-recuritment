<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewSlot extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
    
    public function interviewDay(): BelongsTo
    {
        return $this->belongsTo(InterviewDay::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'slot_id');
    }
}
