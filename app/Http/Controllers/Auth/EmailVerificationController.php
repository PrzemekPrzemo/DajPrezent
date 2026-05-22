<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user !== null && $user->hasVerifiedEmail()) {
            return redirect()->intended(route('owner.dashboard'));
        }

        return view('auth.verify-email');
    }

    /**
     * Signed-URL callback. Laravel verifies the signature + the
     * user_id + the sha1(email) hash before we get here.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('owner.dashboard')->with('status', 'Twój e-mail jest już zweryfikowany.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('owner.dashboard')->with('status', 'Dziękujemy — adres e-mail został potwierdzony.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('owner.dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Wysłaliśmy nowy link weryfikacyjny.');
    }
}
