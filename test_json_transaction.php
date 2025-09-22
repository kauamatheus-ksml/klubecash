<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/ClientController.php';

// --- DADOS DE TESTE ---
// Substitua com um ID de usuário MVP e um ID de loja válidos do seu banco de dados.
$testData = [
    'usuario_id' => 9, // Substitua pelo ID de um usuário MVP
    'loja_id' => 59,      // Substitua pelo ID de uma loja aprovada
    'valor_total' => 50.00,
    'codigo_transacao' => 'TESTE-JSON-' . time(),
    'descricao' => 'Transação de teste para log JSON'
];

// --- EXECUÇÃO DO TESTE ---
echo "Iniciando teste de registro de transação com log JSON...\n";

// Chamar a função diretamente
$result = ClientController::registerTransaction($testData);

// --- EXIBIR RESULTADO ---
echo "\nResultado da operação:\n";
print_r($result);

echo "\n";

if ($result['status']) {
    echo "SUCESSO: Transação registrada com sucesso.\n";
    // Verificar se o arquivo JSON foi criado
    $logFilePath = __DIR__ . '/transaction_json_logs/transaction_' . $result['data']['transaction_id'] . '.json';
    if (file_exists($logFilePath)) {
        echo "VERIFICAÇÃO: Arquivo JSON '" . basename($logFilePath) . "' foi criado com sucesso.\n";
        echo "Conteúdo do JSON:\n";
        echo file_get_contents($logFilePath) . "\n";
    } else {
        echo "FALHA NA VERIFICAÇÃO: O arquivo JSON não foi encontrado para o usuário MVP.\n";
    }
} else {
    echo "FALHA: Não foi possível registrar a transação.\n";
    echo "Mensagem: " . $result['message'] . "\n";
}

echo "\nTeste concluído.\n";
?>
