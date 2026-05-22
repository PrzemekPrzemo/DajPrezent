# DajPrezent.pl — Plan systemu

## Kontekst

DajPrezent.pl ma być polskim, multi‑tenantowym SaaS-em do tworzenia list wymarzonych prezentów. Użytkownik kupuje pakiet (ważny 9 mc dla pakietów standardowych, 12 mc dla ślubnych — przedłużalny), tworzy listę pod własnym slugiem `dajprezent.pl/<slug>`, udostępnia ją bliskim. Osoby obdarowujące mogą bez zakładania konta kliknąć **„Zarezerwuj prezent”** lub **„Daj prezent”** — wymagamy jedynie potwierdzonego adresu e-mail (link aktywacyjny, anty‑spam). Właściciel listy nie widzi *kto* zarezerwował (anonimowość), ale po otrzymaniu prezentu może oznaczyć go jako „Otrzymany”.

Drugi filar produktu to **strony ślubne** (Basic / Premium) z RSVP, harmonogramem, mapą, hasłem i listą prezentów — konkurencyjnie wycenione względem `stronaweselna.com`, `bedzieslub.app`, `weddingcard.pl`.

Master Admin (my) zarządza pakietami, subskrypcjami, konfiguracjami PayU/KSeF i wystawia FV.

Repo jest puste — projekt greenfield.

## Ustalenia operacyjne (2026-05-22)

- **Sprzedawca / wystawca FV:** Sendormeco Holding, NIP **525-28-66-457**.
  Dane scentralizowane w `config/seller.php`, KSeF używa NIP-u sprzedawcy.
- **Hosting:** własny VPS z panelem **Plesk Obsidian**. Layout
  releasów + cron + queue worker udokumentowane w
  [`docs/DEPLOY-PLESK.md`](docs/DEPLOY-PLESK.md).
- **Branding:** nazwa **DajPrezent.pl** zatwierdzona, logo w
  przygotowaniu — w widokach używamy na razie wordmarku tekstowego,
  podmiana bez zmian kodu (`config/app.php` → `name`).
- **DPA (umowa powierzenia):** draft 1.0 w
  [`docs/legal/DPA.md`](docs/legal/DPA.md) — gotowy do konsultacji
  prawnej przed wdrożeniem pakietów weselnych.
- **Pakiet ślubny:** **w fazie 2** (po wishlist MVP). Schema i flow
  wesela są zaplanowane, ale nie wchodzą do pierwszego release'u —
  wycina ok. 3–4 tygodnie z time-to-market.

## Decyzje już zaakceptowane (z dialogu z użytkownikiem)

- **Backend:** Laravel 11 (PHP 8.3) + MySQL 8
- **Frontend:** Blade + Alpine.js + HTMX + Tailwind CSS (SSR, lekko, dobry SEO)
- **Multi-tenancy:** single DB + kolumna `tenant_id` w każdej tabeli (scope’y Eloquent + global scope)
- **Integracje MVP:** PayU, KSeF, transactional e-mail (Postmark albo Mailgun)
- **Pakiet ślubny:** dwa warianty — *Wedding Basic* (~199 zł) i *Wedding Premium* (~399 zł), 12 mc, przedłużalne
- **Group gifting / honeymoon fund:** poza MVP (klasyczna rezerwacja prezentu)
- **Sluga:** wolny rynek + lista zakazanych słów (wulgaryzmy, marki, slugi systemowe `admin`, `api`, `login`, `panel`, `master`, …)
- **Extra w MVP:** bookmarklet „Dodaj z dowolnego sklepu” (parser OpenGraph), wielojęzyczność PL/EN

## Stos technologiczny

