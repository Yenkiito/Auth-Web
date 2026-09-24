<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn (Request $request) => redirect()->route($request->user()->role->dashboard()))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('admin')->name('admin.')->middleware('role:OWNER,ADMIN')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::post('/projects/{project}/select', [ProjectController::class, 'select'])->name('projects.select');
        Route::post('/projects/{project}/toggle', [ProjectController::class, 'toggle'])->name('projects.toggle');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/projects/{project}/rotate-key', [ProjectController::class, 'rotate'])->middleware('password.confirm')->name('projects.rotate');
        Route::post('/projects/{project}/reveal-key', [ProjectController::class, 'reveal'])->middleware('password.confirm')->name('projects.reveal');
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::post('/licenses/bulk', [LicenseController::class, 'bulk'])->name('licenses.bulk');
        Route::put('/licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::get('/logs', ActivityLogController::class)->name('logs.index');
        Route::get('/settings', fn () => Inertia::render('Settings'))->name('settings');
    });

    Route::prefix('partner')->name('partner.')->middleware('role:PARTNER')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::post('/licenses/bulk', [LicenseController::class, 'bulk'])->name('licenses.bulk');
        Route::put('/licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::get('/activity', ActivityLogController::class)->name('logs.index');
    });

    Route::prefix('client')->name('client.')->middleware('role:CLIENT')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    });
});

require __DIR__.'/auth.php';
