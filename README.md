# Kenyra License

Panel web de administración de proyectos, socios, clientes, licencias y dispositivos construido con Laravel 12, PostgreSQL, Inertia, React y TypeScript.

## Requisitos

- PHP 8.3 mínimo para Laravel 12; PHP 8.4 recomendado para producción.
- Composer 2, Node.js 20+ y Docker Desktop (o PostgreSQL 17 instalado).

## Inicio local

1. Copia `.env.example` a `.env`, define `DB_PASSWORD` y las variables `INITIAL_OWNER_*`. La base predeterminada se llama `auth_web`.
2. Si el puerto 5432 está ocupado, cambia `DB_PORT` en `.env` (por ejemplo, 5433).
3. Ejecuta:

```bash
composer install
npm install
php artisan key:generate
docker compose up -d postgres
php artisan migrate --seed
npm run build
composer run dev
```

Abre `http://localhost:8000/login`. Después del primer seed, elimina `INITIAL_OWNER_PASSWORD` de `.env` si ya no la necesitas.

## Procesos de producción

Los lotes superiores a 1.000 licencias se dividen en trabajos de cola:

```bash
php artisan queue:work --queue=default --tries=3
php artisan schedule:work
```

El scheduler ejecuta `kenyra:expire-licenses` cada hora. En producción configura `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS, una contraseña PostgreSQL fuerte y un worker/scheduler supervisados.

## Verificación

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

La API para clientes externos está disponible en `/api/v1`. El flujo es `init`, `login`, `license` y `check`; acepta el token mediante `Authorization: Bearer <token>` o `session_token` en el JSON. Consulta [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) para las decisiones de seguridad y aislamiento.
