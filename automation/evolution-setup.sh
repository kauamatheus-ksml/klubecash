#!/bin/bash

# Script de Instalação do Evolution API para Klube Cash
# Este script automatiza a instalação completa do Evolution API via Docker

echo "🚀 Instalando Evolution API para Klube Cash..."
echo ""

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado. Instalando Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    echo "✅ Docker instalado com sucesso"
else
    echo "✅ Docker já está instalado: $(docker --version)"
fi

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo "📦 Instalando Docker Compose..."
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    echo "✅ Docker Compose instalado com sucesso"
else
    echo "✅ Docker Compose já está instalado: $(docker-compose --version)"
fi

# Criar diretório para o Evolution API
EVOLUTION_DIR="$HOME/evolution-api"
mkdir -p $EVOLUTION_DIR
cd $EVOLUTION_DIR

echo ""
echo "📁 Criando configuração do Evolution API em: $EVOLUTION_DIR"

# Criar arquivo docker-compose.yml
cat > docker-compose.yml <<'EOF'
version: '3.8'

services:
  evolution-api:
    container_name: evolution-api-klubecash
    image: atendai/evolution-api:latest
    restart: always
    ports:
      - "8080:8080"
    environment:
      # Configurações básicas
      - SERVER_URL=http://localhost:8080
      - AUTHENTICATION_API_KEY=klubecash-2024-secret-key-change-this
      
      # Configurações de instância
      - DEL_INSTANCE=false
      - DEL_TEMP_INSTANCES=true
      
      # Logs
      - LOG_LEVEL=ERROR
      - LOG_COLOR=true
      
      # Banco de dados (opcional - para persistência)
      - DATABASE_ENABLED=true
      - DATABASE_CONNECTION_URI=mongodb://mongodb:27017/evolution
      - DATABASE_CONNECTION_DB_PREFIX_NAME=evolution
      
      # Redis (opcional - para cache)
      - REDIS_ENABLED=false
      
      # Webhook
      - WEBHOOK_GLOBAL_ENABLED=true
      - WEBHOOK_GLOBAL_URL=https://klubecash.com/api/webhook/whatsapp
      - WEBHOOK_GLOBAL_WEBHOOK_BY_EVENTS=true
      
      # QR Code
      - QRCODE_LIMIT=30
      
      # Configurações de mensagem
      - MESSAGES_HISTORY_LIMIT=1000
      
    volumes:
      - evolution_data:/evolution/instances
      - evolution_store:/evolution/store
    
    networks:
      - evolution-network
    
    depends_on:
      - mongodb

  mongodb:
    container_name: mongodb-evolution
    image: mongo:6.0
    restart: always
    ports:
      - "27017:27017"
    environment:
      - MONGO_INITDB_ROOT_USERNAME=root
      - MONGO_INITDB_ROOT_PASSWORD=strongpassword
    volumes:
      - mongodb_data:/data/db
    networks:
      - evolution-network

volumes:
  evolution_data:
  evolution_store:
  mongodb_data:

networks:
  evolution-network:
    driver: bridge
EOF

echo "✅ Arquivo docker-compose.yml criado"

# Criar arquivo .env para Evolution
cat > .env <<'EOF'
# Configurações do Evolution API
SERVER_URL=http://localhost:8080
AUTHENTICATION_API_KEY=klubecash-2024-secret-key-change-this
DATABASE_CONNECTION_URI=mongodb://mongodb:27017/evolution
LOG_LEVEL=ERROR
EOF

echo "✅ Arquivo .env criado"

# Iniciar containers
echo ""
echo "🐳 Iniciando containers Docker..."
docker-compose up -d

echo ""
echo "⏳ Aguardando Evolution API inicializar (30 segundos)..."
sleep 30

# Verificar se está rodando
if docker ps | grep -q "evolution-api-klubecash"; then
    echo "✅ Evolution API está rodando!"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📱 EVOLUTION API INSTALADO COM SUCESSO!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "🌐 URL da API: http://localhost:8080"
    echo "🔑 API Key: klubecash-2024-secret-key-change-this"
    echo ""
    echo "⚠️  IMPORTANTE: Troque a API Key em produção!"
    echo ""
    echo "📝 Próximos passos:"
    echo "1. Criar uma instância WhatsApp"
    echo "2. Escanear o QR Code"
    echo "3. Configurar o script Python"
    echo ""
    echo "🔧 Comandos úteis:"
    echo "   Ver logs: docker-compose logs -f"
    echo "   Parar: docker-compose stop"
    echo "   Reiniciar: docker-compose restart"
    echo ""
else
    echo "❌ Erro ao iniciar Evolution API"
    echo "Execute: docker-compose logs"
    exit 1
fi