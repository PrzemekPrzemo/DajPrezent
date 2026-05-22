<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class TenantSettingsController extends Controller
{
    public function edit(Request $request, Tenant $tenant): View
    {
        $this->authorize($request, $tenant);

        return view('owner.tenant.settings', ['tenant' => $tenant]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize($request, $tenant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:4', 'max:128'],
            'remove_password' => ['nullable', 'in:1'],
        ]);

        $update = ['name' => $data['name']];

        if (($data['remove_password'] ?? null) === '1') {
            $update['password_hash'] = null;
        } elseif (! empty($data['password'])) {
            $update['password_hash'] = Hash::make($data['password']);
        }

        $tenant->update($update);

        return redirect()
            ->route('owner.tenant.settings.edit', $tenant)
            ->with('status', 'Zapisano ustawienia listy.');
    }

    private function authorize(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
    }
}
