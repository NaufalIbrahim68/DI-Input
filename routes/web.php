<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiInputController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DnController;
use App\Http\Controllers\DsInputController;

// ========================================
// 🔐 AUTH ROUTES
// ========================================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/', function () {
    return view('auth.login');
});

// ========================================
// 🔒 PROTECTED ROUTES (REQUIRES LOGIN)
// ========================================
Route::middleware('auth')->group(function () {

    // ===============================
    // 📊 DASHBOARD
    // ===============================
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // ===============================
    // 📦 DI INPUT
    // ===============================
    Route::prefix('DI_Input')->name('DI_Input.')->group(function () {
        Route::get('/', [DiInputController::class, 'index'])->name('index');
        Route::get('/create', [DiInputController::class, 'create'])->name('form');
        Route::post('/store', [DiInputController::class, 'store'])->name('store');
        Route::delete('/{id}', [DiInputController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/edit', [DiInputController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DiInputController::class, 'update'])->name('update');
        Route::post('/import', [DiInputController::class, 'import'])->name('import');
    });

    // ===============================
    // 📤 DELIVERY (Import Excel DI + DS)
    // ===============================
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('/import-form', function () {
            return view('DI_Input.import');
        })->name('import.form');
        Route::get('/import', [DeliveryController::class, 'index'])->name('import.index');
        Route::post('/import', [DeliveryController::class, 'import'])->name('import.submit');
        Route::get('/{id}', [DeliveryController::class, 'show'])->name('show');
    });

    // Route POST tanpa prefix
    Route::post('/di-input/import', [DeliveryController::class, 'import'])->name('delivery.import');

    // ===============================
    // 🗓️ DS INPUT (Delivery Schedule)
    // ===============================
    Route::prefix('ds-input')->name('ds_input.')->group(function () {
        Route::get('/', [DsInputController::class, 'index'])->name('index');
        Route::post('/import', [DsInputController::class, 'import'])->name('import');
        Route::post('/generate', [DsInputController::class, 'generateFromDate'])->name('generate');
        Route::get('/import-form', function () {
            return view('ds_input.import');
        })->name('import.form');

        Route::get('/create', [DsInputController::class, 'create'])->name('create');
        Route::post('/', [DsInputController::class, 'store'])->name('store');
        Route::get('/{ds}/dn', [DsInputController::class, 'createDn'])->name('create_dn'); 
        Route::post('/{ds}/dn', [DsInputController::class, 'storeDn'])->name('store_dn');
        Route::get('/{ds_number}/edit', [DsInputController::class, 'edit'])->name('edit');
        Route::put('/{ds_number}', [DsInputController::class, 'update'])->name('update');
        Route::delete('/{ds_number}', [DsInputController::class, 'destroy'])->name('destroy');
    });

    // ===============================
    // 📑 DN INPUT (with prefix)
    // ===============================
    Route::prefix('dn')->name('dn.')->group(function () {
        Route::get('/', [DnController::class, 'index'])->name('index');
        Route::get('/create/{ds_number}', [DnController::class, 'create'])->name('create');
        Route::post('/store/{ds_number}', [DnController::class, 'store'])->name('store');
    });

    // ===============================
    // 🔍 CEK BAAN (Testing Route)
    // ===============================
    Route::get('/cek-baan', function () {
        $data = DB::table('di_input')
            ->whereNotNull('baan_pn')
            ->orderBy('di_created_date', 'desc')
            ->first();

        return response()->json($data);
    })->name('cek_baan');
});
