<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'jeremiah@crxfarm.local'],
            [
                'name' => 'Jeremiah',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'crxfarm-admin-change-me')),
                'is_admin' => true,
            ]
        );
    }
}
