#!/bin/bash

###############################################################################
# Script para ejecutar pruebas de estrés de API de Bagisto con Newman
# 
# Este script simula carga ejecutando la colección de Postman 100 veces
# con un delay de 100ms entre cada request
#
# Requisitos:
# - Newman (CLI de Postman): npm install -g newman newman-reporter-htmlextra
# - Variables de entorno configuradas
###############################################################################

set -e  # Salir si hay algún error

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Bagisto API - Prueba de Estrés${NC}"
echo -e "${BLUE}========================================${NC}"

# Variables de entorno (pueden venir de Jenkins)
BASE_URL=${BAGISTO_BASE_URL:-"http://localhost:8000"}
CUSTOMER_EMAIL=${BAGISTO_CUSTOMER_EMAIL:-"test@example.com"}
CUSTOMER_PASSWORD=${BAGISTO_CUSTOMER_PASSWORD:-"password"}
ITERATIONS=${STRESS_TEST_ITERATIONS:-100}
DELAY=${STRESS_TEST_DELAY:-100}

echo -e "${YELLOW}Configuración de la prueba:${NC}"
echo "  - Base URL: $BASE_URL"
echo "  - Customer Email: $CUSTOMER_EMAIL"
echo "  - Iteraciones: $ITERATIONS"
echo "  - Delay entre requests: ${DELAY}ms"
echo ""

# Directorio de resultados
RESULTS_DIR="test-results"
mkdir -p $RESULTS_DIR

echo -e "${YELLOW}Iniciando prueba de estrés...${NC}"
echo -e "${YELLOW}Esto ejecutará la colección $ITERATIONS veces${NC}"
echo ""

# Ejecutar Newman con múltiples iteraciones para simular carga
newman run postman/Bagisto_API_Collection.postman_collection.json \
  --environment postman/Bagisto_Environment.postman_environment.json \
  --env-var "base_url=$BASE_URL" \
  --env-var "customer_email=$CUSTOMER_EMAIL" \
  --env-var "customer_password=$CUSTOMER_PASSWORD" \
  --iteration-count $ITERATIONS \
  --delay-request $DELAY \
  --reporters cli,htmlextra,junit \
  --reporter-htmlextra-export "$RESULTS_DIR/stress-test-report.html" \
  --reporter-junit-export "$RESULTS_DIR/stress-test-results.xml" \
  --color on \
  --timeout-request 10000 \
  --reporter-htmlextra-title "Bagisto API - Stress Test Report" \
  --reporter-htmlextra-logs

# Verificar el resultado
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ Prueba de estrés completada${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${GREEN}Resultados:${NC}"
    echo "  - Iteraciones completadas: $ITERATIONS"
    echo "  - Reporte HTML: $RESULTS_DIR/stress-test-report.html"
    echo "  - Reporte JUnit: $RESULTS_DIR/stress-test-results.xml"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}✗ La prueba de estrés falló${NC}"
    echo -e "${RED}========================================${NC}"
    echo ""
    echo -e "${RED}Revisa el reporte en: $RESULTS_DIR/stress-test-report.html${NC}"
    echo ""
    exit 1
fi

