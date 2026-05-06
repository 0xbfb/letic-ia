<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_EXTRACTING = 'extracting';
    public const STATUS_EXTRACTED = 'extracted';
    public const STATUS_CHUNKING = 'chunking';
    public const STATUS_CHUNKED = 'chunked';
    public const STATUS_EMBEDDING = 'embedding';
    public const STATUS_EMBEDDED = 'embedded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_type',
        'source_type',
        'status',
        'extracted_text_path',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];


    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }

    protected static function booted(): void
    {
        static::creating(function (SourceDocument $document): void {
            if (empty($document->status)) {
                $document->status = self::STATUS_UPLOADED;
            }
        });
    }
}
