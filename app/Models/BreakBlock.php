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
}
