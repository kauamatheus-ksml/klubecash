#!/bin/bash

# Script para iniciar o serviço de envio WhatsApp
# Klube Cash - 2025

echo "🚀 Iniciando WhatsApp Automation Service..."

# Ativar ambiente virtual
source venv/bin/activate

# Verificar se .env existe
if [ ! -f .env ]; then
    echo "❌ Arquivo .env não encontrado!"
    echo "Execute ./install.sh primeiro"
    exit 1
fi

# Executar script principal
echo "▶️  Executando whatsapp_sender.py..."
python3 whatsapp_sender.py

# Se o script parar, mostrar mensagem
echo "⏹️  Serviço parado"