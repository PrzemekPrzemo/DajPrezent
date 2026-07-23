# PHP / Laravel / Filament — Coolify

> Ten plik jest częścią uniwersalnego skilla `coolify-deploy-prep`. Użyj go, gdy Krok 0 w głównym `SKILL.md` wykryje `composer.json` z `laravel/framework`. Materiał pochodzi z prawdziwego wdrożenia Laravel 13 + Filament 5 + MySQL i zawiera dokładniejsze, specyficzne dla PHP wersje zasad opisanych ogólnie w głównym pliku (porty, env, multi-stage build) — czytaj to jako uszczegółowienie, nie zamiennik.

# Coolify + Laravel/Filament deploy — troubleshooting i best practices

Ten skill jest wyciągnięty z prawdziwego wdrożenia. Kolejność sekcji odpowiada kolejności, w jakiej sprawy zwykle się psują.

## 1. Wybór typu zasobu w Coolify — pułapka #1

**Problem**: Coolify ma sześć typów zasobu i domyślnie kusi „Application → Public Repository". Dla Laravela z własnym Dockerfile'em + `docker-compose.yml` to zły wybór, bo wtedy Coolify odpala **Nixpacks**, który sam generuje własnego głupiego Dockerfile'a (`nginx:alpine` + `COPY /app/out .`) — ignoruje Twój.

**Fix**: zamiast tego wybierz:
1. **Public Repository** → potem na następnym ekranie w polu **Build Pack** rozwiń i wybierz **Docker Compose**.
2. Alternatywnie „Private Repository (with GitHub App)" jeśli repo prywatne.
3. NIE „Docker Compose Empty" — traci się auto-deploy z gita.

Sygnał w logach że wpadłeś w Nixpacks: `Generating nixpacks configuration` + `Found application type: php`. Jak to widzisz, jesteś na złej ścieżce.

## 2. Ścieżka do docker-compose

**Problem**: `Docker Compose file not found at: /docker-compose.yaml`.

