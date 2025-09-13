/**
 * KlubeCash API - Exemplo de Integração em JavaScript
 * 
 * Este exemplo pode ser usado tanto no navegador quanto no Node.js
 * para integrar com a KlubeCash API
 */

class KlubeCashAPI {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseUrl = 'https://klubecash.com/api-external/v1';
    }
    
    /**
     * Fazer requisição para a API
     */
    async makeRequest(endpoint, method = 'GET', data = null) {
        const url = this.baseUrl + endpoint;
        
        const options = {
            method: method,
            headers: {
                'X-API-Key': this.apiKey,
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-JS-Client/1.0'
            }
        };
        
        // Adicionar body se necessário
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const responseData = await response.json();
            
            if (!response.ok) {
                throw new Error(`Erro API (HTTP ${response.status}): ${responseData.message || 'Erro desconhecido'}`);
            }
            
            return responseData;
            
        } catch (error) {
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Erro de rede: Verifique sua conexão ou se a URL está correta');
            }
            throw error;
        }
    }
    
    /**
     * Obter informações da API
     */
    async getApiInfo() {
        return await this.makeRequest('/auth/info');
    }
    
    /**
     * Verificar saúde da API
     */
    async checkHealth() {
        return await this.makeRequest('/auth/health');
    }
    
    /**
     * Listar usuários
     */
    async getUsers() {
        return await this.makeRequest('/users');
    }
    
    /**
     * Listar lojas
     */
    async getStores() {
        return await this.makeRequest('/stores');
    }
    
    /**
     * Calcular cashback
     */
    async calculateCashback(storeId, amount) {
        const data = {
            store_id: parseInt(storeId),
            amount: parseFloat(amount)
        };
        
        return await this.makeRequest('/cashback/calculate', 'POST', data);
    }
}

/**
 * Exemplo básico de uso
 */
async function exemploBasico() {
    console.log('=== EXEMPLO DE INTEGRAÇÃO KLUBECASH API ===\n');
    
    try {
        // IMPORTANTE: Substitua pela sua API Key real
        const apiKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';
        const api = new KlubeCashAPI(apiKey);
        
        // 1. Verificar informações da API
        console.log('1. Informações da API:');
        const info = await api.getApiInfo();
        console.log(`   Nome: ${info.data.api_name}`);
        console.log(`   Versão: ${info.data.version}`);
        console.log(`   Requer API Key: ${info.data.requires_api_key ? 'Sim' : 'Não'}\n`);
        
        // 2. Verificar saúde da API
        console.log('2. Status da API:');
        const health = await api.checkHealth();
        console.log(`   Status: ${health.data.status}`);
        console.log(`   Database: ${health.data.database}\n`);
        
        // 3. Listar usuários
        console.log('3. Últimos usuários:');
        const users = await api.getUsers();
        if (users.data && users.data.length > 0) {
            users.data.slice(0, 3).forEach(user => {
                console.log(`   - ID: ${user.id} | Nome: ${user.name} | Email: ${user.email}`);
            });
            console.log(`   Total de usuários retornados: ${users.data.length}\n`);
        } else {
            console.log('   Nenhum usuário encontrado.\n');
        }
        
        // 4. Listar lojas
        console.log('4. Lojas disponíveis:');
        const stores = await api.getStores();
        if (stores.data && stores.data.length > 0) {
            stores.data.forEach(store => {
                console.log(`   - ID: ${store.id} | Nome: ${store.trade_name} | Status: ${store.status}`);
            });
            console.log(`   Total de lojas retornadas: ${stores.data.length}\n`);
            
            // 5. Calcular cashback (usando primeira loja)
            const firstStore = stores.data[0];
            console.log('5. Calculando cashback:');
            console.log(`   Loja: ${firstStore.trade_name} (ID: ${firstStore.id})`);
            console.log('   Valor da compra: R$ 100,00');
            
            const cashback = await api.calculateCashback(firstStore.id, 100.00);
            
            if (cashback.data) {
                const calc = cashback.data.cashback_calculation;
                console.log(`   Porcentagem da loja: ${cashback.data.store_cashback_percentage}%`);
                console.log(`   Cashback total: R$ ${calc.total_cashback.toFixed(2)}`);
                console.log(`   Cliente recebe: R$ ${calc.client_receives.toFixed(2)}`);
                console.log(`   Admin recebe: R$ ${calc.admin_receives.toFixed(2)}`);
                console.log(`   Loja recebe: R$ ${calc.store_receives.toFixed(2)}`);
            }
        } else {
            console.log('   Nenhuma loja encontrada.\n');
        }
        
        console.log('\n=== INTEGRAÇÃO CONCLUÍDA COM SUCESSO ===');
        
    } catch (error) {
        console.error('ERRO:', error.message);
        
        // Dicas para resolução de problemas
        console.log('\nDicas para resolução:');
        console.log('- Verifique se sua API Key está correta');
        console.log('- Confirme se você tem acesso à internet');
        console.log('- Verifique se a API está funcionando em: https://klubecash.com/api-external/v1/auth/info');
        console.log('- Entre em contato com o suporte se o problema persistir');
    }
}

