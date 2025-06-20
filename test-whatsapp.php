<?php
// Arquivo de teste para verificar integração WhatsApp
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste de Integração WhatsApp Bot - Klube Cash</h2>";

// Verificar se os arquivos existem
$arquivos_necessarios = [
    'config/constants.php' => 'Constantes do sistema',
    'utils/WhatsAppBot.php' => 'Classe WhatsApp Bot'
];

echo "<h3>📁 Verificando Arquivos:</h3>";
foreach ($arquivos_necessarios as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "<p style='color: green;'>✅ $descricao: OK</p>";
        require_once $arquivo;
    } else {
        echo "<p style='color: red;'>❌ $descricao: NÃO ENCONTRADO ($arquivo)</p>";
    }
}

echo "<h3>🔧 Testando Configurações:</h3>";

// Verificar constantes
if (defined('WHATSAPP_BOT_URL')) {
    echo "<p>🌐 Bot URL: " . WHATSAPP_BOT_URL . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ WHATSAPP_BOT_URL não definida</p>";
}

if (defined('WHATSAPP_ENABLED')) {
    echo "<p>🔛 WhatsApp habilitado: " . (WHATSAPP_ENABLED ? 'SIM' : 'NÃO') . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ WHATSAPP_ENABLED não definida</p>";
}

echo "<h3>🤖 Testando Conexão com o Bot:</h3>";

// Verificar se a classe existe
if (class_exists('WhatsAppBot')) {
    echo "<p style='color: green;'>✅ Classe WhatsAppBot carregada</p>";
    
    // Testar conexão
    $conectado = WhatsAppBot::isConnected();
    echo "<p>📡 Status de conexão: " . ($conectado ? 
        "<span style='color: green;'>CONECTADO</span>" : 
        "<span style='color: orange;'>DESCONECTADO (normal se bot não estiver rodando)</span>") . "</p>";
    
    // Testar função de envio
    echo "<h4>📤 Testando Função de Envio:</h4>";
    $resultado_teste = WhatsAppBot::sendTestMessage('34999999999', 'Teste de integração Klube Cash');
    echo "<p>Resultado: <code>" . json_encode($resultado_teste) . "</code></p>";
    
} else {
    echo "<p style='color: red;'>❌ Classe WhatsAppBot não encontrada</p>";
}

echo "<hr>";
echo "<h3>📋 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se tudo estiver verde acima, a integração está funcionando!</li>";
echo "<li>Mantenha o bot Node.js rodando: <code>npm start</code></li>";
echo "<li>Aguarde instruções para conectar o WhatsApp (QR Code)</li>";
echo "<li>Depois implementaremos o envio real de mensagens</li>";
echo "</ol>";
?>