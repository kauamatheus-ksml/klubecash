# Configuração Evolution API + n8n para Klube Cash

## 📋 Pré-requisitos

1. **Evolution API instalada e rodando** ✅ (`https://evolutionapi.klubecash.com`)
2. **n8n instalado e configurado** ✅ (`https://n8n.klubecash.com`)
3. **Instância WhatsApp configurada na Evolution API** ✅ (`KluebCash`)

## 🚀 Configuração da Evolution API

### 1. ✅ Instância WhatsApp já criada

A instância **KluebCash** já está criada e conectada:
- **Nome**: KluebCash
- **ID**: 19A79573077E-4B3D-AB2B-DA0AC8110989
- **Status**: CONECTADO ✅
- **WhatsApp**: 553430301344

### 2. ✅ WhatsApp já conectado

O WhatsApp já está conectado e funcionando conforme logs:
```
┌──────────────────────────────┐
│    CONNECTED TO WHATSAPP     │
└──────────────────────────────┘
wuid: 553430301344
name: KluebCash
```

### 3. Configurar Webhook para n8n

```bash
# Configurar webhook
curl -X POST \
  'https://evolutionapi.klubecash.com/webhook/set/KluebCash' \
  -H 'Content-Type: application/json' \
  -H 'apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2' \
  -d '{
    "url": "https://n8n.klubecash.com/webhook/whatsapp",
    "enabled": true,
    "events": [
      "MESSAGE_RECEIVED",
      "MESSAGE_SENT",
      "CONNECTION_UPDATE"
    ]
  }'
```

## ⚙️ Configuração do n8n

### 1. Importar Workflow

1. Acesse n8n: `https://n8n.klubecash.com`
2. Login: `kaua@ticketsync.com.br` / `Aaku_2004@`
3. Clique em **"Import from file"**
4. Selecione o arquivo: `n8n-workflow-saldo-whatsapp.json`
5. Configure as credenciais necessárias

### 2. Configurar Credenciais

#### Credencial Evolution API
- **Nome**: `evolution-api-auth`
- **Tipo**: `HTTP Header Auth`
- **Header Name**: `apikey`
- **Header Value**: `HONejkqQLlxZoeYiaQxmUczVRTdqscw2`

### 3. Configurar Variáveis do Workflow

No node **"Enviar Resposta Evolution"** e **"Enviar Menu Padrão"**:

```json
{
  "url": "https://evolutionapi.klubecash.com/message/sendText/KluebCash",
  "headers": {
    "apikey": "HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
  }
}
```

## 🔄 Fluxo do Workflow

1. **Webhook WhatsApp** - Recebe mensagens da Evolution API
2. **Filtrar Mensagens** - Ignora mensagens próprias e de grupos
3. **Processar Dados** - Extrai telefone e texto da mensagem
4. **É Consulta Saldo?** - Verifica se contém keywords de saldo
5. **Consultar Saldo API** - Chama API do Klube Cash
6. **Processar Resposta** - Formata resposta do sistema
7. **Enviar Resposta** - Envia via Evolution API

## 📝 Keywords de Saldo Detectadas

- `saldo`
- `extrato`
- `consulta`
- `dinheiro`
- `valor`
- `quanto tenho`

## 🛠️ Testando o Sistema

### 1. Verificar Status da Instância

```bash
curl -X GET \
  'https://evolutionapi.klubecash.com/instance/connectionState/KluebCash' \
  -H 'apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2'
```

### 2. Enviar Mensagem de Teste

```bash
curl -X POST \
  'https://evolutionapi.klubecash.com/message/sendText/KluebCash' \
  -H 'Content-Type: application/json' \
  -H 'apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2' \
  -d '{
    "number": "5534991191534",
    "text": "🧪 Teste do sistema Klube Cash via Evolution API + n8n!"
  }'
```

### 3. Testar Consulta de Saldo

Envie uma mensagem com a palavra "saldo" para o WhatsApp conectado.

