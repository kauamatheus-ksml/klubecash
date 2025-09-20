// bot.js
// Bot WhatsApp Klube Cash - Versão 2.1 Corrigida
// Sistema completo com menu dinâmico e cadastro

const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const axios = require('axios');
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const fs = require('fs-extra');
const path = require('path');
const CONFIG = require('./config');

// === INICIALIZAÇÃO ===
const app = express();
const client = new Client({
    authStrategy: new LocalAuth({ clientId: CONFIG.sessionName }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu'
        ]
    }
});

// === MIDDLEWARES ===
app.use(helmet());
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// === VARIÁVEIS GLOBAIS ===
let isReady = false;
let isInitializing = false;

// === LOGS ===
function log(message, level = 'info') {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}`;
    
    console.log(logMessage);
    
    if (CONFIG.enableLogs) {
        const logDir = path.join(__dirname, 'logs');
        fs.ensureDirSync(logDir);
        
        const logFile = path.join(logDir, `bot-${new Date().toISOString().split('T')[0]}.log`);
        fs.appendFileSync(logFile, logMessage + '\n');
    }
}

// === INICIALIZAÇÃO DO BOT ===
async function initializeBot() {
    if (isInitializing) {
        log('Bot já está sendo inicializado...', 'warn');
        return;
    }
    
    isInitializing = true;
    log('🚀 Inicializando WhatsApp Bot Klube Cash v2.1...');
    
    try {
        await client.initialize();
        log('✅ Bot inicializado com sucesso');
    } catch (error) {
        log(`❌ Erro na inicialização: ${error.message}`, 'error');
        isInitializing = false;
        
        // Tentar reinicializar após 30 segundos
        setTimeout(() => {
            log('🔄 Tentando reinicializar...');
            initializeBot();
        }, 30000);
    }
}

// === EVENTOS DO WHATSAPP ===
client.on('qr', (qr) => {
    log('📱 QR Code gerado. Escaneie com seu WhatsApp:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    isReady = true;
    isInitializing = false;
    log('✅ WhatsApp conectado e pronto!');
    log(`📱 Bot ativo para: ${client.info.wid.user}`);
});

client.on('authenticated', () => {
    log('🔐 WhatsApp autenticado com sucesso');
});

client.on('auth_failure', (msg) => {
    log(`❌ Falha na autenticação: ${msg}`, 'error');
    isReady = false;
    isInitializing = false;
});

client.on('disconnected', (reason) => {
    log(`📱 WhatsApp desconectado: ${reason}`, 'warn');
    isReady = false;
    isInitializing = false;
    
    // Tentar reconectar após 10 segundos
    setTimeout(() => {
        log('🔄 Tentando reconectar...');
        initializeBot();
    }, 10000);
});

// === PROCESSAMENTO DE MENSAGENS ===
client.on('message', async (message) => {
    try {
        // Ignorar mensagens próprias e de grupos
        if (message.fromMe || message.from.includes('@g.us')) {
            return;
        }
        
        const phoneNumber = message.from;
        const messageText = message.body.trim();
        
        log(`📥 Mensagem recebida de ${phoneNumber}: ${messageText}`);
        
        // Processar mensagem
        await processarMensagemRecebida(phoneNumber, messageText);
        
    } catch (error) {
        log(`❌ Erro ao processar mensagem: ${error.message}`, 'error');
    }
});

// === FUNÇÕES AUXILIARES ===

/**
 * ENVIAR MENSAGEM - FUNÇÃO CORRIGIDA
 */
async function enviarMensagem(phoneNumber, message) {
    try {
        if (!isReady) {
            throw new Error('Bot não está pronto');
        }
        
        const formattedPhone = phoneNumber.includes('@c.us') ? phoneNumber : `${phoneNumber}@c.us`;
        await client.sendMessage(formattedPhone, message);
        
        return { success: true };
        
    } catch (error) {
        log(`❌ Erro ao enviar mensagem: ${error.message}`, 'error');
        throw error;
    }
}

/**
 * DETERMINAR TIPO DE CLIENTE - FUNÇÃO LOCAL
 */
function determinarTipoCliente(responseData) {
    // Se a API já retorna client_type, usar ele
    if (responseData.client_type) {
        return responseData.client_type;
    }
    
    // Fallback: determinar pelo conteúdo da mensagem
    if (responseData.user_found && responseData.message) {
        const message = responseData.message.toLowerCase();
        
        // Se encontrou usuário mas precisa de mais informações, é visitante
        if (message.includes('complete') || message.includes('cadastro')) {
            return 'visitante';
        }
        
        // Se tem saldo completo, provavelmente é cliente completo
        if (message.includes('saldo total') && message.includes('suas carteiras')) {
            return 'completo';
        }
    }
    
    // Se tem usuário mas não conseguiu determinar, assumir visitante para mostrar opção de cadastro
    if (responseData.user_found) {
        return 'visitante';
    }
    
    return 'unknown';
}

// === FUNÇÕES PRINCIPAIS ===

/**
 * PROCESSAR MENSAGENS RECEBIDAS - VERSÃO CORRIGIDA
 */
async function processarMensagemRecebida(phoneNumber, message) {
    try {
        log(`📥 Processando mensagem de ${phoneNumber}: ${message}`);
        
        // Verificar se está em processo de cadastro
        const emCadastro = await verificarProcessoCadastro(phoneNumber);
        
        if (emCadastro) {
            log('📝 Usuário em processo de cadastro - processando mensagem');
            await processarMensagemCadastro(phoneNumber, message);
            return;
        }
        
        // Verificar se é uma opção do menu
        const opcao = message.trim();
        if (['1', '2'].includes(opcao)) {
            log(`📋 Processando opção do menu: ${opcao}`);
            await processarOpcaoMenu(phoneNumber, opcao);
            return;
        }
        
        // Verificar se é keyword de saldo
        const isKeywordSaldo = CONFIG.saldoKeywords.some(keyword => 
            message.toLowerCase().includes(keyword.toLowerCase())
        );
        
        if (isKeywordSaldo) {
            log('💰 Keyword de saldo detectada - consultando saldo');
            await consultarSaldo(phoneNumber);
            return;
        }
        
        // Menu padrão para outras mensagens
        log('🏠 Exibindo menu principal');
        await exibirMenuDinamico(phoneNumber);
        
    } catch (error) {
        log(`❌ Erro ao processar mensagem: ${error.message}`, 'error');
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * MENU DINÂMICO - VERSÃO CORRIGIDA
 */
async function exibirMenuDinamico(phoneNumber) {
    try {
        const cleanPhone = phoneNumber.replace('@c.us', '');
        log(`🔍 Verificando tipo de cliente para: ${cleanPhone}`);
        
        const response = await axios.post(`${CONFIG.apiBaseUrl}${CONFIG.apis.saldo}`, {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, { timeout: CONFIG.apiTimeout });
        
        log(`📋 Resposta da API: ${JSON.stringify(response.data)}`);
        
        const clientType = determinarTipoCliente(response.data);
        const userName = response.data.user_name || '';
        
        log(`✅ Tipo de cliente determinado: ${clientType} - Usuário: ${userName}`);
        
        let menuMessage;
        
        if (clientType === 'visitante') {
            log('🔄 Exibindo menu para VISITANTE');
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Digite o número da opção desejada:

1️⃣ Consultar Saldo
2️⃣ Completar Cadastro`;
        } else if (clientType === 'completo') {
            log('🔄 Exibindo menu para CLIENTE COMPLETO');
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Digite o número da opção desejada:

1️⃣ Consultar Saldo
2️⃣ Atualizar Cadastro`;
        } else {
            log('🔄 Exibindo menu PADRÃO (tipo unknown)');
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Digite o número da opção desejada:

1️⃣ Consultar Saldo`;
        }
        
        await enviarMensagem(phoneNumber, menuMessage);
        log(`✅ Menu enviado para ${cleanPhone} - Tipo: ${clientType}`);
        
    } catch (error) {
        log(`❌ Erro no menu dinâmico: ${error.message}`, 'error');
        
        // Menu de fallback em caso de erro
        const fallbackMenu = `🏪 *Klube Cash* - Bem-vindo!

Digite o número da opção desejada:

1️⃣ Consultar Saldo`;
        
        try {
            await enviarMensagem(phoneNumber, fallbackMenu);
            log('⚠️ Menu de fallback enviado devido ao erro');
        } catch (fallbackError) {
            log(`❌ Erro ao enviar menu de fallback: ${fallbackError.message}`, 'error');
        }
    }
}

