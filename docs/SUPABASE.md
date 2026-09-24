# Supabase como PostgreSQL

Supabase almacena los datos de Kenyra License. Laravel sigue siendo el servidor del panel y de `/api/v1`; las aplicaciones C++ nunca deben conectarse directamente a PostgreSQL ni recibir credenciales de Supabase.

## Conexión recomendada

En el proyecto de Supabase, abre **Connect**, selecciona **Session pooler** y copia sus valores. El modo de sesión usa el puerto `5432`, funciona mediante IPv4 y admite las características normales de una conexión persistente.

Configura el `.env` local o los secretos del servidor Laravel:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=aws-0-REGION.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.PROJECT_REF
DB_PASSWORD=CONTRASENA_DEL_PROYECTO
DB_SSLMODE=require
```

Se deben copiar `DB_HOST` y `DB_USERNAME` exactamente desde Supabase; no se deben construir manualmente. Al usar campos separados, la contraseña no requiere codificación URL.

Después de guardar las variables:

```bash
php artisan config:clear
php artisan migrate --force
php artisan migrate:status
```

Para crear el propietario inicial en una base vacía, define temporalmente `INITIAL_OWNER_USERNAME`, `INITIAL_OWNER_EMAIL` e `INITIAL_OWNER_PASSWORD`, ejecuta `php artisan db:seed --force` y elimina inmediatamente `INITIAL_OWNER_PASSWORD`.

## Seguridad

Este proyecto accede a PostgreSQL exclusivamente desde Laravel y no utiliza la Data API de Supabase. En **Integrations > Data API**, desactiva **Enable Data API** para evitar exponer las tablas del esquema `public` mediante REST o GraphQL. No guardes la contraseña, la cadena de conexión ni claves secretas en GitHub.