## 🔧 Configurações Importantes

### Evolution API (docker-compose.yml)

```yaml
version: '3.8'
services:
  evolution-api:
    image: atendai/evolution-api:latest
    ports:
      - "8080:8080"
    environment:
      - SERVER_PORT=8080
      - SERVER_URL=http://localhost:8080
      - CORS_ORIGIN=*
      - CORS_METHODS=GET,POST,PUT,DELETE
      - CORS_CREDENTIALS=true
      - LOG_LEVEL=ERROR,WARN,DEBUG,INFO,LOG,VERBOSE,DARK,WEBHOOKS
      - DEL_INSTANCE=false
      - PROVIDER_ENABLED=true
      - PROVIDER_HOST=127.0.0.1
      - PROVIDER_PORT=5656
      - PROVIDER_PREFIX=evolution
      - DATABASE_ENABLED=true
      - DATABASE_CONNECTION_URI=postgresql://postgres:password@postgres:5432/evolution
      - RABBITMQ_ENABLED=false
      - CACHE_REDIS_ENABLED=false
      - WEBHOOK_GLOBAL_URL=http://localhost:5678/webhook/whatsapp
      - WEBHOOK_GLOBAL_ENABLED=true
      - WEBHOOK_GLOBAL_WEBHOOK_BY_EVENTS=true
    volumes:
      - ./evolution_instances:/evolution/instances
      - ./evolution_store:/evolution/store
    depends_on:
      - postgres

  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: evolution
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: password
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

volumes:
  postgres_data:
```

### n8n (docker-compose.yml)

```yaml
version: '3.8'
services:
  n8n:
    image: n8nio/n8n:latest
    ports:
      - "5678:5678"
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=admin
      - N8N_BASIC_AUTH_PASSWORD=admin123
      - WEBHOOK_URL=http://localhost:5678/
      - GENERIC_TIMEZONE=America/Sao_Paulo
    volumes:
      - n8n_data:/home/node/.n8n

volumes:
  n8n_data:
```

## 🚨 Troubleshooting

### Problema: Webhook não recebe mensagens
```bash
# Verificar webhook configurado
curl -X GET \
  'https://evolutionapi.klubecash.com/webhook/find/KluebCash' \
  -H 'apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2'
```

### Problema: Mensagens não são enviadas
```bash
# Verificar status da conexão
curl -X GET \
  'https://evolutionapi.klubecash.com/instance/connectionState/KluebCash' \
  -H 'apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2'
```

### Problema: API de saldo não responde
- Verificar se o servidor Klube Cash está rodando
- Confirmar que a URL da API está correta: `https://klubecash.com/api/whatsapp-saldo.php`
- Verificar se o secret está correto: `klube-cash-2024`

## 📊 Monitoramento

### Logs Evolution API
```bash
docker logs evolution-api
```

### Logs n8n
- Acesse n8n UI: `http://localhost:5678`
- Vá em **Executions** para ver os logs dos workflows

### Logs Klube Cash API
- Verificar logs no servidor: `/var/log/apache2/error.log`
- Logs específicos da aplicação nos arquivos de log da API

## 🔐 Segurança

1. **Trocar API keys padrão**
2. **Configurar HTTPS em produção**
3. **Limitar acesso por IP**
4. **Configurar rate limiting**
5. **Backup regular das instâncias**

## 📱 Recursos Suportados

- ✅ Consulta de saldo por telefone
- ✅ Mensagens de texto simples
- ✅ Filtros de mensagens próprias
- ✅ Logs detalhados
- ✅ Tratamento de erros
- ✅ Menu padrão para outras mensagens

## 🚀 Próximos Passos

1. **Expandir para outros comandos** (extrato, transferência)
2. **Adicionar suporte a mídia** (imagens, áudios)
3. **Implementar chatbot mais inteligente**
4. **Adicionar autenticação por OTP**
5. **Integrar com sistema de notificações**