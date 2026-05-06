<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentBrief extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY_TO_GENERATE = 'ready_to_generate';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_GENERATED_OUTLINE = 'generated_outline';

    protected $fillable = [
        'title',
        'content_type',
        'main_keyword',
        'secondary_keywords',
        'target_audience',
        'search_intent',
        'business_objective',
        'tone_of_voice',
        'cta_goal',
        'minimum_words',
        'maximum_words',
        'mandatory_sources',
        'metadata',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'secondary_keywords' => 'array',
        'mandatory_sources' => 'array',
        'metadata' => 'array',
    ];

    public function sourceDocuments(): BelongsToMany
    {
        return $this->belongsToMany(SourceDocument::class, 'content_brief_source_document')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (ContentBrief $contentBrief): void {
            if (empty($contentBrief->status)) {
                $contentBrief->status = self::STATUS_DRAFT;
            }
        });
    }
}
