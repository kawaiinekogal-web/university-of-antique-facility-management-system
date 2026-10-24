<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Facility;
use App\Policies\FacilityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Facility::class, FacilityPolicy::class);

        // Normalize legacy "booked" status to "reserved"
        try {
            Booking::where('status', 'booked')->update(['status' => 'reserved']);
        } catch (\Exception $e) {
            // ignore
        }

        // Auto-mark reserved requests as completed after end_time passes
        try {
            Booking::where('status', 'reserved')
                ->where('end_time', '<', now())
                ->update(['status' => 'completed']);
        } catch (\Exception $e) {
            // Silent fail - don't break the app on update error
        }

        View::composer('*', function ($view) {
            $user = auth()->user();
            $unreadNotificationsCount = 0;

            if ($user && method_exists($user, 'notifications')) {
                $unreadNotificationsCount = $user->notifications()
                    ->where('is_read', false)
                    ->count();
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
        });
    }
}
  