---
name: coolify-deploy-prep
description: "Uniwersalny skill do przygotowania i naprawy DOWOLNEGO repo (Next.js, NestJS/Node, Python/FastAPI, PHP/Laravel, statyczne strony, monorepo) pod wdrożenie przez Coolify na Hetznerze/VPS, zwłaszcza gdy jeden serwer hostuje wiele aplikacji naraz. Użyj gdy user: prosi o przygotowanie repo do Coolify, dodanie/naprawę Dockerfile lub docker-compose.yml, zgłasza konflikt portów, pyta jak zrobić env edytowalne w Coolify UI zamiast hardcoded, zgłasza że build Dockera pada, 404 po domenie, DB unhealthy, Nixpacks wybrał złe źródło, healthcheck failuje, albo pyta jak wdrożyć projekt na Coolify/Hetznerze. Zawiera szablony Dockerfile/compose dla każdego stacku, wykrywanie stacku, zasady bezkolizyjnych portów i env, oraz pułapki Coolify."
---

# Coolify Deploy Prep — uniwersalne przygotowanie repo do wdrożenia

Ten skill służy do tego, żeby **przerobić dowolne repozytorium** (Twoje własne albo cudze, w dowolnym stacku) tak, żeby dało się je bezproblemowo wdrożyć przez Coolify — na serwerze, na którym **działa już wiele innych aplikacji** obok siebie. Dwie zasady nadrzędne, które obowiązują niezależnie od stacku:

1. **Żaden port nie jest hardcoded.** Aplikacje na tym samym serwerze nie mogą się o port bić — Coolify i Traefik mają się tym zająć automatycznie, nie Ty ręcznie w kodzie.
2. **Żadna zmienna środowiskowa nie jest hardcoded.** Wszystko musi dać się zmienić z poziomu Coolify UI (Environment Variables), bez potrzeby commitowania zmian do repo.

Jeśli refaktoryzujesz repo i któraś z tych dwóch zasad jest złamana — to jest bug, napraw go, zanim przejdziesz dalej.

---

## Kontekst środowiska — przeczytaj zanim zaczniesz

Ten skill zakłada konkretny, realny setup, nie hipotetyczny pojedynczy serwer:

