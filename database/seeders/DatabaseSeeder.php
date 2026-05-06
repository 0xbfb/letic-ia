<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SourceDocumentSeeder::class,
            ContentBriefSeeder::class,
            GeneratedPostSeeder::class,
        ]);
    }
}
