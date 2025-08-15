const venom = require('venom-bot');
const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors());

let client = null;
let isReady = false;

// Configurações expandidas para produção
const CONFIG = {
    sessionName: 'klube-cash-bot',
    port: process.env.PORT || 3001,
    webhookSecret: process.env.WEBHOOK_SECRET || 'klube-cash-2024'
};

/**
 * Inicializa o cliente do Venom Bot com configurações otimizadas
 * Esta função estabelece a conexão fundamental com o WhatsApp
 */
async function initializeBot() {
    try {
        console.log('🚀 Iniciando Klube Cash WhatsApp Bot v2.0...');
        
        client = await venom.create({
            session: CONFIG.sessionName,
            headless: "new",
            debug: false,
            logQR: true,
            disableWelcome: true,
            updatesLog: false,
            autoClose: 60000,
            browserArgs: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-extensions'
            ]
        });

        console.log('✅ Bot conectado com sucesso ao WhatsApp!');
        isReady = true;

        // Configurar eventos do bot para monitoramento
        client.onMessage(handleIncomingMessage);
        client.onStateChange(handleStateChange);
        client.onAck(handleMessageAck);

        return client;
    } catch (error) {
        console.error('❌ Erro ao inicializar o bot:', error);
        isReady = false;
        
        // Implementar reconexão automática mais robusta
        setTimeout(() => {
            console.log('🔄 Tentando reconectar em 30 segundos...');
            initializeBot();
        }, 30000);
    }
}

/**
 * Processa mensagens recebidas e implementa funcionalidades automáticas
 * Esta função nos permite criar respostas automáticas no futuro
 */
async function handleIncomingMessage(message) {
    try {
        console.log('📥 Mensagem recebida:');
        console.log('   📞 De:', message.from);
        console.log('   💬 Texto:', message.body);
        console.log('   📋 Tipo:', message.type);
        
        // Log estruturado para análise posterior
        console.log({
            timestamp: new Date().toISOString(),
            from: message.from,
            body: message.body.substring(0, 100) + (message.body.length > 100 ? '...' : ''),
            type: message.type,
            timestamp: new Date().toISOString()
        });

        // ===== NOVA FUNCIONALIDADE: CONSULTA DE SALDO =====
        
        // Verificar se é mensagem de texto e contém palavra-chave de saldo
        if (message.type === 'chat' && message.body) {
            const messageText = message.body.toLowerCase().trim();
            const saldoKeywords = ['saldo', 'extrato', 'cashback', 'consulta'];
            
            // Verificar se contém alguma palavra-chave
            const isSaldoRequest = saldoKeywords.some(keyword => 
                messageText.includes(keyword) || messageText === keyword
            );
            
            if (isSaldoRequest) {
                console.log('💰 Detectada consulta de saldo!');
                await processarConsultaSaldo(message.from);
                return; // Não processar outras funcionalidades
            }
        }
        
        // Aqui podem vir outras funcionalidades futuras
        // Por exemplo: cadastro, dúvidas, etc.
        
    } catch (error) {
        console.error('❌ Erro ao processar mensagem:', error);
    }
}

/**
 * Processa consulta de saldo do usuário
 * Faz requisição para o PHP e envia resposta
 */
