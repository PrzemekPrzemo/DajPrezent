{{-- Minimal cookie notice. We only use technically-necessary cookies
     (session, CSRF, locale) so we don't need a full Cookiebot-style
     consent gate — informacja + akceptacja w localStorage. --}}
<div id="cookie-banner" style="display:none;position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);max-width:560px;width:calc(100% - 2rem);background:#1f2937;color:#fff;padding:1rem 1.25rem;border-radius:.75rem;box-shadow:0 10px 30px rgba(0,0,0,.25);z-index:100;font-size:.9rem;line-height:1.5;">
    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
        <span style="flex:1;min-width:200px;">
            Używamy wyłącznie ciasteczek niezbędnych do działania serwisu (sesja, ochrona CSRF, język).
            Szczegóły: <a href="{{ route('public.legal.privacy') }}" style="color:#fbcfe8;">Polityka prywatności</a>.
        </span>
        <button type="button" id="cookie-accept" style="background:#ec4899;color:#fff;border:0;padding:.45rem 1.1rem;border-radius:.5rem;font-weight:600;cursor:pointer;font-size:.9rem;">Rozumiem</button>
    </div>
</div>
<script>
    (function () {
        try {
            if (localStorage.getItem('dajprezent.cookie-ack') !== '1') {
                document.getElementById('cookie-banner').style.display = 'block';
            }
            document.getElementById('cookie-accept').addEventListener('click', function () {
                localStorage.setItem('dajprezent.cookie-ack', '1');
                document.getElementById('cookie-banner').style.display = 'none';
            });
        } catch (e) { /* localStorage disabled — show banner once per page */ }
    })();
</script>
