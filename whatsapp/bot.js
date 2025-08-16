// whatsapp/bot.js
const venom = require('venom-bot');
const express = require('express');
const cors = require('cors');
const axios = require('axios');

// === CONFIGURAÇÕES ===
const CONFIG = {
    sessionName: 'klubecash-session',
    port: process.env.PORT || 3002,
    webhookSecret: 'klube-cash-2024'
};

// === VARIÁVEIS GLOBAIS ===
let client = null;
let isReady = false;
const activeTimeouts = new Map();

const app = express();
app.use(cors());
app.use(express.json());

console.log('🚀 Iniciando Klube Cash WhatsApp Bot com Menu Dinâmico...');

/**
 * INICIALIZAR O BOT
 */
async function initializeBot() {
    try {
        console.log('📱 Conectando ao WhatsApp...');
        
        client = await venom.create(
            CONFIG.sessionName,
            undefined,
            (statusSession, session) => {
                console.log('📊 Status da sessão:', statusSession);
            },
            {
                headless: true,
                devtools: false,
                useChrome: true,
                debug: false,
                logQR: true,
                browserArgs: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--single-process',
                    '--disable-gpu'
                ],
                refreshQR: 15000,
                autoClose: 60000,
                disableSpins: true
            }
        );

        console.log('✅ Bot inicializado com sucesso!');

        client.onStateChange(handleStateChange);
        client.onMessage(processarMensagem);
        client.onAck(handleMessageAck);

        isReady = true;

    } catch (error) {
        console.error('❌ Erro ao inicializar bot:', error);
        process.exit(1);
    }
}

/**
 * PROCESSAR MENSAGENS - SISTEMA DE MENU DINÂMICO
 */
