<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AuthDiagnostics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $sessionIdBefore = $request->session()->getId();

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Track successful logins so we can confirm the session actually persisted
        // (session id rotates on regenerate) when chasing intermittent login issues.
        Log::channel('auth')->info('login.success', array_merge(
            AuthDiagnostics::context($request),
            [
                'user_id' => $user?->id,
                'role' => $user?->role,
                'session_id_before' => $sessionIdBefore,
                'session_id_after' => $request->session()->getId(),
            ]
        ));

        if ($user && in_array($user->role, ['admin', 'support', 'accountant'])) {
            return redirect()->intended('/admin');
        }
        return redirect()->intended('/teacher');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
