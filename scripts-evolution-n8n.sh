#!/bin/bash

# Scripts prontos para Evolution API + n8n - Klube Cash
# Baseado nos dados do DadosNecessários.txt

API_KEY="HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
EVOLUTION_URL="https://evolutionapi.klubecash.com"
N8N_URL="https://n8n.klubecash.com"
INSTANCE_NAME="KluebCash"
INSTANCE_ID="19A79573077E-4B3D-AB2B-DA0AC8110989"
WHATSAPP_NUMBER="553430301344"

echo "🚀 Scripts Klube Cash - Evolution API + n8n"
echo "================================================"

# 1. CONFIGURAR WEBHOOK
setup_webhook() {
    echo "📡 Configurando webhook do n8n..."

    curl -X POST \
      "${EVOLUTION_URL}/webhook/set/${INSTANCE_NAME}" \
      -H 'Content-Type: application/json' \
      -H "apikey: ${API_KEY}" \
      -d '{
        "url": "'${N8N_URL}'/webhook/whatsapp",
        "enabled": true,
        "events": [
          "MESSAGE_RECEIVED",
          "MESSAGE_SENT",
          "CONNECTION_UPDATE"
        ]
      }'

    echo -e "\n✅ Webhook configurado!"
}

# 2. VERIFICAR STATUS DA INSTÂNCIA
check_status() {
    echo "🔍 Verificando status da instância..."

    curl -X GET \
      "${EVOLUTION_URL}/instance/connectionState/${INSTANCE_NAME}" \
      -H "apikey: ${API_KEY}"

    echo -e "\n"
}

# 3. VERIFICAR WEBHOOK CONFIGURADO
check_webhook() {
    echo "📡 Verificando webhook configurado..."

    curl -X GET \
      "${EVOLUTION_URL}/webhook/find/${INSTANCE_NAME}" \
      -H "apikey: ${API_KEY}"

    echo -e "\n"
}

# 4. ENVIAR MENSAGEM DE TESTE
send_test_message() {
    local number=${1:-"5534991191534"}

    echo "📤 Enviando mensagem de teste para ${number}..."

    curl -X POST \
      "${EVOLUTION_URL}/message/sendText/${INSTANCE_NAME}" \
      -H 'Content-Type: application/json' \
      -H "apikey: ${API_KEY}" \
      -d '{
        "number": "'${number}'",
        "text": "🧪 *Teste do Sistema Klube Cash*\n\nEvolution API + n8n funcionando!\n\nData: '$(date)'\n\nDigite *saldo* para testar a consulta!",
        "delay": 1000
      }'

    echo -e "\n✅ Mensagem enviada!"
}

# 5. TESTAR CONSULTA DE SALDO (simular recebimento)
test_saldo() {
    local number=${1:-"5534991191534"}

    echo "💰 Testando consulta de saldo para ${number}..."

    # Enviar palavra "saldo" para triggerar o workflow
    curl -X POST \
      "${EVOLUTION_URL}/message/sendText/${INSTANCE_NAME}" \
      -H 'Content-Type: application/json' \
      -H "apikey: ${API_KEY}" \
      -d '{
        "number": "'${number}'",
        "text": "saldo",
        "delay": 1000
      }'

    echo -e "\n💡 Palavra 'saldo' enviada. Verifique se o n8n processou a mensagem!"
}

# 6. VERIFICAR LOGS DA INSTÂNCIA
check_logs() {
    echo "📋 Logs da instância (últimas mensagens)..."

    curl -X GET \
      "${EVOLUTION_URL}/chat/findMessages/${INSTANCE_NAME}" \
      -H "apikey: ${API_KEY}" \
      -H 'Content-Type: application/json'

    echo -e "\n"
}

# 7. RESETAR WEBHOOK (se necessário)
reset_webhook() {
    echo "🔄 Resetando webhook..."

    # Primeiro desabilitar
    curl -X DELETE \
      "${EVOLUTION_URL}/webhook/set/${INSTANCE_NAME}" \
      -H "apikey: ${API_KEY}"

    sleep 2

    # Depois reconfigurar
    setup_webhook
}

# 8. STATUS COMPLETO
full_status() {
    echo "📊 STATUS COMPLETO DO SISTEMA"
    echo "================================"

    echo -e "\n🔗 URLs:"
    echo "Evolution API: ${EVOLUTION_URL}"
    echo "n8n: ${N8N_URL}"
    echo "Instância: ${INSTANCE_NAME}"
    echo "WhatsApp: ${WHATSAPP_NUMBER}"

    echo -e "\n📱 Status da Instância:"
    check_status

    echo -e "\n📡 Status do Webhook:"
    check_webhook

    echo -e "\n✅ Sistema pronto para uso!"
}

# MENU INTERATIVO
show_menu() {
    echo -e "\n📋 MENU DE COMANDOS:"
    echo "1. Setup inicial (configurar webhook)"
    echo "2. Verificar status"
    echo "3. Verificar webhook"
    echo "4. Enviar mensagem de teste"
    echo "5. Testar consulta de saldo"
    echo "6. Ver logs"
    echo "7. Resetar webhook"
    echo "8. Status completo"
    echo "9. Sair"
}

# EXECUÇÃO PRINCIPAL
case "${1}" in
    "setup"|"1")
        setup_webhook
        ;;
    "status"|"2")
        check_status
        ;;
    "webhook"|"3")
        check_webhook
        ;;
    "test"|"4")
        send_test_message $2
        ;;
    "saldo"|"5")
        test_saldo $2
        ;;
    "logs"|"6")
        check_logs
        ;;
    "reset"|"7")
        reset_webhook
        ;;
    "full"|"8")
        full_status
        ;;
    *)
        echo "🚀 Evolution API + n8n - Klube Cash"
        echo "Uso: $0 [comando] [número_opcional]"
        echo ""
        echo "Comandos disponíveis:"
        echo "  setup    - Configurar webhook inicial"
        echo "  status   - Verificar status da instância"
        echo "  webhook  - Verificar webhook configurado"
        echo "  test     - Enviar mensagem de teste"
        echo "  saldo    - Testar consulta de saldo"
        echo "  logs     - Ver logs da instância"
        echo "  reset    - Resetar webhook"
        echo "  full     - Status completo do sistema"
        echo ""
        echo "Exemplos:"
        echo "  $0 setup"
        echo "  $0 test 5534999999999"
        echo "  $0 saldo 5534991191534"
        echo "  $0 full"
        ;;
esac