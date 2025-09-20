#!/bin/bash

# KlubeCash API - Exemplos usando cURL
# 
# Este script demonstra como usar a KlubeCash API usando cURL
# Ideal para testes rápidos e integração em scripts shell

# Configurações
API_KEY="kc_live_123456789012345678901234567890123456789012345678901234567890"
BASE_URL="https://klubecash.com/api-external/v1"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== KLUBECASH API - EXEMPLOS CURL ===${NC}\n"

# Função para fazer requisições com melhor formatação
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -e "${YELLOW}${description}${NC}"
    echo -e "${BLUE}${method} ${BASE_URL}${endpoint}${NC}"
    
    if [ "$method" = "GET" ]; then
        if [ "$endpoint" = "/auth/info" ] || [ "$endpoint" = "/auth/health" ]; then
            # Endpoints públicos
            response=$(curl -s -w "HTTP_CODE:%{http_code}" \
                -X GET "${BASE_URL}${endpoint}" \
                -H "Content-Type: application/json")
        else
            # Endpoints com autenticação
            response=$(curl -s -w "HTTP_CODE:%{http_code}" \
                -X GET "${BASE_URL}${endpoint}" \
                -H "X-API-Key: ${API_KEY}" \
                -H "Content-Type: application/json")
        fi
    else
        # POST/PUT/DELETE
        response=$(curl -s -w "HTTP_CODE:%{http_code}" \
            -X "${method}" "${BASE_URL}${endpoint}" \
            -H "X-API-Key: ${API_KEY}" \
            -H "Content-Type: application/json" \
            -d "${data}")
    fi
    
    # Extrair código HTTP e body
    http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
    body=$(echo "$response" | sed 's/HTTP_CODE:[0-9]*$//')
    
    # Colorir baseado no status
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}Status: $http_code${NC}"
    else
        echo -e "${RED}Status: $http_code${NC}"
    fi
    
    # Formatar JSON se possível
    if command -v jq &> /dev/null; then
        echo "$body" | jq . 2>/dev/null || echo "$body"
    else
        echo "$body"
    fi
    
    echo -e "\n${NC}---\n"
}

# 1. Informações da API (público)
make_request "GET" "/auth/info" "" "1. Obtendo informações da API"

# 2. Health Check (público)
make_request "GET" "/auth/health" "" "2. Verificando saúde da API"

# 3. Listar usuários (requer autenticação)
make_request "GET" "/users" "" "3. Listando usuários"

# 4. Listar lojas (requer autenticação)
make_request "GET" "/stores" "" "4. Listando lojas"

# 5. Calcular cashback (requer autenticação)
cashback_data='{"store_id": 59, "amount": 100.00}'
make_request "POST" "/cashback/calculate" "$cashback_data" "5. Calculando cashback para loja ID 59 com valor R$ 100,00"

echo -e "${GREEN}=== EXEMPLOS BÁSICOS CONCLUÍDOS ===${NC}\n"

# Exemplos mais complexos
echo -e "${BLUE}=== EXEMPLOS AVANÇADOS ===${NC}\n"

# Função para testar diferentes valores de cashback
test_cashback_values() {
    echo -e "${YELLOW}Testando diferentes valores de cashback:${NC}"
    
    values=(50 100 250 500 1000)
    store_id=59
    
    for value in "${values[@]}"; do
        echo -e "${BLUE}Valor: R$ ${value}.00${NC}"
        
        data="{\"store_id\": $store_id, \"amount\": $value.00}"
        response=$(curl -s -w "HTTP_CODE:%{http_code}" \
            -X POST "${BASE_URL}/cashback/calculate" \
            -H "X-API-Key: ${API_KEY}" \
            -H "Content-Type: application/json" \
            -d "$data")
        
        http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
        body=$(echo "$response" | sed 's/HTTP_CODE:[0-9]*$//')
        
        if [ "$http_code" = "200" ]; then
            if command -v jq &> /dev/null; then
                total_cashback=$(echo "$body" | jq -r '.data.cashback_calculation.total_cashback')
                client_receives=$(echo "$body" | jq -r '.data.cashback_calculation.client_receives')
                echo -e "  ${GREEN}✓ Cashback total: R$ $total_cashback | Cliente recebe: R$ $client_receives${NC}"
            else
                echo -e "  ${GREEN}✓ Sucesso${NC}"
            fi
        else
            echo -e "  ${RED}✗ Erro (HTTP $http_code)${NC}"
        fi
    done
    echo
}

