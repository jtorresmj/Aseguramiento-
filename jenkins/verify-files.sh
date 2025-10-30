#!/bin/bash

###############################################################################
# Script para verificar que los archivos necesarios estén en el lugar correcto
# para Jenkins
###############################################################################

echo "========================================="
echo "Verificando archivos para Jenkins"
echo "========================================="

# Verificar directorio actual
echo "Directorio actual:"
pwd
echo ""

# Verificar estructura de directorios
echo "Estructura de directorios:"
ls -la
echo ""

# Verificar archivos de Postman
echo "Verificando archivos de Postman:"
if [ -d "postman" ]; then
    echo "✓ Directorio postman encontrado"
    ls -la postman/
    
    if [ -f "postman/Bagisto_API_Collection.postman_collection.json" ]; then
        echo "✓ Colección de Postman encontrada"
    else
        echo "❌ Colección de Postman NO encontrada"
    fi
    
    if [ -f "postman/Bagisto_Environment.postman_environment.json" ]; then
        echo "✓ Environment de Postman encontrado"
    else
        echo "❌ Environment de Postman NO encontrado"
    fi
else
    echo "❌ Directorio postman NO encontrado"
fi

echo ""

# Verificar scripts de Jenkins
echo "Verificando scripts de Jenkins:"
if [ -d "jenkins" ]; then
    echo "✓ Directorio jenkins encontrado"
    ls -la jenkins/
    
    if [ -f "jenkins/run-postman-tests.sh" ]; then
        echo "✓ Script run-postman-tests.sh encontrado"
    else
        echo "❌ Script run-postman-tests.sh NO encontrado"
    fi
else
    echo "❌ Directorio jenkins NO encontrado"
fi

echo ""

# Verificar Newman
echo "Verificando Newman:"
if command -v newman &> /dev/null; then
    echo "✓ Newman instalado"
    newman --version
else
    echo "❌ Newman NO instalado"
    echo "Instalando Newman..."
    npm install -g newman newman-reporter-htmlextra
fi

echo ""

# Crear directorio de resultados
echo "Creando directorio de resultados:"
mkdir -p test-results
echo "✓ Directorio test-results creado"

echo ""
echo "========================================="
echo "Verificación completada"
echo "========================================="
