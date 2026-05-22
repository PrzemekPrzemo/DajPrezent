<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class UnlockController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant->isPasswordProtected() || $this->isUnlocked($request, $tenant)) {
            return redirect('/'.$tenant->slug);
        }

        return view('public.unlock', ['tenant' => $tenant]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant->isPasswordProtected()) {
            return redirect('/'.$tenant->slug);
        }

        $key = 'unlock:'.$tenant->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'password' => 'Zbyt wiele prób. Spróbuj ponownie za minutę.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $password = (string) $request->validate(['password' => ['required', 'string', 'max:128']])['password'];

        if (! Hash::check($password, (string) $tenant->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'Nieprawidłowe hasło.',
            ]);
        }

        $request->session()->put("tenant.unlocked.{$tenant->id}", true);
        RateLimiter::clear($key);

        return redirect('/'.$tenant->slug);
    }

    private function isUnlocked(Request $request, Tenant $tenant): bool
    {
        return $request->session()->get("tenant.unlocked.{$tenant->id}") === true;
    }
}
