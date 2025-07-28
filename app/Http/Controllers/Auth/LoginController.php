<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller  // <-- pastikan ini ada
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'npk' => 'required',
            'password' => 'required',
        ]);

        $npk = $request->input('npk');
        $password = $request->input('password');

        $throttleKey = Str::lower($npk) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'npk' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."
            ]);
        }

        if (Auth::attempt(['npk' => $npk, 'password' => $password])) {
            RateLimiter::clear($throttleKey);
            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($throttleKey, 60);
        return back()->withErrors([
            'npk' => 'Password invalid.'
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }

    public function username()
    {
        return 'npk';
    }
}
