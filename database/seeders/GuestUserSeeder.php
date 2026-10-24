<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('password123'),
                'role' => 'guest',
                'organization_name' => 'External Organization',
                'college_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'guest2@example.com'],
            [
                'name' => 'Guest User 2',
                'password' => Hash::make('password123'),
                'role' => 'guest',
                'organization_name' => 'Community Partner',
                'college_id' => null,
            ]
        );
    }
}
