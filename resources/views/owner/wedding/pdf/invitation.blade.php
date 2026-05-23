<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zaproszenie — {{ $event?->couple_names ?: $tenant->name }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; padding: 0; }
        .card {
            width: 105mm; height: 148mm;     /* A6 */
            padding: 14mm 10mm;
            background: #ffffff;
            color: #1E293B;
            text-align: center;
            position: relative;
        }
        .ribbon {
            position: absolute; left: 0; top: 0; right: 0; height: 16mm;
            background: linear-gradient(135deg, #4F46E5 0%, #3B82F6 100%);
        }
        .ribbon::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: -6mm; height: 6mm;
            background: linear-gradient(180deg, rgba(79,70,229,.15), transparent);
        }
        .badge { color: #fff; font-size: 9pt; letter-spacing: 2pt; text-transform: uppercase; padding-top: 5mm; }
        h1 { font-family: "Times New Roman", serif; font-size: 28pt; margin: 18mm 0 4mm; line-height: 1.05; }
        .meta { font-size: 11pt; color: #475569; margin: 2mm 0; }
        .qr { margin: 8mm auto 4mm; }
        .qr img { width: 32mm; height: 32mm; display: block; margin: 0 auto; }
        .url { font-size: 9pt; color: #4F46E5; word-break: break-all; }
        .footer { position: absolute; left: 0; right: 0; bottom: 6mm; font-size: 8pt; color: #94a3b8; }
        .hashtag { font-size: 10pt; color: #64748B; margin-top: 1mm; }
    </style>
</head>
<body>
<div class="card">
    <div class="ribbon"><div class="badge">DajPrezent.pl</div></div>

    <h1>{{ $event?->couple_names ?: $tenant->name }}</h1>

    @if ($event?->ceremony_at)
        <div class="meta">{{ $event->ceremony_at->translatedFormat('j F Y') }} · {{ $event->ceremony_at->format('H:i') }}</div>
    @endif
    @if ($event?->venue_name)
        <div class="meta">{{ $event->venue_name }}</div>
    @endif
    @if ($event?->venue_address)
        <div class="meta">{{ $event->venue_address }}</div>
    @endif

    <div class="qr">
        <img src="{{ $qrDataUri }}" alt="QR do strony ślubnej">
    </div>
    <div class="url">{{ str_replace(['https://','http://'], '', $publicUrl) }}</div>

    @if ($event?->rsvp_deadline)
        <div class="meta" style="margin-top:6mm;">RSVP do: {{ $event->rsvp_deadline->translatedFormat('j F Y') }}</div>
    @endif

    @if ($event?->hashtag)
        <div class="hashtag">{{ $event->hashtag }}</div>
    @endif

    <div class="footer">Zeskanuj QR aby potwierdzić obecność i zobaczyć szczegóły</div>
</div>
</body>
</html>
