<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_UPLOADED = 'uploaded';

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

    protected static function booted(): void
    {
        static::creating(function (SourceDocument $document): void {
            if (empty($document->status)) {
                $document->status = self::STATUS_UPLOADED;
            }
        });
    }
}
