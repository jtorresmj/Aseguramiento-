#!/bin/bash

###############################################################################
# Script para ejecutar pruebas de API de Bagisto con Postman en Jenkins
# 
# Requisitos:
# - Newman (CLI de Postman): npm install -g newman newman-reporter-htmlextra
# - Variables de entorno configuradas en Jenkins
###############################################################################

set -e  # Salir si hay algún error

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Bagisto API Tests - Ejecución con Newman${NC}"
echo -e "${GREEN}========================================${NC}"

# Variables de entorno (pueden venir de Jenkins)
BASE_URL=${BAGISTO_BASE_URL:-"http://localhost:8000"}
CUSTOMER_EMAIL=${BAGISTO_CUSTOMER_EMAIL:-"test@example.com"}
CUSTOMER_PASSWORD=${BAGISTO_CUSTOMER_PASSWORD:-"password"}

echo -e "${YELLOW}Configuración:${NC}"
echo "  - Base URL: $BASE_URL"
echo "  - Customer Email: $CUSTOMER_EMAIL"
echo ""

# Directorio de resultados
RESULTS_DIR="test-results"
mkdir -p $RESULTS_DIR

echo -e "${YELLOW}Ejecutando pruebas de API...${NC}"

# Ejecutar Newman con variables de entorno
newman run postman/Bagisto_API_Collection.postman_collection.json \
  --environment postman/Bagisto_Environment.postman_environment.json \
  --env-var "base_url=$BASE_URL" \
  --env-var "customer_email=$CUSTOMER_EMAIL" \
  --env-var "customer_password=$CUSTOMER_PASSWORD" \
  --reporters cli,htmlextra,junit \
  --reporter-htmlextra-export "$RESULTS_DIR/api-test-report.html" \
  --reporter-junit-export "$RESULTS_DIR/api-test-results.xml" \
  --color on \
  --bail \
  --timeout-request 10000

# Verificar el resultado
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Todas las pruebas pasaron exitosamente${NC}"
    exit 0
else
    echo -e "${RED}✗ Algunas pruebas fallaron${NC}"
    exit 1
fi

