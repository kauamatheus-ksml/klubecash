<?php
// Arquivo de teste para verificar integração WhatsApp
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste de Integração WhatsApp Bot - Klube Cash</h2>";

// Verificar se os arquivos existem ANTES de incluir
$arquivos_necessarios = [
    'config/constants.php' => 'Constantes do sistema',
    'utils/WhatsAppBot.php' => 'Classe WhatsApp Bot'
];

echo "<h3>📁 Verificando Arquivos:</h3>";
$arquivos_carregados = 0;

foreach ($arquivos_necessarios as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "<p style='color: green;'>✅ $descricao: Encontrado</p>";
        
        // Usar require_once para evitar inclusões duplas
        try {
            require_once $arquivo;
            echo "<p style='color: blue;'>📦 $descricao: Carregado com sucesso</p>";
            $arquivos_carregados++;
        } catch (Error $e) {
            echo "<p style='color: red;'>❌ Erro ao carregar $descricao: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $descricao: NÃO ENCONTRADO ($arquivo)</p>";
    }
}

// Só continuar se todos os arquivos foram carregados
if ($arquivos_carregados < count($arquivos_necessarios)) {
    echo "<p style='color: red;'>⚠️ Nem todos os arquivos necessários foram carregados. Interrompendo teste.</p>";
    exit;
}

echo "<h3>🔧 Testando Configurações:</h3>";

// Verificar constantes com mais detalhes
$constantes_obrigatorias = [
    'WHATSAPP_BOT_URL' => 'URL do bot WhatsApp',
    'WHATSAPP_ENABLED' => 'WhatsApp habilitado',
    'WHATSAPP_BOT_SECRET' => 'Chave secreta do bot'
];

foreach ($constantes_obrigatorias as $constante => $descricao) {
    if (defined($constante)) {
        $valor = constant($constante);
        // Não mostrar a chave secreta por segurança
        if ($constante === 'WHATSAPP_BOT_SECRET') {
            $valor = str_repeat('*', strlen($valor));
        }
        echo "<p>🔧 $descricao: <code>$valor</code></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $descricao não definida</p>";
    }
}

echo "<h3>🤖 Testando Conexão com o Bot:</h3>";

// Verificar se a classe existe e seus métodos
if (class_exists('WhatsAppBot')) {
    echo "<p style='color: green;'>✅ Classe WhatsAppBot carregada</p>";
    
    // Verificar quais métodos estão disponíveis
    $metodos_disponiveis = get_class_methods('WhatsAppBot');
    echo "<p>📋 Métodos disponíveis: <code>" . implode(', ', $metodos_disponiveis) . "</code></p>";
    
    // Testar conexão se o método existir
    if (method_exists('WhatsAppBot', 'isConnected')) {
        $conectado = WhatsAppBot::isConnected();
        echo "<p>📡 Status de conexão: " . ($conectado ? 
            "<span style='color: green;'>CONECTADO</span>" : 
            "<span style='color: orange;'>DESCONECTADO (normal se bot não estiver rodando)</span>") . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Método isConnected() não encontrado</p>";
    }
    
    // Testar função de envio apenas se existir
    if (method_exists('WhatsAppBot', 'sendTestMessage')) {
        echo "<h4>📤 Testando Função de Envio:</h4>";
        $resultado_teste = WhatsAppBot::sendTestMessage('34999999999', 'Teste de integração Klube Cash');
        echo "<p>Resultado: <code>" . json_encode($resultado_teste) . "</code></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Método sendTestMessage() ainda não implementado (isso é esperado nesta fase)</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Classe WhatsAppBot não encontrada</p>";
}

echo "<hr>";
echo "<h3>📋 Status Atual do Projeto:</h3>";
echo "<ul>";
echo "<li>✅ Estrutura básica do PHP: Funcionando</li>";
echo "<li>✅ Configurações: Carregadas</li>";
echo "<li>🔄 Bot Node.js: Precisa ser iniciado</li>";
echo "<li>🔄 Conexão WhatsApp: Próximo passo</li>";
echo "<li>🔄 Envio de mensagens: Em desenvolvimento</li>";
echo "</ul>";

echo "<h3>🎯 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Iniciar o bot Node.js com: <code>cd whatsapp && npm start</code></li>";
echo "<li>Conectar o WhatsApp escaneando o QR Code</li>";
echo "<li>Implementar o envio real de mensagens</li>";
echo "<li>Integrar com o sistema de notificações existente</li>";
echo "</ol>";
?>