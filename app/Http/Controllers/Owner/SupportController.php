<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Support\Models\SupportTicket;
use App\Http\Controllers\Controller;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Owner support inbox: tickets list, create form, single-ticket view.
 * Each ticket auto-routes by mail to support@dajprezent.pl with the
 * SLA „odpowiedź w 1 dniu roboczym" promised in the support flow.
 */
final class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);

        $tickets = SupportTicket::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('owner.support.index', ['tickets' => $tickets]);
    }

    public function create(): View
    {
        return view('owner.support.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $data = $request->validate([
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:8000'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        // If owner picks a tenant from a dropdown, sanity-check ownership
        // (a malicious POST shouldn't pin a foreign tenant to our ticket).
        if (! empty($data['tenant_id'])) {
            $tenant = $user->tenants()->find($data['tenant_id']);
            if ($tenant === null) {
                abort(403);
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'tenant_id' => $data['tenant_id'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'contact_email' => $user->email,
            'status' => 'open',
            'ip' => (string) $request->ip(),
        ]);

        // Route do supportu — mail na adres support@dajprezent.pl.
        Notification::route('mail', config('seller.contact.email', 'kontakt@dajprezent.pl'))
            ->notify(new SupportTicketCreatedNotification($ticket));

        return redirect()
            ->route('owner.support.show', $ticket)
            ->with('status', 'Zgłoszenie wysłane. Odpowiemy mailowo w ciągu 1 dnia roboczego.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $user = $request->user();
        if ($user === null || $ticket->user_id !== $user->id) {
            abort(403);
        }

        return view('owner.support.show', ['ticket' => $ticket]);
    }
}
