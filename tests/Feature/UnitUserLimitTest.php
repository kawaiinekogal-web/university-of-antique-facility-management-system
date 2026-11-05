<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitUserLimitTest extends TestCase
{
    use RefreshDatabase;

    private function newAccount(string $email): array
    {
        $password = 'Secret#Pass1';

        return [
            'name' => 'Jamie Example',
            'last_name' => 'Example',
            'position' => 'Secretary',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    public function test_org_head_can_add_only_two_active_accounts(): void
    {
        $head = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Student Council', 'position' => 'Governor']);
        // Another organization's accounts don't use up this one's slots
        User::factory()->count(3)->create(['role' => 'org_staff', 'organization_name' => 'Other Org']);

        foreach (['one@example.com', 'two@example.com'] as $email) {
            $this->actingAs($head)->post(route('org.users.store'), $this->newAccount($email))
                ->assertRedirect(route('org.users.index'))->assertSessionHas('status');
        }

        $this->actingAs($head)->post(route('org.users.store'), $this->newAccount('three@example.com'))
            ->assertRedirect(route('org.users.index'))->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'three@example.com']);
        $this->actingAs($head)->get(route('org.users.create'))->assertRedirect(route('org.users.index'));
        $this->actingAs($head)->get(route('org.users.index'))->assertOk()->assertSee('Limit reached');

        // Deactivating an account frees its slot
        User::where('email', 'one@example.com')->update(['is_active' => false]);
        $this->actingAs($head)->post(route('org.users.store'), $this->newAccount('three@example.com'))
            ->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'three@example.com', 'organization_name' => 'Student Council']);
    }

    public function test_head_can_deactivate_and_activate_accounts_within_the_limit(): void
    {
        $head = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Student Council', 'position' => 'Governor']);
        $member = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Student Council']);
        $other = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Student Council']);
        $outsider = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Other Org', 'is_active' => false]);

        $this->actingAs($head)->patch(route('org.users.deactivate', $member))->assertSessionHas('status');
        $this->assertFalse($member->fresh()->is_active);
        $this->actingAs($head)->get(route('org.users.index'))->assertSee(route('org.users.activate', $member));

        // Slot taken by a new account while deactivated: activation is refused
        $third = User::factory()->create(['role' => 'org_staff', 'organization_name' => 'Student Council']);
        $this->actingAs($head)->patch(route('org.users.activate', $member))->assertSessionHas('error');
        $this->assertFalse($member->fresh()->is_active);

        $third->forceFill(['is_active' => false])->save();
        $this->actingAs($head)->patch(route('org.users.activate', $member))->assertSessionHas('status');
        $this->assertTrue($member->fresh()->is_active);

        // Non-heads can't activate, and other organizations' accounts are out of reach
        $this->actingAs($other)->patch(route('org.users.activate', $third))->assertForbidden();
        $this->actingAs($head)->patch(route('org.users.activate', $outsider))->assertNotFound();
    }

    public function test_college_head_is_limited_to_two_added_accounts(): void
    {
        $head = User::factory()->create(['role' => 'college_staff', 'college_name' => 'CCIS']);
        User::factory()->count(2)->create(['role' => 'college_staff', 'college_name' => 'CCIS']);

        $this->actingAs($head)->post(route('college.users.store'), $this->newAccount('extra@example.com'))
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'extra@example.com']);
    }
}
