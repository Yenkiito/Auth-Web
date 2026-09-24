# Integración del cliente C++ con Kenyra License

Este documento sirve como especificación técnica y como prompt para implementar el cliente de autenticación en un proyecto C++ con Dear ImGui, Win32, DirectX 11, libcurl y nlohmann/json.

## Prompt listo para usar

```text
Implementa en este proyecto C++ existente un cliente para Kenyra License.

Contexto del proyecto C++:
- Aplicación Windows x64.
- Interfaz Dear ImGui.
- Backend Win32 + DirectX 11.
- La interfaz principal está en examples/example_win32_directx11/menu.h.
- El proyecto ya integra libcurl y nlohmann/json.
- No reemplaces ni reestructures el renderizado existente de ImGui.
- Reutiliza las dependencias existentes y no agregues otra implementación HTTP o JSON.

Backend:
- URL base: https://auth-web-rbs9.onrender.com/api/v1/
- Formato: JSON mediante HTTPS.
- Flujo obligatorio: init -> login -> license -> check.
- El token recibido en init se envía en las siguientes solicitudes mediante:
  Authorization: Bearer <session_token>
- Credenciales iniciales de la aplicación:
  name: CNSI
  ownerid: W3CzqUZwRJ
  version: 1.0
- Confirma estos tres valores con los mostrados en el panel antes de compilar.

Crea, como mínimo:
- KenyraAuthClient.h
- KenyraAuthClient.cpp
- Hwid.h
- Hwid.cpp

KenyraAuthClient debe:
- Inicializar libcurl una sola vez y liberarlo al cerrar el programa.
- Verificar el certificado TLS y el nombre del host. Nunca desactivar SSL_VERIFYPEER ni SSL_VERIFYHOST.
- Enviar Content-Type: application/json y Accept: application/json.
- Tener métodos Init(), Login(), ActivateLicense() y Check().
- Parsear respuestas con nlohmann::json sin permitir que una excepción cierre la aplicación.
- Guardar session_token únicamente en memoria.
- No imprimir ni registrar password, licencia completa ni session_token.
- Exponer success, message, HTTP status y los datos útiles de usuario/licencia.
- Manejar HTTP 401, 403, 422, 426, 429 y errores de red.
- Usar timeouts razonables. La primera solicitud puede tardar por el arranque del plan gratuito de Render.
- Ejecutar solicitudes en un worker o std::async para no congelar el bucle de Dear ImGui.
- Impedir dos solicitudes simultáneas desde los botones.

HWID:
- Genera un identificador estable de Windows usando MachineGuid y, si no está disponible, un fallback estable del equipo.
- No uses un valor aleatorio en cada inicio.
- Evita mostrar el HWID completo en la interfaz o en logs.
- Usa exactamente el mismo HWID en login, license y check.

Interfaz Dear ImGui:
- Añade campos Username, Password y License.
- Añade botones Login y Activate License.
- Oculta Password con ImGuiInputTextFlags_Password.
- Muestra estados: initializing, idle, loading, authenticated, licensed y error.
- Ejecuta Init al comenzar o antes del primer Login.
- Login requiere username, password y HWID.
- Activate License solo se habilita después de Login.
- Después de activar, ejecuta Check periódicamente, como máximo una vez por minuto.
- Si Check responde que la sesión, cuenta, licencia o HWID ya no son válidos, bloquea el acceso protegido y muestra el mensaje devuelto por el servidor.
- No llames a la API en cada frame.

Conserva el estilo del código existente, configura correctamente el proyecto x64 y entrega una lista de archivos modificados. Compila el proyecto y corrige todos los errores antes de finalizar.
```

## Configuración

```cpp
std::string name = skCrypt("CNSI").decrypt();
std::string ownerid = skCrypt("W3CzqUZwRJ").decrypt();
std::string version = skCrypt("1.0").decrypt();
std::string url = skCrypt("https://auth-web-rbs9.onrender.com/api/v1/").decrypt();
std::string path = skCrypt("").decrypt();
```

`name`, `ownerid` y `version` deben coincidir exactamente con la aplicación seleccionada en el panel. El API actual no requiere `Application Secret` ni `path`.

## Contrato de la API

Todas las rutas usan `POST`, `Content-Type: application/json` y `Accept: application/json`.

### 1. Inicializar la aplicación

`POST https://auth-web-rbs9.onrender.com/api/v1/init`

```json
{
  "name": "CNSI",
  "ownerid": "W3CzqUZwRJ",
  "version": "1.0"
}
```

Respuesta satisfactoria:

```json
{
  "success": true,
  "message": "Aplicación inicializada.",
  "session_token": "TOKEN_DE_64_CARACTERES",
  "expires_at": "2026-09-24T02:30:00Z",
  "application": {
    "name": "CNSI",
    "version": "1.0"
  }
}
```

La sesión inicial dura 30 minutos. Una versión incorrecta responde HTTP `426` e incluye `current_version`.

### 2. Iniciar sesión

`POST /login`

Header:

```text
Authorization: Bearer SESSION_TOKEN
```

Body:

```json
{
  "username": "usuario",
  "password": "contraseña",
  "hwid": "IDENTIFICADOR_ESTABLE_DEL_EQUIPO"
}
```

Respuesta satisfactoria:

```json
{
  "success": true,
  "message": "Usuario autenticado.",
  "expires_at": "2026-09-24T14:00:00Z",
  "user": {
    "username": "usuario",
    "expiration": "2027-09-24T00:00:00Z",
    "hwid_affected": true
  }
}
```

Después del login, la sesión dura 12 horas. Si `hwid_affected` está activo, el primer equipo queda vinculado y los siguientes HWID serán rechazados.

