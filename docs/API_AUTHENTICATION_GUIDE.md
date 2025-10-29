# Guía de Autenticación API de Bagisto

## 📋 Tabla de Contenidos

1. [Resumen](#resumen)
2. [Cambios Implementados](#cambios-implementados)
3. [Cómo Funciona](#cómo-funciona)
4. [Uso con Postman](#uso-con-postman)
5. [Integración con Jenkins](#integración-con-jenkins)
6. [Solución al Security Hotspot](#solución-al-security-hotspot)
7. [Troubleshooting](#troubleshooting)

---

## Resumen

Se ha implementado autenticación con **Laravel Sanctum** para proteger los endpoints de la API de Bagisto, eliminando la necesidad de excluir rutas críticas de la protección CSRF.

### ✅ Beneficios

- **Seguridad mejorada**: CSRF protección activada en producción
- **Autenticación robusta**: Tokens Bearer para APIs
- **Compatibilidad**: Funciona con Postman, Jenkins y cualquier cliente HTTP
- **Sin warnings de seguridad**: SonarQube ya no reportará el security hotspot

---

## Cambios Implementados

### 1. Rutas Protegidas con Sanctum

**Archivo**: `packages/Webkul/Shop/src/Routes/api.php`

Las siguientes rutas ahora requieren autenticación con token Bearer:

- ✅ `POST /api/checkout/cart` - Agregar productos al carrito
- ✅ `GET /api/checkout/cart` - Obtener carrito
- ✅ `PUT /api/checkout/cart` - Actualizar carrito
- ✅ `DELETE /api/checkout/cart` - Eliminar items del carrito
- ✅ `POST /api/checkout/onepage/orders` - Crear orden
- ✅ Todos los demás endpoints de cart y checkout

**Ruta NO protegida** (debe permanecer pública):
- ❌ `POST /api/customer/login` - Endpoint para obtener el token

### 2. CSRF Middleware Actualizado

**Archivo**: `app/Http/Middleware/VerifyCsrfToken.php`

**Antes:**
```php
protected $except = [
    'api/customer/login',
    'api/checkout/cart',
    'api/checkout/onepage/orders',
];
```

**Ahora:**
```php
protected $except = [
    // Solo login excluido - el resto usa Sanctum
    'api/customer/login',
];
```

---

## Cómo Funciona

### Flujo de Autenticación

```
┌─────────────┐
│   Cliente   │
└──────┬──────┘
       │
       │ 1. POST /api/customer/login
       │    {email, password}
       ▼
┌─────────────────┐
│  Bagisto API    │
│  (Laravel)      │
└──────┬──────────┘
       │
       │ 2. Valida credenciales
       │ 3. Genera token Sanctum
       │
       ▼
┌─────────────┐
│   Cliente   │◄── 4. Retorna {token, customer}
└──────┬──────┘
       │
       │ 5. Guarda token
       │
       │ 6. Usa token en siguientes requests
       │    Authorization: Bearer {token}
       ▼
┌─────────────────┐
│  Rutas API      │
│  Protegidas     │
└─────────────────┘
```

### Ejemplo de Request con Token

```bash
# 1. Login
curl -X POST http://localhost:8000/api/customer/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123"
  }'

# Respuesta:
{
  "data": {
    "customer": {...},
    "token": "1|abc123xyz...",
    "token_type": "Bearer"
  },
  "message": "Logged in successfully."
}

# 2. Usar el token para agregar al carrito
curl -X POST http://localhost:8000/api/checkout/cart \
  -H "Authorization: Bearer 1|abc123xyz..." \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 1
  }'
```

---

## Uso con Postman

### Instalación

1. **Importar la colección**:
   - Abrir Postman
   - Click en "Import"
   - Seleccionar `postman/Bagisto_API_Collection.postman_collection.json`

2. **Importar el environment**:
   - Click en "Import"
   - Seleccionar `postman/Bagisto_Environment.postman_environment.json`

3. **Configurar variables**:
   - Seleccionar el environment "Bagisto API - Local"
   - Editar estas variables:
     - `base_url`: URL de tu aplicación (ej: `http://localhost:8000`)
     - `customer_email`: Email de un cliente de prueba
     - `customer_password`: Contraseña del cliente

### Ejecución

1. **Ejecutar "Login - Get Token"**
   - El token se guarda **automáticamente** en la variable `access_token`
   - El token se usa en todas las demás requests

2. **Ejecutar cualquier otro endpoint**
   - Todos heredan el token automáticamente
   - No necesitas copiar/pegar el token manualmente

### Script de Test Automático

El request de login incluye un script que guarda el token automáticamente:

```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.token) {
        pm.collectionVariables.set("access_token", jsonData.data.token);
        console.log("Token guardado exitosamente");
    }
}
```

---

## Integración con Jenkins

### Requisitos Previos

1. **Instalar Newman** (CLI de Postman):
```bash
npm install -g newman newman-reporter-htmlextra
```

2. **Configurar credenciales en Jenkins**:
   - Ir a Jenkins > Manage Jenkins > Credentials
   - Agregar dos "Secret text" credentials:
     - ID: `bagisto-test-email`
     - ID: `bagisto-test-password`

### Opción 1: Usando el Jenkinsfile

1. **Copiar el Jenkinsfile** a la raíz del proyecto:
```bash
cp jenkins/Jenkinsfile ./
```

2. **Crear un Pipeline Job** en Jenkins:
   - New Item > Pipeline
   - En "Pipeline" section, seleccionar "Pipeline script from SCM"
   - Configurar tu repositorio Git

3. **Ejecutar** el pipeline

### Opción 2: Usando el Script Bash Directamente

En tu Jenkins job (Freestyle o Pipeline):

```groovy
stage('API Tests') {
    steps {
        sh '''
            export BAGISTO_BASE_URL="http://your-server.com"
            export BAGISTO_CUSTOMER_EMAIL="test@example.com"
            export BAGISTO_CUSTOMER_PASSWORD="password"
            
            chmod +x jenkins/run-postman-tests.sh
            ./jenkins/run-postman-tests.sh
        '''
    }
}
```

### Reportes Generados

El script genera automáticamente:

1. **Reporte HTML**: `test-results/api-test-report.html`
   - Reporte visual detallado
   - Incluye tiempo de respuesta, headers, body, etc.

2. **Reporte JUnit XML**: `test-results/api-test-results.xml`
   - Compatible con Jenkins
   - Permite tracking histórico de tests

---

## Solución al Security Hotspot

### ❌ Problema Original (SonarQube)

```
Security Hotspot: Disabling CSRF protection is security-sensitive
Category: Cross-Site Request Forgery (CSRF)
Priority: High
File: app/Http/Middleware/VerifyCsrfToken.php
```

**Causa**: Los endpoints críticos estaban excluidos de CSRF:
- `api/customer/login`
- `api/checkout/cart`
- `api/checkout/onepage/orders`

### ✅ Solución Implementada

1. **Protección con Sanctum**: Las rutas ahora usan tokens Bearer en lugar de cookies de sesión
2. **CSRF Restaurado**: Solo el login está excluido (necesario para obtener el token)
3. **Sin vulnerabilidad**: Los endpoints críticos están protegidos con autenticación robusta

### Verificación

Ejecuta SonarQube nuevamente. El warning debería cambiar de:

```diff
- High Priority: 3 endpoints sin protección CSRF
+ Low Priority: 1 endpoint (login) sin protección CSRF
```

El endpoint de login **debe** estar excluido porque:
- Es el punto de entrada para obtener tokens
- No tiene estado (stateless)
- Está protegido por rate limiting y validación de credenciales

---

## Troubleshooting

### Error: "Unauthenticated"

**Problema**: No se está enviando el token o está expirado.

**Solución**:
1. Verificar que el header `Authorization: Bearer {token}` está presente
2. Hacer login nuevamente para obtener un token fresco
3. Verificar que `config/sanctum.php` tiene `expiration` configurado

### Error: "CSRF token mismatch"

**Problema**: Estás enviando requests desde el navegador sin token.

**Solución**:
- Para APIs: Usar tokens Bearer (no cookies)
- Para frontend web: Laravel incluye automáticamente el token CSRF

### Error: "Token not found"

**Problema**: El modelo Customer no tiene el trait HasApiTokens.

**Solución**:
Verificar que el modelo Customer incluya:
```php
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model
{
    use HasApiTokens;
}
```

### Tests fallan en Jenkins

**Problema**: Variables de entorno no configuradas.

**Solución**:
1. Verificar que las credenciales existen en Jenkins
2. Verificar que `BAGISTO_BASE_URL` apunta al servidor correcto
3. Revisar logs: `test-results/api-test-report.html`

---

## Referencias

- [Laravel Sanctum Documentation](https://laravel.com/docs/11.x/sanctum)
- [Postman Learning Center](https://learning.postman.com/)
- [Newman Documentation](https://learning.postman.com/docs/running-collections/using-newman-cli/command-line-integration-with-newman/)
- [Jenkins Pipeline Documentation](https://www.jenkins.io/doc/book/pipeline/)

---

## Contacto y Soporte

Si tienes preguntas o problemas:
1. Revisa la sección de [Troubleshooting](#troubleshooting)
2. Verifica los logs de Laravel: `storage/logs/laravel.log`
3. Revisa los reportes de Newman: `test-results/api-test-report.html`


