<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);

        $tenantIds = $user->tenants()->pluck('id');

        $invoices = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('tenant_id', $tenantIds)
            ->with('tenant:id,slug,name')
            ->orderByDesc('id')
            ->paginate(20);

        return view('owner.invoices.index', ['invoices' => $invoices]);
    }

    public function show(Request $request, int $invoice): View
    {
        $user = $request->user();
        assert($user !== null);

        $tenantIds = $user->tenants()->pluck('id');

        $model = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('tenant_id', $tenantIds)
            ->with('tenant:id,slug,name')
            ->findOrFail($invoice);

        return view('owner.invoices.show', [
            'invoice' => $model,
            'seller' => (array) config('seller'),
        ]);
    }
}
