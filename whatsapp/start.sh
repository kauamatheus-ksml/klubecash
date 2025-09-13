#!/bin/bash

# Script para iniciar o bot WhatsApp
echo "Iniciando Klube Cash WhatsApp Bot..."

# Instalar dependências se necessário
if [ ! -d "node_modules" ]; then
    echo "Instalando dependências..."
    npm install
fi

# Iniciar o bot
echo "Iniciando bot..."
npm start