# Função para testar todas as lojas
test_all_stores() {
    echo -e "${YELLOW}Testando cashback para todas as lojas:${NC}"
    
    # Obter lista de lojas
    stores_response=$(curl -s \
        -X GET "${BASE_URL}/stores" \
        -H "X-API-Key: ${API_KEY}" \
        -H "Content-Type: application/json")
    
    if command -v jq &> /dev/null; then
        # Usar jq para processar JSON
        echo "$stores_response" | jq -r '.data[] | "\(.id) \(.trade_name) \(.status)"' | while read -r id name status; do
            if [ "$status" = "aprovado" ]; then
                echo -e "${BLUE}Loja: $name (ID: $id)${NC}"
                
                data="{\"store_id\": $id, \"amount\": 100.00}"
                cashback_response=$(curl -s -w "HTTP_CODE:%{http_code}" \
                    -X POST "${BASE_URL}/cashback/calculate" \
                    -H "X-API-Key: ${API_KEY}" \
                    -H "Content-Type: application/json" \
                    -d "$data")
                
                http_code=$(echo "$cashback_response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
                
                if [ "$http_code" = "200" ]; then
                    body=$(echo "$cashback_response" | sed 's/HTTP_CODE:[0-9]*$//')
                    percentage=$(echo "$body" | jq -r '.data.store_cashback_percentage')
                    total_cashback=$(echo "$body" | jq -r '.data.cashback_calculation.total_cashback')
                    echo -e "  ${GREEN}✓ $percentage% de cashback = R$ $total_cashback${NC}"
                else
                    echo -e "  ${RED}✗ Erro no cálculo${NC}"
                fi
            else
                echo -e "${YELLOW}Loja: $name (ID: $id) - Status: $status (não testada)${NC}"
            fi
        done
    else
        echo "jq não disponível. Instale com: sudo apt-get install jq (Ubuntu) ou brew install jq (macOS)"
    fi
    echo
}

# Executar testes avançados
test_cashback_values
test_all_stores

# Exemplo de monitoramento contínuo
monitor_api() {
    echo -e "${YELLOW}Monitorando API por 30 segundos (pressione Ctrl+C para parar):${NC}"
    
    for i in {1..6}; do
        timestamp=$(date '+%H:%M:%S')
        echo -e "${BLUE}[$timestamp] Verificando saúde da API...${NC}"
        
        response=$(curl -s -w "HTTP_CODE:%{http_code}" \
            -X GET "${BASE_URL}/auth/health" \
            -H "Content-Type: application/json")
        
        http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
        
        if [ "$http_code" = "200" ]; then
            echo -e "${GREEN}✓ API está funcionando${NC}"
        else
            echo -e "${RED}✗ API com problemas (HTTP $http_code)${NC}"
        fi
        
        if [ $i -lt 6 ]; then
            sleep 5
        fi
    done
    echo
}

# Exemplo de script de backup/export
export_data() {
    echo -e "${YELLOW}Exportando dados da API:${NC}"
    
    timestamp=$(date '+%Y%m%d_%H%M%S')
    export_dir="klubecash_export_$timestamp"
    mkdir -p "$export_dir"
    
    # Exportar usuários
    echo "Exportando usuários..."
    curl -s -X GET "${BASE_URL}/users" \
        -H "X-API-Key: ${API_KEY}" \
        -H "Content-Type: application/json" > "${export_dir}/users.json"
    
    # Exportar lojas
    echo "Exportando lojas..."
    curl -s -X GET "${BASE_URL}/stores" \
        -H "X-API-Key: ${API_KEY}" \
        -H "Content-Type: application/json" > "${export_dir}/stores.json"
    
    # Criar relatório
    echo "Criando relatório..."
    cat > "${export_dir}/report.txt" << EOF
KlubeCash API Export Report
Timestamp: $(date)
API Key: ${API_KEY:0:10}...
Base URL: $BASE_URL

Files created:
- users.json: Lista de usuários
- stores.json: Lista de lojas

EOF
    
    echo -e "${GREEN}✓ Dados exportados para: $export_dir${NC}"
    ls -la "$export_dir"
    echo
}

# Menu interativo
show_menu() {
    echo -e "${BLUE}=== MENU INTERATIVO ===${NC}"
    echo "1. Testar valores de cashback"
    echo "2. Testar todas as lojas"
    echo "3. Monitorar API"
    echo "4. Exportar dados"
    echo "5. Teste customizado"
    echo "6. Sair"
    echo
}

interactive_mode() {
    while true; do
        show_menu
        read -p "Escolha uma opção (1-6): " option
        
        case $option in
            1)
                test_cashback_values
                ;;
            2)
                test_all_stores
                ;;
            3)
                monitor_api
                ;;
            4)
                export_data
                ;;
            5)
                echo "Digite o endpoint (ex: /users):"
                read -r endpoint
                echo "Digite o método (GET/POST/PUT/DELETE):"
                read -r method
                
                if [ "$method" = "POST" ] || [ "$method" = "PUT" ]; then
                    echo "Digite o JSON de dados (opcional):"
                    read -r data
                    make_request "$method" "$endpoint" "$data" "Teste customizado"
                else
                    make_request "$method" "$endpoint" "" "Teste customizado"
                fi
                ;;
            6)
                echo -e "${GREEN}Até mais!${NC}"
                exit 0
                ;;
            *)
                echo -e "${RED}Opção inválida!${NC}"
                ;;
        esac
        
        echo -e "Pressione Enter para continuar..."
        read -r
        clear
    done
}

# Verificar se deve entrar em modo interativo
if [ "$1" = "--interactive" ] || [ "$1" = "-i" ]; then
    clear
    interactive_mode
fi

echo -e "${GREEN}=== SCRIPT CONCLUÍDO ===${NC}"
echo -e "Dicas:"
echo -e "- Execute com ${YELLOW}--interactive${NC} para modo interativo"
echo -e "- Modifique a variável ${YELLOW}API_KEY${NC} no início do script"
echo -e "- Instale ${YELLOW}jq${NC} para melhor formatação JSON"
echo -e "- Use ${YELLOW}./curl-examples.sh -i${NC} para o menu interativo"