/**
 * Classe avançada para gerenciar transações
 */
class KlubeCashTransactionManager extends KlubeCashAPI {
    
    /**
     * Processar uma compra completa com cashback
     */
    async processTransaction(storeId, amount, customerEmail) {
        try {
            // 1. Verificar se a loja existe e está ativa
            const stores = await this.getStores();
            const targetStore = stores.data.find(store => 
                store.id == storeId && store.status === 'aprovado'
            );
            
            if (!targetStore) {
                throw new Error(`Loja não encontrada ou não aprovada (ID: ${storeId})`);
            }
            
            // 2. Calcular cashback
            const cashbackData = await this.calculateCashback(storeId, amount);
            
            // 3. Simular salvamento da transação
            const transaction = {
                id: 'tx_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                store_id: storeId,
                store_name: targetStore.trade_name,
                customer_email: customerEmail,
                purchase_amount: amount,
                cashback_data: cashbackData.data,
                created_at: new Date().toISOString(),
                status: 'processed'
            };
            
            return {
                success: true,
                message: 'Transação processada com sucesso',
                transaction: transaction
            };
            
        } catch (error) {
            return {
                success: false,
                message: `Erro ao processar transação: ${error.message}`
            };
        }
    }
    
    /**
     * Obter resumo mensal de cashback
     */
    async getMonthlyCashbackSummary() {
        try {
            const stores = await this.getStores();
            const summary = {
                month: new Date().toISOString().substr(0, 7), // YYYY-MM
                total_stores: stores.data.length,
                active_stores: 0,
                estimated_cashback: 0
            };
            
            // Calcular resumo
            for (const store of stores.data) {
                if (store.status === 'aprovado') {
                    summary.active_stores++;
                    
                    try {
                        // Simular cálculo de cashback médio
                        const avgPurchase = 100; // R$ 100 valor médio simulado
                        const cashback = await this.calculateCashback(store.id, avgPurchase);
                        if (cashback.data && cashback.data.cashback_calculation) {
                            summary.estimated_cashback += cashback.data.cashback_calculation.total_cashback;
                        }
                    } catch (error) {
                        console.warn(`Erro ao calcular cashback para loja ${store.id}:`, error.message);
                    }
                }
            }
            
            return summary;
            
        } catch (error) {
            throw new Error(`Erro ao gerar resumo mensal: ${error.message}`);
        }
    }
    
    /**
     * Monitorar transações em tempo real (simulado)
     */
    async startTransactionMonitor(callback) {
        console.log('Iniciando monitoramento de transações...');
        
        // Simular monitoramento com verificações periódicas
        const monitor = setInterval(async () => {
            try {
                // Simular nova transação
                const stores = await this.getStores();
                if (stores.data && stores.data.length > 0) {
                    const randomStore = stores.data[Math.floor(Math.random() * stores.data.length)];
                    const randomAmount = Math.floor(Math.random() * 500) + 50; // R$ 50-550
                    
                    const result = await this.processTransaction(
                        randomStore.id,
                        randomAmount,
                        `cliente${Date.now()}@email.com`
                    );
                    
                    if (callback) {
                        callback(result);
                    }
                }
            } catch (error) {
                console.error('Erro no monitoramento:', error.message);
            }
        }, 10000); // A cada 10 segundos
        
        return monitor;
    }
}

/**
 * Exemplo avançado de uso
 */
