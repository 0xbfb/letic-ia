<?php

namespace Database\Factories;

use App\Models\SourceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceDocumentFactory extends Factory
{
    protected $model = SourceDocument::class;

    public function definition(): array
    {
        return [
            'title' => 'Documento '.$this->faker->words(2, true),
            'description' => 'Documento de teste',
            'file_path' => 'documents/'.$this->faker->uuid.'.txt',
            'file_type' => 'txt',
            'source_type' => 'upload',
            'status' => SourceDocument::STATUS_UPLOADED,
        ];
    }
}
