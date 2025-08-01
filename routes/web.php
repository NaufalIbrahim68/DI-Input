<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DiInputController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DashboardController;

Route::get('/cek-baan', function () {
    $data = DB::table('di_input')
        ->whereNotNull('baan_pn')
        ->orderBy('di_created_date', 'desc')
        ->first();

    
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Register
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);


Route::get('/', function () {
    return view('auth.login');
});
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Dashboard - hanya untuk user yang sudah login

Route::middleware('auth')->group(function () {

    // Dashboard
  Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // DI Input
    Route::get('/DI_Input', [DiInputController::class, 'index'])->name('DI_Input.index');
    Route::get('/DI_Input/create', [DiInputController::class, 'create'])->name('DI_Input.form');
    Route::post('/DI_Input/store', [DiInputController::class, 'store'])->name('DI_Input.store');
    Route::delete('/DI_Input/{id}', [DiInputController::class, 'destroy'])->name('DI_Input.destroy');
    Route::get('/di_input/{id}/edit', [DiInputController::class, 'edit'])->name('di_input.edit');
    Route::put('/di_input/{id}', [DiInputController::class, 'update'])->name('di_input.update');
    Route::post('/di-input/import', [DiInputController::class, 'import'])->name('di-input.import');

    // Delivery (Data Excel DI)
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/import-form', function () {
        return view('deliveries.import');
    })->name('deliveries.import.form');
   Route::get('/deliveries/import', [DeliveryController::class, 'index'])->name('deliveries.import');
    Route::post('/deliveries/import', [DeliveryController::class, 'import'])->name('deliveries.import');
  Route::get('/deliveries/{id}', [DeliveryController::class, 'show'])->name('deliveries.show');
});
