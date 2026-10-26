<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Example test user (optional; idempotent)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
            ]
        );

        // Seed colleges before facilities, signatories, and users so relationships can be assigned.
        $this->call(CollegeSeeder::class);

        // Official facilities from UA list
        $this->call(FacilitySeeder::class);

        // Placeholder signatories for testing
        $this->call(SignatorySeeder::class);

        // Sample GSU, college, and provincial organization accounts
        $this->call(SampleUsersSeeder::class);

        // Guest users for testing
        $this->call(GuestUserSeeder::class);

        // Requests and bookings across the workflow, including one completed event.
        $this->call(DemoReservationSeeder::class);
    }
}
