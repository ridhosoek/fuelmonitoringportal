<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validated();
        $login = $credentials['login'];

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Login gagal. Periksa username/email dan password.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $auditLogger->log('LOGIN', 'Authentication', null, null, ['user_id' => $user->id], $user, $user->email);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(AuditLogger $auditLogger): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $auditLogger->log('LOGOUT', 'Authentication', null, null, ['user_id' => $user->id], $user, $user->email);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
