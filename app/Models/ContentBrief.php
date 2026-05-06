<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentBrief extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY_TO_GENERATE = 'ready_to_generate';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_GENERATED_OUTLINE = 'generated_outline';
    public const STATUS_GENERATED_ARTICLE = 'generated_article';

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

    public function generatedPosts(): HasMany
    {
        return $this->hasMany(GeneratedPost::class);
    }


    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_READY_TO_GENERATE => 'Pronto para gerar',
            self::STATUS_GENERATING => 'Gerando',
            self::STATUS_GENERATED_OUTLINE => 'Outline gerado',
            self::STATUS_GENERATED_ARTICLE => 'Artigo gerado',
        ];
    }

    /** @return array<string, string|array<int,string>> */
    public static function statusColors(): array
    {
        return [
            'gray' => self::STATUS_DRAFT,
            'warning' => self::STATUS_READY_TO_GENERATE,
            'info' => [self::STATUS_GENERATING, self::STATUS_GENERATED_OUTLINE],
            'success' => self::STATUS_GENERATED_ARTICLE,
        ];
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
