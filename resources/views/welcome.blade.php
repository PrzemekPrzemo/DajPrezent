<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DajPrezent.pl — Twoja lista wymarzonych prezentów</title>
    <meta name="description" content="Stwórz listę wymarzonych prezentów i podziel się nią z bliskimi. Strony ślubne z RSVP, listą prezentów i mapą.">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: linear-gradient(180deg,#fdf2f8 0%,#fff 100%); }
        .wrap { max-width: 760px; margin: 0 auto; padding: 6rem 1.5rem 4rem; text-align: center; }
        h1 { font-size: clamp(2rem, 5vw, 3.25rem); margin: 0 0 1rem; }
        p.lead { font-size: 1.15rem; color: #4b5563; max-width: 36rem; margin: 0 auto 2rem; }
        .badge { display:inline-block; padding:.25rem .75rem; border-radius:9999px; background:#fce7f3; color:#9d174d; font-size:.8rem; font-weight:600; margin-bottom: 1rem; }
        footer { text-align:center; color:#9ca3af; font-size:.85rem; padding-bottom: 2rem; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>DajPrezent.pl</h1>
        <p class="lead">Stwórz wymarzoną listę prezentów i podziel się nią z bliskimi. Bliscy zarezerwują anonimowo, Ty zobaczysz tylko status — kto, dowiesz się dopiero przy rozpakowywaniu.</p>
        <p>
            <a href="/pakiety" style="display:inline-block;background:#ec4899;color:#fff;padding:.75rem 1.5rem;border-radius:.5rem;text-decoration:none;font-weight:600;font-size:1.05rem;">Wybierz pakiet</a>
            <a href="/login" style="display:inline-block;color:#6b7280;padding:.75rem 1rem;text-decoration:none;">Mam już konto</a>
        </p>
    </main>
    <footer style="text-align:center;color:#9ca3af;font-size:.85rem;padding:0 1rem 2.5rem;">
        <div style="margin-bottom:.5rem;">
            <a href="/pakiety" style="color:#9ca3af;">Pakiety</a> ·
            <a href="/faq" style="color:#9ca3af;">FAQ</a> ·
            <a href="/kontakt" style="color:#9ca3af;">Kontakt</a> ·
            <a href="/regulamin" style="color:#9ca3af;">Regulamin</a> ·
            <a href="/polityka-prywatnosci" style="color:#9ca3af;">Polityka prywatności</a>
        </div>
        © {{ date('Y') }} DajPrezent.pl · Sendormeco Holding, NIP 525-28-66-457
    </footer>
    @include('partials.cookie-banner')
</body>
</html>