async function processarConsultaSaldo(phoneNumber) {
    try {
        console.log('🔍 Iniciando consulta de saldo para:', phoneNumber);
        
        // Enviar mensagem de "aguarde"
        await client.sendText(phoneNumber, '💰 Consultando seu saldo de cashback... ⏳');
        
        const cleanPhone = phoneNumber.replace('@c.us', '');
        const axios = require('axios');
        
        const response = await axios.post('https://klubecash.com/api/whatsapp-saldo.php', {
            phone: cleanPhone,
            secret: CONFIG.webhookSecret
        }, {
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'KlubeCash-WhatsApp-Bot/1.0'
            }
        });
        
        console.log('📊 Resposta da API:', response.data);
        
        if (response.data && response.data.success) {
            
            // FORÇAR GERAÇÃO E ENVIO DE IMAGEM (SEMPRE)
            console.log('🖼️ FORÇANDO geração de imagem...');
            
            try {
                // Gerar imagem direto via API
                const imageGenResponse = await axios.post('https://klubecash.com/api/generate-saldo-image.php', {
                    phone: cleanPhone,
                    secret: CONFIG.webhookSecret
                }, {
                    timeout: 10000,
                    headers: { 'Content-Type': 'application/json' }
                });
                
                console.log('🖼️ Resposta da geração de imagem:', imageGenResponse.data);
                
                if (imageGenResponse.data.success && imageGenResponse.data.image_url) {
                    console.log('📤 Enviando imagem:', imageGenResponse.data.image_url);
                    
                    // Baixar e enviar como base64 (método mais confiável)
                    const imgData = await axios.get(imageGenResponse.data.image_url, {
                        responseType: 'arraybuffer'
                    });
                    
                    const base64 = Buffer.from(imgData.data).toString('base64');
                    
                    await client.sendImageFromBase64(
                        phoneNumber,
                        base64,
                        'saldo-klube-cash.png',
                        '💰 Seu Saldo Klube Cash'
                    );
                    
                    console.log('✅ IMAGEM ENVIADA COM SUCESSO!');
                }
                
            } catch (imgError) {
                console.log('❌ Erro na imagem, enviando imagem padrão...');
                
                // FALLBACK: Enviar imagem padrão existente
                const defaultImageUrl = 'https://klubecash.com/uploads/whatsapp_images/saldo_whatsapp_999_1723726320.png';
                
                try {
                    const imgData = await axios.get(defaultImageUrl, { responseType: 'arraybuffer' });
                    const base64 = Buffer.from(imgData.data).toString('base64');
                    
                    await client.sendImageFromBase64(
                        phoneNumber,
                        base64,
                        'saldo-padrao.png',
                        '💰 Seu Saldo Klube Cash'
                    );
                    
                    console.log('✅ Imagem padrão enviada!');
                } catch (defaultError) {
                    console.log('❌ Falha total na imagem:', defaultError.message);
                }
            }
            
            // Pausa
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // ENVIAR MENSAGEM DE TEXTO
            if (response.data.message) {
                await client.sendText(phoneNumber, response.data.message);
                console.log('✅ Mensagem de texto enviada');
            }
            
        } else {
            throw new Error('Resposta inválida da API');
        }
        
    } catch (error) {
        console.error('❌ Erro na consulta de saldo:', error.message);
        
        const errorMessage = `⚠️ *Klube Cash*

Ocorreu um erro temporário ao consultar seu saldo.

🔄 Tente novamente em alguns instantes.`;

        try {
            await client.sendText(phoneNumber, errorMessage);
        } catch (sendError) {
            console.error('❌ Erro ao enviar mensagem de erro:', sendError);
        }
    }
}

/**
 * Monitora mudanças no estado da conexão
 * Essencial para manter o sistema estável e confiável
 */
function handleStateChange(state) {
    console.log('🔄 Estado da conexão alterado:', state, new Date().toISOString());
    
    if (state === 'CONNECTED') {
        console.log('✅ WhatsApp totalmente conectado e operacional!');
        isReady = true;
    } else if (state === 'DISCONNECTED') {
        console.log('❌ WhatsApp desconectado - tentando reconectar...');
        isReady = false;
    } else if (state === 'OPENING') {
        console.log('🔄 Estabelecendo conexão com WhatsApp...');
    }
}

/**
 * Monitora confirmações de entrega e leitura das mensagens
 * Importante para relatórios de entrega de notificações
 */
function handleMessageAck(ack) {
    const statusMap = {
        1: 'Enviada para o servidor',
        2: 'Entregue ao dispositivo', 
        3: 'Lida pelo destinatário'
    };
    
    console.log(`📋 Status da mensagem ${ack.id}: ${statusMap[ack.ack] || 'Status desconhecido'}`);
}

/**
 * Formata número de telefone para o padrão WhatsApp brasileiro
 * Esta função é crucial para garantir que as mensagens sejam entregues corretamente
 */
function formatPhoneNumber(phone) {
    // Remove todos os caracteres não numéricos
    let cleanPhone = phone.replace(/\D/g, '');
    
    console.log(`📞 Formatando número: ${phone} -> ${cleanPhone}`);
    
    // Lógica específica para números brasileiros
    if (cleanPhone.length === 11 && cleanPhone.startsWith('0')) {
        // Remove o zero inicial se presente (formato antigo)
        cleanPhone = '55' + cleanPhone.substring(1);
    } else if (cleanPhone.length === 11 && !cleanPhone.startsWith('55')) {
        // Adiciona código do país se não presente
        cleanPhone = '55' + cleanPhone;
    } else if (cleanPhone.length === 10) {
        // Número fixo ou celular sem 9º dígito
        cleanPhone = '55' + cleanPhone;
    } else if (cleanPhone.length === 13 && cleanPhone.startsWith('55')) {
        // Já está no formato correto
        // Não fazer nada
    }
    
    const finalNumber = cleanPhone + '@c.us';
    console.log(`📞 Número final formatado: ${finalNumber}`);
    return finalNumber;
}

