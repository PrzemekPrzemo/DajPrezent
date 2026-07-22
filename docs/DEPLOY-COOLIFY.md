# Deployment przez Coolify na serwerze Hetzner

Alternatywa dla `docs/DEPLOY-PLESK.md` — zamiast gołego VPS z Pleskiem,
Coolify (self-hosted PaaS) na Hetzner Cloud, z appką budowaną z repo przez
dołączony `Dockerfile`. Repo jest już przygotowane pod ten model:

- `Dockerfile` — jeden obraz produkcyjny: nginx + php-fpm 8.4 + queue
  worker (`queue:work redis`) + scheduler (`schedule:run` co minutę),
  wszystko pod `supervisord`. Assety budowane przez Vite w osobnym stage'u.
- `docker/` — konfiguracja nginx, php-fpm, opcache, supervisor, entrypoint.
- `.dockerignore` — wyklucza `vendor/`, `node_modules/`, `.env`, testy.
- `bootstrap/app.php` — `trustProxies(at: '*')`, bo appka siedzi za
  Traefikiem Coolify (bez tego `isSecure()`/HSTS i linki `https://` się
  psują).
- Healthcheck: `/up` (domyślny endpoint Laravela 11), używany zarówno przez
  `HEALTHCHECK` w obrazie, jak i przez Coolify.

## 1. Serwer na Hetzner

1. Hetzner Cloud Console → **New Project** → **Add Server**.
   - Lokalizacja: Nürnberg/Falkenstein (najniższy ping z PL) albo Helsinki.
   - Obraz: **Ubuntu 24.04**.
   - Typ: min. **CPX21** (3 vCPU / 4 GB RAM) na start; **CPX31** (4 vCPU /
     8 GB) jeśli od razu ruszają worker + scheduler + Filament pod większym
     ruchem. Coolify sam w sobie zużywa ~0.5–1 GB RAM.
   - Firewall (Hetzner Cloud Firewall, nie iptables ręcznie): wejście tylko
     `22` (SSH, docelowo ograniczone do Twojego IP), `80`, `443`. Port
     `8000` (dashboard Coolify) tymczasowo na czas instalacji, potem
     zawężony do whitelisty IP albo dostępny przez VPN/SSH tunnel.
2. SSH do serwera i instalacja Coolify:
   ```bash
   ssh root@<IP_SERWERA>
   curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash
   ```
   Instalator stawia Dockera, Coolify i wypisuje adres dashboardu
   (`http://<IP_SERWERA>:8000`). Utwórz tam konto admina przy pierwszym
   wejściu.

## 2. DNS

W panelu rejestratora `dajprezent.pl`:

- `A dajprezent.pl` → IP serwera
- `A www.dajprezent.pl` → IP serwera (albo CNAME na `dajprezent.pl`)
- `A *.dajprezent.pl` → IP serwera (custom domeny CNAME klientów Pro
  rozwiążą się przez to samo IP, dopóki Coolify/Traefik obsługuje SNI per
  domena z panelu klienta — patrz sekcja 7)
- Środowisko staging (opcjonalnie): `A stg.dajprezent.pl` → to samo IP,
  osobna aplikacja w Coolify na branchu `develop`/`staging`.

## 3. Nowy projekt i aplikacja w Coolify

1. **Projects** → **New Project** → np. `dajprezent`.
2. **+ New Resource** → **Application** → **Public/Private Git Repository**
   (dla prywatnego repo: podłącz GitHub App Coolify albo deploy key).
   - Repository: `przemekprzemo/dajprezent`
   - Branch: `main`
   - **Build Pack: Dockerfile** (Coolify wykryje `Dockerfile` w roocie
     automatycznie).
   - Port: `8080` (patrz `EXPOSE 8080` w `Dockerfile` — nginx w kontenerze
     nie binduje się na uprzywilejowanym porcie).
3. **Domains** → dodaj `https://dajprezent.pl` (i `https://www.dajprezent.pl`
   jako redirect). Coolify/Traefik sam wystawi certyfikat Let's Encrypt po
   propagacji DNS.
