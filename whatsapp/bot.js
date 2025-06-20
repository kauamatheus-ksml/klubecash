const venom = require('venom-bot');
const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors());

let client = null;
let isReady = false;

// Configurações
const CONFIG = {
    sessionName: 'klube-cash-bot',
    port: process.env.PORT || 3001,
    webhookSecret: process.env.WEBHOOK_SECRET || 'klube-cash-2024'
};

/**
 * Inicializa o cliente do Venom Bot
 */
async function initializeBot() {
    try {
        console.log('🚀 Iniciando Klube Cash WhatsApp Bot...');
        
        client = await venom.create({
            session: CONFIG.sessionName,
            headless: "new", // Use o novo modo headless
            debug: false,
            logQR: true,
            browserArgs: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu'
            ],
            // Desabilitar verificações de atualização
            disableWelcome: true,
            updatesLog: false
        });

        console.log('✅ Bot conectado com sucesso!');
        isReady = true;

        // Eventos do bot
        client.onMessage(handleIncomingMessage);
        client.onStateChange(handleStateChange);

        return client;
    } catch (error) {
        console.error('❌ Erro ao inicializar o bot:', error);
        isReady = false;
        
        // Tentar reconectar após 30 segundos
        setTimeout(() => {
            console.log('🔄 Tentando reconectar...');
            initializeBot();
        }, 30000);
    }
}

/**
 * Manipula mensagens recebidas
 */
async function handleIncomingMessage(message) {
    try {
        // Log da mensagem recebida (para debug)
        console.log('📨 Mensagem recebida:', {
            from: message.from,
            body: message.body,
            type: message.type
        });

        // Por enquanto, apenas log das mensagens
        // Futuramente podemos implementar comandos automáticos aqui
    } catch (error) {
        console.error('❌ Erro ao processar mensagem:', error);
    }
}

/**
 * Manipula mudanças de estado da conexão
 */
function handleStateChange(state) {
    console.log('🔄 Estado da conexão:', state);
    
    if (state === 'CONNECTED') {
        console.log('✅ WhatsApp conectado!');
        isReady = true;
    } else if (state === 'DISCONNECTED') {
        console.log('❌ WhatsApp desconectado!');
        isReady = false;
    }
}

/**
 * Formata número de telefone para WhatsApp
 */
function formatPhoneNumber(phone) {
    // Remove todos os caracteres não numéricos
    let cleanPhone = phone.replace(/\D/g, '');
    
    // Se não tem código do país, adiciona 55 (Brasil)
    if (cleanPhone.length === 11 && cleanPhone.startsWith('0')) {
        cleanPhone = '55' + cleanPhone.substring(1);
    } else if (cleanPhone.length === 11) {
        cleanPhone = '55' + cleanPhone;
    } else if (cleanPhone.length === 10) {
        cleanPhone = '55' + cleanPhone;
    }
    
    return cleanPhone + '@c.us';
}

/**
 * Envia mensagem via WhatsApp
 */
async function sendMessage(phone, message) {
    try {
        if (!isReady || !client) {
            throw new Error('Bot não está conectado');
        }

        const formattedPhone = formatPhoneNumber(phone);
        
        console.log(`📤 Enviando mensagem para ${formattedPhone}:`, message);
        
        const result = await client.sendText(formattedPhone, message);
        
        console.log('✅ Mensagem enviada com sucesso!');
        return { success: true, messageId: result.id };
        
    } catch (error) {
        console.error('❌ Erro ao enviar mensagem:', error);
        return { success: false, error: error.message };
    }
}

// API Routes

/**
 * Endpoint para verificar status do bot
 */
app.get('/status', (req, res) => {
    res.json({
        status: isReady ? 'connected' : 'disconnected',
        timestamp: new Date().toISOString()
    });
});

/**
 * Endpoint para enviar mensagem
 */
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        // Verificar secret de segurança
        if (secret !== CONFIG.webhookSecret) {
            return res.status(401).json({ 
                success: false, 
                error: 'Secret inválido' 
            });
        }

        // Validar dados obrigatórios
        if (!phone || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'Telefone e mensagem são obrigatórios' 
            });
        }

        // Enviar mensagem
        const result = await sendMessage(phone, message);
        
        res.json(result);
        
    } catch (error) {
        console.error('❌ Erro na API:', error);
        res.status(500).json({ 
            success: false, 
            error: 'Erro interno do servidor' 
        });
    }
});

/**
 * Endpoint para reiniciar o bot
 */
app.post('/restart', async (req, res) => {
    try {
        const { secret } = req.body;

        if (secret !== CONFIG.webhookSecret) {
            return res.status(401).json({ 
                success: false, 
                error: 'Secret inválido' 
            });
        }

        console.log('🔄 Reiniciando bot...');
        
        if (client) {
            await client.close();
        }
        
        isReady = false;
        
        // Reinicializar após 2 segundos
        setTimeout(() => {
            initializeBot();
        }, 2000);
        
        res.json({ success: true, message: 'Bot reiniciando...' });
        
    } catch (error) {
        console.error('❌ Erro ao reiniciar:', error);
        res.status(500).json({ 
            success: false, 
            error: 'Erro ao reiniciar bot' 
        });
    }
});

// Iniciar servidor
app.listen(CONFIG.port, () => {
    console.log(`🌐 Servidor rodando na porta ${CONFIG.port}`);
    console.log(`📱 Status: http://localhost:${CONFIG.port}/status`);
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