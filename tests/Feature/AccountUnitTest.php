<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** College / org requests are filed under the account's unit name: it must exist, and a missing one must not crash. */
class AccountUnitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_org_account_without_an_organization_name_gets_a_clear_message_instead_of_an_error(): void
    {
        $user = $this->user('org_staff', ['organization_name' => null]);

        $this->actingAs($user)->post('/org/requests/facilities', $this->orgRequest())
            ->assertSessionHasErrors(['account_unit' => "Your account has no organization name yet, so this request can't be filed. Ask the GSU administrator to add your organization to your account."]);
        $this->assertFalse(FormSubmission::where('requester_id', $user->id)->exists());
    }

    public function test_org_account_with_an_organization_name_can_submit(): void
    {
        $user = $this->user('org_staff', ['organization_name' => 'PSITS']);

        $this->actingAs($user)->post('/org/requests/facilities', $this->orgRequest())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('PSITS', FormSubmission::where('requester_id', $user->id)->firstOrFail()->requester_unit);
    }

    public function test_college_account_without_a_college_name_gets_a_clear_message(): void
    {
        $user = $this->user('college_staff', ['college_name' => null]);

        $this->actingAs($user)->post('/college/requests/facilities', $this->baseRequest())
            ->assertSessionHasErrors(['account_unit' => "Your account has no college name yet, so this request can't be filed. Ask the GSU administrator to add your college to your account."]);
    }

    public function test_admin_cannot_create_or_save_a_college_or_org_account_without_its_unit(): void
    {
        $admin = $this->user('admin');
        $account = fn (string $role, array $extra = []) => $extra + [
            'name' => 'Jamie Example', 'last_name' => 'Example', 'email' => 'unit-' . uniqid() . '@example.test',
            'password' => 'Secret#Pass1', 'password_confirmation' => 'Secret#Pass1', 'role' => $role,
        ];

        $this->actingAs($admin)->post(route('admin.users.store'), $account('college_staff'))
            ->assertSessionHasErrors(['college_name' => 'Enter the college name. College staff requests are filed under it.']);
        $this->actingAs($admin)->post(route('admin.users.store'), $account('org_staff'))
            ->assertSessionHasErrors(['organization_name' => 'Enter the organization name. Organization requests are filed under it.']);

        $org = $this->user('org_staff', ['organization_name' => 'PSITS']);
        $this->actingAs($admin)->put(route('admin.users.update', $org), [
            'name' => $org->name, 'last_name' => 'Example', 'email' => $org->email, 'role' => 'org_staff', 'organization_name' => '',
        ])->assertSessionHasErrors('organization_name');
        $this->assertSame('PSITS', $org->fresh()->organization_name);
    }

    private function user(string $role, array $extra = []): User
    {
        return User::create($extra + [
            'name' => 'Unit Test User', 'last_name' => 'Example', 'email' => 'unit-user-' . uniqid() . '@example.test',
            'password' => Hash::make('Secret#Pass1'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function baseRequest(): array
    {
        $facility = Facility::create(['name' => 'Unit Test Hall ' . uniqid(), 'location' => 'Campus', 'owner_type' => 'gsu', 'is_active' => true, 'availability_status' => 'available']);

        return [
            'date_activity' => now()->addDays(40)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'facility_ids' => [$facility->id], 'purpose' => 'Unit test request', 'number_of_persons' => 20,
        ];
    }

    private function orgRequest(): array
    {
        return $this->baseRequest() + ['noted_signatory_id' => 'dean', 'noted_signatory_name' => 'Dr. Maria Santos'];
    }
}
