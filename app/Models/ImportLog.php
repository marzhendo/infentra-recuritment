<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'imported_at',
        'created_count',
        'updated_count',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'created_count' => 'integer',
            'updated_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
