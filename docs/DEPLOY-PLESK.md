# Deployment na własnym VPS z Plesk

Docelowe środowisko: **VPS z panelem Plesk Obsidian**. Ta notatka spina
typową instalację Laravel 11 + MySQL + Redis pod Pleskiem.

## Wymagania serwera

- Plesk Obsidian (testowane na 18.0.5x+)
- Subskrypcja domeny `dajprezent.pl` + subdomeny dla środowisk
  (`stg.dajprezent.pl`, `*.dajprezent.pl` dla custom domains klientów Pro)
- PHP 8.4 (Plesk → Tools & Settings → PHP) z rozszerzeniami:
  `pdo_mysql`, `mbstring`, `xml`, `ctype`, `curl`, `gd`, `intl`,
  `openssl`, `redis`, `bcmath`, `fileinfo`, `tokenizer`
- MySQL 8 (Plesk DB management)
- Redis 7 — instalowany przez Plesk Extensions („Redis server") lub
  ręcznie `apt install redis-server`. Plesk Firewall: otwórz 6379 tylko
  dla localhost.
- Node 20 + npm — `apt install nodejs npm` (na potrzeby `npm run build`)
- Composer 2 — `curl -sS https://getcomposer.org/installer | php` i
  przenieść do `/usr/local/bin/composer`
- Cloudflare przed Pleskiem (zalecane: DDoS shield + cache reguły dla
  statyków + szybsze TLS)

## Konfiguracja domeny w Plesk

1. **Dodaj domenę** `dajprezent.pl`.
2. **Document root** → `/var/www/vhosts/dajprezent.pl/httpdocs/public`
   (Plesk → Hosting Settings → „Document root").
3. **PHP Selector** → wybierz PHP 8.4 + włącz `opcache` (Plesk → PHP
   Settings → `opcache.enable=1`, `opcache.validate_timestamps=0` w
   prodzie).
4. **HTTPS** → włącz Let's Encrypt (Plesk Extensions) z
   `*.dajprezent.pl` jako wildcard (wymaga DNS-01; jeśli DNS u
   zewnętrznego rejestratora, Plesk poprosi o token).
5. **Cron** → Plesk → Scheduled Tasks → dodaj zadanie minutowe:
   ```
   * * * * *  cd /var/www/vhosts/dajprezent.pl/httpdocs && php artisan schedule:run >> /dev/null 2>&1
   ```
6. **Queue worker** → Plesk → Scheduled Tasks → „Run at server startup"
   uruchamia supervisord, albo dodaj systemd unit (poniżej).

## Layout katalogów

Repo klonujemy poza domyślnym `httpdocs`, a `public/` linkujemy jako
docroot. To upraszcza deploy i chroni `.env`.

```
/var/www/vhosts/dajprezent.pl/
├── httpdocs/                # symlink do current/public
├── current → releases/2026-05-22_140000/
├── releases/
│   ├── 2026-05-22_140000/   # repo + vendor + node_modules build
│   └── 2026-05-15_113000/
├── shared/
│   ├── .env                 # produkcyjny env (NIE w repo)
│   └── storage/             # uploady, logi, sesje
```

## Pierwszy deploy (ręczny)

```bash
ssh root@vps
cd /var/www/vhosts/dajprezent.pl/
mkdir -p releases shared
git clone git@github.com:przemekprzemo/dajprezent.git releases/initial
cd releases/initial

# .env
cp .env.example ../../shared/.env
nano ../../shared/.env   # uzupełnij DB, REDIS, PAYU, KSEF, POSTMARK
ln -s ../../shared/.env .env

# storage
rm -rf storage
ln -s ../../shared/storage storage

# Composer, npm, klucz aplikacji
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force --class=PackageSeeder

# Cache prod
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Aktywuj release
cd ..
ln -sfn releases/initial current
ln -sfn current/public /var/www/vhosts/dajprezent.pl/httpdocs
```

## Queue worker (systemd, opcjonalnie)

`/etc/systemd/system/dajprezent-worker.service`:

```ini
[Unit]
Description=DajPrezent.pl queue worker
After=redis-server.service mysql.service

[Service]
User=dajprezent
Group=psacln
Restart=always
RestartSec=3
ExecStart=/opt/plesk/php/8.4/bin/php /var/www/vhosts/dajprezent.pl/current/artisan queue:work redis --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

`systemctl enable --now dajprezent-worker.service`

## Deploy kolejnych releasów (skrót)

Skrypt `bin/deploy.sh` (do dopisania w późniejszej iteracji):

1. `git pull` w nowym katalogu `releases/$(date +%Y-%m-%d_%H%M%S)`
2. `composer install --no-dev`, `npm ci && npm run build`
3. Linkowanie `.env` i `storage/`
4. `php artisan migrate --force`, cache:re-build
5. Atomowy `ln -sfn` na `current`
6. `php artisan queue:restart`
7. Retencja: kasuj releasy starsze niż 5

Docelowo to zaautomatyzujemy w GitHub Actions z `appleboy/ssh-action`,
ale ręczny skrypt wystarcza na MVP.

## Backupy

- **Plesk Backup Manager** → dzienny dump bazy + `shared/.env` +
  `shared/storage/` → S3-zgodne (Wasabi). Retencja 30 dni.
- Dodatkowo logiczny `mysqldump` codzienny przez cron, zaszyfrowany
  `age`'em i wrzucony na drugi storage.

## Monitoring

- **Sentry** — błędy aplikacyjne (DSN w `.env`).
- **Plesk Healthcheck** + Uptime Robot na `https://dajprezent.pl/` i
  `/health`.
- **Logi**: `storage/logs/laravel.log` rotowane przez `logrotate` co 7
  dni (Plesk ustawia logrotate dla docroot automatycznie).

## Bezpieczeństwo

- Plesk → Firewall → blokuj wszystko poza 22 (z whitelisty IP), 80,
  443, 6379 (tylko localhost), 3306 (tylko localhost).
- Plesk → ModSecurity → włącz OWASP CRS, wyłącz na endpointach
  webhook PayU i KSeF callback (false positives).
- Klucze PayU/KSeF/Postmark nigdy w `.env` produkcyjnego klienta —
  są w `shared/.env` z `chmod 600`, owner `dajprezent`.
- Plesk → ImunifyAV+ skanowanie codzienne.
