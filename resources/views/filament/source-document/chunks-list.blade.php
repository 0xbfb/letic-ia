<div class="space-y-3">
    @forelse ($chunks as $chunk)
        <div class="rounded-lg border border-gray-200 p-3 text-sm">
            <div class="mb-1 font-semibold">#{{ $chunk->chunk_index }} · {{ $chunk->token_count }} tokens · {{ $chunk->created_at?->format('d/m/Y H:i') }}</div>
            <div class="text-gray-700">{{ \Illuminate\Support\Str::limit($chunk->content, 240) }}</div>
        </div>
    @empty
        <p class="text-sm text-gray-500">Nenhum chunk disponível.</p>
    @endforelse
</div>
