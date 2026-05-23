<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\AccountDeleter;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Owner profile editor — name, e-mail, password.
 *
 *  - Changing e-mail clears email_verified_at and re-sends the
 *    verification mail.
 *  - Changing password requires the current password.
 */
final class AccountController extends Controller
{
    public function __construct(private readonly AccountDeleter $deleter) {}

    public function edit(Request $request): View
    {
        return view('owner.account.edit', ['user' => $request->user()]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $request->validate([
            'current_password' => ['required', 'string'],
            'confirm_email' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('current_password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Aktualne hasło jest nieprawidłowe.',
            ]);
        }

        if ($request->string('confirm_email')->trim()->lower()->value() !== $user->email) {
            throw ValidationException::withMessages([
                'confirm_email' => 'Wpisany adres nie zgadza się z adresem konta.',
            ]);
        }

        // Auth::logout() refreshes the remember_token via $user->save(),
        // which would re-INSERT the user row if delete() ran first.
        // Log out the session BEFORE touching the user row.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->deleter->delete($user);

        return redirect()->route('home')->with('status', 'Twoje konto zostało usunięte. Listy są zamknięte, dane gości usunięte. Faktury zachowano przez 5 lat zgodnie z ustawą o rachunkowości.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = mb_strtolower($data['email']) !== $user->email;

        $user->name = $data['name'];
        $user->email = mb_strtolower($data['email']);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('owner.account.edit')->with('status', 'Adres e-mail zmieniony. Wysłaliśmy link weryfikacyjny na nowy adres.');
        }

        return redirect()->route('owner.account.edit')->with('status', 'Zapisano profil.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Aktualne hasło jest nieprawidłowe.',
            ]);
        }

        $user->password = $data['password']; // hashed via $casts
        $user->save();

        return redirect()->route('owner.account.edit')->with('status', 'Hasło zmienione.');
    }
}
