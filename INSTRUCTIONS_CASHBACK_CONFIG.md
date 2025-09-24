# 🎯 Sistema de Configuração de Cashback Personalizado

## ✅ O que foi implementado:

### 1. **Banco de Dados**
- Novos campos na tabela `lojas`:
  - `porcentagem_cliente` - Percentual para o cliente (ex: 8.00)
  - `porcentagem_admin` - Percentual para a plataforma (ex: 2.00)
  - `cashback_ativo` - Ativa/desativa cashback por loja
  - `data_config_cashback` - Timestamp da última configuração

### 2. **Interface Administrativa**
- **Página Principal**: `/admin/cashback-config`
- **Link adicionado**: Na página `/admin/configuracoes` dentro da seção "Configurações de Cashback"
- **Funcionalidades**:
  - Configuração individual por loja
  - Configuração em lote (todas, só MVP, só normais)
  - Visualização de estatísticas por loja
  - Identificação visual de lojas MVP vs normais

### 3. **Lógica de Cálculo Atualizada**
- `TransactionController::registerTransactionFixed()` agora usa configurações específicas por loja
- Validações automáticas de percentuais
- Logs detalhados para debug
- Fallback para valores padrão se não configurado

## 🚀 Como Usar:

### Passo 1: Aplicar estrutura no banco
```bash
# Acesse via navegador:
http://seudominio.com/database/apply_cashback_config.php
```

### Passo 2: Configurar percentuais
1. Acesse `/admin/configuracoes` como administrador
2. Na seção "Configurações de Cashback", clique em **"Configuração por Loja"**
3. Configure percentuais específicos para cada loja

### Passo 3: Exemplos de configuração

**Configuração em Lote:**
- Cliente: 6%, Plataforma: 4% → Aplicar a "Só MVP"
- Cliente: 4%, Plataforma: 6% → Aplicar a "Só Normais"

**Configuração Individual:**
- Farmácia MVP: Cliente 12%, Plataforma 0%
- Restaurante Premium: Cliente 3%, Plataforma 7% 
- Loja Promocional: Cliente 15%, Plataforma 5%
- Loja Inativa: Cashback = DESABILITADO

## 📊 Exemplos Práticos:

### Cenário 1: Farmácia MVP
```
Configuração: Cliente 10% | Plataforma 0%
Compra: R$ 100,00
Resultado: Cliente ganha R$ 10,00 | Admin ganha R$ 0,00
```

### Cenário 2: Restaurante Normal
```
Configuração: Cliente 5% | Plataforma 5%
Compra: R$ 100,00
Resultado: Cliente ganha R$ 5,00 | Admin ganha R$ 5,00
```

### Cenário 3: Loja Promocional
```
Configuração: Cliente 20% | Plataforma 5%
Compra: R$ 100,00
Resultado: Cliente ganha R$ 20,00 | Admin ganha R$ 5,00
```

## 🔧 Interface Visual:

### Na página de configurações (`/admin/configuracoes`):
```
┌─────────────────────────────────────────────────────────┐
│ Configurações de Cashback            [Configuração por Loja] │
├─────────────────────────────────────────────────────────┤
│ ℹ️ Configurações Globais: Estas são as configurações    │
│    padrão do sistema. Para configurar percentuais       │
│    específicos por loja, use o botão acima.             │
└─────────────────────────────────────────────────────────┘
```

### Na página de configuração por loja (`/admin/cashback-config`):
```
┌─────────────────────────────────────────────────────────┐
│ Configuração de Cashback por Loja                       │
├─────────────────────────────────────────────────────────┤
│ Configuração em Lote:                                   │
│ Cliente: [5.00] % | Plataforma: [5.00] %               │
│ ☑ Todas  ☐ Só MVP  ☐ Só Normais    [Aplicar em Lote]   │
├─────────────────────────────────────────────────────────┤
│ Loja                │Tipo│Status│Cliente│Plataforma│Ações│
│ Farmácia Central    │MVP │Ativo │ 10.00 │   0.00   │[Salvar]│
│ Restaurante Gourmet │Normal│Ativo│  5.00 │   5.00   │[Salvar]│
│ Loja Promocional    │Normal│Ativo│ 15.00 │   5.00   │[Salvar]│
└─────────────────────────────────────────────────────────┘
```

## 🎯 Benefícios:

1. **Flexibilidade Total**: Cada loja pode ter percentuais únicos
2. **Promoções Específicas**: Criar ofertas especiais por loja
3. **Controle MVP**: Lojas MVP podem ter 0% para a plataforma
4. **Gestão Centralizada**: Interface única para configurar tudo
5. **Estatísticas Integradas**: Volume de vendas por loja
6. **Configuração em Lote**: Aplicar mudanças rapidamente

O sistema agora oferece controle completo sobre a distribuição de cashback por loja! 🎉