<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ManagerController;
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
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/bulk-delete', [UserController::class, 'destroyBulk'])->name('users.bulk-destroy');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::post('/licenses/bulk', [LicenseController::class, 'bulk'])->name('licenses.bulk');
        Route::delete('/licenses/bulk-delete', [LicenseController::class, 'destroyBulk'])->name('licenses.bulk-destroy');
        Route::put('/licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
        Route::delete('/licenses/{license}', [LicenseController::class, 'destroy'])->name('licenses.destroy');
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::get('/logs', ActivityLogController::class)->name('logs.index');
        Route::get('/settings', fn () => Inertia::render('Settings'))->name('settings');
        Route::middleware('role:OWNER')->group(function () {
            Route::get('/managers', [ManagerController::class, 'index'])->name('managers.index');
            Route::post('/managers', [ManagerController::class, 'store'])->name('managers.store');
            Route::delete('/managers/{manager}', [ManagerController::class, 'destroy'])->name('managers.destroy');
        });
    });

    Route::prefix('partner')->name('partner.')->middleware('role:PARTNER')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/bulk-delete', [UserController::class, 'destroyBulk'])->name('users.bulk-destroy');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::post('/licenses/bulk', [LicenseController::class, 'bulk'])->name('licenses.bulk');
        Route::delete('/licenses/bulk-delete', [LicenseController::class, 'destroyBulk'])->name('licenses.bulk-destroy');
        Route::put('/licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
        Route::delete('/licenses/{license}', [LicenseController::class, 'destroy'])->name('licenses.destroy');
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
