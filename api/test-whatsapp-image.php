<?php
// api/test-whatsapp-image.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/SaldoConsulta.php';
require_once __DIR__ . '/../classes/ImageGenerator.php';

// Verificar se é GET para teste
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Use GET para teste']);
    exit;
}

try {
    echo "<h1>🧪 Teste de Geração de Imagem</h1>";
    
    // Dados de teste
    $dadosUsuarioTeste = [
        'id' => 999,
        'nome' => 'João Teste'
    ];
    
    $dadosSaldoTeste = [
        'disponivel' => 150.50,
        'pendente' => 75.25,
        'total' => 225.75
    ];
    
    echo "<h3>📊 Dados de Teste:</h3>";
    echo "<pre>" . json_encode($dadosUsuarioTeste, JSON_PRETTY_PRINT) . "</pre>";
    echo "<pre>" . json_encode($dadosSaldoTeste, JSON_PRETTY_PRINT) . "</pre>";
    
    // Gerar imagem
    $resultado = ImageGenerator::gerarImagemSaldo($dadosUsuarioTeste, $dadosSaldoTeste);
    
    echo "<h3>🖼️ Resultado da Geração:</h3>";
    echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($resultado['success']) {
        echo "<h3>✅ Imagem Gerada com Sucesso!</h3>";
        echo "<img src='{$resultado['file_url']}' style='max-width: 400px; border: 1px solid #ccc;'>";
        echo "<p><a href='{$resultado['file_url']}' target='_blank'>Abrir imagem em nova aba</a></p>";
        
        // Agora vamos tentar enviar via WhatsApp
        echo "<h3>📱 Enviando para WhatsApp...</h3>";
        
        $phoneNumber = '38991045205';
        $webhookData = [
            'phone' => $phoneNumber,
            'image_url' => $resultado['file_url'],
            'caption' => '🧪 Teste de imagem de saldo - Klube Cash',
            'secret' => WHATSAPP_BOT_SECRET
        ];
        
        // Fazer requisição para o bot
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://54.207.165.92:3002/send-image');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: KlubeCash-Test/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<h4>📤 Resposta do Bot:</h4>";
        echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
        echo "<pre>{$response}</pre>";
        
        if ($httpCode == 200) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
            echo "✅ <strong>Imagem enviada com sucesso para {$phoneNumber}!</strong>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
            echo "❌ <strong>Erro ao enviar imagem. Verifique se o bot está online.</strong>";
            echo "</div>";
        }
        
    } else {
        echo "<h3>❌ Erro na Geração:</h3>";
        echo "<p style='color: red;'>{$resultado['error']}</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>💥 Erro Geral:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>