| Warstwa | Wybór | Uzasadnienie |
|---|---|---|
| Framework | Laravel 11 | Cashier-like billing, Sanctum, Queue, Mail, Localization |
| DB | MySQL 8 + Redis (cache/queue/session) | Standard, łatwy hosting |
| Panel master admin | **Filament v3** | Gotowe CRUD-y dla subskrypcji, pakietów, FV — oszczędza tygodnie |
| UI front | Blade + Tailwind + Alpine + HTMX | Bez build-stepu po stronie klienta, szybkie strony list/RSVP |
| Auth | Laravel Breeze (właściciele) + magic-link/email-OTP (obdarowujący) |
| Permissions | `spatie/laravel-permission` (role: master_admin, owner, guest) |
| Płatności | PayU REST API (własny moduł — Cashier nie wspiera PayU natywnie) |
| Faktury | KSeF — `vatsoft/ksef-client` lub bezpośrednio API MF; numeracja FV w DB |
| Mail | Postmark (lepsze deliverability w PL niż SES) + szablony Markdown Laravela |
| Storage | S3-kompatybilny (Wasabi/Backblaze taniej niż AWS) — zdjęcia prezentów i strony ślubnej |
| Tłumaczenia | Laravel `lang/` + middleware `Locale` (pl/en) |
| Obserwowalność | Sentry + Laravel Pulse |
| Hosting | VPS (Mydevil/CloudHosting/Hetzner) + Laravel Forge lub kontener Docker; CDN dla statyków |
| CI | GitHub Actions: Pint, PHPStan (level 6), Pest, Dusk smoke testy |

## Model domeny (kluczowe tabele)

> Wszystkie tabele „tenantowe” mają `tenant_id` + global scope `BelongsToTenant`.

- `tenants` — `id`, `owner_user_id`, `slug` (unique), `name`, `locale`, `password_hash` (nullable, gdy lista chroniona), `expires_at`, `package_id`, `subscription_id`, `kind` ENUM(`wishlist`,`wedding_basic`,`wedding_premium`)
- `users` — właściciele kont i master admin (`role`)
- `packages` — `code`, `name`, `kind` (`standard`|`wedding`), `gift_limit` (nullable = unlimited), `slug_custom` (bool), `password_protection` (bool), `price_pln`, `valid_days`, `features` JSON
- `subscriptions` — `tenant_id`, `package_id`, `status`, `paid_at`, `expires_at`, `payu_order_id`, `invoice_id`
- `invoices` — `number`, `tenant_id`, `nip`, `payload`, `ksef_reference_number`, `pdf_path`, `status`
- `gifts` — `tenant_id`, `title`, `description`, `image_path`, `price_pln`, `url`, `priority`, `category`, `status` ENUM(`available`,`reserved`,`received`), `position`
- `gift_reservations` — `gift_id`, `guest_email`, `guest_name`, `email_verified_at`, `verification_token`, `intent` ENUM(`reserve`,`give`), `created_at`, `cancelled_at`
- **wedding-only:**
  - `wedding_events` — `tenant_id`, `starts_at`, `venue_name`, `address`, `lat`, `lng`, `description`
  - `wedding_pages` — sekcje CMS (`hero`, `story`, `program`, `gallery`, `accommodation`)
  - `rsvps` — `tenant_id`, `guest_name`, `attending`, `plus_one`, `dietary`, `notes`, `token`
- `audit_logs` — co master admin zmienił, kto zarezerwował (na potrzeby anty-fraud, **niewidoczne dla ownera**)
- `slug_blacklist` — lista zarezerwowanych słów

## Kluczowe flow

### 1. Zakup pakietu (PayU)
1. Anon user → wybiera pakiet → wpisuje slug → tworzy konto (e-mail + hasło) → PayU redirect.
2. Webhook PayU `notify` → tworzymy `Subscription`, `Tenant`, ustawiamy `expires_at = now() + valid_days`.
3. Job `IssueInvoiceJob` → KSeF → PDF do S3 → mail z FV.

### 2. Rezerwacja prezentu (bez konta)
1. Gość klika **„Zarezerwuj”** lub **„Daj prezent”** → modal z polem e-mail (+ opcjonalnie imię).
2. Tworzymy `gift_reservation` w stanie *pending* + token. Wysyłamy maila z linkiem aktywacyjnym (czas życia 60 min).
3. Klik w link → `email_verified_at` ustawione → prezent oznaczony jako `reserved` → mail z potwierdzeniem (i opcją „anuluj rezerwację” tokenem).
4. **Właściciel widzi tylko status `reserved` — nigdy e-maila gościa.**
5. Cron `ReleaseExpiredReservations` — kasuje *pending* po 60 min, oraz `reserved` po np. 90 dniach bez zmiany na `received`.

### 3. Oznaczenie „Otrzymany”
- Owner w panelu klika „Otrzymałem” → `gifts.status = received`. Gość który rezerwował dostaje (opcjonalny) mail „dziękujemy”.

