# Como Ativar o Workflow N8N

## 🚨 **Erro Resolvido**

O erro `"The requested webhook is not registered"` acontece porque o workflow precisa estar **ATIVO**.

## ✅ **Soluções**

### **Opção 1 - Automática (Recomendada)**
O arquivo `n8n-workflow-saldo-simples.json` já foi corrigido com `"active": true`.

**Reimporte o arquivo** e ele deve ativar automaticamente.

### **Opção 2 - Manual no N8N**
1. Abra o workflow no N8N
2. Clique no **toggle no canto superior direito**
3. Aguarde aparecer "Active" em verde

### **Opção 3 - Via API**
```bash
curl -X POST https://n8n.klubecash.com/api/v1/workflows/ID/activate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

## 🔧 **Verificar se Está Ativo**

### **1. Interface N8N:**
- Status: "Active" (verde) no canto superior direito
- URL do webhook aparece no node Webhook

### **2. Via Teste:**
```bash
curl -X POST https://n8n.klubecash.com/webhook/saldo-simples \
  -H "Content-Type: application/json" \
  -d '{"phone": "5534991191534", "message": "saldo"}'
```

**Resposta esperada:** Status 200 (ao invés de 404)

## 📋 **Passos Completos**

### **1. Importar Workflow Corrigido**
- Arquivo: `n8n-workflow-saldo-simples.json` (versão corrigida)
- Deve ativar automaticamente

### **2. Configurar Credenciais**
- MySQL: Configurar se necessário
- Evolution API: Já configurada no workflow

### **3. Testar Webhook**
```bash
curl -X POST https://n8n.klubecash.com/webhook/saldo-simples \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "5534991191534",
    "message": "saldo"
  }'
```

### **4. Configurar na Evolution API**
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

## 🐛 **Se Ainda Não Funcionar**

### **Verificar Logs:**
1. N8N → Executions
2. Procurar execuções recentes
3. Ver erros detalhados

### **Webhook Status:**
```bash
# Verificar se webhook existe
curl -I https://n8n.klubecash.com/webhook/saldo-simples
```

### **Reativar Manualmente:**
1. Desative o workflow (toggle OFF)
2. Aguarde 5 segundos
3. Ative novamente (toggle ON)

## ✅ **Status de Sucesso**

Quando tudo estiver funcionando:
- ✅ Toggle "Active" verde no N8N
- ✅ Teste curl retorna 200
- ✅ Mensagem WhatsApp recebe resposta automática

---

**Problema 404 Resolvido** | **Workflow Ativo** | **Setembro 2025**