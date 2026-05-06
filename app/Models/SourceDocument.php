<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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



    public function contentBriefs(): BelongsToMany
    {
        return $this->belongsToMany(ContentBrief::class, 'content_brief_source_document')
            ->withTimestamps();
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }


    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_UPLOADED => 'Enviado',
            self::STATUS_EXTRACTING => 'Extraindo texto',
            self::STATUS_EXTRACTED => 'Texto extraído',
            self::STATUS_CHUNKING => 'Gerando chunks',
            self::STATUS_CHUNKED => 'Chunks gerados',
            self::STATUS_EMBEDDING => 'Gerando embeddings',
            self::STATUS_EMBEDDED => 'Pronto para uso',
            self::STATUS_FAILED => 'Falhou',
        ];
    }

    /** @return array<string, string> */
    public static function statusColors(): array
    {
        return [
            'gray' => self::STATUS_UPLOADED,
            'warning' => [self::STATUS_EXTRACTING, self::STATUS_CHUNKING, self::STATUS_EMBEDDING],
            'info' => [self::STATUS_EXTRACTED, self::STATUS_CHUNKED],
            'success' => self::STATUS_EMBEDDED,
            'danger' => self::STATUS_FAILED,
        ];
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
