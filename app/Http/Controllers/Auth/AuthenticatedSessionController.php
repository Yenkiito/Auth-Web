<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $logger->log('auth.login');

        return redirect()->intended(route($request->user()->role->dashboard(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $logger->log('auth.logout');
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