### 4. RSVP (strona ślubna)
- Każdy gość ma unikalny token w URL (np. `/wedding/<slug>/rsvp/<token>`) lub formularz publiczny.
- Pakiet Premium: hasło na całą stronę (`tenant.password_hash` + cookie/session).

### 5. Bookmarklet „Dodaj do listy”
- JS snippet pobiera `og:image`, `og:title`, `product:price:amount` z aktualnej karty → POST do `/api/gifts/import?token=…`.
- Backend pobiera obraz (sanitizacja, max 2 MB, resize 800×800 WebP) i tworzy prezent w `draft`.

### 6. Master admin (Filament)
- Widoki: Subskrypcje (filtry: aktywne / wygasłe / wkrótce wygasające), Tenanci, Pakiety (edycja cen, limitów, ficzerów), Faktury (re-issue KSeF), **Konfiguracje** (klucze PayU + KSeF API tokeny — zaszyfrowane przez Laravel encrypter, dostęp tylko przez panel, nigdy w `.env` produkcyjnego klienta).
- Akcje: ręczne przedłużenie pakietu, refund, wystawienie korekty, podgląd listy tenant’a (read-only z banerem „Tryb master”).

## Propozycja cen i pakietów

Pozycjonowanie: tańsze niż Stronaweselna/Bedzieslub na ślubach, ale z lepszym UX i RSVP. Standardowe listy w segmencie premium względem darmowych konkurentów (VOLO, Listly) — różnica: własny slug, brak reklam, hasło, KSeF FV.

### Standardowe (9 miesięcy ważności)

| Pakiet | Cena | Limit prezentów | Slug | Hasło | Dodatki |
|---|---|---|---|---|---|
| **Free / Trial** | 0 zł | 3 | losowy `/u/abc123` | nie | znak wodny „dajprezent.pl”, ważny 30 dni |
| **Mini** | **19 zł** | 10 | losowy | nie | bookmarklet, statystyki wyświetleń |
| **Standard** | **39 zł** | 30 | custom | nie | własne tło, ikona, kategorie |
| **Plus** | **69 zł** | 75 | custom | **tak** | wiele list (do 3), motyw kolorystyczny |
| **Pro** | **99 zł** | 200 | custom | tak | własna domena (CNAME), eksport CSV, brak brandingu |

### Ślubne (12 miesięcy, przedłużenie 99 zł/rok)

| Pakiet | Cena | Limit prezentów | Funkcje |
|---|---|---|---|
| **Wedding Basic** | **199 zł** | bez limitu | strona ślubna (1 język), RSVP, harmonogram, mapa, lista prezentów, hasło |
| **Wedding Premium** | **399 zł** | bez limitu | wszystko z Basic + 2 języki (PL/EN/UA), galeria po-ślubna, lista gości z kategoryzacją (rodzina/przyjaciele/praca), dietary/alergie w RSVP, motywy premium, własna domena CNAME, priorytetowy support, generator zaproszenia PDF + QR |

### Add-ony (jednorazowo)

- Dodatkowe 50 prezentów: 19 zł
- Przedłużenie standardowego pakietu o 3 mc: 15 zł
- Custom motyw (grafik robi indywidualnie): 149 zł
- Re-issue FV: 0 zł (przez KSeF automat)

## Propozycje funkcjonalności (poza tym, co podałeś)

Krótko, z moją rekomendacją „MVP / v2 / v3”:

