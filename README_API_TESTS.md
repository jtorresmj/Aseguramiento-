# 🚀 Guía Rápida - Pruebas de API con Postman y Jenkins

## Para desarrolladores

### Uso Local con Postman

1. **Importar archivos en Postman**:
   - `postman/Bagisto_API_Collection.postman_collection.json`
   - `postman/Bagisto_Environment.postman_environment.json`

2. **Configurar environment**:
   - Editar `base_url`, `customer_email`, `customer_password`

3. **Ejecutar "Login - Get Token"**
   - El token se guarda automáticamente

4. **Ejecutar otros endpoints**
   - Todos usan el token automáticamente

---

## Para DevOps / Jenkins

### Setup Rápido

```bash
# 1. Instalar Newman
npm install -g newman newman-reporter-htmlextra

# 2. Ejecutar tests
export BAGISTO_BASE_URL="http://localhost:8000"
export BAGISTO_CUSTOMER_EMAIL="test@example.com"
export BAGISTO_CUSTOMER_PASSWORD="password"

./jenkins/run-postman-tests.sh

# 3. Ver resultados
open test-results/api-test-report.html
```

### Integración con Jenkins

Copiar el `jenkins/Jenkinsfile` a la raíz del proyecto y crear un Pipeline job.

**Credenciales requeridas en Jenkins**:
- `bagisto-test-email`
- `bagisto-test-password`

---

## 📁 Estructura de Archivos

```
├── postman/
│   ├── Bagisto_API_Collection.postman_collection.json  # Colección de endpoints
│   └── Bagisto_Environment.postman_environment.json    # Variables de entorno
├── jenkins/
│   ├── Jenkinsfile                                     # Pipeline de Jenkins
│   └── run-postman-tests.sh                           # Script de ejecución
└── docs/
    └── API_AUTHENTICATION_GUIDE.md                     # Documentación completa
```

---

## 🔒 Cambios de Seguridad

### Antes
- ❌ Endpoints sin CSRF (inseguro)
- ❌ Warning de SonarQube

### Ahora
- ✅ Autenticación con Sanctum
- ✅ CSRF protección activada
- ✅ Sin warnings de seguridad

---

## 📚 Documentación Completa

Ver: [`docs/API_AUTHENTICATION_GUIDE.md`](docs/API_AUTHENTICATION_GUIDE.md)

