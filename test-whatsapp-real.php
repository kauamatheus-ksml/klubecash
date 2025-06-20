<?php
require_once 'config/constants.php';
require_once 'utils/WhatsAppBot.php';

echo "<h2>🚀 Teste Real do WhatsApp Bot - Klube Cash</h2>";

// Status detalhado
echo "<h3>📊 Status Detalhado:</h3>";
$status = WhatsAppBot::getDetailedStatus();
echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Teste de envio real
echo "<h3>📤 Teste de Envio Real:</h3>";
if (isset($_POST['send_test']) && $_POST['send_test'] === 'true') {
    echo "<p>🔄 Enviando mensagem de teste...</p>";
    $result = WhatsAppBot::sendTestMessage();
    echo "<div style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Resultado:</strong><br>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
}
?>

<form method="post" style="margin: 20px 0;">
    <input type="hidden" name="send_test" value="true">
    <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        📱 Enviar Mensagem de Teste para Meu WhatsApp
    </button>
</form>

<p><strong>Nota:</strong> Antes de enviar o teste, certifique-se de atualizar seu número no arquivo bot.js na linha do testPhone.</p>