<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressPublication extends Model
{
    use HasFactory;

    public const STATUS_DRAFT_CREATED = 'draft_created';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'generated_post_id',
        'wordpress_post_id',
        'wordpress_url',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
        'published_by',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function generatedPost(): BelongsTo
    {
        return $this->belongsTo(GeneratedPost::class);
    }
}
