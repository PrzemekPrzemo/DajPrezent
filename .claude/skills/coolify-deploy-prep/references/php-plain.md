# Plain PHP + Composer (no framework) — Coolify

> Ten plik jest częścią uniwersalnego skilla `coolify-deploy-prep`. Użyj go gdy Krok 0 w głównym `SKILL.md` wykryje `composer.json` **BEZ** `laravel/framework` (i bez innych markerów frameworka typu `symfony/framework-bundle`). Typowe repo: własny router + kontrolery + migracje SQL sortowane po nazwie, zero framework runtime.
>
> Dla Laravela z Filamentem masz oddzielny plik `php-laravel.md` z pułapkami specyficznymi dla ekosystemu Laravel (Trust Proxies, APP_KEY, `php artisan`). Ten plik pokrywa scenariusze plain-PHP: własny front controller w `public/`, ręczne migracje przez skrypt bash lub PHP, PHPMailer/TCPDF/phpseclib zamiast paczek Laravela.
>
> Materiał destylowany z realnego wdrożenia PHP 8.3 + MySQL 8 + Redis (~50 klientów, KSeF/e-paragony/faktury) na Hetzner + Coolify. Cały setup wyprodukowany za pomocą tego skilla dostępny jest w PrzemekPrzemo/Billu-System (`Dockerfile`, `docker-compose.yaml`, `docker/*` — do bezpośredniego skopiowania).

---

## 1. Zanim zaczniesz — sanity check

Zanim wygenerujesz Dockerfile'a, przejdź po tej krótkiej liście:

- [ ] Front controller: `public/index.php` (albo `web/index.php`). Jeśli aplikacja odpala się z `index.php` w rootcie — przenieś do `public/` **przed** dokerem, bo inaczej `.htaccess` + hardening (blokada `config/`, `src/`, `vendor/`) nie ma sensu.
- [ ] `composer.json` + `composer.lock` w repo (lock **musi** być commitowany — bez niego build jest niereprodukowalny).
- [ ] `.htaccess` w `public/` z rewrite'em do `index.php` — Apache używamy w kontenerze, bo to najprostsza konfiguracja PHP-under-webserver (nginx+php-fpm to więcej ruchomych części dla plain PHP).
- [ ] Konfig w `config/*.php` czyta env przez `getenv()` / `$_ENV` (12-factor). Jeśli hardkodujesz `localhost` w `config/database.php` — najpierw wyparametryzuj (patrz Zasada B w głównym `SKILL.md`), potem Docker.
- [ ] Migracje SQL sortowane leksykograficznie (`sql/migration_v01.sql`, `v02.sql`, …). Runner opiera się na `sort -V` / `strnatcmp`.

## 2. Pułapka #1 (najczęstsza) — `composer install` w vendor stage kończy `exit code: 2`

**To trafia PRAWIE KAŻDE plain-PHP repo pierwszy raz.** Log deploya Coolify pokazuje:

```
Error: failed to solve: process "/bin/sh -c composer install \
  --no-dev --no-interaction --no-scripts --prefer-dist \
  --optimize-autoloader --no-progress" did not complete successfully:
  exit code: 2
```

**Powód**: obraz `composer:2` używa Alpine z ubogim PHP-em — bez `ext-intl`, `ext-gd`, `ext-zip`, `ext-fileinfo`, `ext-mbstring` (w takim minimum jak potrzebuje phpspreadsheet/TCPDF/PhpUnit). Twoja aplikacja te rozszerzenia MA (bo zainstalujesz je w runtime stage), ale composer w vendor stage tego jeszcze nie wie i failuje resolvowaniem.

**Fix — dwa flagi w vendor stage:**

```dockerfile
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --no-autoloader \            # ← 1. classmap zbudujemy w runtime
        --ignore-platform-reqs \     # ← 2. runtime ma ext'y, ufaj
        --prefer-dist \
        --no-progress
```

**Fix — dump-autoload PO `COPY` w runtime**, bo w vendor stage classmap widzi tylko `composer.json`, nie `src/`:

