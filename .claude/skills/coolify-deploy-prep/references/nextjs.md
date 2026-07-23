# Next.js (SSR / API routes) — Coolify

## Rozpoznanie
- `next.config.js`/`.ts` BEZ `output: 'export'` → to jest ten przypadek (serwer Node, nie static).
- Jeśli JEST `output: 'export'` → to static export, idź do `references/static-sites.md` zamiast tego pliku.

## Kluczowa zmiana w next.config — `output: 'standalone'`

Żeby obraz Dockera był mały i szybki, ustaw w `next.config.ts`:
```ts
const nextConfig = {
  output: 'standalone',
};
export default nextConfig;
```
To sprawia, że `next build` generuje w `.next/standalone` samowystarczalny folder z minimalnym `node_modules` — nie trzeba kopiować całego `node_modules` do obrazu produkcyjnego.

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
# Zmienne NEXT_PUBLIC_* MUSZĄ być dostępne tutaj (Buildtime w Coolify),
# bo Next.js wpieka je w bundle podczas builda, nie da się ich zmienić po fakcie.
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
RUN npm run build

FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nextjs
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
USER nextjs
# NIGDY nie hardcoduj portu na sztywno w EXPOSE jeśli app czyta z env —
# EXPOSE to tylko dokumentacja, faktyczny port bierze się z PORT poniżej.
EXPOSE 3000
ENV PORT=3000
CMD ["node", "server.js"]
```

W Coolify UI: **Ports Exposes** = `3000` (albo to co faktycznie ustawiłeś w `PORT`). Jeśli chcesz żeby był w pełni konfigurowalny, w compose ustaw `PORT: "${PORT:-3000}"` i przekaż go też do `ENV PORT` w Dockerfile jako `ARG`.

## Zmienne środowiskowe — dwa rodzaje

| Rodzaj | Przykład | Kiedy dostępne |
|---|---|---|
| `NEXT_PUBLIC_*` | `NEXT_PUBLIC_API_URL` | **Buildtime** — wpiekane w JS bundle wysyłany do przeglądarki. Zmiana wymaga rebuilda obrazu. |
| Zwykłe (server-only) | `DATABASE_URL`, `API_SECRET_KEY` | **Runtime** — czytane dopiero gdy kod server-side faktycznie się wykonuje (API routes, `getServerSideProps`, Server Components). Można zmienić bez rebuilda. |

W Coolify oznacz `NEXT_PUBLIC_*` z włączonym **Available at Buildtime**, resztę wystarczy **Available at Runtime**.

## Trust proxy / HTTPS za Traefikiem

Next.js w trybie standalone samo w sobie nie potrzebuje osobnej konfiguracji trust proxy jak Express — ale jeśli generujesz absolutne URL-e ręcznie (np. w metadata, redirectach), czytaj protokół z nagłówka zamiast zakładać `http`:
```ts
const proto = headers().get('x-forwarded-proto') ?? 'https';
```

## Healthcheck

Dodaj prosty endpoint `app/api/health/route.ts`:
```ts
export async function GET() {
  return Response.json({ status: 'ok' });
}
```
I w Dockerfile/compose:
```yaml
healthcheck:
  test: ["CMD", "wget", "-qO-", "http://localhost:3000/api/health"]
  interval: 30s
  timeout: 5s
  retries: 3
```
