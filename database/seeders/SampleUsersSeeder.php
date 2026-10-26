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
            ['email' => 'adminA@example.com', 'name' => 'Patricia Mae Salazar', 'last_name' => 'Salazar'],
            ['email' => 'adminB@example.com', 'name' => 'Ramon Luis Villareal', 'last_name' => 'Villareal'],
        ] as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'last_name' => $admin['last_name'],
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
                ['email' => 'college@ccis.com', 'name' => 'Maria Luisa Villanueva', 'last_name' => 'Villanueva', 'position' => 'Dean'],
                ['email' => 'staff.ccis@example.com', 'name' => 'Adrian Miguel Cortez', 'last_name' => 'Cortez', 'position' => 'Program Head'],
            ],
            'CEA' => [
                ['email' => 'college@cea.com', 'name' => 'Daniel Miguel Fortunato', 'last_name' => 'Fortunato', 'position' => 'Dean'],
                ['email' => 'staff.cea@example.com', 'name' => 'Elena Rose Delos Reyes', 'last_name' => 'Delos Reyes', 'position' => 'Program Head'],
                ['email' => 'adminC@example.com', 'name' => 'Sofia Camille Mendoza', 'last_name' => 'Mendoza', 'position' => 'Faculty Coordinator'],
            ],
            'CMG' => [
                ['email' => 'college@cmg.com', 'name' => 'Andrea Nicole Flores', 'last_name' => 'Flores', 'position' => 'Dean'],
                ['email' => 'staff.cmg@example.com', 'name' => 'Gabriel Tomas Mendoza', 'last_name' => 'Mendoza', 'position' => 'Program Head'],
            ],
        ];

        foreach ($collegeUsers as $collegeName => $users) {
            $collegeId = College::where('name', $collegeName)->value('id');

            foreach ($users as $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'last_name' => $user['last_name'],
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
                'last_name' => 'Ferrer',
                'organization_name' => 'Provincial CCIS',
            ],
            [
                'email' => 'provincial.psits@example.com',
                'name' => 'Samantha Joy Dela Cruz',
                'last_name' => 'Dela Cruz',
                'organization_name' => 'PSITS',
            ],
        ] as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'last_name' => $user['last_name'],
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