4. **Health Check** → ścieżka `/up`, port `8080` — Coolify domyślnie też
   honoruje `HEALTHCHECK` z obrazu, ale warto ustawić jawnie w zakładce
   aplikacji, żeby deploye z niezdrowym kontenerem nie podmieniały ruchu
   (zero-downtime deploy czeka na zielony healthcheck nowego kontenera).

## 4. Baza danych i Redis

Najprościej jako osobne zasoby Coolify w tym samym projekcie (własna sieć
Docker, adresowalne po nazwie serwisu):

1. **+ New Resource** → **Database** → **MySQL 8** → zapamiętaj wygenerowane
   hasło i nazwę serwisu (np. `mysql-xyz`).
2. **+ New Resource** → **Database** → **Redis 7**.
3. W aplikacji dajprezent ustaw zmienne (sekcja 5) tak, by `DB_HOST` /
   `REDIS_HOST` wskazywały na nazwy tych serwisów — Coolify podłącza
   wszystko do wspólnej sieci projektu, więc `mysql-xyz` / `redis-xyz`
   rozwiązują się bez wystawiania portów na świat.

Alternatywa: zewnętrzna managed baza (np. Hetzner nie ma natywnego DBaaS,
ale można użyć osobnego VPS albo np. PlanetScale/Aiven) — wtedy `DB_HOST`
to zewnętrzny endpoint, a firewall Hetznera musi dopuszczać ruch z IP
serwera Coolify.

## 5. Zmienne środowiskowe

W Coolify → aplikacja → **Environment Variables** wklej wartości z
`.env.example`, uzupełniając produkcyjne sekrety. Kluczowe różnice
względem `.env.example`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dajprezent.pl
APP_KEY=                      # wygeneruj lokalnie: php artisan key:generate --show

DB_CONNECTION=mysql
DB_HOST=mysql-xyz              # nazwa serwisu Coolify z sekcji 4
DB_PORT=3306
DB_DATABASE=dajprezent
DB_USERNAME=dajprezent
DB_PASSWORD=...                # z panelu MySQL Coolify

SESSION_DOMAIN=.dajprezent.pl  # ważne dla custom-domenowych list (CNAME Pro)
REDIS_HOST=redis-xyz
QUEUE_CONNECTION=redis
CACHE_STORE=redis

MAIL_MAILER=postmark
POSTMARK_TOKEN=...

AWS_ACCESS_KEY_ID=...           # Wasabi/Backblaze — zdjęcia prezentów
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...
AWS_ENDPOINT=...

PAYU_BASE_URL=https://secure.payu.com   # produkcyjny, nie sandbox .snd.
PAYU_POS_ID=...
PAYU_CLIENT_ID=...
PAYU_CLIENT_SECRET=...
PAYU_MD5_KEY=...

KSEF_ENV=production
KSEF_TOKEN=...

TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...

SELLER_BANK_ACCOUNT=...
SELLER_PHONE=...
```

Nie dodawaj `RUN_MIGRATIONS=true` na stałe (patrz sekcja 6) — kontener
uruchamia się bez migracji domyślnie, żeby restart/skalowanie nie
odpalało migracji równolegle.

## 6. Pierwszy deploy — migracje i seed

Pierwszy deploy tworzy kontener, ale bazę trzeba zainicjować ręcznie:

1. Coolify → aplikacja → **Deploy** (buduje obraz z `Dockerfile`, startuje
   kontener, czeka na zielony `/up`).
2. Po starcie: aplikacja → **Terminal** (Coolify daje shell do
   działającego kontenera) i uruchom:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force --class=PackageSeeder
   ```
3. Kolejne deploye ze zmianami w schemacie: albo ten sam ręczny krok przez
   **Terminal**, albo ustaw w Coolify → aplikacja → **Advanced** →
   **Post-deployment Command**: `php artisan migrate --force` — wtedy
   Coolify odpala migrację automatycznie po każdym udanym buildzie, zanim
   przełączy ruch na nowy kontener.

## 7. Trwały storage

`storage/app/public` i `storage/app/private` (zdjęcia prezentów, eksporty
RODO/CSV, wygenerowane PDF-y) muszą przetrwać kolejne deploye — inaczej
znikają razem ze starym kontenerem. W Coolify → aplikacja →
**Storages** → **+ Add** → **Persistent Volume**:

