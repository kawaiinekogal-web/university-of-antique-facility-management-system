<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'legacy_email' => 'adminA@example.com',
                'name' => 'Alicia Mae Navarro',
                'last_name' => 'Navarro',
                'email' => 'navarroalicia@gsu.com',
                'role' => 'admin',
                'position' => 'GSU Administrator',
            ],
            [
                'legacy_email' => 'adminB@example.com',
                'name' => 'Rafael Anton Villanueva',
                'last_name' => 'Villanueva',
                'email' => 'villanuevarafael@gsu.com',
                'role' => 'admin',
                'position' => 'GSU Administrator',
            ],
            [
                'legacy_email' => 'adminC@example.com',
                'name' => 'Carmela Joy Delos Reyes',
                'last_name' => 'Delos Reyes',
                'email' => 'delosreyescarmela@gsu.com',
                'role' => 'admin',
                'position' => 'GSU Administrator',
            ],
            [
                'legacy_email' => 'provincial.psits@example.com',
                'name' => 'Miguel Andres Dela Cruz',
                'last_name' => 'Dela Cruz',
                'email' => 'delacruzmiguel@psits.com',
                'role' => 'org_staff',
                'position' => 'Organization Representative',
                'organization_name' => 'PSITS',
            ],
            [
                'legacy_email' => 'college@cmg.com',
                'name' => 'Elena Sofia Marquez',
                'last_name' => 'Marquez',
                'email' => 'marquezelena@cmg.com',
                'role' => 'college_staff',
                'position' => 'College Representative',
                'college_name' => 'CMG',
            ],
            [
                'legacy_email' => 'college@ccis.com',
                'name' => 'Noah Gabriel Alvarado',
                'last_name' => 'Alvarado',
                'email' => 'alvaradonoah@ccis.com',
                'role' => 'college_staff',
                'position' => 'College Representative',
                'college_name' => 'CCIS',
            ],
            [
                'legacy_email' => 'college@cea.com',
                'name' => 'Isabella Mae Ledesma',
                'last_name' => 'Ledesma',
                'email' => 'ledesmaisabella@cea.com',
                'role' => 'college_staff',
                'position' => 'College Representative',
                'college_name' => 'CEA',
            ],
        ];

        $retiredSeedEmails = [
            'test@example.com',
            'staff.ccis@example.com',
            'staff.cea@example.com',
            'staff.cmg@example.com',
            'provincial.ccis@example.com',
            'guest@example.com',
            'guest2@example.com',
        ];

        User::query()->whereIn('email', $retiredSeedEmails)->update(['is_active' => false]);

        foreach ($accounts as $account) {
            $legacyEmail = $account['legacy_email'];
            unset($account['legacy_email']);

            $password = 'Ua!' . bin2hex(random_bytes(16)) . 'aA';
            $collegeName = $account['college_name'] ?? null;

            $user = User::query()->where('email', $legacyEmail)->first()
                ?? User::query()->where('email', $account['email'])->first()
                ?? new User();

            $user->fill([
                ...$account,
                'password' => Hash::make($password),
                'college_id' => $collegeName
                    ? College::query()->where('name', $collegeName)->value('id')
                    : null,
                'college_name' => $collegeName,
                'organization_name' => $account['organization_name'] ?? null,
            ]);
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            $this->command?->line($account['email'] . '  ' . $password);
        }

        $this->command?->warn('Generated passwords are shown once above. Store them in a password manager.');
    }
}
