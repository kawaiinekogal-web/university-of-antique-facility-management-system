<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class GuestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()
            ->whereIn('email', ['guest@example.com', 'guest2@example.com'])
            ->update(['is_active' => false]);
    }
}
