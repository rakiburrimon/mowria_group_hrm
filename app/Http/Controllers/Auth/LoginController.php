<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Log the login event for audit and tracing
            activity()->performedOn($user)->causedBy($user)->log('user logged in');

            // Admins land on the admin dashboard; everyone else on their own panel
            $defaultRoute = $user->hasAnyRole(['admin', 'super_admin'])
                ? route('dashboard.admin')
                : route('dashboard.private');

            return redirect()->intended($defaultRoute);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        Auth::logout();

        // Log the logout event for audit and tracing
        if ($user) {
            activity()->performedOn($user)->causedBy($user)->log('user logged out');
        }

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
