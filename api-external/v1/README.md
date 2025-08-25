# KlubeCash External API v1

API REST para integração externa com o sistema KlubeCash.

## 📋 Índice

- [Instalação](#instalação)
- [Autenticação](#autenticação)
- [Endpoints Disponíveis](#endpoints-disponíveis)
- [Rate Limiting](#rate-limiting)
- [Códigos de Resposta](#códigos-de-resposta)
- [Exemplos de Uso](#exemplos-de-uso)
- [SDK PHP](#sdk-php)
- [Testes](#testes)

## 🚀 Instalação

### 1. Configurar Banco de Dados

Execute as migrations para criar as tabelas necessárias:

```bash
mysql -u username -p database_name < database/migrations/001_create_api_keys_table.sql
```

### 2. Configurar Servidor Web

Configure seu servidor web (Apache/Nginx) para apontar para `/api-external/v1/` com URL rewriting habilitado.

### 3. Gerar API Key

```php
require_once 'models/ApiKey.php';

$apiKey = new ApiKey();
$result = $apiKey->generateApiKey(
    'Nome do Parceiro',
    'email@parceiro.com',
    ['users.read', 'stores.read', 'transactions.create'], // Permissões
    [
        'rate_limit_per_minute' => 100,
        'rate_limit_per_hour' => 2000
    ]
);

echo "API Key: " . $result['api_key'];
```

## 🔐 Autenticação

### API Key

Inclua sua API Key no header de todas as requisições:

```http
X-API-Key: kc_sua_api_key_aqui
```

### Exemplo cURL

```bash
curl -X GET "https://seu-dominio.com/api-external/v1/users" \
  -H "X-API-Key: kc_sua_api_key_aqui" \
  -H "Content-Type: application/json"
```

## 📡 Endpoints Disponíveis

### Auth
- `GET /auth/info` - Informações da API
- `GET /auth/health` - Status de saúde

### Usuários
- `GET /users` - Listar usuários
- `POST /users` - Criar usuário
- `GET /users/{id}` - Obter usuário
- `PUT /users/{id}` - Atualizar usuário
- `DELETE /users/{id}` - Deletar usuário
- `GET /users/{id}/balance` - Saldo do usuário
- `GET /users/{id}/transactions` - Transações do usuário

### Lojas
- `GET /stores` - Listar lojas
- `POST /stores` - Criar loja
- `GET /stores/{id}` - Obter loja
- `PUT /stores/{id}` - Atualizar loja
- `DELETE /stores/{id}` - Deletar loja
- `GET /stores/{id}/stats` - Estatísticas da loja
- `GET /stores/{id}/transactions` - Transações da loja
- `GET /stores/{id}/cashback-rules` - Regras de cashback

### Transações
- `GET /transactions` - Listar transações
- `POST /transactions` - Criar transação
- `GET /transactions/{id}` - Obter transação
- `PUT /transactions/{id}/status` - Atualizar status
- `GET /transactions/stats` - Estatísticas

### Cashback
- `POST /cashback/calculate` - Calcular cashback
- `GET /cashback/user/{user_id}` - Cashback do usuário

## ⏱️ Rate Limiting

- **Padrão**: 60 requests/minuto, 1000 requests/hora
- **Customizável** por parceiro
- Headers de resposta:
  - `X-RateLimit-Limit-Minute`
  - `X-RateLimit-Remaining-Minute` 
  - `X-RateLimit-Reset-Minute`

## 📊 Códigos de Resposta

| Código | Significado |
|--------|------------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Erro de validação |
| 401 | Não autorizado (API Key inválida) |
| 403 | Sem permissão |
| 404 | Recurso não encontrado |
| 422 | Erro de validação de dados |
| 429 | Rate limit excedido |
| 500 | Erro interno do servidor |

## 💡 Exemplos de Uso

### Criar um usuário

```bash
curl -X POST "https://seu-dominio.com/api-external/v1/users" \
  -H "X-API-Key: kc_sua_api_key_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@email.com", 
    "password": "senha123",
    "type": "cliente"
  }'
```

### Criar uma transação

```bash
curl -X POST "https://seu-dominio.com/api-external/v1/transactions" \
  -H "X-API-Key: kc_sua_api_key_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 123,
    "store_id": 456,
    "total_amount": 100.50,
    "status": "pendente"
  }'
```

### Calcular cashback

```bash
curl -X POST "https://seu-dominio.com/api-external/v1/cashback/calculate" \
  -H "X-API-Key: kc_sua_api_key_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "store_id": 456,
    "amount": 100.00
  }'
```

## 🔧 SDK PHP

Veja o arquivo `sdk/KlubeCashSDK.php` para uma implementação completa do SDK em PHP.

### Exemplo de uso do SDK:

```php
require_once 'sdk/KlubeCashSDK.php';

$sdk = new KlubeCashSDK('kc_sua_api_key_aqui', 'https://seu-dominio.com');

// Criar usuário
$user = $sdk->users()->create([
    'name' => 'João Silva',
    'email' => 'joao@email.com',
    'password' => 'senha123'
]);

// Listar lojas
$stores = $sdk->stores()->list(['status' => 'aprovado']);

// Criar transação
$transaction = $sdk->transactions()->create([
    'user_id' => $user['data']['id'],
    'store_id' => 1,
    'total_amount' => 150.00
]);

// Calcular cashback
$cashback = $sdk->cashback()->calculate([
    'store_id' => 1,
    'amount' => 150.00
]);
```

## 🧪 Testes

Execute os testes automatizados:

```bash
php tests/ApiTest.php
```

## 📚 Documentação Completa

- **OpenAPI/Swagger**: `/docs/openapi.yaml`
- **Postman Collection**: `/docs/KlubeCash-API.postman_collection.json`

## 🐛 Logs e Debugging

Os logs da API são salvos em:
- PHP error logs
- `api_logs` table no banco de dados

## 🔒 Segurança

- API Keys com hash SHA-256
- Rate limiting por endpoint
- Validação rigorosa de dados
- Headers de segurança configurados
- Logs de todas as ações

## 📞 Suporte

Para dúvidas ou problemas:
- **Email**: suporte@klubecash.com
- **Documentação**: [Link para docs online]

---

**Versão da API**: v1.0.0  
**Última atualização**: $(date '+%Y-%m-%d')