```
Source path (nazwa wolumenu): dajprezent-storage
Destination path:             /var/www/html/storage/app
```

`entrypoint.sh` w obrazie sam naprawia właściciela (`chown www:www`) po
zamontowaniu wolumenu, więc świeży wolumen (root-owned) nie blokuje
zapisu. Alternatywa docelowa (zalecana przy skalowaniu do >1 instancji):
przenieść appkę na dysk S3-kompatybilny (`FILESYSTEM_DISK=s3`, już
skonfigurowany w `.env.example`) i traktować wolumen lokalny tylko jako
fallback dla dev/staging.

## 8. Custom domeny klientów (pakiet Pro)

Pakiet Pro pozwala właścicielowi listy podpiąć własną domenę (CNAME).
Traefik w Coolify obsługuje to przez dodanie kolejnej domeny do tej samej
aplikacji (**Domains** → **+ Add Domain**) — SSL Let's Encrypt wystawia
się automatycznie po weryfikacji DNS. To wymaga ręcznej (lub
zautomatyzowanej przez API Coolify) rejestracji domeny klienta przy
aktywacji dodatku — do zaimplementowania w module Billing jako wywołanie
Coolify API (`POST /api/v1/applications/{uuid}/domains`) przy zmianie
`tenants.custom_domain`.

## 9. Zero-downtime deploy i rollback

Coolify buduje nowy obraz z `Dockerfile`, startuje nowy kontener obok
starego, czeka na zielony healthcheck `/up`, dopiero wtedy przełącza
Traefika i ubija stary kontener — więc deploy z tego repo jest
zero-downtime "z pudełka", bez dodatkowej konfiguracji. Rollback: Coolify
→ aplikacja → **Deployments** → wybierz wcześniejszy udany deploy →
**Redeploy**.

## 10. Backupy

- **MySQL** — Coolify → zasób bazy → **Backups** → harmonogram (np.
  codziennie) + docelowy S3-kompatybilny storage (Wasabi/Backblaze, ten
  sam co dla zdjęć). Retencja 30 dni, zgodnie z `DEPLOY-PLESK.md`.
- **Wolumen `dajprezent-storage`** — kopiuj równolegle (Coolify nie robi
  backupu wolumenów appek automatycznie), np. cron na hoście z `rsync`/
  `restic` do drugiego storage'u, albo — docelowo — po prostu trzymaj
  zdjęcia na S3 zamiast na wolumenie (patrz sekcja 7) i backupuj bucket.

## 11. Monitoring

- **Coolify** — wbudowane metryki CPU/RAM/dysk per serwer i per
  aplikacja, plus log stream kontenera (stdout/stderr — nginx, php-fpm,
  queue worker i scheduler w tym repo logują wszystkie na stdout/stderr,
  widoczne w jednym miejscu w Coolify).
- **Sentry** — błędy aplikacyjne, jak w `DEPLOY-PLESK.md` (DSN w env).
- **Healthcheck zewnętrzny** — Uptime Robot / Better Uptime na
  `https://dajprezent.pl/up`.

## 12. Bezpieczeństwo

- Hetzner Cloud Firewall: `22` tylko z zaufanych IP, `80`/`443` z 0.0.0.0/0,
  `8000` (dashboard Coolify) zawężony do VPN/whitelisty po zakończeniu
  wstępnej konfiguracji.
- Sekrety (PayU, KSeF, Postmark, S3) wyłącznie jako zmienne środowiskowe w
  Coolify (szyfrowane w jego bazie) — nigdy w repo ani w `.env` commitowanym
  do gita.
- `APP_DEBUG=false` i `APP_ENV=production` na produkcji — inaczej stack
  trace z danymi wycieka do publicznych odpowiedzi błędów.
- Aktualizacje Coolify (`coolify update` z dashboardu) i obrazu bazowego
  (`php:8.4-fpm-alpine` w `Dockerfile`) rób regularnie — Renovate/Dependabot
  może pilnować wersji w `Dockerfile` tak samo jak `composer.lock`.
