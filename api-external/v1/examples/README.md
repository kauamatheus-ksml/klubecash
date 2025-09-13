# KlubeCash API - Exemplos de Integração

Este diretório contém exemplos práticos de como integrar com a KlubeCash External API em diferentes linguagens e cenários.

## 📁 Estrutura dos Exemplos

### 1. **php-integration.php**
Exemplo completo de integração em PHP com:
- ✅ Cliente básico da API
- ✅ Gerenciador avançado de transações
- ✅ Tratamento de erros
- ✅ Exemplos práticos de uso
- ✅ Classe para relatórios mensais

**Como usar:**
```bash
php php-integration.php
```

### 2. **javascript-integration.js**
Cliente JavaScript/Node.js com:
- ✅ Classe principal da API
- ✅ Suporte para navegador e Node.js
- ✅ Gerenciador de transações
- ✅ Exemplo para React/Vue.js
- ✅ Monitoramento em tempo real
- ✅ Template para Express.js

**Como usar:**
```bash
# Node.js
node javascript-integration.js

# Browser
<script src="javascript-integration.js"></script>
```

### 3. **curl-examples.sh**
Script shell com exemplos cURL:
- ✅ Todos os endpoints da API
- ✅ Modo interativo
- ✅ Testes automatizados
- ✅ Monitoramento contínuo
- ✅ Exportação de dados

**Como usar:**
```bash
# Executar exemplos básicos
./curl-examples.sh

# Modo interativo
./curl-examples.sh --interactive
```

## 🚀 Início Rápido

### 1. Configure sua API Key

Antes de executar qualquer exemplo, você precisa de uma API Key válida:

```bash
# Substitua nos arquivos:
API_KEY="sua_api_key_aqui"
```

### 2. Teste a conectividade

Verifique se a API está acessível:

```bash
curl https://klubecash.com/api-external/v1/auth/info
```

### 3. Execute os exemplos

Escolha a linguagem de sua preferência e execute o exemplo correspondente.

## 📋 Endpoints Disponíveis

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| GET | `/auth/info` | Informações da API | ❌ Não |
| GET | `/auth/health` | Status de saúde | ❌ Não |
| GET | `/users` | Listar usuários | ✅ Sim |
| GET | `/stores` | Listar lojas | ✅ Sim |
| POST | `/cashback/calculate` | Calcular cashback | ✅ Sim |

## 🔑 Autenticação

Para endpoints protegidos, inclua o header:

```
X-API-Key: sua_api_key_aqui
```

## 📊 Exemplos de Resposta

### Listar Usuários
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": [
    {
      "id": 123,
      "name": "João Silva",
      "email": "joao@email.com",
      "type": "cliente",
      "status": "ativo",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### Calcular Cashback
```json
{
  "success": true,
  "data": {
    "store_id": 59,
    "purchase_amount": 100,
    "store_cashback_percentage": 10,
    "cashback_calculation": {
      "total_cashback": 10,
      "client_receives": 0.5,
      "admin_receives": 0.5,
      "store_receives": 0
    }
  }
}
```

## 🛠️ Cenários de Uso

### E-commerce
```php
// Calcular cashback no checkout
$cashback = $api->calculateCashback($storeId, $purchaseAmount);
$customerCashback = $cashback['data']['cashback_calculation']['client_receives'];
```

### Sistema de Afiliados
```javascript
// Processar comissão de afiliado
const transaction = await manager.processTransaction(storeId, amount, customerEmail);
if (transaction.success) {
    await processAffiliateCommission(transaction.transaction);
}
```

### Dashboard Analytics
```bash
# Exportar dados para análise
./curl-examples.sh --export-data
```

## 🔧 Configuração Avançada

### Rate Limits
A API possui limites de taxa:
- **1000 requests/hora**
- **60 requests/minuto**

### Timeouts Recomendados
- **Conexão**: 10 segundos
- **Leitura**: 30 segundos

### Headers Recomendados
```
Content-Type: application/json
X-API-Key: sua_api_key_aqui
User-Agent: SeuApp/1.0
```

## ⚡ Performance

### Boas Práticas
1. **Cache responses** quando apropriado
2. **Implemente retry logic** para falhas de rede
3. **Use connection pooling** para múltiplas requests
4. **Monitore rate limits**

### Exemplo de Cache (PHP)
```php
class CachedKlubeCashAPI extends KlubeCashAPI {
    private $cache = [];
    private $cacheTtl = 300; // 5 minutos
    
    public function getStores() {
        $cacheKey = 'stores';
        if (isset($this->cache[$cacheKey]) && 
            time() - $this->cache[$cacheKey]['time'] < $this->cacheTtl) {
            return $this->cache[$cacheKey]['data'];
        }
        
        $data = parent::getStores();
        $this->cache[$cacheKey] = [
            'data' => $data,
            'time' => time()
        ];
        
        return $data;
    }
}
```

## 🚨 Tratamento de Erros

### Códigos HTTP Comuns
- **200**: Sucesso
- **400**: Dados inválidos
- **401**: API Key inválida
- **404**: Endpoint não encontrado
- **500**: Erro interno do servidor

### Exemplo de Tratamento
```javascript
try {
    const result = await api.calculateCashback(storeId, amount);
    return result.data;
} catch (error) {
    if (error.message.includes('401')) {
        throw new Error('API Key inválida ou expirada');
    } else if (error.message.includes('404')) {
        throw new Error('Loja não encontrada');
    } else {
        throw new Error('Erro interno da API');
    }
}
```

## 🧪 Testes

### Teste Manual
Use o testador interativo em:
- `docs/api-tester.html`
- `curl-examples.sh --interactive`

### Teste Automatizado
```bash
# PHP
php php-integration.php

# JavaScript
npm test

# Shell
./curl-examples.sh
```

## 📞 Suporte

### Problemas Comuns

1. **API Key inválida**
   - Verifique se a chave está correta
   - Confirme se não expirou

2. **Timeout na conexão**
   - Verifique sua conexão
   - Aumente o timeout

3. **Rate limit atingido**
   - Implemente delays entre requests
   - Use cache quando apropriado

### Contato
- **Email**: suporte@klubecash.com
- **Documentação**: https://klubecash.com/api-external/v1/docs
- **Status da API**: https://klubecash.com/api-external/v1/auth/health

---

## 📝 Licença

Os exemplos neste diretório são fornecidos como referência para integração com a KlubeCash API. Adapte conforme necessário para seu projeto.

**Última atualização**: Janeiro 2024