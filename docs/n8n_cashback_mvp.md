# Automacao n8n para cashback MVP liberado

## 1. Objetivo
Garantir que sempre que uma transacao de cashback for criada automaticamente como *aprovada* para uma loja MVP, o cliente receba uma mensagem imediata via API Evolution do WhatsApp informando que o saldo ja esta disponivel.

## 2. Dados que a automacao precisa
- Tabela `transacoes_cashback`: status, valores, codigo da transacao, `loja_id`, `usuario_id`, `data_transacao`.
- Tabela `usuarios`: nome e telefone do cliente (`telefone`).
- Tabela `lojas` + `usuarios` (dono da loja): campo `mvp` igual a `sim` indica lojas que liberam cashback instantaneo.
- Tabela `cashback_notificacoes`: usada para registrar tentativas e evitar envios duplicados.

### Consulta base para buscar pendencias
```sql
SELECT
    t.id,
    t.codigo_transacao,
    t.valor_total,
    t.valor_cliente,
    t.data_transacao,
    u.nome        AS cliente_nome,
    u.telefone    AS cliente_telefone,
    l.nome_fantasia AS loja_nome
FROM transacoes_cashback t
JOIN usuarios u       ON u.id = t.usuario_id
JOIN lojas l          ON l.id = t.loja_id
JOIN usuarios loja_u  ON loja_u.id = l.usuario_id
WHERE loja_u.mvp = 'sim'
  AND t.status = 'aprovado'
  AND u.telefone IS NOT NULL AND u.telefone <> ''
  AND NOT EXISTS (
        SELECT 1
        FROM cashback_notificacoes cn
        WHERE cn.transacao_id = t.id
          AND cn.status = 'enviada'
    )
ORDER BY t.data_transacao DESC
LIMIT 50;
```

## 3. Fluxo sugerido no n8n (mais simples e robusto)
1. **Schedule Trigger**: rodar a cada 1 ou 2 minutos.
2. **MySQL - Buscar transacoes**: executar a consulta acima. Configure com as credenciais do banco em producao.
3. **IF Telefone valido**: validar se o campo `cliente_telefone` continua preenchido apos limpar caracteres nao numericos.
4. **Function Item - Preparar mensagem**:
   - Normalizar telefone para o formato internacional `55DDDNXXXXXXXX`.
   - Montar texto padrao (exemplo abaixo) e formatar valores com duas casas decimais.
5. **HTTP Request - Evolution API**:
   - Metodo `POST` para `https://SEU_HOST_EVOLUTION/message/sendText/SESSAO`.
   - Headers: `apikey: <token>`.
   - Body (JSON):
     ```json
     {
       "number": "{{$json["whatsapp_number"]}}",
       "text": "{{$json["mensagem"]}}",
       "forceSend": true
     }
     ```
   - Ativar "Continue On Fail" para que erros nao parem o fluxo e possam ser tratados.
6. **IF Envio OK?**: checar se o nodo HTTP retornou `success` (ou status HTTP 2xx). Se a API falhar, enviar item para o ramo de erro.
7. **MySQL - Registrar sucesso** (ramo verdadeiro): inserir em `cashback_notificacoes` com `status = 'enviada'` e uma observacao curta.
8. **MySQL - Registrar erro** (ramo falso): registrar tentativa com `status = 'erro'` + mensagem retornada. Opcionalmente inserir/atualizar em `cashback_notification_retries` para controle de repeticoes.

## 4. Template de mensagem sugerido
```
Ola {{primeiro_nome}},

Seu cashback de R$ {{valor_cashback}} gerado na {{loja_nome}} ja foi liberado!

Codigo: {{codigo_transacao}}
Data: {{data_transacao}}
Saldo disponivel agora em klubecash.com
```
Campos dinamicos (definidos no Function Item):
- `primeiro_nome`: primeiro nome do cliente.
- `valor_cashback`: formatado com `number_format` / `toLocaleString` para `pt-BR`.
- `loja_nome`, `codigo_transacao`, `data_transacao` (formatar para `dd/mm/aaaa HH:MM`).

## 5. Tratamento de duplicidade
- O `NOT EXISTS` na consulta ja impede reenviar para transacoes com registro `enviada`.
- Mesmo assim, o nodo de sucesso deve inserir um registro:
  ```sql
  INSERT INTO cashback_notificacoes (transacao_id, status, observacao)
  VALUES (:id, 'enviada', CONCAT('WhatsApp enviado via n8n em ', NOW()));
  ```
- No ramo de erro, guardar a mensagem para diagnostico. Exemplo:
  ```sql
  INSERT INTO cashback_notificacoes (transacao_id, status, observacao)
  VALUES (:id, 'erro', :motivo);
  ```
- Use um `Function` antes do insert para limitar `observacao` a 255 caracteres.

## 6. Observacoes importantes
- Garanta que o timezone do n8n esteja igual ao do banco (America/Sao_Paulo) para logs coerentes.
- Crie uma credencial dedicada do MySQL com permissao apenas de SELECT e INSERT nas tabelas usadas.
- Antes de ativar o Schedule, rode o workflow manualmente com 1 item de teste.
- A API Evolution exige que o numero esteja no formato internacional sem `+`. Ajuste no Function Item.
- Para validacao inicial, ative um `Split In Batches` (tamanho 1) entre a consulta e o Function para processar transacoes uma a uma com confirmacao manual.
- Considere adicionar um alerta (por e-mail ou Slack) quando houver mais de 3 erros consecutivos registradaos em `cashback_notificacoes`.

## 7. Alternativas viaveis (caso queira evoluir)
1. **Webhook direto do PHP**: chamar um endpoint HTTP do n8n logo apos a confirmacao de transacoes MVP no `TransactionController`, reduzindo o tempo de entrega para segundos.
2. **Fila dedicada**: popular `cashback_notification_retries` e deixar o n8n processar a fila, evitando consultar tabelas principais periodicamente.
3. **Disparo via gatilho SQL**: criar uma tabela `eventos_automaticos` preenchida pelo backend; o n8n apenas consome essa fila, mantendo a logica de negocio no PHP.

Seguindo o fluxo acima, voce tera um envio automatizado e rastreavel sem precisar alterar o core do sistema, aproveitando as tabelas de auditoria que ja existem.
