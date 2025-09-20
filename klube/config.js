// config.js
// Configurações do bot WhatsApp Klube Cash

module.exports = {
    // Configurações do servidor
    port: process.env.PORT || 3002,
    
    // Configurações do WhatsApp
    sessionName: 'klube-cash-session',
    
    // API do Klube Cash
    apiBaseUrl: 'https://klubecash.com',
    webhookSecret: 'klube-cash-2024',
    
    // Configurações de timeout
    apiTimeout: 15000,
    messageTimeout: 30000,
    
    // Keywords para saldo
    saldoKeywords: ['saldo', 'extrato', 'cashback', 'consulta', 'dinheiro'],
    
    // Configurações de logs
    enableLogs: true,
    logLevel: 'info',
    
    // Configurações de produção
    production: true,
    
    // URLs das APIs
    apis: {
        saldo: '/api/whatsapp-saldo.php',
        cadastro: '/api/whatsapp-completar-cadastro.php'
    },
    
    // Mensagens do sistema
    messages: {
        welcome: '🏪 *Klube Cash* - Bem-vindo!',
        error: '❌ Erro temporário. Tente novamente em alguns instantes.',
        invalidOption: 'Opção inválida. Digite um número válido.',
        userNotFound: '❌ Usuário não encontrado. Entre em contato com nosso suporte.'
    }
};