/**
 * Função principal de envio de mensagem com tratamento robusto de erros
 * Esta é a função que será chamada pelo sistema PHP
 */
async function sendMessage(phone, message) {
    try {
        if (!isReady || !client) {
            throw new Error('Bot não está conectado ao WhatsApp');
        }

        const formattedPhone = formatPhoneNumber(phone);
        
        console.log(`📤 Preparando envio:`);
        console.log(`   📞 Para: ${formattedPhone}`);
        console.log(`   💬 Mensagem: ${message.substring(0, 100)}${message.length > 100 ? '...' : ''}`);
        
        // Verificar se o número é válido no WhatsApp
        //const isValidNumber = await client.checkNumberStatus(formattedPhone);
        
        //if (!isValidNumber.exists) {
        //    throw new Error(`Número ${phone} não possui WhatsApp`);
        //}
        console.log('Pulando verificação de WhatsApp para teste');
        // Enviar a mensagem
        const result = await client.sendText(formattedPhone, message);
        
        console.log('✅ Mensagem enviada com sucesso!');
        console.log(`   📨 ID da mensagem: ${result.id}`);
        
        return { 
            success: true, 
            messageId: result.id,
            phone: formattedPhone,
            timestamp: new Date().toISOString()
        };
        
    } catch (error) {
        console.error('❌ Erro ao enviar mensagem:', error.message);
        
        // Log detalhado para debug
        console.error('   📞 Número tentado:', phone);
        console.error('   🕐 Horário:', new Date().toISOString());
        
        return { 
            success: false, 
            error: error.message,
            phone: phone,
            timestamp: new Date().toISOString()
        };
    }
}

// ===== ROTAS DA API =====

/**
 * Endpoint para verificar status detalhado do bot
 */
app.get('/status', (req, res) => {
    const status = {
        status: isReady ? 'connected' : 'disconnected',
        bot_ready: isReady,
        session_name: CONFIG.sessionName,
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
        version: '2.0.0'
    };
    
    console.log('📊 Status consultado:', status);
    res.json(status);
});

/**
 * Endpoint principal para envio de mensagens
 * Esta é a rota que o PHP utilizará para enviar notificações
 */
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, secret } = req.body;

        // Verificar autenticação
        if (secret !== CONFIG.webhookSecret) {
            console.log('❌ Tentativa de acesso com secret inválido');
            return res.status(401).json({ 
                success: false, 
                error: 'Acesso não autorizado' 
            });
        }

        // Validar dados obrigatórios
        if (!phone || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'Telefone e mensagem são obrigatórios' 
            });
        }

        // Validar tamanho da mensagem (WhatsApp tem limite)
        if (message.length > 4000) {
            return res.status(400).json({
                success: false,
                error: 'Mensagem muito longa (máximo 4000 caracteres)'
            });
        }

        console.log(`📥 Nova solicitação de envio recebida para ${phone}`);
        
        // Enviar mensagem
        const result = await sendMessage(phone, message);
        
        // Log do resultado para monitoramento
        if (result.success) {
            console.log(`✅ Envio concluído com sucesso para ${phone}`);
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
 * Endpoint para teste de envio com número específico
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

        // Usar seu próprio número para teste
        const testPhone = '38991045205'; // Substitua pelo seu número
        const testMessage = `🧪 Teste do Klube Cash WhatsApp Bot
        
Esta é uma mensagem de teste enviada em ${new Date().toLocaleString('pt-BR')}.

Se você recebeu esta mensagem, significa que o sistema de notificações está funcionando perfeitamente! 🎉

Em breve você receberá notificações automáticas sobre:
💰 Novos cashbacks recebidos
✅ Cashbacks liberados para uso
📊 Resumos mensais

Klube Cash - Seu dinheiro de volta! 💳`;

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

// Iniciar servidor
app.listen(CONFIG.port, () => {
    console.log(`🌐 Servidor WhatsApp Bot rodando na porta ${CONFIG.port}`);
    console.log(`📱 Status: http://localhost:${CONFIG.port}/status`);
    console.log(`📤 Envio: POST http://localhost:${CONFIG.port}/send-message`);
    console.log(`🧪 Teste: POST http://localhost:${CONFIG.port}/send-test`);
});

// Inicializar o bot
initializeBot();

// Graceful shutdown para manter a sessão segura
process.on('SIGINT', async () => {
    console.log('🛑 Encerrando bot de forma segura...');
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