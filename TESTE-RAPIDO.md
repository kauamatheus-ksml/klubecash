# Teste Rápido - Workflow Simples

## 🚀 **Teste em 3 Passos**

### **1. Importar Workflow**
```
Arquivo: n8n-workflow-saldo-simples.json
URL Webhook: https://n8n.klubecash.com/webhook/saldo-simples
```

**🚨 IMPORTANTE**: Verifique se o workflow está **ATIVO** (toggle verde no N8N)

### **2. Teste Manual (sem WhatsApp)**
```bash
curl -X POST https://n8n.klubecash.com/webhook/saldo-simples \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "5534991191534",
    "message": "saldo"
  }'
```

### **3. Verificar Logs**
- Vá em "Executions" no N8N
- Veja se processou com sucesso
- Verifique resposta no WhatsApp

## 📱 **Teste com WhatsApp Real**

### **Configurar Webhook:**
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

### **Enviar Mensagem:**
- Envie "saldo" para o número do WhatsApp
- Aguarde resposta automática

## ✅ **Resposta Esperada**

Se tudo funcionar, você receberá:
```
👋 Olá [Nome]!
💰 Saldo Total: R$ [valor]
```

## 🔧 **Se Não Funcionar**

### **Erro MySQL:**
- Configure credencial MySQL no N8N
- ID da credencial: `63tY4U5gYm6JbGcd`

### **Erro Evolution API:**
- Verifique se instância `KluebCash` está ativa
- Teste API Key: `HONejkqQLlxZoeYiaQxmUczVRTdqscw2`

### **Webhook não chegou:**
- Verifique URL do webhook
- Confirme se está ativo no workflow

---

**Teste Completo em Menos de 5 Minutos!**