/**
 * PROCESSAR OPÇÕES DO MENU - VERSÃO CORRIGIDA
 */
async function processarOpcaoMenu(phoneNumber, opcao) {
    log(`📋 Processando opção ${opcao} para ${phoneNumber}`);
    
    try {
        switch (opcao) {
            case '1':
                log('💰 Opção 1: Consultar Saldo');
                await consultarSaldo(phoneNumber);
                break;
                
            case '2':
                log('📝 Opção 2: Cadastro/Atualização');
                
                // Verificar tipo de cliente antes de processar opção 2
                const cleanPhone = phoneNumber.replace('@c.us', '');
                const response = await axios.post(`${CONFIG.apiBaseUrl}${CONFIG.apis.saldo}`, {
                    phone: cleanPhone,
                    secret: CONFIG.webhookSecret
                }, { timeout: CONFIG.apiTimeout });
                
                if (response.data && response.data.user_found) {
                    const clientType = determinarTipoCliente(response.data);
                    log(`🔍 Tipo de cliente para opção 2: ${clientType}`);
                    
                    if (clientType === 'visitante' || clientType === 'completo') {
                        await iniciarCadastroOuAtualizacao(phoneNumber);
                    } else {
                        await enviarMensagem(phoneNumber, '❌ Opção não disponível para seu perfil. Digite *1* para consultar saldo.');
                    }
                } else {
                    await enviarMensagem(phoneNumber, CONFIG.messages.userNotFound);
                }
                break;
                
            default:
                log('❌ Opção inválida');
                await enviarMensagem(phoneNumber, CONFIG.messages.invalidOption);
                break;
        }
    } catch (error) {
        log(`❌ Erro ao processar opção do menu: ${error.message}`, 'error');
        await enviarMensagem(phoneNumber, CONFIG.messages.error);
    }
}

