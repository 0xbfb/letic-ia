<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LlmRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'model',
        'operation',
        'related_type',
        'related_id',
        'status',
        'error',
        'duration_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
