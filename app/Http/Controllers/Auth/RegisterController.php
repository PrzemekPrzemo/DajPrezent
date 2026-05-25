<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

final class RegisterController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.register', [
            'intendedPackage' => $request->query('package'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'], // hashed via $casts on model
        ]);

        // Wysyłka maila weryfikacyjnego nie może blokować rejestracji —
        // gdy SMTP pada (np. wygasły cert, błędne creds), user i tak
        // zostaje zarejestrowany i może wysłać verify-link z /email/verify.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('register.verify_mail_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $package = $request->input('package');
        if (is_string($package) && $package !== '') {
            return redirect()->route('public.checkout.buy', ['code' => $package]);
        }

        return redirect()->route('owner.dashboard');
    }
}
