<?php

namespace App\Models;

use App\Enums\DecisionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'division_id',
        'status',
        'note',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DecisionStatus::class,
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