1. **Bookmarklet i parser OpenGraph** — *MVP* (zatwierdzone).
2. **„Like” / serca pod prezentami od gości** — *v2*. Zachęca do interakcji, social proof.
3. **Statystyki dla ownera** — wyświetlenia, kliknięcia w link sklepu, % zarezerwowanych — *MVP-light*.
4. **Anty-duplikat** — gdy gość rezerwuje, ostrzeżenie „2 dni temu ktoś już rezerwował podobny tytuł na innej liście tego usera” (pomaga rodzinie). — *v2*.
5. **Affiliate / monetyzacja pasywna** (Allegro CPA, Empik, Ceneo) — *v2*. Auto-rewriting linków sklepowych, prowizja na nasze konto. Wymaga umów partnerskich; może obniżyć cenę pakietu Free.
6. **AI-sugestie prezentów (Claude API)** — *v2*. „Brat, 35, lubi rower” → 10 pomysłów + linki. Wyróżnik w PL.
7. **Aplikacja PWA + push notifications** — *v2*. Bez nativowej apki — Service Worker + Web Push.
8. **Wielojęzyczność PL/EN** — *MVP* (zatwierdzone). UA/DE — *v2*.
9. **Kalendarz okazji** — urodziny, imieniny, rocznice → auto-przypomnienia do ownera „aktualizuj listę przed urodzinami”. — *v2*.
10. **Wiele list per użytkownik** — Plus i wyżej. — *MVP*.
11. **Lista współdzielona** (np. cała rodzina dla dziecka) — owner może zaprosić współedytorów. — *v2*, dobrze pasuje do wesel (oboje narzeczonych edytuje).
12. **Eksport CSV / PDF listy** (i listy gości w wesele) — *MVP* w pakiecie Pro/Premium.
13. **Generator zaproszeń PDF + QR** (link do strony + RSVP) — *MVP* w Wedding Premium.
14. **Galeria po-ślubna** — zdjęcia z wesela tylko dla zalogowanych gości (token). — *MVP* w Wedding Premium.
15. **Tabela menu / alergie** — kelner/restauracja może dostać CSV. — *MVP* w Wedding Premium.
16. **Notyfikacje SMS** (Twilio/SMSApi) dla RSVP. Płatny dodatek. — *v3*.
17. **Anti-spam dla rezerwacji** — rate-limit per IP/email + Cloudflare Turnstile, weryfikacja maila (już w wymaganiach). — *MVP*.
18. **GDPR / RODO** — checkbox zgody, prawo do usunięcia, export danych ownera/RSVP. — *MVP* (regulatory must-have).
19. **„Cofnij rezerwację” linkiem** dla gościa, jeśli coś mu wypadło. — *MVP*.
20. **Marketplace motywów** — projektanci sprzedają motywy ślubne, my 30% prowizji. — *v3*.

## Architektura katalogów (Laravel)

```
app/
  Domain/
    Tenancy/          (Tenant model, BelongsToTenant trait, CurrentTenant facade)
    Billing/          (PayU client, webhook, Subscription service)
    Invoicing/        (KSeF client, InvoiceGenerator)
    Wishlist/         (Gift, Reservation, ImportFromUrl service)
    Wedding/          (WeddingPage, Rsvp, GuestList)
  Http/
    Controllers/Public      (publiczne /\<slug>)
    Controllers/Owner       (panel właściciela)
    Filament/Admin/         (master admin — Filament resources)
  Mail/
config/
  packages.php        (definicje pakietów - źródło prawdy)
  tenancy.php
resources/views/
  themes/             (motywy listy + ślubne)
lang/{pl,en}/
```

## Krytyczne pliki / moduły do zbudowania (kolejność)

1. `database/migrations/*` — schema z sekcji „Model domeny”.
2. `app/Domain/Tenancy/Tenant.php` + global scope, slug routing przez `Route::bind('slug', …)`.
3. `app/Domain/Billing/PayU/*` — klient, webhook, idempotencja (`payu_orders` log).
4. `config/packages.php` + `PackageRepository` — pojedyncze źródło prawdy o limitach (uniknij hard-codingu w wielu miejscach).
5. `app/Domain/Wishlist/Reservation/ReservationService.php` — `requestReservation()`, `verify()`, `cancel()`, `release()`.
6. `app/Domain/Invoicing/Ksef/*` — z trybem sandbox MF + retry queue.
7. `app/Filament/Admin/Resources/*` — Subscription, Tenant, Package, Invoice, Setting (PayU/KSeF zaszyfrowane).
8. `resources/views/public/wishlist/show.blade.php` + Alpine modal rezerwacji + HTMX dla statusów.
9. `resources/js/bookmarklet.js` — kompilowany do jednego minified pliku, hostowany na `/bookmarklet.js`.
10. `app/Console/Commands/ReleaseExpiredReservations` + scheduler.
11. `tests/` — Pest. Krytyczne: webhook PayU (idempotencja), reservation flow z verifikacją maila, slug uniqueness/blacklist, RODO export.