/**
 * CONSULTAR SALDO
 */
async function consultarSaldo(phoneNumber) {
    try {
        const cleanPhone = phoneNumber.replace('@c.us', '');
        log(`💰 Consultando saldo para: ${cleanPhone}`);
        
        const response = await axios.post(`${CONFIG.apiBaseUrl}${CONFIG.apis.saldo}`, {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, { timeout: CONFIG.apiTimeout });
        
        if (response.data && response.data.success && response.data.message) {
            await enviarMensagem(phoneNumber, response.data.message);
            log(`✅ Saldo enviado para ${cleanPhone}`);
        } else {
            await enviarMensagem(phoneNumber, response.data?.message || 'Erro ao consultar saldo.');
            log(`⚠️ Erro na consulta de saldo: ${JSON.stringify(response.data)}`);
        }
        
    } catch (error) {
        log(`❌ Erro ao consultar saldo: ${error.message}`, 'error');
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * INICIAR CADASTRO OU ATUALIZAÇÃO
 */
async function iniciarCadastroOuAtualizacao(phoneNumber) {
    try {
        const cleanPhone = phoneNumber.replace('@c.us', '');
        log(`📝 Iniciando cadastro/atualização para: ${cleanPhone}`);
        
        const response = await axios.post(`${CONFIG.apiBaseUrl}${CONFIG.apis.cadastro}`, {
            phone: cleanPhone,
            action: 'iniciar',
            secret: CONFIG.webhookSecret
        }, { timeout: CONFIG.apiTimeout });
        
        if (response.data && response.data.success) {
            await enviarMensagem(phoneNumber, response.data.message);
            log(`✅ Processo de cadastro iniciado para ${cleanPhone}`);
        } else {
            await enviarMensagem(phoneNumber, response.data?.message || 'Erro ao iniciar processo.');
            log(`⚠️ Erro ao iniciar cadastro: ${JSON.stringify(response.data)}`);
        }
        
    } catch (error) {
        log(`❌ Erro ao iniciar cadastro: ${error.message}`, 'error');
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * PROCESSAR MENSAGENS DURANTE CADASTRO
 */
async function processarMensagemCadastro(phoneNumber, message) {
    try {
        const cleanPhone = phoneNumber.replace('@c.us', '');
        log(`📝 Processando mensagem de cadastro: ${cleanPhone} - ${message}`);
        
        const response = await axios.post(`${CONFIG.apiBaseUrl}${CONFIG.apis.cadastro}`, {
            phone: cleanPhone,
            action: 'processar',
            message: message,
            secret: CONFIG.webhookSecret
        }, { timeout: CONFIG.apiTimeout });
        
        if (response.data && response.data.success) {
            await enviarMensagem(phoneNumber, response.data.message);
            log(`✅ Mensagem de cadastro processada para ${cleanPhone}`);
        } else {
            await enviarMensagem(phoneNumber, response.data?.message || 'Erro no processamento.');
            log(`⚠️ Erro no processamento: ${JSON.stringify(response.data)}`);
        }
        
    } catch (error) {
        log(`❌ Erro no cadastro: ${error.message}`, 'error');
        await enviarMensagem(phoneNumber, '❌ Erro temporário. Digite *2* para tentar novamente.');
    }
}

/**
 * VERIFICAR SE USUÁRIO ESTÁ EM PROCESSO DE CADASTRO
 */
async function verificarProcessoCadastro(phoneNumber) {
    try {
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const tempDir = path.join(__dirname, 'temp');
        fs.ensureDirSync(tempDir);
        const cacheFile = path.join(tempDir, `whatsapp_cadastro_${cleanPhone}.json`);
        
        return fs.existsSync(cacheFile);
    } catch (error) {
        return false;
    }
}

/**
 * ENVIAR MENSAGEM DE ERRO
 */
async function enviarMensagemErro(phoneNumber) {
    try {
        await enviarMensagem(phoneNumber, CONFIG.messages.error);
    } catch (error) {
        log(`❌ Erro ao enviar mensagem de erro: ${error.message}`, 'error');
    }
}

/**
 * ENVIAR MENSAGEM (FUNÇÃO PARA API)
 */
async function sendMessage(phoneNumber, message) {
    try {
        if (!isReady) {
            throw new Error('Bot não está pronto');
        }
        
        const formattedPhone = phoneNumber.includes('@c.us') ? phoneNumber : `${phoneNumber}@c.us`;
        await client.sendMessage(formattedPhone, message);
        
        return { success: true, message: 'Mensagem enviada com sucesso' };
        
    } catch (error) {
        log(`❌ Erro ao enviar mensagem: ${error.message}`, 'error');
        return { success: false, error: error.message };
    }
}

// === API REST ===

/**
 * STATUS DO BOT
 */
app.get('/status', (req, res) => {
    const status = {
        status: isReady ? 'connected' : 'disconnected',
        bot_ready: isReady,
        session_name: CONFIG.sessionName,
        uptime: process.uptime(),
        menu_system: 'dynamic',
        timestamp: new Date().toISOString(),
        version: '2.1.0'
    };
    
    log('📊 Status consultado');
    res.json(status);
});

/**
 * ENVIO DE NOTIFICAÇÕES
 */
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
            log('❌ Tentativa de acesso com secret inválido');
            return res.status(401).json({ 
                success: false, 
                error: 'Acesso não autorizado' 
            });
        }

        if (!phone || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'Telefone e mensagem são obrigatórios' 
            });
        }

        log(`📥 Nova notificação para ${phone}`);
        
        const result = await sendMessage(phone, message);
        
        if (result.success) {
            log(`✅ Notificação enviada para ${phone}`);
        } else {
            log(`❌ Falha no envio para ${phone}: ${result.error}`);
        }
        
        res.json(result);
        
    } catch (error) {
        log(`❌ Erro na API de envio: ${error.message}`, 'error');
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor',
            timestamp: new Date().toISOString()
        });
    }
});

