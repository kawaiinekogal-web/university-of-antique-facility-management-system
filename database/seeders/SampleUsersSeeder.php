<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    /**
     * Seed the application's sample users.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        // GSU administrators.
        foreach ([
            ['email' => 'adminA@example.com', 'name' => 'Patricia Mae Salazar'],
            ['email' => 'adminB@example.com', 'name' => 'Ramon Luis Villareal'],
        ] as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => $password,
                    'role' => 'admin',
                    'position' => 'GSU Admin',
                    'college_name' => null,
                    'college_id' => null,
                    'organization_name' => null,
                ]
            );
        }

        // College staff (official test accounts)
        $collegeUsers = [
            'CCIS' => [
                ['email' => 'college@ccis.com', 'name' => 'Maria Luisa Villanueva', 'position' => 'Dean'],
                ['email' => 'staff.ccis@example.com', 'name' => 'Adrian Miguel Cortez', 'position' => 'Program Head'],
            ],
            'CEA' => [
                ['email' => 'college@cea.com', 'name' => 'Daniel Miguel Fortunato', 'position' => 'Dean'],
                ['email' => 'staff.cea@example.com', 'name' => 'Elena Rose Delos Reyes', 'position' => 'Program Head'],
                ['email' => 'adminC@example.com', 'name' => 'Sofia Camille Mendoza', 'position' => 'Faculty Coordinator'],
            ],
            'CMG' => [
                ['email' => 'college@cmg.com', 'name' => 'Andrea Nicole Flores', 'position' => 'Dean'],
                ['email' => 'staff.cmg@example.com', 'name' => 'Gabriel Tomas Mendoza', 'position' => 'Program Head'],
            ],
        ];

        foreach ($collegeUsers as $collegeName => $users) {
            $collegeId = College::where('name', $collegeName)->value('id');

            foreach ($users as $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password' => $password,
                        'role' => 'college_staff',
                        'position' => $user['position'],
                        'college_name' => $collegeName,
                        'college_id' => $collegeId,
                        'organization_name' => null,
                    ]
                );
            }
        }

        // Provincial organization accounts.
        foreach ([
            [
                'email' => 'provincial.ccis@example.com',
                'name' => 'Joshua Miguel Ferrer',
                'organization_name' => 'Provincial CCIS',
            ],
            [
                'email' => 'provincial.psits@example.com',
                'name' => 'Samantha Joy Dela Cruz',
                'organization_name' => 'PSITS',
            ],
        ] as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $password,
                    'role' => 'org_staff',
                    'college_name' => null,
                    'college_id' => null,
                    'organization_name' => $user['organization_name'],
                ]
            );
        }
    }
}
