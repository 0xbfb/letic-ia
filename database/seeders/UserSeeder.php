<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('seeders.admin_email', env('DEV_ADMIN_EMAIL', 'admin.local@leticia-seo.test'));
        $name = config('seeders.admin_name', env('DEV_ADMIN_NAME', 'Admin Local'));
        $password = config('seeders.admin_password', env('DEV_ADMIN_PASSWORD', 'dev-only-change-me'));

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