- **Na serwerze Hetzner działa już wiele innych aplikacji i stron www obok siebie** (różne produkty SaaS, landing page'e, strony sprzedażowe) — to nie jest środowisko testowe z jedną aplikacją. Każda zmiana w Dockerfile/compose musi zakładać współistnienie z innymi, już działającymi kontenerami na tym samym hoście.
- **Coolify ma już uruchomiony i skonfigurowany wewnętrzny proxy (Traefik)**, który obsługuje routing dla wszystkich tych aplikacji. To oznacza:
  - Traefik już nasłuchuje na portach 80/443 hosta — **żadna aplikacja/serwis nie może próbować zbindować się na te porty bezpośrednio ani deklarować własnego mapowania `80:80`/`443:443`** w compose, bo to się zderzy z istniejącym proxy.
  - Routing do konkretnej aplikacji/strony odbywa się WYŁĄCZNIE przez mechanizmy opisane w Zasadzie A (`SERVICE_FQDN_*` albo wpis domeny w Coolify UI) — nigdy przez ręczne `ports:` na hoście.
  - Nie trzeba (i nie należy) stawiać drugiego proxy, drugiego Traefika czy nginx-a jako reverse proxy przed aplikacją — Coolify już to robi centralnie dla całego serwera.
- Praktyczna konsekwencja dla Ciebie przy przygotowywaniu KAŻDEGO repo: traktuj serwer jako **wspólną, współdzieloną przestrzeń** — sprawdzaj, czy zmiana w jednym repo (port, nazwa sieci Docker, nazwa wolumenu) nie zakłada cichego założenia "jestem tu sam", bo nie jesteś.

---

## Krok 0: Wykryj stack repozytorium

Zanim cokolwiek zmienisz, sprawdź co jest w repo:

| Plik/sygnał w repo | Stack | Idź do |
|---|---|---|
| `next.config.js`/`.ts` z `output: 'export'` | Next.js **static export** — brak backendu | `references/static-sites.md` |
| `next.config.js`/`.ts` bez `output: 'export'`, jest `app/` lub `pages/` | Next.js (SSR/API routes) | `references/nextjs.md` |
| `nest-cli.json` | NestJS backend | `references/node-backend.md` |
| `package.json` z `express`/`fastify`, brak `next`/`nest` | Node ogólny backend | `references/node-backend.md` |
| `requirements.txt` / `pyproject.toml` + `fastapi`/`django`/`flask` | Python | `references/python.md` |
| `composer.json` + `laravel/framework` | PHP/Laravel/Filament | `references/php-laravel.md` |
| `composer.json` bez markera frameworka (`laravel/framework`, `symfony/framework-bundle`) | PHP plain (własny router + kontrolery) | `references/php-plain.md` |
| Sam `index.html`/Astro/Vite bez backendu | Strona statyczna | `references/static-sites.md` |
| Widzisz frontend + backend + baza w JEDNYM repo | Monorepo multi-service | `references/multi-service-compose.md` (dodatkowo, obok pliku dla konkretnego stacku) |
| Frontend, backend, baza to OSOBNE repo | Każde repo osobno jako osobny "resource" w Coolify, każde wg swojego pliku referencyjnego | — |

Jeśli nie jesteś pewien (np. brak jasnych plików konfiguracyjnych), zajrzyj do `package.json` → `scripts` i `dependencies`, albo zapytaj usera jednym konkretnym pytaniem zamiast zgadywać.

---

## Zasada A: Porty — zero hardcoded, zero kolizji

### Dla pojedynczej aplikacji (Coolify resource typu "Application" — Nixpacks albo własny Dockerfile)

Coolify **już to robi dobrze automatycznie**: każdej aplikacji tego typu przydziela osobny, wewnętrzny port i wystawia ją na zewnątrz przez własną domenę/subdomenę (`*.sslip.io` na start). Nie musisz nic randomizować ręcznie. Twoje zadanie:
- Aplikacja MUSI nasłuchiwać na porcie z **zmiennej środowiskowej `PORT`** (albo `process.env.PORT` w Node, `$PORT` w Pythonie), nie na sztywno wpisanej liczbie. Coolify/Nixpacks ustawia tę zmienną za Ciebie.
- Jeśli masz własny Dockerfile z `EXPOSE`, wpisany port musi się zgadzać z tym, co faktycznie nasłuchuje w środku — i musi być wpisany też w Coolify UI (**Ports Exposes**), żeby Traefik wiedział gdzie kierować ruch.

### Dla wielu serwisów w jednym repo (`docker-compose.yml`)

**To jest źródło realnych kolizji na serwerze, na którym stoi wiele aplikacji.** Zasada jest prosta:

> **NIGDY nie publikuj portów na hosta w `docker-compose.yml` używanym przez Coolify.**

Czyli usuń całkowicie takie linie:
```yaml
# ŹLE — na współdzielonym serwerze to prędzej czy później się zderzy z inną apką
ports:
  - "3000:3000"
  - "5432:5432"
```

Zamiast tego:
1. **Wewnątrz sieci Docdocker-compose** serwisy gadają ze sobą po nazwie serwisu i porcie WEWNĘTRZNYM (`db:5432`, `redis:6379`, `api:3000`) — to nigdy nie wychodzi na hosta, więc nie ma jak się zderzyć.
2. **Na zewnątrz wystawiasz TYLKO to, co ma być publiczne** (zwykle jeden serwis — frontend albo API gateway), i to przez **Traefik, nie przez `ports:`**. Dwa sposoby, oba bezkolizyjne bo Coolify/Traefik sam zarządza realnym portem na hoście:
   - **Magic env var w compose** (rekomendowane, bo działa automatycznie z repo, powtarzalnie):
     ```yaml
     services:
       app:
         environment:
           SERVICE_FQDN_APP_3000: /   # "3000" to WEWNĘTRZNY port kontenera
     ```
     Coolify samo generuje z tego regułę Traefika. Domena i realny zewnętrzny port to już warstwa Coolify, nie Twojego compose.
   - **Ręcznie w UI**: Coolify → zasób → **Domains** → `https://twojadomena.pl:3000` (port wewnętrzny kontenera jako sufiks). Coolify tworzy label Traefika automatycznie.
3. Jeśli baza danych/Redis MUSZĄ być dostępne z zewnątrz (rzadko potrzebne, zwykle zły pomysł) — użyj **losowego, wysokiego portu** i sprawdź w Coolify UI czy nie jest zajęty, zamiast wpisywać na sztywno popularny port typu 5432/6379/3306, który z dużym prawdopodobieństwem koliduje z inną apką na tym samym Hetznerze.

**Reguła kciuka**: jeśli w `docker-compose.yml`, który ma trafić do Coolify, widzisz sekcję `ports:` z hostowym mapowaniem — to prawie zawsze błąd. Wyjątkiem jest praca lokalna (masz osobny `docker-compose.override.yml` albo `docker-compose.dev.yml` tylko do lokalnego devu, z portami, i ten plik NIE idzie do Coolify).

---

## Zasada B: Zmienne środowiskowe — zero hardcoded

### W kodzie aplikacji
- Żadnych URL-i, kluczy API, haseł, connection stringów wpisanych na sztywno w kodzie źródłowym ani w plikach configów commitowanych do repo.
- Wszystko czytane z `process.env.X` (Node), `os.environ["X"]` (Python), `env("X")` (PHP/Laravel) — z sensownym fallbackiem dla dev, ale bez sekretów jako fallback.

### W Dockerfile
- Nie używaj `ENV KLUCZ=wartosc` do sekretów ani do niczego co user może chcieć zmienić bez rebuildu obrazu.
- `ARG`/`ENV` w Dockerfile są OK tylko dla rzeczy naprawdę stałych w czasie builda (np. `NODE_ENV=production`), nigdy dla haseł/kluczy/URLi środowiskowych.

### W `docker-compose.yml`
Każda wartość przez `${VAR:-default}`, NIGDY na sztywno:

```yaml
environment:
  NODE_ENV: "${NODE_ENV:-production}"
  DATABASE_URL: "${DATABASE_URL:-}"        # WYMAGANE — Secret w Coolify, bez sensownego defaultu
  REDIS_URL: "${REDIS_URL:-redis://redis:6379}"
  NEXT_PUBLIC_API_URL: "${NEXT_PUBLIC_API_URL:-}"
```

Domyślne wartości (`:-default`) pełnią rolę fallbacka/dokumentacji — realne wartości produkcyjne user wpisuje w Coolify UI → **Environment Variables**, bez commitowania niczego.

### Build Time vs Runtime (Coolify UI)
Coolify ma dla każdej zmiennej dwa przełączniki: **Available at Buildtime** i **Available at Runtime**.
- Zmienne potrzebne **podczas budowania obrazu** (np. `NEXT_PUBLIC_*` w Next.js, bo te są wpiekane w bundle na etapie builda) → włącz **Buildtime**.
- Sekrety typu `DATABASE_URL`, `API_KEY`, hasła do bazy → włącz **Runtime** (i zwykle też Buildtime jeśli coś w trakcie builda ich potrzebuje, np. migracje uruchamiane w build stage — ale generalnie unikaj tego, migracje lepiej odpalać w entrypoincie przy starcie kontenera, nie w trakcie builda).
- Wszystko oznaczone jako sekret/hasło → zaznacz **Is Secret**, inaczej ląduje jawnie w logach builda.

### Zawsze dołącz `.env.example`
Do repo dodaj (albo zaktualizuj) plik `.env.example` z listą WSZYSTKICH zmiennych jakich aplikacja potrzebuje, bez realnych wartości — to jest jednocześnie dokumentacja tego, co user musi wkleić w Coolify UI. Przykład w `references/multi-service-compose.md`.

## Zasada C: Pliki użytkownika/uploady — współdzielony Object Storage, nie lokalny dysk

Serwer, na którym stoi wiele aplikacji, ma ograniczony i współdzielony dysk. **Żadna aplikacja nie powinna trzymać uploadów, generowanych plików, backupów czy dużych assetów na lokalnym systemie plików kontenera/hosta** — to prędzej czy później zapcha dysk kosztem wszystkich pozostałych aplikacji na tym samym serwerze, a przy tym dane giną przy przebudowie/migracji kontenera, jeśli nie są na trwałym wolumenie.

Zamiast tego korzystaj ze **współdzielonego Hetzner Object Storage** (S3-compatible), dostępnego pod endpointem:
```
https://fsn1.your-objectstorage.com
```

### Wzorzec konfiguracji (env-driven, jak wszystko inne)

```yaml
environment:
  S3_ENDPOINT: "${S3_ENDPOINT:-https://fsn1.your-objectstorage.com}"
  S3_REGION: "${S3_REGION:-fsn1}"
  S3_BUCKET: "${S3_BUCKET:-}"          # WYMAGANE — osobny bucket per aplikacja/produkt
  S3_ACCESS_KEY: "${S3_ACCESS_KEY:-}"  # WYMAGANE, Secret
  S3_SECRET_KEY: "${S3_SECRET_KEY:-}"  # WYMAGANE, Secret
```

Każda aplikacja (Hovera, ClubDesk, Shootero, FaktuPilot itd.) korzysta z **tego samego endpointu**, ale z **osobnym bucketem** (albo osobnym prefiksem w ramach jednego bucketu, jeśli wolisz jedną wspólną przestrzeń) — dokładnie ta sama logika izolacji, co przy bazach danych w Zasadzie A: współdzielona infrastruktura, ale rozdzielone przestrzenie nazw, żeby żaden produkt nie nadpisał/nie zobaczył danych innego.

### Co konkretnie trzymać w S3, nie lokalnie

- Uploady użytkowników (zdjęcia, dokumenty, faktury, załączniki).
- Wygenerowane pliki (PDF-y, eksporty, raporty).
- Backupy baz danych (patrz też Mailcow/backup pattern z innych dokumentów — ta sama zasada).
- Statyczne assety generowane w runtime (nie te wpiekane w build — te zostają w obrazie/CDN).

### Kiedy lokalny wolumen nadal ma sens

Tylko dla danych, które MUSZĄ być szybko i lokalnie dostępne dla samego procesu bazy/cache (`db-data`, `redis-data` w `references/multi-service-compose.md`) — to nie są "pliki aplikacji", tylko silniki baz danych, które i tak mają własny mechanizm backupu/replikacji. Nie miej tam natomiast folderu `uploads/` aplikacji — to zawsze S3.

### Integracja w kodzie

Większość frameworków ma gotowe adaptery S3-compatible (AWS SDK z custom `endpoint`, `@aws-sdk/client-s3` w Node, `boto3` w Pythonie z `endpoint_url`, Laravel `filesystems.php` z driverem `s3` i custom endpoint) — nie trzeba pisać własnej integracji od zera, tylko poprawnie skonfigurować klienta pod endpoint Hetznera zamiast domyślnego `s3.amazonaws.com`.

---

## Krok 1: Wybór typu zasobu w Coolify

| Sytuacja | Wybierz w Coolify |
|---|---|
| Repo ma zwykły `package.json`/`requirements.txt`/`composer.json`, BRAK własnego `Dockerfile` | **Application** → źródło (Public/Private Repository) → Build Pack: **Nixpacks** (autodetekcja) |
| Repo ma własny `Dockerfile`, ale TYLKO jedną usługę (bez bazy danych obok) | **Application** → Build Pack: **Dockerfile** |
| Repo ma `docker-compose.yml` (baza danych, cache, worker obok appki) | **Application** → Build Pack: **Docker Compose** (NIE "Docker Compose Empty" — traci się auto-deploy z gita) |
| Sama strona statyczna (HTML/CSS/JS albo build output z Astro/Vite/Next static export) | **Application** → zaznacz **Is it a static site?** → Publish Directory wskazuje folder z gotowymi plikami |

**Pułapka #1, bardzo częsta**: masz własny `Dockerfile`/`docker-compose.yml`, ale wybierasz domyślnie proponowaną opcję i Coolify i tak odpala **Nixpacks**, który generuje WŁASNEGO Dockerfile'a i ignoruje Twój. Sygnał w logach: `Generating nixpacks configuration` + `Found application type: ...`. Jeśli to widzisz — zły Build Pack, zmień ręcznie na **Dockerfile** albo **Docker Compose**.

**Ścieżka do pliku compose**: Coolify domyślnie szuka `docker-compose.yaml` (z „a"). Jeśli w repo masz `.yml`, ustaw ręcznie w polu **Docker Compose Location**: `/docker-compose.yml`.

---

## Krok 2: Dockerfile — uniwersalne dobre praktyki (niezależnie od stacku)

1. **Multi-stage build** zawsze gdy jest krok kompilacji/builda (Next.js, NestJS z TypeScript, Composer, itp.) — jeden stage buduje, drugi (lżejszy, bez dev-dependencies) uruchamia. Mniejszy obraz, szybszy deploy, mniejsza powierzchnia ataku.
2. **Nie uruchamiaj jako root** w stage'u runtime, jeśli baza obrazu na to pozwala (`USER node`, `USER www-data` itp.).
3. **Healthcheck** w Dockerfile albo w compose — Coolify i Traefik używają go, żeby wiedzieć czy kontener faktycznie żyje, nie tylko czy proces wystartował.
4. **Nie mieszaj shella z instrukcjami Dockera** w `COPY`/`ADD` — `COPY foo bar 2>/dev/null || true` nie działa, bo `COPY` to nie shell (błąd: `failed to solve: lstat /2>/dev/null: no such file or directory`). Jeśli plik może nie istnieć, obsłuż to w `RUN` (prawdziwy shell) albo dodaj stub do repo.
5. **Pułapka platform-reqs dla stacków PHP i Node z natywnymi ext'ami.** Gdy vendor stage używa lekkiego obrazu (`composer:2` w Alpine, `node:alpine` bez `build-base`), a paczki w `composer.json`/`package.json` żądają rozszerzeń/bindingów natywnych których w tym obrazie nie ma, resolver kończy `exit code: 2` **zanim zacznie ściągać cokolwiek**. Symptom: log builda pokazuje "did not complete successfully: exit code: 2" na pierwszym `composer install` / `npm ci`. Fix — dwa kroki:
   - w vendor stage: `composer install --ignore-platform-reqs --no-autoloader ...` (albo dla Node: `npm ci --ignore-scripts` + rebuild bindings w runtime),
   - w runtime stage (gdzie masz wszystkie ext'y / toolchain): `composer dump-autoload --optimize --classmap-authoritative` PO `COPY` źródeł (bo classmap wtedy widzi `src/`).

   Konkretny działający Dockerfile per język w `references/php-plain.md`, `references/php-laravel.md`, `references/node-backend.md`.
6. Konkretne szablony per stack (dokładna treść Dockerfile) są w plikach `references/` — użyj tego dla wykrytego stacku zamiast pisać od zera.

---

## Krok 3: Multi-service — baza danych, Redis, worker

Jeśli repo/produkt potrzebuje bazy danych, cache czy workera obok głównej aplikacji, przeczytaj **`references/multi-service-compose.md`** — tam jest pełny, gotowy do wklejenia szablon `docker-compose.yml` z:
- bezkolizyjnym podejściem do portów (Zasada A powyżej),
- wszystkimi env-ami parametryzowanymi (Zasada B powyżej),
- healthchecks dla bazy/cache,
- wolumenami dla persystencji danych,
- właściwą kolejnością startu (`depends_on: condition: service_healthy`).

---

## Krok 4: Domena, SSL, proxy

- DNS musi wskazywać na IP serwera **zanim** zaczniesz deploy — inaczej Let's Encrypt (HTTP-01 challenge) nie przejdzie.
- Jeśli domena jest za **Cloudflare**: przy pierwszym deployu ustaw chmurkę na **szaro (DNS only)**, poczekaj aż Coolify wystawi certyfikat, dopiero potem włącz proxy z powrotem (`SSL/TLS mode: Full (strict)`).
- Sprawdzenie propagacji: `dig twojadomena.pl +short` powinno zwrócić adres IP serwera, oraz https://www.whatsmydns.net dla weryfikacji globalnej.
- Jeśli aplikacja generuje linki `http://` mimo HTTPS (bo Traefik terminuje TLS przed kontenerem i przekazuje ruch po HTTP z nagłówkiem `X-Forwarded-Proto`) — potrzebujesz **trust proxy** w aplikacji. Konkretna implementacja per stack jest w odpowiednim pliku `references/`.

---

## Krok 5: Diagnostyka — kolejność patrzenia w logi (uniwersalna)

1. **Deployment logs** — Coolify → zasób → **Deployments** → najnowszy. Błąd builda Dockera i błąd startu compose widać tutaj.
2. **Container logs → [nazwa serwisu]** — Coolify → zasób → **Logs**, przełącznik między serwisami (app/db/redis/worker). Tu widać co się dzieje po starcie kontenera.
3. **Terminal do kontenera** — Coolify → zasób → **Terminal** → wybierz serwis → sprawdź zmienne (`env`), połączenie z bazą, itp.
4. **Traefik logs** (dla 502/404):
   ```bash
   docker logs coolify-proxy 2>&1 | tail -100
   ```

### Flowchart

```
Deploy failuje
│
├─ Log deployu: "Nixpacks" / "Found application type"?
│   → Zły Build Pack. Zmień na Dockerfile albo Docker Compose (Krok 1).
│
├─ Log deployu: "docker-compose.yaml not found"?
│   → Ustaw Docker Compose Location na /docker-compose.yml.
│
├─ Log builda: "failed to solve: lstat /..."?
│   → Shellowy operator w COPY/ADD. Popraw składnię Dockerfile (Krok 2.4).
│
├─ Log deployu: baza/cache "unhealthy"?
│   → Sprawdź czy WSZYSTKIE wymagane env dla bazy są ustawione w Coolify UI.
│   → Sprawdź czy wolumen bazy nie jest "brudny" z poprzedniej próby (Storages → Delete → redeploy, TYLKO na etapie stawiania, nigdy na produkcji z danymi).
│
├─ Aplikacja startuje ale od razu crashuje / "missing env"?
│   → Brakuje wymaganej zmiennej w Coolify UI. Sprawdź .env.example z repo — czy wszystko stamtąd jest ustawione.
│
├─ Kontener OK w logach, ale 404 na domenie?
│   → Traefik nie zna portu. Dopisz port jako sufiks w Domains, albo SERVICE_FQDN_<SERWIS>_<PORT> w compose (Zasada A).
│
├─ HTTPS działa ale aplikacja generuje linki http://?
│   → Brakuje trust proxy / obsługi X-Forwarded-Proto — patrz references/ dla Twojego stacku.
│
└─ 502 Bad Gateway?
    → docker logs coolify-proxy → sprawdź czy Traefik w ogóle widzi upstream (czy serwis faktycznie działa i nasłuchuje na zadeklarowanym porcie).
```

---

## Anty-wzorce (niezależnie od stacku)

- ❌ `ports:` z hostowym mapowaniem w `docker-compose.yml` na serwerze z wieloma aplikacjami (Zasada A).
- ❌ Sekrety, URL-e, klucze API hardcoded w kodzie, Dockerfile albo compose (Zasada B).
- ❌ Ustawianie sekretów w Coolify bez włączonego przełącznika **Is Secret**.
- ❌ Nixpacks dla repo z własnym Dockerfile'em/compose (Coolify wtedy ignoruje Twoje pliki).
- ❌ Shellowe operatory (`2>/dev/null`, `||`, `&&`) bezpośrednio w `COPY`/`ADD`.
- ❌ Generowanie sekretów (np. kluczy szyfrujących) w runtime przy starcie kontenera zamiast wymagać ich z env — przy restarcie/skalowaniu dostajesz różne wartości i tracisz spójność (sesje, szyfrowanie itp.).
- ❌ Zmiana już ustawionego sekretu bazującego na inicjalizacji wolumenu (np. hasło roota bazy) bez świadomości, że to wymaga wyczyszczenia wolumenu.
- ❌ Kasowanie wolumenów produkcyjnych "żeby sprawdzić czy zadziała" — tylko na etapie budowania/testów.

---

## Checklist — co zrobić krok po kroku, gdy user prosi "przygotuj to repo do Coolify"

1. Wykryj stack (Krok 0). Jeśli monorepo z kilkoma serwisami — zidentyfikuj wszystkie (frontend, backend, baza, worker, redis itd.).
2. Sprawdź czy jest już `Dockerfile`/`docker-compose.yml`:
   - Jest → przejrzyj pod kątem Zasad A i B oraz dobrych praktyk z Kroku 2, popraw naruszenia.
   - Brak → wygeneruj wg szablonu z odpowiedniego pliku `references/`, dopasowanego do wykrytego stacku i realnej struktury repo (nazwy folderów, package manager, itp. — sprawdź `package.json`/`requirements.txt` zamiast zakładać na sztywno).
3. Upewnij się, że ŻADEN port nie jest hardcoded na hosta w compose, a aplikacja nasłuchuje na `PORT` ze środowiska.
4. Upewnij się, że WSZYSTKIE env są parametryzowane `${VAR:-default}`, bez realnych sekretów w repo.
5. Sprawdź czy aplikacja generuje/przechowuje pliki (uploady, PDF-y, eksporty) — jeśli tak, skonfiguruj S3-compatible storage (Zasada C) zamiast lokalnego wolumenu.
6. Dodaj/zaktualizuj `.env.example` z pełną listą zmiennych (łącznie z S3_*, jeśli dotyczy).
7. Napisz dla usera gotową listę zmiennych do wklejenia w Coolify UI (z adnotacją które jako Secret, które Buildtime/Runtime).
8. Podaj dokładne ustawienia do wyboru w Coolify UI: typ zasobu, Build Pack, port/domena, kolejność deployu jeśli multi-service.
9. Jeśli coś w danym repo jest niejednoznaczne (np. brak jasnego portu nasłuchu, nietypowa struktura) — zapytaj usera JEDNYM konkretnym pytaniem zamiast zgadywać po cichu.

---

## Pliki referencyjne

- `references/nextjs.md` — Next.js (SSR/API routes), standalone output, Dockerfile multi-stage, trust proxy
- `references/node-backend.md` — NestJS/Express/Node ogólny backend
- `references/python.md` — FastAPI/Django/Flask
- `references/php-laravel.md` — PHP/Laravel/Filament + MySQL (pełny materiał z pierwotnego wdrożenia, łącznie z pułapkami APP_KEY, composer, MySQL init)
- `references/php-plain.md` — Plain PHP (własny router + kontrolery, bez frameworka) + MySQL/Redis, `php:8.3-apache`, ręczne migracje SQL, healthcheck, hardening `.htaccess` za Traefikiem
- `references/static-sites.md` — strony statyczne (HTML/Astro/Vite/Next static export)
- `references/multi-service-compose.md` — pełny szablon docker-compose.yml dla monorepo z bazą/cache/workerem, plus wzorzec `.env.example`
