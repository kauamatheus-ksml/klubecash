# 🚨 Solução: Erro "Forbidden" na API Saldo

## ❌ **Problema Identificado**
- **Erro**: "Forbidden - perhaps check your credentials?"
- **Local**: Node "API Saldo" no workflow n8n
- **Causa**: Possível problema de autenticação ou configuração

## 🔍 **Possíveis Causas**

### **1. Headers/CORS**
- API pode estar rejeitando requisições do n8n
- Headers de CORS podem estar bloqueando
- User-Agent pode estar sendo filtrado

### **2. Formato da Requisição**
- Content-Type incorreto
- Body mal formatado
- Encoding de caracteres

### **3. Configuração do Servidor**
- WAF (Web Application Firewall) bloqueando
- Limite de rate limiting
- IP do n8n pode estar bloqueado

## 🛠️ **Soluções para Testar**

### **Solução 1: Workflow de Debug** ⭐ **RECOMENDADO**

1. **Importe**: `n8n-workflow-debug.json`
2. **URL de teste**: `https://n8n.klubecash.com/webhook-test/webhook/debug`
3. **Teste**: Envie POST com dados básicos
4. **Verifique logs**: Executions no n8n

### **Solução 2: Teste Manual da API**

```bash
# Execute o script de teste
chmod +x teste-api-saldo.sh
./teste-api-saldo.sh
```

### **Solução 3: Corrigir Headers HTTP**

No workflow original, adicione estes headers:

```json
{
  "headerParameters": {
    "parameters": [
      {
        "name": "Content-Type",
        "value": "application/json"
      },
      {
        "name": "User-Agent",
        "value": "KlubeCash-n8n/1.0"
      },
      {
        "name": "Accept",
        "value": "application/json"
      },
      {
        "name": "Origin",
        "value": "https://n8n.klubecash.com"
      }
    ]
  }
}
```

### **Solução 4: Simplificar Payload**

Troque o `jsonParameters` por:

```json
{
  "contentType": "raw",
  "body": "{\"phone\":\"{{ $json.phone }}\",\"secret\":\"klube-cash-2024\"}"
}
```

## 🧪 **Testes de Diagnóstico**

### **Teste 1: API Direta via curl**
```bash
curl -X POST \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Content-Type: application/json' \
  -H 'User-Agent: n8n-test' \
  -d '{
    "phone": "34991191534",
    "secret": "klube-cash-2024"
  }' \
  -v
```

### **Teste 2: Verificar Headers CORS**
```bash
curl -X OPTIONS \
  'https://klubecash.com/api/whatsapp-saldo.php' \
  -H 'Origin: https://n8n.klubecash.com' \
  -v
```

### **Teste 3: Verificar WAF/Firewall**
```bash
curl -I 'https://klubecash.com/api/whatsapp-saldo.php'
```

## 🔧 **Workflow Corrigido**

### **Versão Simplificada**
Use o `n8n-workflow-simples.json` com estas modificações:

1. **Adicione headers extras**
2. **Use `fullResponse: true`** para ver erro completo
3. **Adicione logs detalhados**

### **Node HTTP Request Corrigido**
```json
{
  "parameters": {
    "url": "https://klubecash.com/api/whatsapp-saldo.php",
    "sendHeaders": true,
    "headerParameters": {
      "parameters": [
        {
          "name": "Content-Type",
          "value": "application/json; charset=utf-8"
        },
        {
          "name": "User-Agent",
          "value": "KlubeCash-Webhook/1.0"
        },
        {
          "name": "Accept",
          "value": "application/json"
        },
        {
          "name": "Cache-Control",
          "value": "no-cache"
        }
      ]
    },
    "sendBody": true,
    "contentType": "json",
    "jsonParameters": "{\n  \"phone\": \"{{ $json.phone }}\",\n  \"secret\": \"klube-cash-2024\"\n}",
    "options": {
      "timeout": 30000,
      "response": {
        "fullResponse": true
      }
    }
  }
}
```

## 📊 **Monitoramento**

### **Logs da API**
- Verificar `/var/log/apache2/error.log`
- Verificar logs do WhatsApp API em `api/whatsapp-saldo.php`

### **Logs do n8n**
- Executions → Ver logs detalhados
- Console do browser → Erros JavaScript

### **Status da Infraestrutura**
- Verificar se HTTPS está funcionando
- Verificar se certificado SSL é válido
- Verificar se não há bloqueios de firewall

## ✅ **Checklist de Verificação**

- [ ] API responde via curl diretamente
- [ ] Headers CORS estão corretos
- [ ] n8n está usando HTTPS
- [ ] Secret está correto (`klube-cash-2024`)
- [ ] Formato JSON está válido
- [ ] Timeout é suficiente (30s)
- [ ] Workflow de debug funciona
- [ ] Logs mostram a requisição chegando na API

## 🎯 **Próximos Passos**

1. **Execute o workflow de debug** primeiro
2. **Analise os logs** no n8n Executions
3. **Teste a API diretamente** com curl
4. **Verifique logs do servidor** se necessário
5. **Ajuste headers** conforme necessário

## 📞 **Se Nada Funcionar**

Pode ser necessário:
- Verificar configurações do WAF/Cloudflare
- Adicionar IP do n8n na whitelist
- Verificar configurações do servidor Apache/Nginx
- Contatar suporte da hospedagem

**Use o workflow de debug para obter informações detalhadas!** 🔍