#!/bin/bash

# Script para criar instância WhatsApp no Evolution API
# Klube Cash - 2025

EVOLUTION_URL="http://localhost:8080"
API_KEY="klubecash-2024-secret-key-change-this"
INSTANCE_NAME="klubecash"

echo "📱 Criando instância WhatsApp no Evolution API..."
echo ""

# Criar instância
RESPONSE=$(curl -s -X POST \
  "${EVOLUTION_URL}/instance/create" \
  -H "apikey: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d "{
    \"instanceName\": \"${INSTANCE_NAME}\",
    \"qrcode\": true,
    \"integration\": \"WHATSAPP-BAILEYS\"
  }")

echo "✅ Instância criada!"
echo ""
echo "$RESPONSE" | jq '.'

# Obter QR Code
echo ""
echo "📷 Obtendo QR Code para conexão..."
echo ""

QR_RESPONSE=$(curl -s -X GET \
  "${EVOLUTION_URL}/instance/connect/${INSTANCE_NAME}" \
  -H "apikey: ${API_KEY}")

# Extrair QR Code base64
QR_CODE=$(echo "$QR_RESPONSE" | jq -r '.qrcode.base64')

if [ "$QR_CODE" != "null" ]; then
    echo "✅ QR Code obtido!"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📱 ESCANEIE O QR CODE ABAIXO COM SEU WHATSAPP"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Acesse no navegador para ver o QR Code:"
    echo "http://localhost:8080/instance/qr/${INSTANCE_NAME}?apikey=${API_KEY}"
    echo ""
    echo "Ou use este link direto (caso tenha qrencode instalado):"
    echo "$QR_CODE" | qrencode -t ANSIUTF8
else
    echo "⚠️  QR Code não disponível. Acesse:"
    echo "http://localhost:8080/instance/qr/${INSTANCE_NAME}?apikey=${API_KEY}"
fi

echo ""
echo "⏳ Aguardando conexão do WhatsApp..."
echo "   Escaneie o QR Code com seu WhatsApp Business"

# Verificar status da conexão
for i in {1..60}; do
    sleep 5
    STATUS=$(curl -s -X GET \
      "${EVOLUTION_URL}/instance/connectionState/${INSTANCE_NAME}" \
      -H "apikey: ${API_KEY}" | jq -r '.instance.state')
    
    if [ "$STATUS" = "open" ]; then
        echo ""
        echo "✅ WhatsApp conectado com sucesso!"
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "🎉 INSTÂNCIA PRONTA PARA USO!"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        echo "Instância: ${INSTANCE_NAME}"
        echo "Status: Conectado"
        echo ""
        echo "Agora você pode:"
        echo "1. Configurar o arquivo .env do Python"
        echo "2. Executar o script de automação"
        echo ""
        exit 0
    fi
    
    echo -n "."
done

echo ""
echo "⏱️  Tempo esgotado. Verifique o status manualmente:"
echo "curl -X GET '${EVOLUTION_URL}/instance/connectionState/${INSTANCE_NAME}' -H 'apikey: ${API_KEY}'"