**Fix**: Coolify domyślnie szuka `.yaml` (z „a"). Jeśli w repo masz `.yml`, w polu **Docker Compose Location** zmień na `/docker-compose.yml`.

## 3. Dockerfile — pułapki składni

### 3.1 Nie używaj shellowych operatorów w `COPY`

**Problem**: `Error: failed to solve: lstat /2>/dev/null: no such file or directory`.

**Powód**: Docker `COPY` NIE jest komendą shella. `COPY foo bar 2>/dev/null || true` = próba skopiowania pliku o nazwie `2>/dev/null`.

**Fix**: jeśli plik może nie istnieć, albo dodaj go do repo (choćby jako pusty stub), albo obsłuż w RUN shellu, albo użyj BuildKit `--parents`. Nie mieszaj shella z instrukcjami Dockera.

### 3.2 `composer install` w obrazie `composer:2` pada z exit 2

**Problem**: `did not complete successfully: exit code: 2` przy composerze w Alpine.

**Powód**: obraz `composer:2` NIE ma `ext-intl`, `ext-gd`, `ext-zip`, `ext-imagick`, `ext-fileinfo` — a Filament/Laravel ich wymaga w `require`.

**Fix**: multi-stage build z rozdzieleniem:

```dockerfile
# stage: vendor — tylko paczki, bez wymagań platformowych
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction --no-dev --no-scripts --no-plugins \
    --no-autoloader --ignore-platform-reqs --prefer-dist

# stage: runtime — tu masz wszystkie ext-*, tu generuj autoloader
FROM php:8.4-fpm-alpine AS runtime
# ... docker-php-ext-install gd intl zip pdo_mysql itd ...
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative
```

Klucz: `--ignore-platform-reqs` w stage'u vendor i `dump-autoload` DOPIERO PO skopiowaniu źródeł (bo PSR-4 potrzebuje `app/`, `database/` żeby zmapować klasy).

## 4. `docker-compose.yml` — wszystko przez env, nic hardcoded

**Problem**: Wpisujesz `APP_ENV: production` na sztywno → user chce zmienić na `staging` z Coolify UI → nie może bez commita.

**Fix**: KAŻDA wartość przez `${VAR:-default}`:

```yaml
environment:
    APP_NAME: "${APP_NAME:-mojapka}"
    APP_ENV: "${APP_ENV:-production}"
    APP_DEBUG: "${APP_DEBUG:-false}"
    APP_KEY: "${APP_KEY:-}"                # WYMAGANE, Secret
    APP_URL: "${APP_URL:-https://example.com}"
    DB_CONNECTION: "${DB_CONNECTION:-mysql}"
    DB_HOST: "${DB_HOST:-db}"              # `db` = nazwa serwisu z compose
    DB_PASSWORD: "${DB_PASSWORD:-}"        # WYMAGANE, Secret
    TRUSTED_PROXIES: "${TRUSTED_PROXIES:-*}"
```

Defaulty pełnią rolę fallbacka. Coolify UI nadpisuje bez commita.

## 5. MySQL — inicjalizacja i pułapki

### 5.1 `MYSQL_ROOT_PASSWORD` musi być ustawione

**Problem**: `[ERROR] [Entrypoint]: Database is uninitialized and password option is not specified`.

**Fix**: W Coolify → Environment Variables dodaj:
- `DB_ROOT_PASSWORD` (Secret)
- `DB_PASSWORD` (Secret, inne niż root)

Generuj:
```bash
openssl rand -base64 24
```

Compose musi mieć:
```yaml
db:
    environment:
        MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD:-}"
        MYSQL_PASSWORD: "${DB_PASSWORD:-}"
    healthcheck:
        test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-u", "root", "-p${DB_ROOT_PASSWORD:-}"]
```

### 5.2 Brudny wolumen `db-data`

Jeśli wcześniejsze próby zdążyły napisać coś do wolumenu, MySQL widzi „istniejącą" bazę i odmawia inicjalizacji z hasłem.

**Fix**: Coolify → zasób → **Storages** → obok `db-data` **Delete** → redeploy. Uwaga: to kasuje bazę, więc tylko na etapie stawiania. Na produkcji NIGDY.

### 5.3 Nie zmieniaj `DB_ROOT_PASSWORD` po pierwszej inicjalizacji

MySQL sprawdza hasło TYLKO przy pierwszym starcie z pustym wolumenem. Zmiana potem = `Access denied for user root`, bez opcji naprawy oprócz nuke'a wolumenu.

## 6. `APP_KEY` — 12-factor, wymagaj z env

**Problem**: `file_get_contents(/var/www/html/.env): Failed to open stream` przy próbie `key:generate` w kontenerze.

**Powód**: kontener nie ma pliku `.env` (env leci z Coolify jako zmienne Dockera). `php artisan key:generate` chce zapisać do `.env`.

**Fix**: entrypoint NIE generuje `APP_KEY`. Wymaga go z env, inaczej exit:

```bash
if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] FATAL: APP_KEY nie jest ustawione."
    echo "[entrypoint]   Wygeneruj: printf 'base64:%s\n' \"\$(openssl rand -base64 32)\""
    exit 1
fi

# Stub .env dla artisan about / tinker (szukają pliku nawet gdy env z Dockera).
[ -f .env ] || touch .env
```

APP_KEY ustaw RAZ i nie zmieniaj — zmienny klucz = wszystkie sesje/cookies unieważnione.

## 7. Trust proxy — bez tego HTTPS ginie

**Problem**: Aplikacja generuje URL-e z `http://` mimo że użytkownik wchodzi po HTTPS. Mixed-content. Redirect loops. Cookies "Secure" nie ustawiane.

**Powód**: Coolify + Traefik/Caddy terminuje TLS przed Twoim kontenerem, przekazuje ruch po HTTP z nagłówkiem `X-Forwarded-Proto: https`. Bez `TrustProxies` Laravel nie wie że oryginalny ruch był HTTPS.

**Fix**: `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(
        at: '*',
        headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB,
    );
})
```

Też w `nginx.conf` wewnątrz kontenera:
```
set_real_ip_from 10.0.0.0/8;
set_real_ip_from 172.16.0.0/12;
real_ip_header X-Forwarded-For;
real_ip_recursive on;
```

## 8. Port kontenera vs Traefik — pułapka 404

**Problem**: Kontener wstał, logi OK, ale domena zwraca 404 z Coolify/Traefika.

**Powód**: Coolify domyślnie szuka portu 3000. Twój nginx w kontenerze siedzi np. na 8080. Traefik nie wie gdzie routować.

**Fix — dwie opcje**:

**A) Ręcznie w UI**: Coolify → zasób → **Domains** → wpisz z sufixem: `https://example.com:8080`. Coolify tworzy label Traefika mapujący domenę na port 8080 kontenera. Użytkownik dalej wchodzi na `https://example.com` bez portu.

**B) Magic env w compose** (lepsze, w repo, powtarzalne):
```yaml
services:
    app:
        environment:
            SERVICE_FQDN_APP_8080: /
```
Coolify sam z tego robi Traefika i wystawia domenę na port 8080.

## 9. Diagnostyka — kolejność patrzenia w logi

Jak coś nie działa, patrz w tej kolejności:

1. **Deployment logs** — Coolify → zasób → **Deployments** → najnowszy. Tu widzisz błąd builda Dockera i błąd startu compose.
2. **Container logs → app** — Coolify → zasób → **Logs** → przełącznik `app`. Tu widzisz co robi entrypoint + supervisord + php-fpm + nginx po starcie.
3. **Container logs → db** — ten sam widok, przełącznik `db`. Kiedy `depends_on: service_healthy` pada, właśnie tu jest przyczyna.
4. **Terminal do kontenera** — Coolify → zasób → **Terminal** → wybierz service → `php artisan about`. Natychmiast widzisz APP_KEY / DB connect / config cache.
5. **Traefik logs (dla 502/404)**:
   ```bash
   docker logs coolify-proxy 2>&1 | tail -100
   ```

## 10. DNS + Let's Encrypt

