<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    protected $fillable = [
        'content_brief_id',
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'excerpt',
        'content',
        'faq_json',
        'cta_json',
        'metadata',
        'status',
        'seo_score',
        'readability_score',
        'tone_score',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'faq_json' => 'array',
        'cta_json' => 'array',
        'metadata' => 'array',
    ];

    public function contentBrief(): BelongsTo
    {
        return $this->belongsTo(ContentBrief::class);
    }

    public function postVersions(): HasMany
    {
        return $this->hasMany(PostVersion::class)->orderByDesc('version_number');
    }

    public function seoAudits(): HasMany
    {
        return $this->hasMany(SeoAudit::class)->latest();
    }

    public function latestSeoAudit(): HasOne
    {
        return $this->hasOne(SeoAudit::class)->latestOfMany();
    }
}
