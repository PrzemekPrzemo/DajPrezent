@extends('layouts.panel')

@section('title', 'Faktura '.$invoice->number)

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
        <h1 style="margin:0;">Faktura {{ $invoice->number }}</h1>
        <div style="display:flex;gap:.5rem;">
            <button type="button" onclick="window.print()" class="btn">🖨 Drukuj / Zapisz PDF</button>
            <a href="{{ route('owner.invoices.index') }}" class="btn btn-secondary">← Faktury</a>
        </div>
    </div>

    <article class="card invoice-paper" style="text-align:left;background:#fff;">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
            <div>
                <h2 style="margin:0 0 .25rem;">Faktura VAT</h2>
                <div style="font-size:.9rem;color:#6b7280;">Nr {{ $invoice->number }}</div>
                <div style="font-size:.9rem;color:#6b7280;">
                    Data wystawienia: {{ $invoice->created_at?->format('d.m.Y') }}<br>
                    Data sprzedaży: {{ $invoice->created_at?->format('d.m.Y') }}
                </div>
            </div>
            <div style="text-align:right;font-size:.9rem;">
                @if ($invoice->ksef_reference_number)
                    <strong>KSeF:</strong><br>
                    <span style="font-family:monospace;">{{ $invoice->ksef_reference_number }}</span><br>
                    @if ($invoice->ksef_acquisition_at)
                        <span style="color:#6b7280;">{{ $invoice->ksef_acquisition_at->format('d.m.Y H:i') }}</span>
                    @endif
                @endif
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
            <div>
                <div style="font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">Sprzedawca</div>
                <strong>{{ $seller['legal_name'] }}</strong><br>
                {{ $seller['address']['street'] }}<br>
                {{ $seller['address']['postal_code'] }} {{ $seller['address']['city'] }}<br>
                NIP: {{ $seller['nip'] }}<br>
                REGON: {{ $seller['regon'] }}<br>
                KRS: {{ $seller['krs'] }}<br>
                @if (! empty($seller['share_capital_pln']))
                    <small style="color:#6b7280;">Kapitał zakładowy: {{ number_format($seller['share_capital_pln'], 0, ',', ' ') }} zł</small>
                @endif
            </div>
            <div>
                <div style="font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">Nabywca</div>
                <strong>{{ $invoice->buyer_name }}</strong><br>
                @if ($invoice->buyer_nip)
                    NIP: {{ $invoice->buyer_nip }}<br>
                @endif
                @foreach ((array) $invoice->buyer_address as $line)
                    @if (is_string($line) && $line !== '')
                        {{ $line }}<br>
                    @endif
                @endforeach
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;margin-bottom:1rem;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="text-align:left;padding:.5rem;border-bottom:1px solid #e5e7eb;">Pozycja</th>
                    <th style="text-align:right;padding:.5rem;border-bottom:1px solid #e5e7eb;">Ilość</th>
                    <th style="text-align:right;padding:.5rem;border-bottom:1px solid #e5e7eb;">Netto</th>
                    <th style="text-align:right;padding:.5rem;border-bottom:1px solid #e5e7eb;">VAT</th>
                    <th style="text-align:right;padding:.5rem;border-bottom:1px solid #e5e7eb;">Brutto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    @php
                        $qty = (int) ($item['qty'] ?? 1);
                        $unitNet = (int) ($item['unit_net_gr'] ?? 0);
                        $unitGross = (int) ($item['unit_gross_gr'] ?? 0);
                        $vat = $unitGross - $unitNet;
                    @endphp
                    <tr>
                        <td style="padding:.5rem;border-bottom:1px solid #f3f4f6;">{{ $item['name'] ?? '—' }}</td>
                        <td style="padding:.5rem;border-bottom:1px solid #f3f4f6;text-align:right;">{{ $qty }}</td>
                        <td style="padding:.5rem;border-bottom:1px solid #f3f4f6;text-align:right;">{{ number_format($unitNet / 100, 2, ',', ' ') }} zł</td>
                        <td style="padding:.5rem;border-bottom:1px solid #f3f4f6;text-align:right;">{{ ($item['vat_rate'] ?? 23) }}% ({{ number_format($vat / 100, 2, ',', ' ') }} zł)</td>
                        <td style="padding:.5rem;border-bottom:1px solid #f3f4f6;text-align:right;">{{ number_format($unitGross / 100, 2, ',', ' ') }} zł</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="padding:.5rem;text-align:right;color:#6b7280;">Netto:</td>
                    <td style="padding:.5rem;text-align:right;">{{ number_format($invoice->total_net_gr / 100, 2, ',', ' ') }} zł</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding:.5rem;text-align:right;color:#6b7280;">VAT:</td>
                    <td style="padding:.5rem;text-align:right;">{{ number_format($invoice->total_vat_gr / 100, 2, ',', ' ') }} zł</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding:.5rem;text-align:right;font-weight:700;font-size:1.05rem;">Razem brutto:</td>
                    <td style="padding:.5rem;text-align:right;font-weight:700;font-size:1.05rem;">{{ number_format($invoice->total_gross_gr / 100, 2, ',', ' ') }} zł</td>
                </tr>
            </tfoot>
        </table>

        <div style="font-size:.85rem;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:.75rem;">
            <strong>Płatność:</strong> opłacona w momencie zamówienia za pośrednictwem PayU S.A.<br>
            <strong>Forma faktury:</strong> faktura elektroniczna wystawiona w Krajowym Systemie e-Faktur (KSeF).
            @if (! empty($seller['registry_court']))
                <br>{{ $seller['registry_court'] }}.
            @endif
        </div>
    </article>

    <style>
        @media print {
            .nav, footer, #cookie-banner, .btn { display: none !important; }
            body { background: #fff !important; }
            .invoice-paper { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
        }
    </style>
@endsection
