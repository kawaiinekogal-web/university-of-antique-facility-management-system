<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecordTrendTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** On the 31st, stepping back from "today" used to repeat some months and skip others. */
    public function test_twelve_month_trend_lists_each_month_once_on_the_last_day_of_a_month(): void
    {
        Carbon::setTestNow('2026-10-31 15:00:00');
        $admin = User::create([
            'name' => 'Trend Admin', 'email' => 'trend-admin@example.test', 'role' => 'admin',
            'password' => Hash::make('password'), 'is_active' => true,
        ]);

        $trend = $this->actingAs($admin)->get('/admin/monthly-records')->assertOk()->viewData('monthlyTrend');

        $this->assertSame([
            'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026',
            'May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026',
        ], array_column($trend, 'month'));
    }

    public function test_report_trend_follows_the_selected_period_and_occupancy_uses_operating_hours(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        $admin = $this->admin();

        $view = $this->actingAs($admin)->get('/admin/monthly-records?period=weekly&date=2026-12-17')->assertOk();
        $this->assertSame('Dec 2026', last($view->viewData('monthlyTrend'))['month']); // not today's month (Nov)
        $this->assertSame('Jan 2026 – Dec 2026', $view->viewData('trendLabel'));
        $this->assertSame(91, $view->viewData('calendarHours')); // 7 days × 13 operating hours, not 168
    }

    public function test_week_crossing_new_year_shows_both_years_and_year_list_includes_the_viewed_year(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/monthly-records?period=weekly&date=2025-12-31')
            ->assertOk()->assertSee('Dec 29, 2025 – Jan 04, 2026');
        $this->actingAs($admin)->get('/admin/monthly-records?period=monthly&month=12&year=2023')
            ->assertOk()->assertSee('<option value="2023" selected', false);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Trend Admin', 'email' => 'trend-admin-' . uniqid() . '@example.test', 'role' => 'admin',
            'password' => Hash::make('password'), 'is_active' => true,
        ]);
    }
}