async function processarMensagem(message) {
    try {
        if (message.isGroupMsg || message.fromMe) {
            return;
        }

        const phoneNumber = message.from;
        const messageText = message.body.trim();
        
        console.log('📨 Nova mensagem de:', phoneNumber, '- Conteúdo:', messageText);

        // VERIFICAR SE É OPÇÃO 1 (SALDO)
        if (messageText === '1') {
            console.log('💰 Opção 1 - Consultando saldo...');
            await consultarSaldoComTipo(phoneNumber);
            return;
        }

        // VERIFICAR SE É OPÇÃO 2 (COMPLETAR CADASTRO)
        if (messageText === '2') {
            console.log('📝 Opção 2 - Completar cadastro...');
            await completarCadastro(phoneNumber);
            return;
        }

        // VERIFICAR SE É NÚMERO DE LOJA (3-9)
        if (/^[3-9]$/.test(messageText)) {
            console.log('🏪 Loja específica:', messageText);
            await consultarLojaEspecifica(phoneNumber, messageText);
            return;
        }

        // QUALQUER OUTRA MENSAGEM = CANCELAR TIMEOUT E MOSTRAR MENU
        if (activeTimeouts.has(phoneNumber)) {
            clearTimeout(activeTimeouts.get(phoneNumber));
            activeTimeouts.delete(phoneNumber);
            console.log('⏰ Timeout cancelado - novo menu solicitado');
        }
        
        // EXIBIR MENU (será determinado quando consultar o tipo de cliente)
        await exibirMenuDinamico(phoneNumber);
        
    } catch (error) {
        console.error('❌ Erro ao processar mensagem:', error);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * CONSULTAR SALDO COM IDENTIFICAÇÃO DE TIPO
 */
async function consultarSaldoComTipo(phoneNumber) {
    try {
        await client.sendText(phoneNumber, '💰 Consultando saldo... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, {
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-WhatsApp-Bot/2.1'
            }
        });
        
        if (response.data && response.data.success) {
            
            // ENVIAR IMAGEM SE DISPONÍVEL
            if (response.data.send_image && response.data.image_url) {
                try {
                    const imgData = await axios.get(response.data.image_url, {
                        responseType: 'arraybuffer',
                        timeout: 10000
                    });
                    
                    const base64 = Buffer.from(imgData.data).toString('base64');
                    
                    await client.sendImageFromBase64(
                        phoneNumber,
                        base64,
                        'saldo-klube-cash.png',
                        '💰 Seu Saldo Klube Cash'
                    );
                    
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    
                } catch (imgError) {
                    console.log('⚠️ Erro ao enviar imagem:', imgError.message);
                }
            }
            
            // ENVIAR DADOS DO SALDO (SEM FINALIZAÇÃO)
            if (response.data.message) {
                await client.sendText(phoneNumber, response.data.message);
                console.log('✅ Saldo enviado - iniciando timeout');
                
                // INICIAR TIMEOUT DE 30 MINUTOS
                const timeoutId = setTimeout(async () => {
                    console.log('⏰ Timeout atingido para:', phoneNumber);
                    await finalizarConsultaPorTimeout(phoneNumber);
                }, 30 * 60 * 1000);
                
                activeTimeouts.set(phoneNumber, timeoutId);
            }
            
        } else {
            throw new Error('Resposta inválida da API de saldo');
        }
        
    } catch (error) {
        console.error('❌ Erro na consulta de saldo:', error.message);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * COMPLETAR CADASTRO (OPÇÃO 2)
 */
async function completarCadastro(phoneNumber) {
    try {
        await client.sendText(phoneNumber, '📝 Preparando informações... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const response = await axios.post('https://klubecash.com/api/whatsapp-completar-cadastro.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, {
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-WhatsApp-Bot/2.1'
            }
        });
        
        if (response.data && response.data.success) {
            // Enviar mensagem de completar cadastro
            await client.sendText(phoneNumber, response.data.message);
            console.log('✅ Informações de cadastro enviadas e finalizadas');
        } else {
            throw new Error('Resposta inválida da API de completar cadastro');
        }
        
    } catch (error) {
        console.error('❌ Erro ao completar cadastro:', error.message);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * CONSULTAR LOJA ESPECÍFICA
 */
async function consultarLojaEspecifica(phoneNumber, numeroLoja) {
    try {
        if (activeTimeouts.has(phoneNumber)) {
            clearTimeout(activeTimeouts.get(phoneNumber));
            activeTimeouts.delete(phoneNumber);
            console.log('⏰ Timeout cancelado - loja selecionada');
        }
        
        await client.sendText(phoneNumber, '🏪 Consultando loja... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo-loja.php', {
            phone: cleanPhone,
            loja: numeroLoja,
            secret: CONFIG.webhookSecret
        });
        
        if (response.data && response.data.success) {
            // Dados da loja
            if (response.data.message) {
                await client.sendText(phoneNumber, response.data.message);
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
            
            // Finalização
            const finalizacao = `✅ *Consulta finalizada!*

Para nova consulta, envie qualquer mensagem.`;
            
            await client.sendText(phoneNumber, finalizacao);
            console.log('✅ Loja consultada e finalizada');
        }
        
    } catch (error) {
        console.error('❌ Erro loja:', error);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * EXIBIR MENU DINÂMICO (DESCOBRE TIPO DE CLIENTE)
 */
async function exibirMenuDinamico(phoneNumber) {
    try {
        console.log('🔍 Descobrindo tipo de cliente para:', phoneNumber);
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        
        // Fazer consulta rápida para descobrir tipo de cliente
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, {
            timeout: 10000,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-WhatsApp-Bot/2.1'
            }
        });
        
        let clientType = 'unknown';
        if (response.data && response.data.user_found) {
            clientType = response.data.client_type || 'unknown';
        }
        
        console.log('👤 Tipo de cliente identificado:', clientType);
        
        // ESCOLHER MENU BASEADO NO TIPO
        let menuMessage;
        
        if (clientType === 'visitante') {
            // Menu para visitantes (com opção de completar cadastro)
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Escolha uma das opções abaixo:

1️⃣ Consultar Saldo
2️⃣ Completar Cadastro

Digite o número da opção desejada:`;
        } else if (clientType === 'completo') {
            // Menu para clientes cadastrados (só saldo)
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Escolha uma das opções abaixo:

1️⃣ Consultar Saldo

Digite o número da opção desejada:`;
        } else {
            // Menu padrão para usuários não encontrados
            menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Para consultar seu saldo, digite:

1️⃣ Consultar Saldo

Digite o número da opção desejada:`;
        }
        
        await client.sendText(phoneNumber, menuMessage);
        console.log('✅ Menu dinâmico enviado para:', phoneNumber, '- Tipo:', clientType);
        
    } catch (error) {
        console.error('❌ Erro ao exibir menu dinâmico:', error);
        
        // Menu de fallback
        const menuFallback = `🏪 *Klube Cash* - Bem-vindo!

Digite o número da opção desejada:

1️⃣ Consultar Saldo`;
        
        await client.sendText(phoneNumber, menuFallback);
    }
}

/**
 * FINALIZAR POR TIMEOUT
 */
async function finalizarConsultaPorTimeout(phoneNumber) {
    try {
        const mensagemTimeout = `⏰ *Tempo esgotado!*

Sua consulta foi finalizada automaticamente após 30 minutos.

Para nova consulta, envie qualquer mensagem.`;

        await client.sendText(phoneNumber, mensagemTimeout);
        activeTimeouts.delete(phoneNumber);
        
        console.log('✅ Consulta finalizada por timeout:', phoneNumber);
        
    } catch (error) {
        console.error('❌ Erro ao finalizar por timeout:', error);
    }
}

/**
 * ENVIAR MENSAGEM DE ERRO
 */
async function enviarMensagemErro(phoneNumber) {
    try {
        const errorMessage = `⚠️ *Klube Cash*

Ocorreu um erro temporário.

🔄 Tente novamente em alguns instantes.

Para acessar o menu, envie qualquer mensagem.`;

        await client.sendText(phoneNumber, errorMessage);
        
    } catch (sendError) {
        console.error('❌ Erro ao enviar mensagem de erro:', sendError);
    }
}

/**
 * EVENTOS DE ESTADO E ACK
 */
function handleStateChange(state) {
    console.log('🔄 Estado da conexão:', state, new Date().toISOString());
    
    if (state === 'CONNECTED') {
        console.log('✅ WhatsApp conectado - Menu dinâmico ativo!');
        isReady = true;
    } else if (state === 'DISCONNECTED') {
        console.log('❌ WhatsApp desconectado');
        isReady = false;
    }
}

function handleMessageAck(ack) {
    const statusMap = {
        1: 'Enviada',
        2: 'Entregue', 
        3: 'Lida'
    };
    
    console.log(`📋 Mensagem ${ack.id}: ${statusMap[ack.ack] || 'Status desconhecido'}`);
}

/**
 * FUNÇÃO DE ENVIO PARA NOTIFICAÇÕES
 */
async function sendMessage(phone, message) {
    try {
        if (!isReady || !client) {
            throw new Error('Bot não está conectado');
        }

        const formattedPhone = formatPhoneNumber(phone);
        const result = await client.sendText(formattedPhone, message);
        
        return { 
            success: true, 
            messageId: result.id,
            phone: formattedPhone,
            timestamp: new Date().toISOString()
        };
        
    } catch (error) {
        return { 
            success: false, 
            error: error.message,
            phone: phone,
            timestamp: new Date().toISOString()
        };
    }
}

function formatPhoneNumber(phone) {
    let cleanPhone = phone.replace(/\D/g, '');
    
    if (cleanPhone.length === 11 && cleanPhone.startsWith('0')) {
        cleanPhone = '55' + cleanPhone.substring(1);
    } else if (cleanPhone.length === 11 && !cleanPhone.startsWith('55')) {
        cleanPhone = '55' + cleanPhone;
    } else if (cleanPhone.length === 10) {
        cleanPhone = '55' + cleanPhone;
    }
    
    return cleanPhone + '@c.us';
}

// === ROTAS DA API ===

app.get('/status', (req, res) => {
    const status = {
        status: isReady ? 'connected' : 'disconnected',
        bot_ready: isReady,
        session_name: CONFIG.sessionName,
        uptime: process.uptime(),
        menu_system: 'dynamic',
        features: ['client_type_detection', 'complete_registration'],
        timestamp: new Date().toISOString(),
        version: '2.2.0'
    };
    
    res.json(status);
});

app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
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

        const result = await sendMessage(phone, message);
        res.json(result);
        
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor',
            timestamp: new Date().toISOString()
        });
    }
});

// === INICIALIZAR ===
app.listen(CONFIG.port, () => {
    console.log(`🌐 Servidor WhatsApp Bot na porta ${CONFIG.port}`);
    console.log(`📋 Sistema de Menu Dinâmico ATIVADO`);
    console.log(`👥 Detecta automaticamente: Visitante vs Cliente Completo`);
});

initializeBot();

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('🛑 Encerrando bot...');
    if (client) {
        await client.close();
    }
    process.exit(0);
});