```dockerfile
FROM php:8.3-apache-bookworm AS runtime
# ... apt install libicu-dev + docker-php-ext-install intl gd zip ...

COPY --chown=www-data:www-data . /var/www/html
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

RUN composer dump-autoload \
        --working-dir=/var/www/html \
        --no-dev \
        --optimize \
        --classmap-authoritative \
        --no-interaction
```

`--classmap-authoritative` = opcache-friendly, bez runtime'owych probów `file_exists`. Warto.

## 3. Wzór Dockerfile (skopiuj i dopasuj)

Pełen, sprawdzony w produkcji plain-PHP Dockerfile — po `php:8.3-apache-bookworm`, z wszystkimi ext'ami które realnie używa aplikacja księgowa (KSeF XML, PDF, XLSX, HTTP client, Redis cache):

```dockerfile
# syntax=docker/dockerfile:1.7

# ─── Stage 1: composer install ─────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-interaction --no-scripts \
        --no-autoloader --ignore-platform-reqs \
        --prefer-dist --no-progress

# ─── Stage 2: runtime ──────────────────────────────────
FROM php:8.3-apache-bookworm AS runtime

ENV DEBIAN_FRONTEND=noninteractive \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

# Natywne libki potrzebne dla ext-* jakie zaraz instalujemy.
# Konkretny zestaw dobierz do swojego composer.json:
#   phpoffice/phpspreadsheet → gd + zip + xml + intl
#   tecnickcom/tcpdf          → gd + zip + mbstring
#   phpmailer/phpmailer       → mbstring + openssl (już w bazie)
#   phpseclib/phpseclib       → mbstring + openssl
#   guzzlehttp/guzzle         → curl (już w bazie)
#   predis/predis             → nic ekstra, ale phpredis (PECL) szybszy
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
        libxml2-dev libonig-dev libcurl4-openssl-dev libssl-dev \
        default-mysql-client \
        git unzip curl ca-certificates tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mysqli mbstring intl bcmath zip gd sockets opcache exif \
    && pecl install redis && docker-php-ext-enable redis \
    && a2enmod rewrite headers deflate expires remoteip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini              /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache-vhost.conf    /etc/apache2/sites-available/000-default.conf
COPY docker/apache-remoteip.conf /etc/apache2/conf-available/remoteip.conf
RUN a2enconf remoteip

COPY --from=vendor /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# TU dump-autoload dostaje w końcu prawdziwe src/ i widzi wszystkie klasy.
RUN composer dump-autoload \
        --no-dev --optimize --classmap-authoritative --no-interaction

# Katalogi runtime-write. Zostawiasz JEDNO polecenie mkdir + chown/chmod,
# żeby vendor dependency mógł się rozpisać (KSeF XMLs, logi, PDF-y, cache).
RUN mkdir -p storage/logs storage/exports storage/imports storage/cache \
    && chown -R www-data:www-data storage \
    && chmod -R ug+rwX storage

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/migrate.php   /usr/local/bin/migrate.php
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1/healthz || curl -fsS http://127.0.0.1/ || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
```

## 4. Apache vhost — `public/` jako DocumentRoot + hardening

`docker/apache-vhost.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Fallback rewrite gdy .htaccess by nie zadziałał.
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>

    # Wszystko poza public/ zablokowane z HTTP — obrona w głąb
    # nawet gdyby ktoś kiedyś powiesił nie-hardened plik w src/.
    <Directory /var/www/html/config>  Require all denied </Directory>
    <Directory /var/www/html/src>     Require all denied </Directory>
    <Directory /var/www/html/storage> Require all denied </Directory>
    <Directory /var/www/html/vendor>  Require all denied </Directory>

    # Logi do stdout/stderr → Coolify je łapie.
    ErrorLog  /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
```

`docker/apache-remoteip.conf` — konieczne żeby REMOTE_ADDR i `HTTPS=on` odzwierciedlały prawdziwego klienta za Coolify Traefikiem:

