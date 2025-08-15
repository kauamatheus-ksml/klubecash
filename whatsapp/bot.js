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

// === INICIALIZAÇÃO DO EXPRESS ===
const app = express();
app.use(cors());
app.use(express.json());

console.log('🚀 Iniciando Klube Cash WhatsApp Bot...');

/**
 * INICIALIZAR O BOT WHATSAPP
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

        // === LISTENERS DE EVENTOS ===
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
 * PROCESSAR MENSAGENS RECEBIDAS - SISTEMA DE MENU LINEAR
 */
async function processarMensagem(message) {
    try {
        if (message.isGroupMsg || message.fromMe) {
            return;
        }

        const phoneNumber = message.from;
        const messageText = message.body.trim();
        
        console.log('📨 Nova mensagem:', phoneNumber, messageText);

        // VERIFICAR SE É OPÇÃO DO MENU (apenas "1" para saldo)
        if (messageText === '1') {
            console.log('💰 Menu Opção 1 - Saldo geral');
            await consultarSaldoGeral(phoneNumber);
            return;
        }

        // VERIFICAR SE É NÚMERO DE LOJA (2-9, após já ter consultado saldo)
        if (/^[2-9]$/.test(messageText)) {
            console.log('🏪 Loja específica:', messageText);
            await consultarLojaEspecifica(phoneNumber, messageText);
            return;
        }

        // QUALQUER OUTRA MENSAGEM = MENU
        await exibirMenu(phoneNumber);
        
    } catch (error) {
        console.error('❌ Erro:', error);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * CONSULTAR SALDO GERAL (SEM FINALIZAR)
 */
async function consultarSaldoGeral(phoneNumber) {
    try {
        await client.sendText(phoneNumber, '💰 Consultando saldo... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        });
        
        if (response.data && response.data.success && response.data.message) {
            // APENAS enviar dados do saldo (SEM finalização)
            await client.sendText(phoneNumber, response.data.message);
            console.log('✅ Saldo enviado - aguardando seleção de loja');
        }
        
    } catch (error) {
        console.error('❌ Erro saldo:', error);
        await enviarMensagemErro(phoneNumber);
    }
}

/**
 * CONSULTAR LOJA ESPECÍFICA (DUAS MENSAGENS)
 */
async function consultarLojaEspecifica(phoneNumber, numeroLoja) {
    try {
        await client.sendText(phoneNumber, '🏪 Consultando loja... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo-loja.php', {
            phone: cleanPhone,
            loja: numeroLoja,
            secret: CONFIG.webhookSecret
        });
        
        if (response.data && response.data.success) {
            // PRIMEIRA mensagem: dados da loja
            if (response.data.message) {
                await client.sendText(phoneNumber, response.data.message);
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
            
            // SEGUNDA mensagem: finalização
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
 * EXIBIR MENU PRINCIPAL
 */
async function exibirMenu(phoneNumber) {
    try {
        const menuMessage = `🏪 *Klube Cash* - Bem-vindo!

Escolha uma das opções abaixo:

1️⃣ Consultar Saldo

Digite o número da opção desejada:`;

        await client.sendText(phoneNumber, menuMessage);
        console.log('✅ Menu enviado para:', phoneNumber);
        
    } catch (error) {
        console.error('❌ Erro ao enviar menu:', error);
    }
}

/**
 * CONSULTAR SALDO (OPÇÃO 1)
 */
async function consultarSaldo(phoneNumber) {
    try {
        console.log('💰 Iniciando consulta de saldo para:', phoneNumber);
        
        // Enviar mensagem de aguarde
        await client.sendText(phoneNumber, '💰 Consultando seu saldo... ⏳');
        
        // Limpar formatação do telefone para API
        const cleanPhone = phoneNumber.replace('@c.us', '');
        
        // Chamar API de saldo
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, {
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-WhatsApp-Bot/2.0'
            }
        });
        
        console.log('📊 Resposta da API de saldo:', response.data);
        
        if (response.data && response.data.success) {
            
            // ENVIAR IMAGEM SE DISPONÍVEL
            if (response.data.send_image && response.data.image_url) {
                try {
                    console.log('🖼️ Baixando e enviando imagem...');
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
                    
                    console.log('✅ Imagem enviada com sucesso!');
                    
                    // Pausa entre imagem e texto
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    
                } catch (imgError) {
                    console.log('⚠️ Erro ao enviar imagem (continuando com texto):', imgError.message);
                }
            }
            
            // ENVIAR MENSAGEM DE TEXTO COM SALDO
            if (response.data.message) {
                console.log('📤 Enviando mensagem de saldo...');
                
                // Adicionar mensagem de encerramento
                const mensagemCompleta = response.data.message + `

─────────────────────────
✅ *Consulta finalizada!*

Para nova consulta, envie qualquer mensagem.`;
                
                await client.sendText(phoneNumber, mensagemCompleta);
                console.log('✅ Saldo enviado e conversa encerrada!');
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
 * MONITORAR MUDANÇAS DE ESTADO
 */
function handleStateChange(state) {
    console.log('🔄 Estado da conexão:', state, new Date().toISOString());
    
    if (state === 'CONNECTED') {
        console.log('✅ WhatsApp conectado e operacional!');
        isReady = true;
    } else if (state === 'DISCONNECTED') {
        console.log('❌ WhatsApp desconectado - tentando reconectar...');
        isReady = false;
    }
}

/**
 * MONITORAR CONFIRMAÇÕES DE ENTREGA
 */
function handleMessageAck(ack) {
    const statusMap = {
        1: 'Enviada',
        2: 'Entregue', 
        3: 'Lida'
    };
    
    console.log(`📋 Mensagem ${ack.id}: ${statusMap[ack.ack] || 'Status desconhecido'}`);
}

/**
 * FORMATAR NÚMERO DE TELEFONE
 */
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

/**
 * FUNÇÃO DE ENVIO DE MENSAGEM (PARA NOTIFICAÇÕES)
 */
async function sendMessage(phone, message) {
    try {
        if (!isReady || !client) {
            throw new Error('Bot não está conectado');
        }

        const formattedPhone = formatPhoneNumber(phone);
        
        console.log(`📤 Enviando notificação para: ${formattedPhone}`);
        
        const result = await client.sendText(formattedPhone, message);
        
        console.log('✅ Notificação enviada com sucesso!');
        
        return { 
            success: true, 
            messageId: result.id,
            phone: formattedPhone,
            timestamp: new Date().toISOString()
        };
        
    } catch (error) {
        console.error('❌ Erro ao enviar notificação:', error.message);
        
        return { 
            success: false, 
            error: error.message,
            phone: phone,
            timestamp: new Date().toISOString()
        };
    }
}

// === ROTAS DA API ===

/**
 * VERIFICAR STATUS DO BOT
 */
app.get('/status', (req, res) => {
    const status = {
        status: isReady ? 'connected' : 'disconnected',
        bot_ready: isReady,
        session_name: CONFIG.sessionName,
        uptime: process.uptime(),
        menu_system: 'linear',
        timestamp: new Date().toISOString(),
        version: '2.1.0'
    };
    
    console.log('📊 Status consultado:', status);
    res.json(status);
});

/**
 * ENVIO DE NOTIFICAÇÕES (MANTÉM COMPATIBILIDADE)
 */
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
            console.log('❌ Tentativa de acesso com secret inválido');
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

        console.log(`📥 Nova notificação para ${phone}`);
        
        const result = await sendMessage(phone, message);
        
        if (result.success) {
            console.log(`✅ Notificação enviada para ${phone}`);
        } else {
            console.log(`❌ Falha no envio para ${phone}: ${result.error}`);
        }
        
        res.json(result);
        
    } catch (error) {
        console.error('❌ Erro na API de envio:', error);
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor',
            timestamp: new Date().toISOString()
        });
    }
});

/**
 * TESTE DE ENVIO
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

        const testPhone = '38991045205';
        const testMessage = `🧪 *Teste Klube Cash WhatsApp Bot*

Sistema de Menu Linear Ativado! ✅

Data: ${new Date().toLocaleString('pt-BR')}

Se recebeu esta mensagem, o sistema está funcionando perfeitamente!

Para testar o menu, envie qualquer mensagem.`;

        const result = await sendMessage(testPhone, testMessage);
        res.json(result);
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor' 
        });
    }
});

// === INICIALIZAR SERVIDOR ===
app.listen(CONFIG.port, () => {
    console.log(`🌐 Servidor WhatsApp Bot rodando na porta ${CONFIG.port}`);
    console.log(`📱 Status: http://localhost:${CONFIG.port}/status`);
    console.log(`📤 Envio: POST http://localhost:${CONFIG.port}/send-message`);
    console.log(`🧪 Teste: POST http://localhost:${CONFIG.port}/send-test`);
    console.log(`📋 Sistema de Menu Linear ATIVADO`);
});

// Inicializar o bot
initializeBot();

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('🛑 Encerrando bot...');
    if (client) {
        await client.close();
    }
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('🛑 Recebido sinal de término...');
    if (client) {
        await client.close();
    }
    process.exit(0);
});