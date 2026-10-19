<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect($this->landing($request));
    }

    // Abaikan tujuan milik role lain
    private function landing(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! $intended) {
            return '/';
        }

        try {
            $route = Route::getRoutes()->match(Request::create($intended));
        } catch (Throwable) {
            return '/';
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:')
                && ! in_array($request->user()->role, explode(',', substr($middleware, 5)), true)) {
                return '/';
            }
        }

        return $intended;
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'npm' => ['required', 'digits_between:8,20', 'unique:users,npm'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $user = User::create($data + ['prodi' => 'SI', 'angkatan' => 2024]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Selamat datang di COBIT, '.$user->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->user()?->clearLastSeen();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
