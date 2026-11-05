<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Facility;
use App\Models\FormSubmission;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** A college request may only be "Noted by" an active signatory of the requester's own college. */
class CollegeSignatoryScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_own_college_dean_is_saved_but_another_colleges_dean_is_refused(): void
    {
        $mine = College::create(['name' => 'Test College A ' . uniqid()]);
        $other = College::create(['name' => 'Test College B ' . uniqid()]);
        $myDean = Signatory::create(['type' => 'dean', 'name' => 'Dr. Own Dean', 'unit' => $mine->name, 'college_id' => $mine->id, 'is_active' => true]);
        $otherDean = Signatory::create(['type' => 'dean', 'name' => 'Dr. Other Dean', 'unit' => $other->name, 'college_id' => $other->id, 'is_active' => true]);
        $user = User::create([
            'name' => 'Scope Test User', 'last_name' => 'User', 'email' => 'scope-' . uniqid() . '@example.test',
            'password' => Hash::make('Secret#Pass1'), 'role' => 'college_staff', 'college_id' => $mine->id, 'college_name' => $mine->name, 'is_active' => true,
        ]);

        // Another college's dean (e.g. an edited form value): refused, nothing saved
        $this->actingAs($user)->post('/college/requests/facilities', $this->request("dean:{$otherDean->id}"))
            ->assertSessionHasErrors('noted_signatory_id');
        // Right person, wrong type prefix: refused too
        $this->actingAs($user)->post('/college/requests/facilities', $this->request("program_head:{$myDean->id}"))
            ->assertSessionHasErrors('noted_signatory_id');
        $this->assertFalse(FormSubmission::where('requester_id', $user->id)->exists());

        // Own college's dean: saved with that name
        $this->actingAs($user)->post('/college/requests/facilities', $this->request("dean:{$myDean->id}"))->assertSessionHasNoErrors();
        $this->assertSame('Dr. Own Dean', FormSubmission::where('requester_id', $user->id)->latest('id')->firstOrFail()->payload['noted_signatory_name']);
    }

    private function request(string $notedBy): array
    {
        $facility = Facility::create(['name' => 'Scope Hall ' . uniqid(), 'location' => 'Campus', 'owner_type' => 'gsu', 'is_active' => true, 'availability_status' => 'available']);

        return [
            'date_activity' => now()->addDays(40)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'facility_ids' => [$facility->id], 'purpose' => 'Signatory scope test', 'number_of_persons' => 20,
            'noted_signatory_id' => $notedBy,
        ];
    }
}
