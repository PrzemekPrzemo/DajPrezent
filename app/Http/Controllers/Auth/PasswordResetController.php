<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * "Forgot password" + "reset password" flow built on Laravel's
 * Password broker. Custom controllers (we hand-rolled auth instead
 * of Breeze) but the broker handles tokens, expiry and throttle.
 */
final class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('auth.passwords.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Password broker returns one of the constants below. We do NOT
        // distinguish RESET_LINK_SENT vs INVALID_USER in the UI so we
        // don't leak which emails are registered.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Jeśli ten adres jest w naszym systemie, wysłaliśmy na niego link do resetu.');
    }

    public function showReset(string $token, Request $request): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()->route('login')->with('status', 'Hasło zmienione. Możesz się zalogować.');
    }
}
