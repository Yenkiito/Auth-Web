# Kenyra License

La primera etapa es una aplicación web administrativa. No expone una API pública de validación ni SDKs.

## Identidad y jerarquía

Todas las cuentas autenticables se almacenan en `users`; el rol `PARTNER` enlaza uno a uno con un perfil en `partners`. Así existe un solo hash de contraseña por identidad. Los clientes usan `project_id` y `partner_id`; OWNER y ADMIN son globales.

El acceso se comprueba en Policies y en `ProjectAccessService`. Para PARTNER, el ámbito incluye su nodo y descendientes del mismo proyecto. Los filtros React son únicamente presentación.

## Claves

`project_key` y `licenses.key` son identificadores aleatorios con restricciones UNIQUE. La clave de proyecto solo se revela tras confirmación de contraseña y puede rotarse sin modificar recursos. Las licencias revocadas son terminales.

## API de clientes

`/api/v1/init` valida nombre, owner ID y versión de la aplicación y emite un token aleatorio almacenado solamente como SHA-256. `/login` valida una cuenta CLIENT, su vencimiento y el HWID opcional; `/license` activa o valida una licencia, la asocia al cliente y registra el dispositivo; `/check` vuelve a comprobar sesión, cuenta, licencia y HWID.

El secreto de la aplicación no se distribuye en los ejecutables. Los endpoints tienen limitación de solicitudes y las sesiones expiradas se eliminan diariamente con el scheduler.
