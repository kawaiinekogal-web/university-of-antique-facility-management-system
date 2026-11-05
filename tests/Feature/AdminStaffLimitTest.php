<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffLimitTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $email, array $extra = []): array
    {
        return $extra + [
            'name' => 'Jamie Example',
            'last_name' => 'Example',
            'email' => $email,
            'password' => 'Secret#Pass1',
            'password_confirmation' => 'Secret#Pass1',
            'role' => 'admin',
            'admin_access' => 'facility_staff',
        ];
    }

    public function test_admin_team_is_limited_to_five_added_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(4)->create(['role' => 'maintenance_staff']);
        User::factory()->create(['role' => 'admin', 'is_active' => false]); // deactivated: doesn't count

        $this->actingAs($admin)->post(route('admin.users.store'), $this->account('fifth@example.com'))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'fifth@example.com', 'role' => 'facility_staff']);

        $this->actingAs($admin)->from(route('admin.users.create'))
            ->post(route('admin.users.store'), $this->account('sixth@example.com'))
            ->assertRedirect(route('admin.users.create'))->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'sixth@example.com']);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Staff limit reached');

        // College, organization and guest accounts are still unlimited
        $this->actingAs($admin)->post(route('admin.users.store'), $this->account('college@example.com', [
            'role' => 'college_staff', 'college_name' => 'CCIS', 'admin_access' => null,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'college@example.com', 'role' => 'college_staff']);

        // Promoting that college account onto the full admin team is refused too
        $college = User::where('email', 'college@example.com')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.users.update', $college), [
            'name' => $college->name, 'last_name' => 'Example', 'email' => $college->email,
            'role' => 'admin', 'admin_access' => 'full',
        ])->assertSessionHasErrors('role');
        $this->assertSame('college_staff', $college->fresh()->role);
    }
}