/**
 * TESTE DO BOT
 */
app.post('/send-test', async (req, res) => {
    try {
        const { secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
            return res.status(401).json({ 
                success: false, 
                error: 'Acesso não autorizado' 
            });
        }

        const testPhone = '34991191534';
        const testMessage = `🧪 *Teste Klube Cash WhatsApp Bot v2.1*

Sistema de Menu Dinâmico Ativado! ✅

Data: ${new Date().toLocaleString('pt-BR')}

Se recebeu esta mensagem, o sistema está funcionando perfeitamente!

Para testar o menu, envie qualquer mensagem.`;

        const result = await sendMessage(testPhone, testMessage);
        res.json(result);
        
    } catch (error) {
        log(`❌ Erro no teste: ${error.message}`, 'error');
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor' 
        });
    }
});

/**
 * ENDPOINT DE TESTE FORÇADO - Para testar sem WhatsApp conectado
 */
app.post('/send-test-force', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
            log('❌ Tentativa de acesso com secret inválido no teste forçado');
            return res.status(401).json({
                success: false,
                error: 'Acesso não autorizado'
            });
        }

        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                error: 'Telefone e mensagem são obrigatórios'
            });
        }

        // SIMULAR ENVIO MESMO SEM WHATSAPP CONECTADO
        log(`📤 TESTE FORÇADO: Simulando envio para ${phone}`);
        log(`📝 MENSAGEM: ${message.substring(0, 100)}...`);

        res.json({
            success: true,
            message: 'Mensagem enviada com sucesso (SIMULADO para teste)',
            phone: phone,
            simulated: true,
            whatsapp_ready: isReady,
            timestamp: new Date().toISOString()
        });

    } catch (error) {
        log(`❌ Erro no teste forçado: ${error.message}`, 'error');
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// === INICIALIZAR SERVIDOR ===
app.listen(CONFIG.port, () => {
    log(`🌐 Servidor WhatsApp Bot rodando na porta ${CONFIG.port}`);
    log(`📱 Status: http://localhost:${CONFIG.port}/status`);
    log(`📤 Envio: POST http://localhost:${CONFIG.port}/send-message`);
    log(`🧪 Teste: POST http://localhost:${CONFIG.port}/send-test`);
    log(`📋 Sistema de Menu Dinâmico ATIVADO`);
});

// Inicializar o bot
initializeBot();

// Graceful shutdown
process.on('SIGINT', async () => {
    log('🛑 Encerrando bot...');
    if (client) {
        await client.close();
    }
    process.exit(0);
});

process.on('SIGTERM', async () => {
    log('🛑 Recebido sinal de término...');
    if (client) {
        await client.close();
    }
    process.exit(0);
});

// Tratamento de erros não capturados
process.on('uncaughtException', (error) => {
    log(`❌ Erro não capturado: ${error.message}`, 'error');
    log(`Stack trace: ${error.stack}`, 'error');
});

process.on('unhandledRejection', (reason, promise) => {
    log(`❌ Promise rejeitada: ${reason}`, 'error');
});

module.exports = { client, app, sendMessage };