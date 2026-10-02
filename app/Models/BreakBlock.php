<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakBlock extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function interviewDay()
    {
        return $this->belongsTo(InterviewDay::class);
    }

    public function getEndsAtAttribute()
    {
        return \Carbon\Carbon::parse($this->starts_at)
            ->addMinutes($this->duration_minutes)
            ->format('H:i:s');
    }
}
