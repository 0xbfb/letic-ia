<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_post_id',
        'score',
        'checks_json',
        'warnings_json',
        'errors_json',
    ];

    protected $casts = [
        'checks_json' => 'array',
        'warnings_json' => 'array',
        'errors_json' => 'array',
    ];

    public function generatedPost(): BelongsTo
    {
        return $this->belongsTo(GeneratedPost::class);
    }
}
