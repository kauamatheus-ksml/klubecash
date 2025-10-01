#!/bin/bash

# Script para testar a API de saldo diretamente
# Para diagnosticar o erro "Forbidden"

echo "🧪 Testando API de Saldo Klube Cash"
echo "=================================="

# Teste 1: Testar com telefone conhecido
echo "📞 Teste 1: Telefone conhecido (34991191534)"
curl -X POST \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Content-Type: application/json' \
  -H 'User-Agent: n8n-workflow' \
  -d '{
    "phone": "34991191534",
    "secret": "klube-cash-2024"
  }' \
  -v

echo -e "\n\n"

# Teste 2: Verificar se endpoint existe
echo "🔍 Teste 2: Verificando se endpoint existe"
curl -I 'https://klubecash.com/api/whatsapp-saldo.php'

echo -e "\n\n"

# Teste 3: Testar com método GET (deve falhar)
echo "❌ Teste 3: Método GET (deve falhar)"
curl -X GET 'https://klubecash.com/api/whatsapp-saldo.php'

echo -e "\n\n"

# Teste 4: Testar sem secret
echo "🔒 Teste 4: Sem secret (deve falhar)"
curl -X POST \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Content-Type: application/json' \
  -d '{
    "phone": "34991191534"
  }'

echo -e "\n\n"

# Teste 5: Testar com secret errado
echo "🔐 Teste 5: Secret errado (deve falhar)"
curl -X POST \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Content-Type: application/json' \
  -d '{
    "phone": "34991191534",
    "secret": "wrong-secret"
  }'

echo -e "\n\n"

# Teste 6: Verificar headers de CORS
echo "🌐 Teste 6: Headers de CORS"
curl -X OPTIONS \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Origin: https://n8n.klubecash.com' \
  -H 'Access-Control-Request-Method: POST' \
  -H 'Access-Control-Request-Headers: Content-Type' \
  -v

echo -e "\n\n✅ Testes concluídos!"