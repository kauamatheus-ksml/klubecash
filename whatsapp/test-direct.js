// whatsapp/test-direct.js
const { Client } = require('whatsapp-web.js');

console.log('🧪 Iniciando teste direto do WhatsApp...');

const client = new Client({
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ]
    }
});

client.on('qr', (qr) => {
    console.log('QR RECEBIDO - Escaneie no WhatsApp Web');
});

client.on('ready', async () => {
    console.log('✅ Cliente conectado!');
    
    // Teste de envio direto
    try {
        const phoneNumber = '5538991045205@c.us'; // Com código do país
        const message = '🧪 TESTE DIRETO - Klube Cash Bot está funcionando!';
        
        console.log('📤 Enviando mensagem de teste...');
        await client.sendMessage(phoneNumber, message);
        console.log('✅ Mensagem enviada com sucesso!');
        
        // Encerrar após 5 segundos
        setTimeout(() => {
            console.log('🔚 Encerrando teste...');
            process.exit(0);
        }, 5000);
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        process.exit(1);
    }
});

client.on('auth_failure', () => {
    console.error('❌ Falha na autenticação');
});

client.on('disconnected', () => {
    console.log('❌ Cliente desconectado');
});

client.initialize();