async function exemploAvancado() {
    console.log('\n\n=== EXEMPLO AVANÇADO: GERENCIADOR DE TRANSAÇÕES ===');
    
    try {
        const apiKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';
        const transactionManager = new KlubeCashTransactionManager(apiKey);
        
        // Processar uma transação
        const result = await transactionManager.processTransaction(59, 250.00, 'cliente@email.com');
        
        if (result.success) {
            console.log('Transação processada:');
            const tx = result.transaction;
            console.log(`  ID: ${tx.id}`);
            console.log(`  Loja: ${tx.store_name}`);
            console.log(`  Cliente: ${tx.customer_email}`);
            console.log(`  Valor: R$ ${tx.purchase_amount.toFixed(2)}`);
            console.log(`  Cashback Cliente: R$ ${tx.cashback_data.cashback_calculation.client_receives.toFixed(2)}`);
        } else {
            console.log(`Erro: ${result.message}`);
        }
        
        // Obter resumo mensal
        console.log('\nResumo mensal:');
        const summary = await transactionManager.getMonthlyCashbackSummary();
        console.log(`  Mês: ${summary.month}`);
        console.log(`  Total de lojas: ${summary.total_stores}`);
        console.log(`  Lojas ativas: ${summary.active_stores}`);
        console.log(`  Cashback estimado: R$ ${summary.estimated_cashback.toFixed(2)}`);
        
        // Exemplo de monitoramento (descomente para testar)
        /*
        console.log('\nIniciando monitoramento...');
        const monitor = await transactionManager.startTransactionMonitor((transaction) => {
            if (transaction.success) {
                console.log(`📊 Nova transação: ${transaction.transaction.store_name} - R$ ${transaction.transaction.purchase_amount}`);
            }
        });
        
        // Parar monitoramento após 30 segundos
        setTimeout(() => {
            clearInterval(monitor);
            console.log('Monitoramento encerrado.');
        }, 30000);
        */
        
    } catch (error) {
        console.error('ERRO AVANÇADO:', error.message);
    }
}

/**
 * Exemplo para uso em React/Vue.js
 */
class KlubeCashReactHook {
    constructor(apiKey) {
        this.api = new KlubeCashAPI(apiKey);
    }
    
    /**
     * Hook personalizado para React (exemplo de estrutura)
     */
    useKlubeCashStores() {
        // Este é um exemplo de como estruturar um hook para React
        // Você adaptaria para usar useState, useEffect, etc.
        
        return {
            stores: [],
            loading: false,
            error: null,
            fetchStores: async () => {
                try {
                    const response = await this.api.getStores();
                    return response.data;
                } catch (error) {
                    throw error;
                }
            },
            calculateCashback: async (storeId, amount) => {
                try {
                    const response = await this.api.calculateCashback(storeId, amount);
                    return response.data;
                } catch (error) {
                    throw error;
                }
            }
        };
    }
}

/**
 * Exemplo de uso em Node.js com Express
 */
function exemploNodeExpress() {
    // Este exemplo mostra como criar uma API intermediária usando Express
    
    const express = require('express');
    const app = express();
    
    app.use(express.json());
    
    const apiKey = 'sua_api_key_aqui';
    const klubeCash = new KlubeCashAPI(apiKey);
    
    // Endpoint para listar lojas
    app.get('/api/stores', async (req, res) => {
        try {
            const stores = await klubeCash.getStores();
            res.json(stores);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    });
    
    // Endpoint para calcular cashback
    app.post('/api/cashback/calculate', async (req, res) => {
        try {
            const { store_id, amount } = req.body;
            const cashback = await klubeCash.calculateCashback(store_id, amount);
            res.json(cashback);
        } catch (error) {
            res.status(500).json({ error: error.message });
        }
    });
    
    const port = process.env.PORT || 3000;
    app.listen(port, () => {
        console.log(`API intermediária rodando na porta ${port}`);
    });
}

// Executar exemplos
if (typeof window === 'undefined') {
    // Node.js
    exemploBasico().then(() => {
        return exemploAvancado();
    });
} else {
    // Browser
    console.log('KlubeCash API Client carregado. Use as classes KlubeCashAPI ou KlubeCashTransactionManager.');
    console.log('Exemplo: const api = new KlubeCashAPI("sua_api_key");');
}

// Exportar para uso em outros módulos (Node.js/ES6)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        KlubeCashAPI,
        KlubeCashTransactionManager,
        KlubeCashReactHook
    };
}

// Exportar para ES6 modules
if (typeof exports !== 'undefined') {
    exports.KlubeCashAPI = KlubeCashAPI;
    exports.KlubeCashTransactionManager = KlubeCashTransactionManager;
    exports.KlubeCashReactHook = KlubeCashReactHook;
}