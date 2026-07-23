# Strony statyczne (HTML/Astro/Vite/Next.js static export) — Coolify

## Rozpoznanie
- Next.js z `output: 'export'` w `next.config.ts`.
- Astro, Vite, plain HTML/CSS/JS bez własnego backendu.
- Brak potrzeby bazy danych/serwera Node w runtime — tylko gotowe pliki do wyświetlenia.

## Nie potrzebujesz żadnego Dockerfile w większości przypadków

Coolify ma dedykowany tryb dla tego przypadku:

1. **+ Add New Resource → Application**
2. Źródło: Git repository (public albo private).
3. **Build Pack**: zostaw Nixpacks, ale zaznacz checkbox **"Is it a static site?"**
4. **Publish Directory** (pojawia się po zaznaczeniu checkboxa) — folder z gotowymi plikami PO zbudowaniu:
   - Next.js static export → `out`
   - Astro → `dist`
   - Vite (React/Vue) → `dist`
   - Plain HTML bez builda → `/` (główny katalog repo)
5. **Build Command** (jeśli strona wymaga kroku budowania) — np. `npm run build`. Coolify uruchamia to automatycznie przy każdym deployu.

Coolify serwuje wynik przez nginx wewnętrznie — nie ma tu w ogóle Node.js w runtime, więc:
- Zero zużycia RAM/CPU poza samym serwowaniem plików statycznych.
- Można wdrożyć dowolnie wiele takich stron na tym samym serwerze bez wpływu na resztę infrastruktury.
- Pytanie o porty/env praktycznie nie istnieje — nie ma runtime'owej aplikacji, która by je potrzebowała.

## Jeśli strona MUSI mieć zmienne środowiskowe (np. klucz do formularza kontaktowego, publiczny klucz analityki)

To są zmienne **wyłącznie Buildtime** (bo strona nie ma serwera w runtime, wszystko jest wpiekane w statyczne pliki podczas builda):
```
NEXT_PUBLIC_ANALYTICS_ID=xxxxx
PUBLIC_CONTACT_FORM_ENDPOINT=https://...
```
W Coolify UI: włącz **Available at Buildtime**, Runtime nie ma tu zastosowania.

## Jeśli mimo wszystko wolisz własny Dockerfile (np. potrzebujesz customowej konfiguracji nginx)

```dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
# albo /app/out dla Next.js static export
EXPOSE 80
```
W tym wariancie port wewnątrz kontenera to zawsze `80` (standard nginx) — Coolify i tak routuje przez Traefik na zewnętrzną domenę, więc nie trzeba niczego zmieniać ani randomizować ręcznie.
