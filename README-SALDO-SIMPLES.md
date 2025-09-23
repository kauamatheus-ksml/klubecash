# Klube Cash - Workflow Simples para Consulta de Saldo

## 🎯 **Abordagem Ultra Simples**

Workflow N8N minimalista com apenas **8 nodes** e **lógica direta** para consulta de saldo. Foca no que funciona, sem complexidades desnecessárias.

## ✨ **Características**

- ✅ **Apenas 8 nodes** (ao invés de 12+)
- ✅ **Suporta múltiplos webhooks** (Evolution API, WAHA, direto)
- ✅ **Lógica JavaScript simples**
- ✅ **Sem validações complexas**
- ✅ **Uma única query MySQL por função**
- ✅ **Resposta direta** sem processamentos excessivos

## 🚀 **Como Importar**

1. Acesse: `https://n8n.klubecash.com`
2. Import: `n8n-workflow-saldo-simples.json`
3. **IMPORTANTE**: Verifique se está **ATIVO** (toggle verde)
4. Configure credencial MySQL se necessário

⚠️ **O workflow DEVE estar ATIVO** para o webhook funcionar!

## 📊 **Fluxo Simplificado**

```
Webhook → Processar → É Saldo? → Buscar Usuário → Buscar Saldos → Gerar Resposta → Enviar → Resposta
```

### **URLs do Webhook:**
```
https://n8n.klubecash.com/webhook/saldo-simples
```

## 🔧 **Nodes Explicados**

### **1. Webhook**
- Recebe qualquer tipo de webhook
- Path: `/saldo-simples`

### **2. Processar (JavaScript)**
```javascript
// Detecta automaticamente o formato:
// - Evolution API: data.key.remoteJid
// - WAHA: body.payload.from
// - Direto: phone

// Verifica se é consulta saldo:
// - Contém "saldo" ou "extrato"
// - É opção "1"
```

### **3. É Saldo? (IF)**
- Se TRUE: vai para busca
- Se FALSE: ignora mensagem

### **4. Buscar Usuário (MySQL)**
```sql
SELECT * FROM usuarios WHERE telefone = '{{ telefone }}' LIMIT 1
```

### **5. Buscar Saldos (MySQL)**
```sql
SELECT * FROM cashback_saldos WHERE user_id = '{{ usuario.id }}'
```

### **6. Gerar Resposta (JavaScript)**
```javascript
// Três cenários:
// 1. Usuário não encontrado: "❌ Usuário não encontrado"
// 2. Saldo zero: "👋 Olá Nome! 💰 Saldo: R$ 0,00"
// 3. Com saldo: "👋 Olá Nome! 💰 Saldo Total: R$ 123,45"
```

### **7. Enviar (HTTP Request)**
```javascript
POST https://evolutionapi.klubecash.com/message/sendText/KluebCash
Headers: apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2
Body: {"number": "telefone", "text": "resposta"}
```

### **8. Resposta (Webhook Response)**
- Retorna "OK" para confirmar processamento

## 💬 **Exemplos de Resposta**

### **Usuário com Saldo:**
```
👋 Olá João Silva!
💰 Saldo Total: R$ 125,50
```

### **Usuário sem Saldo:**
```
👋 Olá Maria Santos!
💰 Saldo: R$ 0,00
```

### **Usuário não Cadastrado:**
```
❌ Usuário não encontrado para 11987654321
```

## 🔧 **Configuração**

### **MySQL**
- Usar credencial existente do N8N
- Tabelas: `usuarios`, `cashback_saldos`

### **Evolution API**
- URL: `https://evolutionapi.klubecash.com/message/sendText/KluebCash`
- API Key: `HONejkqQLlxZoeYiaQxmUczVRTdqscw2`

### **Webhook na Evolution API**
```bash
curl -X POST https://evolutionapi.klubecash.com/webhook/set/KluebCash \
  -H "Content-Type: application/json" \
  -H "apikey: HONejkqQLlxZoeYiaQxmUczVRTdqscw2" \
  -d '{
    "url": "https://n8n.klubecash.com/webhook/saldo-simples",
    "enabled": true,
    "events": ["MESSAGES_UPSERT"]
  }'
```

## 🎯 **Comandos Reconhecidos**

- `saldo`
- `extrato`
- `1` (opção menu)
- Qualquer mensagem contendo essas palavras

## ✅ **Vantagens da Versão Simples**

### **Performance**
- ✅ Menos nodes = execução mais rápida
- ✅ Menos validações = menos pontos de falha
- ✅ JavaScript direto = processamento eficiente

### **Manutenibilidade**
- ✅ Código fácil de entender
- ✅ Poucos pontos de configuração
- ✅ Debug simplificado

### **Robustez**
- ✅ Suporta múltiplos formatos de webhook
- ✅ Fallbacks automáticos
- ✅ Menos dependências

### **Flexibilidade**
- ✅ Funciona com Evolution API ou WAHA
- ✅ Pode receber webhooks diretos
- ✅ Fácil de adaptar

## 🔄 **Teste Manual**

### **Via Webhook Direto:**
```bash
curl -X POST https://n8n.klubecash.com/webhook/saldo-simples \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "5534991191534",
    "message": "saldo"
  }'
```

### **Via Evolution API:**
Envie uma mensagem "saldo" para o WhatsApp conectado.

## 🚨 **Troubleshooting**

### **Webhook não funciona**
- Verifique URL: `/webhook/saldo-simples`
- Teste com curl manual

### **MySQL falha**
- Configure credencial no N8N
- Teste conexão nas configurações

### **Evolution API erro 404**
- Verifique API Key
- Teste endpoint com curl

### **Não processa mensagem**
- Verifique se contém palavra "saldo"
- Debug node "Processar"

## 📈 **Diferenças das Outras Versões**

| Aspecto | Versão Simples | Versões Complexas |
|---------|----------------|-------------------|
| **Nodes** | 8 | 12+ |
| **Validações** | Básicas | Extensivas |
| **Mensagens** | Diretas | Personalizadas |
| **Configuração** | Mínima | Complexa |
| **Performance** | Rápida | Mais lenta |
| **Debug** | Fácil | Complexo |

## 🎯 **Ideal Para:**

- ✅ **Testes rápidos**
- ✅ **Ambientes de desenvolvimento**
- ✅ **Quando precisa funcionar rapidamente**
- ✅ **Debugging de problemas**
- ✅ **Prova de conceito**

---

**Klube Cash - Versão Simples** | **8 Nodes = Máxima Eficiência** | **Setembro 2025**