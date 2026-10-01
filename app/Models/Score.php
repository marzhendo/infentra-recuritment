<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'interviewer_id',
        'rubric_aspect_id',
        'value',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(InterviewSlot::class, 'slot_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(RubricAspect::class, 'rubric_aspect_id');
    }
}
