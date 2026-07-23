# Python (FastAPI / Django / Flask) — Coolify

## Rozpoznanie
- `requirements.txt` albo `pyproject.toml` + `fastapi`/`uvicorn` → FastAPI.
- `manage.py` obecny → Django.
- `flask` w zależnościach → Flask.

## Dockerfile (multi-stage, FastAPI jako przykład — analogicznie dla Django/Flask)

```dockerfile
FROM python:3.12-slim AS builder
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir --user -r requirements.txt

FROM python:3.12-slim AS runner
WORKDIR /app
ENV PYTHONUNBUFFERED=1
RUN groupadd -r appuser && useradd -r -g appuser appuser
COPY --from=builder /root/.local /home/appuser/.local
COPY . .
RUN chown -R appuser:appuser /app
USER appuser
ENV PATH=/home/appuser/.local/bin:$PATH
EXPOSE 8000
# Port ZAWSZE z env — nie zaszywaj "8000" jeśli aplikacja ma dzielić serwer z innymi
CMD ["sh", "-c", "uvicorn main:app --host 0.0.0.0 --port ${PORT:-8000}"]
```

Dla Django z Gunicornem:
```dockerfile
CMD ["sh", "-c", "gunicorn myproject.wsgi:application --bind 0.0.0.0:${PORT:-8000}"]
```

## Migracje

Podobnie jak w Node — migracje (`alembic upgrade head`, `python manage.py migrate`) w entrypoincie przy starcie kontenera, nie w trakcie `docker build`:

```bash
#!/bin/sh
set -e
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  alembic upgrade head   # albo: python manage.py migrate
fi
exec "$@"
```

## Zmienne środowiskowe

```yaml
environment:
  PORT: "${PORT:-8000}"
  DATABASE_URL: "${DATABASE_URL:-}"       # WYMAGANE, Secret
  SECRET_KEY: "${SECRET_KEY:-}"           # WYMAGANE, Secret (Django SECRET_KEY / podpisywanie tokenów)
  REDIS_URL: "${REDIS_URL:-redis://redis:6379}"
  ALLOWED_HOSTS: "${ALLOWED_HOSTS:-*}"
```

Django: `ALLOWED_HOSTS` i `CSRF_TRUSTED_ORIGINS` muszą zawierać domenę produkcyjną — czytaj je z env, nie hardcoduj w `settings.py`:
```python
ALLOWED_HOSTS = os.environ.get("ALLOWED_HOSTS", "*").split(",")
CSRF_TRUSTED_ORIGINS = os.environ.get("CSRF_TRUSTED_ORIGINS", "").split(",")
```

## Trust proxy / HTTPS za Traefikiem

Django — w `settings.py`:
```python
SECURE_PROXY_SSL_HEADER = ("HTTP_X_FORWARDED_PROTO", "https")
USE_X_FORWARDED_HOST = True
```
FastAPI/Starlette za Traefikiem zwykle nie wymaga dodatkowej konfiguracji, ale jeśli generujesz absolutne URL-e ręcznie, czytaj `X-Forwarded-Proto` z nagłówków requestu zamiast zakładać `http`.

## Healthcheck

FastAPI — dodaj endpoint:
```python
@app.get("/health")
def health():
    return {"status": "ok"}
```
Compose:
```yaml
healthcheck:
  test: ["CMD", "python", "-c", "import urllib.request; urllib.request.urlopen('http://localhost:8000/health')"]
  interval: 30s
  timeout: 5s
  retries: 3
```