```apache
RemoteIPHeader        X-Forwarded-For
RemoteIPTrustedProxy  10.0.0.0/8
RemoteIPTrustedProxy  172.16.0.0/12
RemoteIPTrustedProxy  192.168.0.0/16

# X-Forwarded-Proto=https → HTTPS=on w PHP, żeby is_https()
# i secure-cookie logika działała.
SetEnvIf X-Forwarded-Proto "https" HTTPS=on
```

## 5. Pułapka #2 — `.htaccess` HTTPS redirect zapętla się za Traefikiem

Twoje `public/.htaccess` prawdopodobnie ma:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Za Traefikiem `%{HTTPS}` **jest** `off` (bo TLS terminuje się na Traefiku, do kontenera leci HTTP z `X-Forwarded-Proto: https`) → nieskończona pętla przekierowań.

**Fix**: dorzuć drugi warunek:

```apache
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Wciąż działa dla directto-Apache developmentu (X-Forwarded-Proto nie ustawiony), a za proxy nie zapętla się.

## 6. Migracje SQL — runner odpalany w entrypoincie

Plain-PHP nie ma `php artisan migrate`. Klasyczne rozwiązanie: `sql/migration_v*.sql` sortowane leksykograficznie + tabela `schema_migrations(filename, applied_at)` — dokładnie ten wzorzec z `UpdateFaktury.sh`/`Sequel Pro`.

`docker/migrate.php` — idempotentny, tolerancyjny na błędy (nigdy nie failuje startu kontenera):

```php
<?php
declare(strict_types=1);

$opts = getopt('', ['host:', 'port::', 'user:', 'database:', 'dir:']);
$pass = getenv('MYSQL_PWD') ?: '';

$pdo = new PDO(
    "mysql:host={$opts['host']};port=" . ($opts['port'] ?? 3306) .
        ";dbname={$opts['database']};charset=utf8mb4",
    $opts['user'], $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::MYSQL_ATTR_MULTI_STATEMENTS => true]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$applied = array_flip(array_column(
    $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_ASSOC),
    'filename'
));

$files = glob("{$opts['dir']}/migration_v*.sql") ?: [];
usort($files, 'strnatcmp');

foreach ($files as $path) {
    $name = basename($path);
    if (isset($applied[$name])) continue;
    try {
        $pdo->exec(file_get_contents($path));
        $pdo->prepare("INSERT IGNORE INTO schema_migrations (filename) VALUES (?)")
            ->execute([$name]);
        echo "[migrate] applied {$name}\n";
    } catch (Throwable $e) {
        // Świadomie NIE failujemy kontenera — legacy migracje
        // mogą już częściowo istnieć na produkcji. Zapisujemy że
        // "próbowaliśmy" i idziemy dalej.
        fwrite(STDERR, "[migrate] WARN {$name} — {$e->getMessage()}\n");
        $pdo->prepare("INSERT IGNORE INTO schema_migrations (filename) VALUES (?)")
            ->execute([$name]);
    }
}
exit(0);
```

`docker/entrypoint.sh` — czeka na DB, materializuje `config/database.php` z env, odpala migracje, oddaje sterowanie Apache:

```bash
#!/usr/bin/env bash
set -euo pipefail

: "${MYSQL_HOST:?required}"
: "${MYSQL_DATABASE:?required}"
: "${MYSQL_USER:?required}"
: "${MYSQL_PASSWORD:?required}"

# 1. Wygeneruj config/database.php z env (idempotentnie).
cat > /var/www/html/config/database.php <<'PHP'
<?php
$e = fn(string $k, ?string $d = null): ?string =>
    ($v = getenv($k)) !== false ? $v : ($_ENV[$k] ?? $d);
return [
    'host'     => $e('MYSQL_HOST'),
    'port'     => (int) $e('MYSQL_PORT', '3306'),
    'database' => $e('MYSQL_DATABASE'),
    'username' => $e('MYSQL_USER'),
    'password' => $e('MYSQL_PASSWORD'),
    'charset'  => $e('MYSQL_CHARSET', 'utf8mb4'),
];
PHP

