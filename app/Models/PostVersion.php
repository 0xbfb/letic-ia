<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'generated_post_id',
        'version_number',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'change_summary',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function generatedPost(): BelongsTo
    {
        return $this->belongsTo(GeneratedPost::class);
    }
}
