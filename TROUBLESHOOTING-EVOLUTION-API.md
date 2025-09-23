# Troubleshooting - Evolution API no N8N

## 🚨 Erro: "The resource you are requesting could not be found"

### 🔍 **Diagnóstico**
Este erro ocorre quando o N8N não consegue conectar com a Evolution API. Possíveis causas:

1. **URL incorreta**
2. **Instância inativa**
3. **API Key inválida**
4. **Endpoint não existe**

### ✅ **Soluções**

#### 1. **Verificar Status da Instância**
```bash
curl -X GET https://evolutionapi.klubecash.com/instance/fetchInstances \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
```

**Resposta esperada:**
```json
[
  {
    "instance": {
      "instanceName": "KluebCash",
      "status": "open"
    }
  }
]
```

#### 2. **Verificar Conectividade da Instância**
```bash
curl -X GET https://evolutionapi.klubecash.com/instance/connectionState/KluebCash \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
```

**Resposta esperada:**
```json
{
  "instance": {
    "instanceName": "KluebCash",
    "state": "open"
  }
}
```

#### 3. **Testar Envio de Mensagem**
```bash
curl -X POST https://evolutionapi.klubecash.com/message/sendText/KluebCash \
  -H "Content-Type: application/json" \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2" \
  -d '{
    "number": "5534991191534",
    "text": "Teste de conexão Evolution API"
  }'
```

#### 4. **URLs Alternativas para Testar**
Se a URL principal falhar, teste estas variações:

```bash
# Opção 1: Sem /KluebCash no final
https://evolutionapi.klubecash.com/message/sendText

# Opção 2: Com instance no path
https://evolutionapi.klubecash.com/instance/KluebCash/message/sendText

# Opção 3: Versão da API
https://evolutionapi.klubecash.com/v1/message/sendText/KluebCash
```

### 🔧 **Correções no Workflow**

#### **Versão FINAL Corrigida:**
Use: `n8n-workflow-klube-saldo-evolution-final.json`

**Melhorias aplicadas:**
- ✅ `followRedirect: true` nos HTTP Requests
- ✅ Timeout aumentado para 15 segundos
- ✅ Validação segura com operadores Elvis (`?.`)

#### **Node HTTP Request - Configuração Correta:**
```json
{
  "url": "https://evolutionapi.klubecash.com/message/sendText/KluebCash",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "apikey": "HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
  },
  "body": {
    "number": "{{ phoneNumber }}",
    "text": "{{ message }}"
  },
  "timeout": 15000,
  "followRedirect": true
}
```

## 🔍 **Outros Problemas Comuns**

### **1. Instância Desconectada**
```bash
# Reconnect da instância
curl -X PUT https://evolutionapi.klubecash.com/instance/restart/KluebCash \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2"
```

### **2. API Key Inválida**
Confirme a API Key nos `DadosNecessários.txt`:
```
API Key Evolution = HONejkqQLlxZoeYiaQxmUczVRTdqscw2
```

### **3. Problema de Webhook**
```bash
# Configurar webhook corretamente
curl -X POST https://evolutionapi.klubecash.com/webhook/set/KluebCash \
  -H "Content-Type: application/json" \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2" \
  -d '{
    "url": "https://n8n.klubecash.com/webhook/saldo-evolution",
    "enabled": true,
    "events": ["MESSAGES_UPSERT"]
  }'
```

### **4. Formato do Número**
Certifique-se que o número está no formato correto:
```javascript
// Correto
"number": "5534991191534"

// Incorreto
"number": "+55 (34) 99119-1534"
"number": "5534991191534@s.whatsapp.net"
```

## 📊 **Logs de Debug**

### **No N8N:**
1. Vá em **Executions**
2. Clique na execução com erro
3. Veja o erro no node específico
4. Verifique a resposta HTTP

### **Códigos de Erro Comuns:**
- **404**: Endpoint não encontrado
- **401**: API Key inválida
- **500**: Erro interno da Evolution API
- **timeout**: Instância demorou para responder

## 🔄 **Passos de Recuperação**

### **1. Restart Completo:**
```bash
# 1. Restart da instância
curl -X PUT https://evolutionapi.klubecash.com/instance/restart/KluebCash \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2"

# 2. Aguardar 30 segundos

# 3. Verificar status
curl -X GET https://evolutionapi.klubecash.com/instance/connectionState/KluebCash \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2"

# 4. Reconfigurar webhook
curl -X POST https://evolutionapi.klubecash.com/webhook/set/KluebCash \
  -H "Content-Type: application/json" \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2" \
  -d '{
    "url": "https://n8n.klubecash.com/webhook/saldo-evolution",
    "enabled": true,
    "events": ["MESSAGES_UPSERT"]
  }'
```

### **2. Teste no N8N:**
1. Importe: `n8n-workflow-klube-saldo-evolution-final.json`
2. Configure credencial MySQL (se necessário)
3. Execute teste manual
4. Verifique logs de execução

## 🆘 **Contatos de Suporte**

Se os problemas persistirem:

1. **Verificar documentação Evolution API**
2. **Logs do servidor Evolution API**
3. **Status do servidor**: `https://evolutionapi.klubecash.com/health`

---

**Troubleshooting Guide** | **Klube Cash** | **Setembro 2025**