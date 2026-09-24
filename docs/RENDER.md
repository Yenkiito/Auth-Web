# Despliegue en Render con Supabase

El repositorio incluye un `Dockerfile` y un Blueprint `render.yaml`. La Web Service ejecuta Nginx, PHP-FPM, el worker de Laravel y el scheduler dentro del mismo contenedor. PostgreSQL permanece en Supabase.

## Crear el servicio

1. Confirma y sube los archivos nuevos a la rama `master` de GitHub.
2. En Render abre **New > Blueprint**.
3. Conecta `Yenkiito/Auth-Web` y selecciona `render.yaml`.
4. Render solicitará las variables marcadas con `sync: false`.

Configura:

- `APP_KEY`: salida de `php artisan key:generate --show`. Puede usarse la clave local actual si ya existen datos cifrados que deban conservarse.
- `APP_URL`: URL final, por ejemplo `https://auth-web.onrender.com`.
- `ASSET_URL`: la misma URL de `APP_URL`.
- `DB_PASSWORD`: solamente la contraseña PostgreSQL de Supabase, nunca la URI completa.

El resto de los parámetros PostgreSQL ya corresponde al Session Pooler del proyecto configurado.

## Primer despliegue

El contenedor ejecuta `php artisan migrate --force` antes de arrancar los servicios. La base Supabase ya tiene sus migraciones, por lo que Laravel no repetirá cambios aplicados.

Render comprobará `GET /up`. Cuando el estado sea **Live**, abre `/login` y verifica el acceso del propietario existente.

La URL para el cliente C++ será:

```cpp
std::string url = skCrypt("https://TU-SERVICIO.onrender.com/api/v1/").decrypt();
```

## Plan gratuito

El servicio gratuito puede suspenderse después de un periodo sin tráfico. Mientras esté suspendido, el worker y el scheduler tampoco se ejecutan; vuelven a iniciar con la siguiente solicitud web. Para procesos continuos se necesita una instancia de pago o servicios separados.
