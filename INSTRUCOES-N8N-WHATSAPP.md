# Instruções - N8N WhatsApp Saldo Integration

## Arquivos Criados

1. **`n8n-workflow-CORRIGIDO-FINAL.json`** - Workflow completo e corrigido
2. **`n8n-workflow-TESTE-FINAL.json`** - Workflow simplificado para teste
3. **`INSTRUCOES-N8N-WHATSAPP.md`** - Este arquivo de instruções

## Problema Identificado e Solução

### Problema:
- HTTP Request v4 estava causando erro 401 (Authorization failed)
- Configuração incorreta do body JSON
- Método HTTP mal configurado

### Solução Aplicada:
- Usar **HTTP Request v1** (typeVersion: 1)
- Configuração explícita: `"requestMethod": "POST"`
- Body JSON fixo: `{"phone": "{{ $json.phone }}", "secret": "{{ $json.secret }}"}`
- Headers corretos: `"Content-Type": "application/json"`

## Como Testar

### 1. Teste Básico (RECOMENDADO)
```bash
# Importe o arquivo: n8n-workflow-TESTE-FINAL.json
# Ative o workflow
# Teste com: POST para o webhook URL
```

### 2. Teste Completo
```bash
# Importe o arquivo: n8n-workflow-CORRIGIDO-FINAL.json
# Configure webhook na Evolution API
# Envie mensagem WhatsApp com palavra "saldo"
```

## Configuração Evolution API

No webhook da Evolution API, configure:
```json
{
  "webhook": "https://seu-n8n.com/webhook/whatsapp-saldo",
  "events": ["messages.upsert"]
}
```

## URLs dos Webhooks

- **Teste**: `/webhook/teste-final`
- **Produção**: `/webhook/whatsapp-saldo`

## Verificações de Segurança

✅ Secret correto: `klube-cash-2024`
✅ API endpoint: `https://klubecash.com/api/whatsapp-saldo.php`
✅ Evolution API key: `HONejkqQLlxZoeYiaQxmUczVRTdqscw2`
✅ Instance: `KluebCash`

## Logs de Debug

Os workflows incluem logs detalhados:
- 📥 Input do webhook
- 📊 Resposta da API
- ✅ Sucesso / ❌ Erro
- ⚠️ Mensagens ignoradas

## Próximos Passos

1. Teste o workflow simplificado primeiro
2. Se funcionar, use o workflow completo
3. Configure webhook na Evolution API
4. Monitore os logs para debug