# 2. Poczekaj aż MySQL wstanie (Coolify's depends_on nie gwarantuje ready).
export MYSQL_PWD="$MYSQL_PASSWORD"
for i in $(seq 1 30); do
    mysql --protocol=TCP -h "$MYSQL_HOST" -P "${MYSQL_PORT:-3306}" \
          -u "$MYSQL_USER" -e 'SELECT 1' >/dev/null 2>&1 && break
    [ "$i" -eq 30 ] && { echo "MySQL never became reachable"; exit 1; }
    echo "waiting for MySQL ($i/30)…"
    sleep 2
done

# 3. Utwórz bazę jeśli nie istnieje (external DBs startują puste).
mysql --protocol=TCP -h "$MYSQL_HOST" -u "$MYSQL_USER" \
    -e "CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\`
        DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Odpal migracje, jeśli SKIP_MIGRATIONS!=1.
if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    php /usr/local/bin/migrate.php \
        --host="$MYSQL_HOST" --port="${MYSQL_PORT:-3306}" \
        --user="$MYSQL_USER" --database="$MYSQL_DATABASE" \
        --dir=/var/www/html/sql
fi
unset MYSQL_PWD

# 5. Fix uprawnień do storage/ (wolumen może wpaść jako root).
chown -R www-data:www-data /var/www/html/storage || true
chmod -R ug+rwX            /var/www/html/storage || true

exec "$@"
```

## 7. Healthcheck — `/healthz` PRZED sesją

Coolify + Docker HEALTHCHECK potrzebują cheap 200 który nie failuje przy padniętym Redisie / DB. Wsadź to na sam początek `public/index.php`, **przed** `Session::start()` i `Cache::init()`:

```php
if (($_SERVER['REQUEST_URI'] ?? '') === '/healthz') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-store');
    echo "ok\n";
    exit;
}
```

Dzięki temu probe zostaje zielony nawet gdy Redis chwilowo padnie — ważne, bo inaczej Coolify zaczyna rolling-restart w kółko i Redis nie ma szansy wstać.

## 8. `docker-compose.yaml` — pattern Coolify z bundled DB + Redis

**Domyślny wzorzec dla plain-PHP**: MySQL + Redis są w tym samym compose co aplikacja. To sprawia że każdy nowy projekt w Coolify (osobny Project → osobny Resource → osobny compose) dostaje własny izolowany stack DB+cache bez ręcznego klikania "New Resource → Database" w UI. User tylko wpisuje 2 hasła w Env Variables (`MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`) i klika Deploy.

Bez `ports:` (Zasada A z SKILL.md), z `SERVICE_FQDN_APP_80` żeby Traefik sam znalazł domenę:

```yaml
services:
  app:
    build: { context: ., dockerfile: Dockerfile }
    image: app:latest
    restart: unless-stopped
    depends_on:
      mysql:
        condition: service_healthy   # ← KLUCZOWE, patrz "Pułapka #4" niżej
      redis:
        condition: service_started
    environment:
      SERVICE_FQDN_APP_80: /

      APP_URL:        "${APP_URL:-}"
      APP_TIMEZONE:   "${APP_TIMEZONE:-Europe/Warsaw}"

      # Defaulty pokazują na nazwy serwisów niżej — user nie musi
      # niczego wpisywać dopóki nie chce zewnętrznej DB.
      MYSQL_HOST:     "${MYSQL_HOST:-mysql}"
      MYSQL_PORT:     "${MYSQL_PORT:-3306}"
      MYSQL_DATABASE: "${MYSQL_DATABASE:-app}"
      MYSQL_USER:     "${MYSQL_USER:-app}"
      MYSQL_PASSWORD: "${MYSQL_PASSWORD:-}"   # ← user w Coolify UI

      CACHE_DRIVER:   "${CACHE_DRIVER:-redis}"
      REDIS_HOST:     "${REDIS_HOST:-redis}"
      REDIS_PASSWORD: "${REDIS_PASSWORD:-}"

      MAIL_HOST:      "${MAIL_HOST:-}"
      MAIL_USERNAME:  "${MAIL_USERNAME:-}"
      MAIL_PASSWORD:  "${MAIL_PASSWORD:-}"

      # Storage — patrz Zasada C w SKILL.md
      STORAGE_DRIVER:      "${STORAGE_DRIVER:-local}"
      S3_ENDPOINT:         "${S3_ENDPOINT:-}"
      S3_BUCKET:           "${S3_BUCKET:-}"
      S3_ACCESS_KEY_ID:    "${S3_ACCESS_KEY_ID:-}"
      S3_ACCESS_KEY_SECRET:"${S3_ACCESS_KEY_SECRET:-}"
    volumes:
      # Runtime-write dla lokalnych plików które nie idą do S3
      # (logi, wygenerowane PDF-y, XML-e KSeF itd.).
      - app_storage:/var/www/html/storage

  # MariaDB 11 — same wersja co MySQL 8 kompatybilna, ale mniejszy obraz
  # i szybszy start. Wolumen persystuje między redeployami.
  mysql:
    image: mariadb:11
    restart: unless-stopped
    environment:
      MARIADB_DATABASE:      "${MYSQL_DATABASE:-app}"
      MARIADB_USER:          "${MYSQL_USER:-app}"
      MARIADB_PASSWORD:      "${MYSQL_PASSWORD:-}"
      MARIADB_ROOT_PASSWORD: "${MYSQL_ROOT_PASSWORD:-}"
    volumes:
      - app_mysql:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 12
      start_period: 30s

  # Redis 7 z AOF persystencją — cache przetrwa restart kontenera.
  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: ["redis-server", "--appendonly", "yes"]
    volumes:
      - app_redis:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5

