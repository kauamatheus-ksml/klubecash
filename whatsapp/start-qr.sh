#!/bin/bash

# Script para inicializar bot e mostrar QR code completo
# Uso: ./start-qr.sh

echo "🚀 Iniciando WhatsApp Bot para gerar QR Code..."
echo "📱 O QR Code aparecerá abaixo quando estiver pronto"
echo "🔍 Use este QR Code para conectar seu WhatsApp"
echo ""
echo "⚠️  IMPORTANTE: Deixe este processo rodando até conectar!"
echo "⚠️  Após conectar, pressione Ctrl+C e execute: systemctl start klube-whatsapp-bot.service"
echo ""
echo "=" | tr '=' '=' | head -c 80; echo ""

cd /root/whatsapp-klube-venom1
export NODE_OPTIONS="--max-old-space-size=512"
node bot.js