- DNS musi wskazywać na IP serwera **przed** deployem, inaczej Let's Encrypt HTTP-01 challenge failuje.
- Cloudflare: przy pierwszym deployu **wyłącz proxy** (chmurka na szaro). Po wystawieniu certa włącz z `SSL/TLS mode: Full (strict)`.
- Sprawdzenie: `dig example.com +short` powinno zwrócić IP serwera.

## 11. Zmienne — Build Time vs Runtime

Coolify ma dla każdej env-vary dwa toggle: `Available at Buildtime` i `Available at Runtime`. Sekrety typu `DB_PASSWORD`, `APP_KEY` muszą mieć oba włączone — inaczej:
- Tylko Buildtime: dostępne tylko podczas `docker build` (przez ARG). Nie ma ich w runtime kontenera.
- Tylko Runtime (default): OK dla większości sekretów.

Jeśli `DB_ROOT_PASSWORD` masz tylko Buildtime, MySQL dostanie pusty string i zdycha.

## 12. Wolumeny — persystencja

W compose:
```yaml
volumes:
    - db-data:/var/lib/mysql            # MySQL data
    - storage:/var/www/html/storage/app # uploady, logo, favicon
    - bootstrap-cache:/var/www/html/bootstrap/cache
```

Coolify tworzy je automatycznie. Przetrwają redeploy. Nie kasuj bez świadomej decyzji.

## 13. Zestaw ENV do Coolify na start

Minimalny zestaw dla Laravela/Filamenta:

```
# App core (Secret gdy ma znaczek [SECRET])
APP_NAME="MojaApka"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_KEY=base64:xxxxx                    # SECRET, wygeneruj raz

# DB
DB_DATABASE=mojaapka
DB_USERNAME=mojaapka
DB_PASSWORD=xxxxx                       # SECRET
DB_ROOT_PASSWORD=xxxxx                  # SECRET (do usługi mysql)

# Admin (jeśli seedujesz pierwszego usera)
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=xxxxx                    # SECRET

# Mail (Resend / Postmark / Brevo)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=xxxxx                     # SECRET
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=info@example.com

# Proxy
TRUSTED_PROXIES=*

# Sterowanie entrypointem
RUN_MIGRATIONS=true                     # false na dodatkowych replicach
```

## 14. GitHub UI: „Hang in there while we check the branch's status"

To **NIE** jest problem stanu PR-a — to zawieszone UI GitHuba. API zwraca `mergeable_state: clean`.

**Fix**:
1. Twardy refresh: `Ctrl+Shift+R` / `Cmd+Shift+R`.
2. Otwórz PR w nowej karcie / oknie prywatnym.
3. Wyloguj/zaloguj do GH.
4. Ostatecznie: merge przez API/CLI (`gh pr merge` albo MCP).

## 15. Anty-wzorce

- ❌ Zmieniać `APP_KEY` między deployami (unieważnia wszystkie sesje).
- ❌ Zmieniać `DB_ROOT_PASSWORD` po inicjalizacji wolumenu (Access denied, jedyny fix to nuke wolumenu).
- ❌ Ustawiać sekrety bez toggle „Is Secret" (trafiają do logów).
- ❌ Hardcode'ować wartości w `docker-compose.yml` które user może chcieć zmienić z UI.
- ❌ Zostawiać `--no-verify` w git hookach albo skipować pre-commit żeby PR przeszedł.
- ❌ Używać Nixpacks dla aplikacji która ma własny Dockerfile (Coolify wtedy Twój Dockerfile ignoruje).
- ❌ Deployować „Application" gdy masz `docker-compose.yml` (Coolify wybiera Nixpacks).

## 16. Kolejność debugowania — flowchart

```
Deploy failuje
│
├─ Log deployu: "Nixpacks" / "Found application type"?
│   → Zły typ zasobu. Usuń, załóż Docker Compose z Build Pack: Docker Compose.
│
├─ Log deployu: "docker-compose.yaml not found"?
│   → Podaj /docker-compose.yml (Twoja ścieżka), Coolify domyśla się .yaml.
│
├─ Log builda: "failed to solve: lstat /..."?
│   → Zła składnia w Dockerfile. Nie ma shellowych operatorów w COPY.
│
├─ Log builda: "composer install exit code 2"?
│   → Dodaj --ignore-platform-reqs w stage vendor. Autoloader w runtime.
│
├─ Log deployu: "db is unhealthy"?
│   → Log db: "Database is uninitialized and password option..."
│     → Brakuje DB_ROOT_PASSWORD w env.
│     → Albo wolumen db-data brudny — Storages → Delete → redeploy.
│
├─ Log app: "APP_KEY missing" / "file_get_contents(/var/www/html/.env)"?
│   → Ustaw APP_KEY w env. Nigdy nie generuj runtime'owo.
│
├─ Kontener OK ale 404 na domenie?
│   → Traefik nie zna portu. Dopisz :8080 w Domains albo SERVICE_FQDN_APP_8080 w compose.
│
├─ HTTPS działa ale linki generują http://?
│   → trustProxies('*') w bootstrap/app.php.
│
└─ 502 Bad Gateway?
    → docker logs coolify-proxy → sprawdź czy Traefik widzi upstream.
```