volumes:
  app_storage:
  app_mysql:
  app_redis:
```

Ważne — **żadnego `${VAR:?required}`**. Coolify wstrzykuje env przez UI, więc `:?required` w compose failuje walidację ZANIM UI zdąży. Zamiast tego wymuszaj obecność w entrypoincie (`: "${MYSQL_HOST:?required}"` w bashu — tam już OK).

### Pułapka #4 — `depends_on` bez `condition: service_healthy` = race condition

Coolify's compose builder respektuje `depends_on` ale bez `condition` to zwykłe "wait until container process starts" — MySQL init trwa 5-15 sekund PO tym jak proces `mysqld` wystartował. W tym oknie entrypoint aplikacji dostaje connection refused, pada, kontener leci na restart. Ping-pong.

Fix — **zawsze** `condition: service_healthy` na DB w `depends_on`, i sensowny `healthcheck` w DB (`start_period: 30s` na case pierwszej inicjalizacji volume'u, kiedy MariaDB tworzy system tables + user + database + first flush — łącznie ~20-25s na cold start).

### Kiedy NIE bundlować DB w compose

Trzy realne scenariusze kiedy bundled DB jest gorsze niż osobny Coolify resource:

1. **Wiele aplikacji dzieli tę samą bazę** (np. main app + background worker + admin panel jako 3 osobne Coolify resources). Wtedy DB to osobny resource, każdy z tych 3 punktuje w niego przez `MYSQL_HOST=<db-service-name>`.
2. **Ktoś w zespole wymaga snapshotów DB przez Coolify UI** (nie przez `mysqldump` w skrypcie). Osobny resource daje backup panel w Coolify.
3. **DB rośnie do >5-10GB i chcesz osobny dysk / osobny plan hostingu** dla samej bazy.

Dla wszystkiego innego (MVP, jeden projekt = jedna appka + jedna DB) bundled compose wygrywa prostotą.

## 9. Anty-wzorce specyficzne dla plain-PHP

- ❌ Trzymać `index.php` w rootcie projektu (nie w `public/`). Bez separacji nie da się bezpiecznie zablokować dostępu HTTP do `config/`, `vendor/`, `.env`. Pierwsze `.env` które wycieknie = klucz do bazy w internecie.
- ❌ `composer install` z `--optimize-autoloader` W VENDOR STAGE. Classmap zbudowany bez `src/` = puste mapowania klas = 500 na produkcji, "Class X not found". Fix: `--no-autoloader` w vendor, `dump-autoload` w runtime po COPY.
- ❌ Session files w `/tmp` (default PHP). Coolify replikuje kontenery bez shared filesystem → logout na każdym drugim requeście. Fix: `session.save_handler = redis` + `session.save_path = "tcp://redis:6379"` w `docker/php.ini`.
- ❌ Generowanie klucza szyfrującego (do `Crypt::encrypt()`) w kontenerze przy starcie. Restart = nowy klucz = wszystkie zaszyfrowane hasła / cookies w bazie stają się bezużyteczne. Fix: wymagaj `APP_SECRET_KEY` z env, wygeneruj RAZ przez `openssl rand -hex 32` i wklej w Coolify UI jako Secret.
- ❌ Odpalanie migracji w BUILD stage (RUN php migrate.php podczas buildu). Build nie ma dostępu do prod DB, a jak ma to jeszcze gorzej — testowa i prod baza się mieszają. Migracje ZAWSZE w entrypoincie, przy starcie kontenera.

## 10. Coolify UI — konkretne ustawienia

Dla plain-PHP + Dockerfile + docker-compose:

| Pole                        | Wartość                                     |
|-----------------------------|---------------------------------------------|
| Resource Type               | Public Repository (albo Private with GH App)|
| Build Pack                  | **Docker Compose** (nie "Empty", nie Nixpacks) |
| Docker Compose Location     | `/docker-compose.yaml`                     |
| Domains                     | Twoja domena — Coolify sam robi Traefika    |
| Ports Exposes               | pusto (SERVICE_FQDN_APP_80 w compose robi to za Ciebie) |
| Storages                    | zostaw automat — Coolify zobaczy `app_storage` volume z compose |

Env-y do wklejenia w UI (wszystko Secret gdy `[SECRET]`):

```
APP_URL=https://twojadomena.pl
APP_TIMEZONE=Europe/Warsaw