## Bezpieczeństwo i RODO

- E-mail gościa nigdy nie trafia do ownera (anonimowość rezerwacji) — twardy invariant w testach.
- Hasła do list/wesel — `bcrypt` po stronie serwera, nie w cookie.
- KSeF i PayU klucze — `Crypt::encryptString` w DB, deszyfracja tylko w runtime.
- Wszystkie publiczne formularze (rezerwacja, RSVP) — Turnstile + rate-limit `throttle:5,1` per IP.
- Eksport danych usera (RODO) jako job → ZIP do S3 → link 24h.
- Backupy MySQL (logical dump dziennie, encrypted) → S3, retencja 30 dni.
- Polityka cookies + analytics-friendly (Plausible zamiast GA).

## Weryfikacja (jak sprawdzimy, że działa end-to-end)

1. `php artisan migrate:fresh --seed` — seedery tworzą master admina, pakiety, sample tenant z 10 prezentami.
2. Smoke testy Pest:
   - `it('reserves a gift only after email verification')`
   - `it('hides guest email from owner reservation list')`
   - `it('expires subscription after 9 months and disables public list')`
   - `it('rejects blacklisted slugs (admin, api, login, …)')`
   - `it('issues KSeF invoice after successful PayU webhook')` (z mocked KSeF/PayU)
3. Dusk: end-to-end „kup pakiet → utwórz listę → gość rezerwuje → owner oznacza otrzymany”.
4. Manualnie w sandboxie PayU + KSeF Test (dane testowe ministerstwa) — pełny flow zakupu i FV.
5. Pakiet ślubny: utworzenie strony, ustawienie hasła, RSVP od trzech gości, eksport listy do CSV.
6. Bookmarklet: testy na 5 popularnych sklepach (Allegro, Empik, Zalando, Media Expert, IKEA) — sprawdzenie czy OpenGraph zwraca poprawne dane.
7. Lighthouse na publicznej liście i stronie ślubnej — cel ≥ 90 mobile.

## Otwarte tematy (po ustaleniach z 2026-05-22)

1. ✅ **Hosting docelowy** — własny VPS z Plesk, instrukcja w
   `docs/DEPLOY-PLESK.md`.
2. ✅ **Sprzedawca FV** — Sendormeco Holding, NIP 525-28-66-457 w
   `config/seller.php`. Adres, REGON, KRS, konto bankowe i telefon
   uzupełnimy w `.env` produkcyjnym (placeholdery są w `.env.example`).
3. 🟡 **Logo / brand book** — w przygotowaniu. Do czasu otrzymania
   plików używamy wordmarku tekstowego.
4. ✅ **DPA dla pakietów weselnych** — szablon w `docs/legal/DPA.md`.
   **Przed wdrożeniem fazy 2 wymaga weryfikacji przez radcę prawnego.**
5. 🔲 **Polityka zwrotów** — sugeruję 14-dniowe odstąpienie tylko
   jeśli lista nie była upubliczniona ani nie odbyła się żadna
   rezerwacja gościa. Wymaga decyzji + tekstu regulaminu.
6. 🔲 **Provider transactional e-mail** — Postmark (~10 USD/mc za
   10k maili) vs Mailgun vs SES. Sugeruję Postmark dla najlepszego
   deliverability w PL.
7. ✅ **Wesele w MVP czy fazie 2?** — w fazie 2 (po wishlist).

## Roadmapa skrócona

- ✅ **Faza 0 (1 tydz.)** — repo, CI, Laravel skeleton, Filament, schema, seedery. **Zrobione (commity 5315adf, 2ae8651, 23f1bb8, cb76c90).**
- 🚧 **Faza 1 — Wishlist MVP (4–6 tyg.)** — pakiety standardowe, PayU,
  KSeF, rezerwacja z verify (✅), bookmarklet, panel ownera, public
  lista, master admin (Filament). **W trakcie.**
- ⏭️ **Faza 2 — Wedding (3–4 tyg.)** — Basic + Premium, RSVP, motywy,
  hasło, eksporty, generator zaproszeń. Wymaga finalizacji DPA przez
  prawnika.
- ⏭️ **Faza 3 — Wzrost (ongoing)** — affiliate, AI sugestie, PWA,
  marketplace motywów, SMS.
