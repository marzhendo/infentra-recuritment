<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewDay extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'is_published' => 'boolean',
    ];

    public function breakBlocks()
    {
        return $this->hasMany(BreakBlock::class);
    }

    public function interviewSlots()
    {
        return $this->hasMany(InterviewSlot::class);
    }
}