MYSQL_HOST=<coolify-mysql-service-name>
MYSQL_DATABASE=<db>
MYSQL_USER=<user>
MYSQL_PASSWORD=<...>          # [SECRET]

CACHE_DRIVER=redis
REDIS_HOST=<coolify-redis-service-name>
REDIS_PASSWORD=<...>          # [SECRET, jeśli ustawiłeś]

MAIL_HOST=smtp.provider.com
MAIL_USERNAME=<...>
MAIL_PASSWORD=<...>           # [SECRET]

APP_SECRET_KEY=<64-hex-chars> # [SECRET], wygeneruj: openssl rand -hex 32
```

## 11. Flowchart — plain-PHP-specific

```
Deploy failuje
│
├─ "Nixpacks" / "Found application type: php"?
│   → Build Pack niewłaściwy. Zmień na Docker Compose.
│   → Nixpacks buduje generic php-fpm+nginx, ignoruje Twój php:8.3-apache Dockerfile.
│
├─ "composer install ... exit code: 2"?
│   → Ext-* brakuje w composer:2 Alpine. Dodaj --ignore-platform-reqs
│     i --no-autoloader w vendor stage. Dump-autoload w runtime po COPY.
│
├─ Build zakończył się OK ale kontener od razu restartuje?
│   → `docker logs <container>` → prawdopodobnie entrypoint waliduje env
│     (: "${MYSQL_HOST:?required}"). Uzupełnij w Coolify UI.
│
├─ Kontener żyje (healthy) ale https://domena zwraca 404 albo pętla https→https?
│   → 404: SERVICE_FQDN_APP_80 nie ustawione w compose, dopisz.
│   → Pętla: .htaccess nie honoruje X-Forwarded-Proto. Fix z sekcji 5.
│
├─ 500 na starcie: "Class X not found"?
│   → Wybudowałeś classmap bez src/. Fix: `--no-autoloader` w vendor,
│     dump-autoload PO `COPY . /var/www/html`.
│
├─ Session gubi się przy każdym drugim requeście?
│   → Session files w /tmp kontenera, Coolify replikuje → różne
│     kontenery, różne /tmp. Fix: session.save_handler=redis.
│
└─ 200 OK ale KSeF/e-paragony (albo jakiś inny worker) nie odpalają?
    → Sprawdź Coolify → Scheduled Tasks. Kontener nie ma crona wewnątrz;
      background scripts musisz uruchomić z Coolify UI.
```
