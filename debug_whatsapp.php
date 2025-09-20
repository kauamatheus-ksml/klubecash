<?php
// debug_whatsapp.php
// --- SCRIPT DE DEPURAÇÃO PARA TESTAR O FLUXO DE NOTIFICAÇÃO DO WHATSAPP ---

// Habilitar a exibição de todos os erros para um diagnóstico completo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "<h1>Diagnóstico de Notificação do WhatsApp</h1>";
echo "Iniciando teste...<hr>";

// --- CONFIGURAÇÃO MANUAL ---
// Altere este ID para um ID de uma transação REAL e recente no seu banco de dados.
$testTransactionId = 100; // <--- COLOQUE UM ID DE TRANSAÇÃO VÁLIDO AQUI
// --------------------------

// 1. Carregando dependências essenciais
echo "<h2>Passo 1: Carregando arquivos necessários</h2>";
try {
    require_once __DIR__ . '/config/constants.php';
    echo "[OK] config/constants.php carregado.\n";
    
    require_once __DIR__ . '/config/database.php';
    echo "[OK] config/database.php carregado.\n";

    require_once __DIR__ . '/utils/NotificationTrigger.php';
    echo "[OK] utils/NotificationTrigger.php carregado.\n";

    require_once __DIR__ . '/utils/WhatsAppBot.php';
    echo "[OK] utils/WhatsAppBot.php carregado.\n";

    require_once __DIR__ . '/classes/CashbackNotifier.php';
    echo "[OK] classes/CashbackNotifier.php carregado.\n";

} catch (Exception $e) {
    echo "<strong style='color:red;'>[ERRO FATAL]</strong> Não foi possível carregar um arquivo essencial: " . $e->getMessage();
    echo "\nVerifique se os caminhos dos arquivos estão corretos.";
    echo "</pre>";
    die();
}
echo "<hr>";

// 2. Conexão com o Banco de Dados
echo "<h2>Passo 2: Verificando conexão com o Banco de Dados</h2>";
try {
    $db = Database::getConnection();
    if ($db) {
        echo "[OK] Conexão com o banco de dados bem-sucedida.\n";
    } else {
        throw new Exception("A conexão com o banco de dados retornou nulo.");
    }
} catch (Exception $e) {
    echo "<strong style='color:red;'>[ERRO FATAL]</strong> Falha ao conectar com o banco de dados: " . $e->getMessage();
    echo "</pre>";
    die();
}
echo "<hr>";

// 3. Testando o NotificationTrigger
echo "<h2>Passo 3: Acionando o NotificationTrigger para a transação ID: $testTransactionId</h2>";

if (!class_exists('NotificationTrigger')) {
     echo "<strong style='color:red;'>[ERRO FATAL]</strong> A classe 'NotificationTrigger' não foi encontrada, mesmo após o require. Verifique o nome da classe no arquivo.";
     echo "</pre>";
     die();
}

try {
    // A função `send` do NotificationTrigger é o ponto de partida. Vamos chamá-la.
    $result = NotificationTrigger::send($testTransactionId);
    
    if (isset($result['success']) && $result['success']) {
        echo "[OK] NotificationTrigger::send() executado com sucesso.\n";
        echo "<strong>Resultado:</strong> " . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "\n";
    } else {
        echo "<strong style='color:orange;'>[AVISO]</strong> NotificationTrigger::send() retornou falha.\n";
        echo "<strong>Resultado:</strong> " . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "\n";
        echo "Isso pode acontecer se a transação não for encontrada ou se o cliente não tiver telefone. Verifique os detalhes abaixo.\n";
    }

} catch (Exception $e) {
    echo "<strong style='color:red;'>[ERRO INESPERADO]</strong> Ocorreu uma exceção ao chamar NotificationTrigger::send(): " . $e->getMessage();
    echo "\n<strong>Arquivo:</strong> " . $e->getFile() . "\n<strong>Linha:</strong> " . $e->getLine();
}
echo "<hr>";


echo "<h2>Análise Final:</h2>";
echo "<p>Se você viu '[OK]' em todos os passos e a mensagem apareceu no WhatsApp, a correção funcionou, mas pode haver um problema no fluxo da página de registro.</p>";
echo "<p>Se você viu um '[ERRO]' ou '[AVISO]', a saída acima deve indicar onde está o problema:</p>";
echo "<ul>";
echo "<li><strong>ERRO no Passo 1:</strong> Problema na estrutura de arquivos do projeto.</li>";
echo "<li><strong>ERRO no Passo 2:</strong> As credenciais do banco de dados em `config/database.php` estão incorretas.</li>";
echo "<li><strong>AVISO/ERRO no Passo 3:</strong> Este é o ponto mais provável da falha. Analise a '<strong>Resultado</strong>' e as mensagens de erro para entender o que aconteceu. Pode ser desde 'Transação não encontrada' até um erro de conexão com o bot Node.js.</li>";
echo "</ul>";


echo "</pre>";

?>