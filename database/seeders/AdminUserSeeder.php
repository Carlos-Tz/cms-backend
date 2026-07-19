<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or update) the single administrator account.
     *
     * Idempotent: safe to run on every deploy. Credentials come from
     * ADMIN_EMAIL / ADMIN_PASSWORD, with local defaults as a fallback.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'password' => config('admin.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