### 3. Activar o validar una licencia

`POST /license`

Header:

```text
Authorization: Bearer SESSION_TOKEN
```

Body:

```json
{
  "license": "KNYR-XXXX-XXXX-XXXX",
  "hwid": "IDENTIFICADOR_ESTABLE_DEL_EQUIPO",
  "device_name": "PC principal"
}
```

Respuesta satisfactoria:

```json
{
  "success": true,
  "message": "Licencia validada.",
  "license": {
    "status": "active",
    "subscription": "default",
    "expires_at": "2026-10-24T00:00:00Z",
    "max_devices": 1
  }
}
```

Es obligatorio iniciar sesión antes de activar una licencia. Una licencia disponible se asigna al usuario durante la primera activación.

### 4. Comprobar la sesión

`POST /check`

Header:

```text
Authorization: Bearer SESSION_TOKEN
```

Body:

```json
{
  "hwid": "IDENTIFICADOR_ESTABLE_DEL_EQUIPO"
}
```

Respuesta satisfactoria:

```json
{
  "success": true,
  "message": "Sesión válida.",
  "authenticated": true,
  "licensed": true,
  "expires_at": "2026-09-24T14:00:00Z"
}
```

El cliente debe exigir `authenticated == true` y `licensed == true` antes de habilitar funciones protegidas.

## Estructura C++ sugerida

```cpp
struct ApiResult {
    bool success = false;
    long httpStatus = 0;
    std::string message;
    nlohmann::json data;
};

class KenyraAuthClient {
public:
    KenyraAuthClient(std::string baseUrl,
                     std::string applicationName,
                     std::string ownerId,
                     std::string version);

    ApiResult Init();
    ApiResult Login(const std::string& username,
                    const std::string& password,
                    const std::string& hwid);
    ApiResult ActivateLicense(const std::string& license,
                              const std::string& hwid,
                              const std::string& deviceName);
    ApiResult Check(const std::string& hwid);

    bool IsInitialized() const;
    bool IsAuthenticated() const;
    bool IsLicensed() const;

private:
    ApiResult PostJson(const std::string& endpoint,
                       const nlohmann::json& body,
                       bool includeBearerToken);

    std::string baseUrl_;
    std::string applicationName_;
    std::string ownerId_;
    std::string version_;
    std::string sessionToken_;
    bool authenticated_ = false;
    bool licensed_ = false;
};
```

Opciones mínimas de libcurl:

```cpp
curl_easy_setopt(curl, CURLOPT_URL, requestUrl.c_str());
curl_easy_setopt(curl, CURLOPT_POST, 1L);
curl_easy_setopt(curl, CURLOPT_POSTFIELDS, requestBody.c_str());
curl_easy_setopt(curl, CURLOPT_POSTFIELDSIZE, requestBody.size());
curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
curl_easy_setopt(curl, CURLOPT_WRITEDATA, &responseBody);
curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1L);
curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2L);
curl_easy_setopt(curl, CURLOPT_CONNECTTIMEOUT, 20L);
curl_easy_setopt(curl, CURLOPT_TIMEOUT, 90L);
curl_easy_setopt(curl, CURLOPT_USERAGENT, "Kenyra-Cpp/1.0");
```

El timeout amplio permite que la primera petición espere el arranque del servicio gratuito. Las solicitudes posteriores normalmente serán mucho más rápidas.

## Errores que debe manejar el cliente

- `401`: credenciales, aplicación, licencia o sesión no válidas.
- `403`: cuenta, aplicación o licencia bloqueada; HWID diferente; límite de dispositivos.
- `422`: datos incompletos. Laravel devuelve `message` y un objeto `errors`.
- `426`: versión del cliente no permitida. Mostrar `current_version`.
- `429`: demasiadas solicitudes. No reintentar inmediatamente.
- `5xx` o error de red: mostrar un error temporal y permitir reintento controlado.

Límites actuales:

- `init`: 30 solicitudes por minuto.
- `login`: 10 solicitudes por minuto.
- `license`: 20 solicitudes por minuto.
- `check`: 60 solicitudes por minuto.

## Estado recomendado para Dear ImGui

```cpp
enum class AuthState {
    Initializing,
    Idle,
    Loading,
    Authenticated,
    Licensed,
    Error
};
```

El hilo de red actualiza un resultado protegido por mutex o una cola segura. El hilo que dibuja ImGui consume ese resultado y cambia el estado. Ninguna llamada HTTP debe ejecutarse directamente dentro del frame de ImGui.

## Reglas de seguridad

- No conectarse directamente a Supabase desde C++.
- No incluir la contraseña de Supabase ni `APP_KEY` en el ejecutable.
- No desactivar la validación TLS.
- No tratar `skCrypt` como sustituto de la validación del servidor.
- No guardar contraseñas, licencias o tokens en archivos de texto.
- Considerar que cualquier secreto incluido en un binario cliente puede extraerse.
- Toda autorización real debe depender de las respuestas vigentes del servidor.

## Prueba de aceptación

La implementación queda terminada cuando:

1. `Init` obtiene un token sin bloquear la interfaz.
2. Un usuario válido puede iniciar sesión con su HWID.
3. Una licencia disponible puede activarse y queda vinculada al usuario/dispositivo.
4. `Check` devuelve `authenticated: true` y `licensed: true`.
5. Contraseña, versión, licencia o HWID incorrectos muestran el mensaje del servidor.
6. El cliente deja de habilitar las funciones protegidas si `Check` falla.
7. El proyecto compila en Visual Studio para x64 sin advertencias nuevas relevantes.

