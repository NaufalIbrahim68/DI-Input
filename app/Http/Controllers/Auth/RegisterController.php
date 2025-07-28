<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

public function register(Request $request)
{
    // Validasi input, tambahkan 'confirmed' untuk password agar cocok dengan password_confirmation
  $request->validate([
    'name' => 'required|string|max:255',
    'npk' => 'required|string|max:50|unique:users',
    'password' => 'required|string|confirmed|min:6',
]);


    // Simpan data pengguna ke database dengan password yang sudah di-hash
    DB::table('users')->insert([
    'name' => $request->input('name'),
    'npk' => $request->input('npk'),
    'password' => Hash::make($request->input('password')),
    'created_at' => now(),
    'updated_at' => now(),
]);


    // Redirect setelah registrasi berhasil
    return redirect()->route('login')->with('success', 'Registration successful. Please login.');
}
}
