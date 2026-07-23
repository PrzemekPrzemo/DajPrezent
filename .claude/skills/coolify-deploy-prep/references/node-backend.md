# NestJS / Express / ogólny Node backend — Coolify

## Rozpoznanie
- `nest-cli.json` obecny → NestJS.
- Brak, ale `express`/`fastify`/`koa` w `package.json` → ogólny Node backend. Wzorzec Dockerfile jest ten sam.

## Dockerfile (multi-stage)

```dockerfile
FROM node:20-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

FROM node:20-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN npm run build

FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nestjs
COPY --from=builder /app/package.json ./
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/dist ./dist
USER nestjs
EXPOSE 3000
# Port ZAWSZE z env, nigdy zaszyty w kodzie startowym (main.ts: app.listen(process.env.PORT ?? 3000))
CMD ["node", "dist/main.js"]
```

W kodzie (`main.ts` w NestJS albo odpowiednik w Express):
```ts
await app.listen(process.env.PORT ?? 3000);
```
Nigdy `app.listen(3000)` na sztywno — na współdzielonym serwerze różne apki muszą móc dostać różne porty bez zmiany kodu.

## Migracje bazy danych — NIE w trakcie builda

Migracje (Prisma/TypeORM/Knex) uruchamiaj w **entrypoincie kontenera przy starcie**, nie jako krok w Dockerfile podczas `docker build`. Powód: podczas builda nie ma jeszcze połączenia z bazą produkcyjną (albo nie powinno być), a poza tym build powinien być odtwarzalny niezależnie od stanu bazy.

Przykładowy `entrypoint.sh`:
```bash
#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "[entrypoint] Uruchamiam migracje..."
  npx prisma migrate deploy   # albo odpowiednik dla Twojego ORM-a
fi

exec "$@"
```
```dockerfile
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["node", "dist/main.js"]
```
`RUN_MIGRATIONS` jako zmienna środowiskowa w Coolify — ustaw `false` na dodatkowych replikach, żeby migracje nie odpalały się równolegle z wielu instancji naraz.

## Zmienne środowiskowe — wzorzec

Nigdy connection stringi/klucze w kodzie ani w `docker-compose.yml` na sztywno:
```yaml
environment:
  NODE_ENV: "${NODE_ENV:-production}"
  PORT: "${PORT:-3000}"
  DATABASE_URL: "${DATABASE_URL:-}"      # WYMAGANE, Secret w Coolify
  REDIS_URL: "${REDIS_URL:-redis://redis:6379}"
  JWT_SECRET: "${JWT_SECRET:-}"          # WYMAGANE, Secret
```

## Trust proxy (jeśli generujesz absolutne URL-e / obsługujesz cookies Secure)

Express:
```ts
app.set('trust proxy', 1);
```
NestJS (Express adapter):
```ts
const app = await NestFactory.create(AppModule);
app.getHttpAdapter().getInstance().set('trust proxy', 1);
```
Bez tego, za Traefikiem terminującym TLS, aplikacja może generować URL-e `http://` albo nie ustawiać cookies jako `Secure`.

## Healthcheck

Dodaj endpoint `/health` zwracający 200, i w compose:
```yaml
healthcheck:
  test: ["CMD", "wget", "-qO-", "http://localhost:3000/health"]
  interval: 30s
  timeout: 5s
  retries: 3
```
Jeśli masz worker (BullMQ, Celery-podobny) obok API w tym samym repo — traktuj go jako osobny serwis w compose, bez wystawionego portu na zewnątrz (worker nie przyjmuje ruchu HTTP, tylko konsumuje kolejkę z Redis).
