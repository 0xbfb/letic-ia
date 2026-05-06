<?php

namespace App\Services\Content;

use App\Models\GeneratedPost;
use App\Models\PostVersion;

class PostVersionService
{
    public function createInitialVersion(GeneratedPost $post, ?string $changeSummary = null): PostVersion
    {
        return $this->createVersion($post, $changeSummary ?? 'Versão inicial criada automaticamente.');
    }

    public function createVersionIfChanged(GeneratedPost $post, ?string $changeSummary = null): ?PostVersion
    {
        $latestVersion = $post->postVersions()->latest('version_number')->first();

        if ($latestVersion !== null && ! $this->hasRelevantChanges($post, $latestVersion)) {
            return null;
        }

        return $this->createVersion($post, $changeSummary ?? 'Nova versão criada após edição manual.');
    }

    private function createVersion(GeneratedPost $post, ?string $changeSummary = null): PostVersion
    {
        $nextVersionNumber = ((int) $post->postVersions()->max('version_number')) + 1;

        return $post->postVersions()->create([
            'version_number' => $nextVersionNumber,
            'title' => (string) $post->title,
            'content' => (string) $post->content,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'change_summary' => $changeSummary,
            'created_by' => $post->created_by,
            'created_at' => now(),
        ]);
    }

    private function hasRelevantChanges(GeneratedPost $post, PostVersion $latestVersion): bool
    {
        return $post->title !== $latestVersion->title
            || $post->content !== $latestVersion->content
            || $post->meta_title !== $latestVersion->meta_title
            || $post->meta_description !== $latestVersion->meta_description;
    }
}
