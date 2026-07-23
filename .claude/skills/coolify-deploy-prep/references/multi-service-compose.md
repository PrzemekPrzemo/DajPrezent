# Multi-service docker-compose — monorepo z bazą/Redis/workerem — Coolify

To jest plik, który stosujesz, gdy repo (albo grupa repo tego samego produktu) zawiera więcej niż jeden serwis: frontend + backend API + baza danych + ewentualnie Redis/worker. Dotyczy to np. produktów typu Next.js (frontend) + NestJS (API) + PostgreSQL + Redis w jednym `docker-compose.yml`.

## Pełny szablon — skopiuj i dostosuj nazwy serwisów/portów wewnętrznych

```yaml
services:
  # ── Frontend (Next.js) ──────────────────────────────────────────────
  web:
    build:
      context: ./web
      dockerfile: Dockerfile
      args:
        NEXT_PUBLIC_API_URL: "${NEXT_PUBLIC_API_URL:-}"
    environment:
      NODE_ENV: "${NODE_ENV:-production}"
      PORT: "${WEB_PORT:-3000}"
    # BRAK sekcji "ports:" — to jedyny serwis wystawiony na zewnątrz, przez Traefik:
    labels:
      - "traefik.enable=true"
    environment:
      SERVICE_FQDN_WEB_3000: /   # "3000" = port WEWNĘTRZNY kontenera web, nie hosta
    depends_on:
      api:
        condition: service_started
    restart: unless-stopped

  # ── Backend API (NestJS) ────────────────────────────────────────────
  api:
    build:
      context: ./api
      dockerfile: Dockerfile
    environment:
      NODE_ENV: "${NODE_ENV:-production}"
      PORT: "${API_PORT:-4000}"
      DATABASE_URL: "${DATABASE_URL:-}"          # WYMAGANE, Secret w Coolify
      REDIS_URL: "${REDIS_URL:-redis://redis:6379}"
      JWT_SECRET: "${JWT_SECRET:-}"               # WYMAGANE, Secret
      RUN_MIGRATIONS: "${RUN_MIGRATIONS:-true}"
    # BRAK "ports:" tutaj też — api rozmawia z web WEWNĄTRZ sieci compose po nazwie serwisu "api:4000",
    # nie potrzebuje własnej publicznej domeny (chyba że chcesz wystawić API osobno — wtedy dodaj
    # analogiczny SERVICE_FQDN_API_4000 tylko jeśli faktycznie ma być publiczne).
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "wget", "-qO-", "http://localhost:4000/health"]
      interval: 30s
      timeout: 5s
      retries: 3

  # ── Worker (kolejki, np. BullMQ) — opcjonalnie ──────────────────────
  worker:
    build:
      context: ./api
      dockerfile: Dockerfile.worker
    environment:
      NODE_ENV: "${NODE_ENV:-production}"
      DATABASE_URL: "${DATABASE_URL:-}"
      REDIS_URL: "${REDIS_URL:-redis://redis:6379}"
    # Worker NIGDY nie potrzebuje wystawionego portu — nie przyjmuje ruchu HTTP,
    # tylko konsumuje zadania z kolejki. Brak "ports:" i brak SERVICE_FQDN.
    depends_on:
      redis:
        condition: service_healthy
    restart: unless-stopped

  # ── PostgreSQL ───────────────────────────────────────────────────────
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: "${POSTGRES_DB:-appdb}"
      POSTGRES_USER: "${POSTGRES_USER:-appuser}"
      POSTGRES_PASSWORD: "${POSTGRES_PASSWORD:-}"   # WYMAGANE, Secret
    # BRAK "ports:" — baza jest osiągalna TYLKO wewnątrz sieci compose jako "db:5432".
    # Jeśli naprawdę potrzebujesz zewnętrznego dostępu (np. do backupu z Twojego laptopa),
    # użyj tunelu SSH zamiast wystawiania 5432 na hosta:
    #   ssh -L 5432:localhost:5432 root@adres_serwera
    # (patrz też: docker exec -it <kontener_db> pg_dump ... jeśli wolisz backup z poziomu kontenera)
    volumes:
      - db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-appuser}"]
      interval: 10s
      timeout: 5s
      retries: 5
    restart: unless-stopped

  # ── Redis ────────────────────────────────────────────────────────────
  redis:
    image: redis:7-alpine
    # BRAK "ports:" — tak samo jak baza, dostępny tylko jako "redis:6379" wewnątrz sieci.
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    restart: unless-stopped

volumes:
  db-data:
  redis-data:
```

## Dlaczego to rozwiązuje problem "randomowych portów"

Zauważ, że **żaden serwis oprócz `web` nie ma w ogóle wystawionego portu na hosta**. To jest właśnie mechanizm, który eliminuje kolizje na serwerze z wieloma aplikacjami:

- `db`, `redis`, `worker`, `api` (jeśli nie musi być publiczne) komunikują się WYŁĄCZNIE przez wewnętrzną sieć Dockera, adresowane po nazwie serwisu. Dwa różne produkty na tym samym serwerze mogą mieć swoje własne `db`/`redis` o tej samej nazwie wewnętrznej i **nigdy się nie zobaczą ani nie zderzą** — każdy `docker-compose.yml` w Coolify dostaje własną, izolowaną sieć.
- Jedyny serwis wystawiony na zewnątrz (`web`) nie rezerwuje portu na hoście przez `ports:` — zamiast tego Coolify/Traefik samo zarządza routingiem przez `SERVICE_FQDN_*` albo wpis w Domains. To Coolify decyduje, jak fizycznie poprowadzić ruch z internetu do kontenera, nie Twój plik compose.
- Efekt: możesz wdrożyć ten sam szablon dla Hovera, ClubDesk, Shootero i FaktuPilot na jednym serwerze, z takimi samymi nazwami serwisów (`web`, `api`, `db`, `redis`) w każdym z nich, i **nic się nie pogryzie**, bo każdy stack żyje w swojej własnej sieci Dockera.

## `.env.example` — dołącz do repo, to jednocześnie ściągawka do Coolify UI

```bash
# ── Wymagane, oznacz jako Secret w Coolify ──────────────────────────
DATABASE_URL=postgresql://appuser:CHANGEME@db:5432/appdb
POSTGRES_PASSWORD=CHANGEME
JWT_SECRET=CHANGEME
NEXT_PUBLIC_API_URL=https://api.twojadomena.pl

# ── Opcjonalne, mają sensowny default ───────────────────────────────
NODE_ENV=production
WEB_PORT=3000
API_PORT=4000
REDIS_URL=redis://redis:6379
RUN_MIGRATIONS=true
```

## Kolejność wdrażania przy pierwszym deployu

1. Wklej wszystkie zmienne z `.env.example` do Coolify UI → Environment Variables, realne wartości zamiast `CHANGEME`.
2. Oznacz `Secret` + `Runtime` dla: `DATABASE_URL`, `POSTGRES_PASSWORD`, `JWT_SECRET`.
3. Oznacz `Buildtime` dla `NEXT_PUBLIC_API_URL` (patrz `references/nextjs.md` dlaczego).
4. Deploy — kolejność startu (`depends_on: condition: service_healthy`) sama zadba o to, żeby `db`/`redis` wstały i przeszły healthcheck zanim `api` spróbuje się połączyć.
5. Sprawdź logi `api` — czy migracje (`RUN_MIGRATIONS=true`) przeszły czysto przy pierwszym starcie.
6. Dopiero potem podłącz domenę do `web` (Krok 4 w głównym SKILL.md).
