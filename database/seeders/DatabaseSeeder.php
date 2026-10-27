<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // College records are required for the college staff accounts.
        $this->call(CollegeSeeder::class);
        $this->call(SampleUsersSeeder::class);
    }
}
