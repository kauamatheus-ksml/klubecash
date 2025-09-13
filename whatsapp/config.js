// config.js - VERSÃO CORRIGIDA
module.exports = {
    // Configurações do WhatsApp
    sessionName: 'klubecash-session',

    // CORRIGIR - Usar URLs do projeto real
    apiBaseUrl: 'https://klubecash.com',
    apiTimeout: 30000,
    webhookSecret: 'klube-cash-2024', // Usar mesmo valor do .env

    // APIs endpoints - CORRIGIR CAMINHOS
    apis: {
        saldo: '/api/whatsapp-saldo.php',
        cadastro: '/api/whatsapp-cadastro.php' // Criar este arquivo
    },

    // CORRIGIR - Usar porta do .env
    port: process.env.PORT || 3002,

    // Keywords para consulta de saldo
    saldoKeywords: [
        'saldo',
        'extrato',
        'consulta',
        'dinheiro',
        'valor',
        'quanto tenho'
    ],

    // Mensagens padrão
    messages: {
        welcome: '🏪 *Klube Cash* - Bem-vindo! Digite *1* para consultar saldo ou *2* para cadastro.',
        userNotFound: '❌ Usuário não encontrado. Digite *2* para completar seu cadastro.',
        invalidOption: '❌ Opção inválida. Digite *1* para saldo ou *2* para cadastro.',
        error: '❌ Erro temporário. Tente novamente em alguns minutos.',
        processing: '⏳ Processando sua solicitação...'
    },

    // Logs
    enableLogs: true
};