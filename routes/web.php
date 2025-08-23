<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiInputController;
use App\Http\Controllers\DeliveryController;
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
    return redirect()->route('login');
});

// ========================================
// 🔒 PROTECTED ROUTES (REQUIRES LOGIN)
// ========================================
Route::middleware(['auth'])->group(function () {

    // Dashboard
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
    // 🚚 Deliveries (DI Import)
    // ===============================
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('/import-form', fn() => view('DI_Input.import'))->name('import.form');
        Route::post('/import', [DeliveryController::class, 'import'])->name('import.submit');
        Route::get('/{id}', [DeliveryController::class, 'show'])->name('show');
    });

   // 📑 DS INPUT (Data DS + Generate DS)
// ===============================
Route::prefix('ds-input')->name('ds_input.')->group(function () {
    // Halaman Data DS (list)
    Route::get('/', [DsInputController::class, 'index'])->name('index');

    // Generate DS
    Route::get('/generate-form', [DsInputController::class, 'generateForm'])->name('generate.form');
    Route::post('/generate', [DsInputController::class, 'generate'])->name('generate');

    // Import DS
    Route::get('/import-form', fn() => view('ds_input.import'))->name('import.form');
    Route::post('/import', [DsInputController::class, 'import'])->name('import');

    // CRUD DS
    Route::get('/create', [DsInputController::class, 'create'])->name('create');           // ds_input.create
    Route::post('/', [DsInputController::class, 'store'])->name('store');                 // ds_input.store
    Route::get('/{ds_number}/edit', [DsInputController::class, 'edit'])->name('edit');    // ds_input.edit
    Route::put('/{ds_number}', [DsInputController::class, 'update'])->name('update');     // ds_input.update
    Route::delete('/{ds_number}', [DsInputController::class, 'destroy'])->name('destroy');// ds_input.destroy

    // Export PDF
    Route::get('/export-pdf', [DsInputController::class, 'exportPdf'])->name('export_pdf');
});

    // 🔎 Cek Baan
    Route::get('/cek-baan', function () {
        $data = DB::table('di_input')
            ->whereNotNull('baan_pn')
            ->orderBy('di_created_date', 'desc')
            ->first();
        return response()->json($data);
    })->name('cek_baan');
});
