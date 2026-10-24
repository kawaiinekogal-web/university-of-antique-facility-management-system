<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $key = 'login:' . $request->ip();
        $attempts = app('cache')->get($key, 0);

        if ($attempts >= 5) {
            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again later.',
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]) + ['is_active' => true];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            app('cache')->increment($key);
            app('cache')->put($key, app('cache')->get($key, 0), now()->addMinute());

            return back()->withErrors([
                'email' => 'Access denied. Invalid credentials.',
            ])->onlyInput('email');
        }

        app('cache')->forget($key);

        // Each portal card on the landing page only lets its own accounts in
        // (accounts created by a college/org head share that unit's role, so they follow the same rule)
        $portal = (string) $request->input('portal', '');
        if (isset(self::PORTALS[$portal]) && !$this->belongsToPortal(Auth::user(), $portal)) {
            $home = collect(array_keys(self::PORTALS))->first(fn ($p) => $this->belongsToPortal(Auth::user(), $p));

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', ['portal' => $portal])->withErrors([
                'email' => 'This account can\'t sign in to the ' . self::PORTALS[$portal] . '.'
                    . ($home ? ' Please use the ' . self::PORTALS[$home] . ' instead.' : ''),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole(Auth::user());
    }

    /** Portal key (from the landing page cards) => name shown to the user. */
    private const PORTALS = [
        'admin'   => 'Admin Portal',
        'college' => 'College Staff Portal',
        'org'     => 'Org Unit Portal',
    ];

    protected function belongsToPortal($user, string $portal): bool
    {
        return match ($portal) {
            'admin'   => $user->isAdmin(),
            'college' => $user->isCollegeStaff(),
            'org'     => $user->isOrgStaff(),
            default   => false,
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function redirectByRole($user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isCollegeStaff()) {
            return redirect()->route('college.dashboard');
        }

        if ($user->isOrgStaff()) {
            return redirect()->route('org.dashboard');
        }

        // Guests no longer sign in: they request facilities from the public form and track with their ticket code
        if ($user->isGuest()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Guest accounts no longer sign in. You can request a facility from the home page — no account needed.',
            ]);
        }

        // Fallback for invalid roles - log them out
        Auth::logout();
        return redirect()->route('login')->withErrors([
            'email' => 'Invalid user role. Please contact an administrator.',
        ]);
    }
}
