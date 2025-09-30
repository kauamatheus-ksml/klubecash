# 📱 Automação N8N - Notificação WhatsApp Cashback

## 🎯 Como Importar e Configurar

### 1️⃣ Importar Workflow no N8N

1. Acesse seu N8N
2. Clique em **"Add workflow"** ou **"+"**
3. Clique nos **3 pontinhos** (menu) → **Import from file**
4. Selecione o arquivo: `n8n-workflow-whatsapp-cashback.json`

### 2️⃣ Configurar Credenciais MySQL

1. Clique no nó **"Buscar Transações Não Notificadas"**
2. Em **Credential to connect with**, clique em **"Create New Credential"**
3. Preencha:
   - **Host**: Seu host MySQL (ex: localhost ou IP do servidor)
   - **Database**: `u383946504_klubecash`
   - **User**: Seu usuário MySQL
   - **Password**: Sua senha MySQL
   - **Port**: `3306` (padrão)
4. Clique em **Save**
5. Repita para o nó **"Registrar Notificação Enviada"** (use a mesma credencial)

### 3️⃣ Configurar Evolution API

✅ **JÁ CONFIGURADO!**

- URL: `https://evolutionapi.klubecash.com/message/sendText/KluebCash`
- API Key: `HONejkqQLlxZoeYiaQxmUczVRTdqscw2`

Não precisa alterar nada!

### 4️⃣ Ativar Workflow

1. Clique em **Save** (salvar workflow)
2. Ative o workflow clicando no **toggle** no canto superior direito
3. Pronto! O workflow vai rodar automaticamente a cada 1 minuto

---

## 🔍 Como Funciona

### Fluxo de Execução

```
┌──────────────────┐
│ A cada 1 minuto  │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────────┐
│ Busca no banco transações    │
│ aprovadas SEM notificação    │
└────────┬─────────────────────┘
         │
         ▼
┌──────────────────┐
│ Tem transações?  │
└────┬────────┬────┘
     │ SIM    │ NÃO → [Fim]
     ▼        │
┌──────────────────┐
│ Para cada uma... │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Envia WhatsApp   │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────┐
│ Registra como "enviada"  │
│ na tabela                │
│ cashback_notificacoes    │
└──────────────────────────┘
```

### Query SQL Utilizada

O workflow busca transações que:
- ✅ Estão com status **"aprovado"**
- ✅ **NÃO** têm registro na tabela `cashback_notificacoes` com status "enviada"
- ✅ Foram criadas nos **últimos 10 minutos**
- ✅ Limita a **10 transações** por execução (evita sobrecarga)

---

## 📱 Mensagem Enviada ao Cliente

```
🎉 *Parabéns [NOME]!*

Seu cashback foi liberado! 💰

📋 *Detalhes:*
• Compra: R$ [VALOR_TOTAL]
• Cashback: R$ [VALOR_CASHBACK]
• Loja: [NOME_LOJA]

✅ Saldo disponível para uso!

Acesse: https://klubecash.com/cliente/dashboard
```

---

## 🛠️ Personalizar Mensagem

1. Clique no nó **"Enviar WhatsApp"**
2. Em **Body Parameters**, edite o campo **text**
3. Variáveis disponíveis:
   - `{{ $json.nome }}` - Nome do cliente
   - `{{ $json.telefone }}` - Telefone (já formatado com 55)
   - `{{ $json.valor_total }}` - Valor da compra
   - `{{ $json.valor_cliente }}` - Valor do cashback
   - `{{ $json.loja_nome }}` - Nome da loja
   - `{{ $json.transacao_id }}` - ID da transação

---

## 🧪 Testar Workflow

### Teste Manual

1. No N8N, clique em **"Execute Workflow"**
2. Veja o resultado em tempo real

### Criar Transação de Teste

Execute no banco de dados:

```sql
-- 1. Cria uma transação aprovada
INSERT INTO transacoes_cashback
(usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, valor_admin, valor_loja, status, data_transacao)
VALUES
(9, 59, 100.00, 10.00, 10.00, 0.00, 0.00, 'aprovado', NOW());

-- 2. Aguarde 1 minuto
-- 3. Verifique se recebeu WhatsApp
-- 4. Confirme no banco:

SELECT * FROM cashback_notificacoes
ORDER BY id DESC LIMIT 1;
```

---

## 🚨 Troubleshooting

### Mensagens não estão sendo enviadas

**1. Verifique se o workflow está ativo**
   - Toggle deve estar verde/ligado

**2. Verifique conexão MySQL**
   - Teste as credenciais
   - Confirme que o host está acessível

**3. Verifique Evolution API**
   - URL está correta?
   - API Key está correta?
   - Instância está conectada?

**4. Verifique logs do N8N**
   - Clique em **"Executions"** no menu lateral
   - Veja os erros detalhados

### Mensagens duplicadas

- Verifique se há múltiplos workflows ativos
- Confirme que a query SQL está funcionando (deve retornar apenas transações sem notificação)

### Telefone inválido

- Certifique-se que o campo `telefone` na tabela `usuarios` tem DDD
- O workflow adiciona automaticamente `55` (código do Brasil)
- Formato esperado do telefone: `38991045205` (será enviado como `5538991045205`)

---

## 📊 Monitoramento

### Ver execuções

1. No N8N, menu lateral → **"Executions"**
2. Veja todas as execuções (sucesso/erro)
3. Clique em uma execução para ver detalhes

### Verificar notificações enviadas

```sql
SELECT
    cn.id,
    cn.transacao_id,
    cn.status,
    cn.data_tentativa,
    u.nome,
    u.telefone
FROM cashback_notificacoes cn
JOIN transacoes_cashback t ON t.id = cn.transacao_id
JOIN usuarios u ON u.id = t.usuario_id
ORDER BY cn.data_tentativa DESC
LIMIT 20;
```

---

## ⚙️ Ajustes Opcionais

### Mudar intervalo de execução

1. Clique no nó **"A cada 1 minuto"**
2. Altere **Minutes Interval**:
   - `1` = a cada 1 minuto
   - `5` = a cada 5 minutos
   - `10` = a cada 10 minutos

### Aumentar limite de transações por execução

1. Clique no nó **"Buscar Transações Não Notificadas"**
2. Na query SQL, altere:
   ```sql
   LIMIT 10  -- Mude para 20, 50, etc
   ```

### Adicionar retry em caso de erro

Adicione um nó **"Error Trigger"** após o envio do WhatsApp para tentar novamente em caso de falha.

---

## 📞 Suporte

Se precisar de ajuda:
1. Verifique os logs no N8N
2. Verifique a tabela `cashback_notificacoes` no banco
3. Teste manualmente a Evolution API com Postman/Insomnia

---

## ✅ Checklist Final

- [ ] Workflow importado no N8N
- [ ] Credenciais MySQL configuradas
- [ ] URL da Evolution API configurada
- [ ] API Key da Evolution configurada
- [ ] Workflow ativado (toggle verde)
- [ ] Teste manual realizado com sucesso
- [ ] Mensagem recebida no WhatsApp

**Pronto! Seu sistema está automatizado! 🚀**