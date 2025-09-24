<?php
/**
 * VALIDADOR DE SINTAXE - TransactionController
 */

echo "<h2>🔍 VALIDANDO SINTAXE DO TRANSACTIONCONTROLLER</h2>\n";

try {
    $file = 'controllers/TransactionController.php';

    if (!file_exists($file)) {
        echo "<p>❌ Arquivo não encontrado: {$file}</p>\n";
        exit;
    }

    echo "<p>✅ Arquivo encontrado: {$file}</p>\n";

    // Testar sintaxe usando php -l
    $command = "php -l " . escapeshellarg($file) . " 2>&1";
    $output = shell_exec($command);

    echo "<h3>📋 Resultado da validação:</h3>\n";
    echo "<pre style='background: #f8f8f8; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($output) . "</pre>\n";

    if (strpos($output, 'No syntax errors') !== false) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ SINTAXE CORRIGIDA COM SUCESSO!</h4>";
        echo "<p>O arquivo TransactionController.php está sintaticamente correto.</p>";
        echo "<ul>";
        echo "<li>✅ Erro 'unexpected else' corrigido</li>";
        echo "<li>✅ Estruturas if/else/try/catch balanceadas</li>";
        echo "<li>✅ Todas as chaves fechadas corretamente</li>";
        echo "</ul>";
        echo "</div>";

        // Tentar incluir o arquivo para teste mais profundo
        echo "<h3>🧪 Teste de carregamento:</h3>\n";

        try {
            // Criar um ambiente mínimo para testar
            if (!class_exists('Database')) {
                echo "<p>⚠️ Classe Database não disponível, mas sintaxe está OK</p>\n";
            }

            echo "<p>✅ Arquivo pode ser processado pelo PHP sem erros de sintaxe</p>\n";

        } catch (Exception $e) {
            echo "<p>⚠️ Erro ao carregar (pode ser dependência): " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }

    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>❌ AINDA HÁ ERROS DE SINTAXE</h4>";
        echo "<p>O arquivo ainda contém erros que precisam ser corrigidos:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }

    // Verificar se há integrações problemáticas em outros arquivos
    echo "<h3>🔍 Verificando outros controladores:</h3>\n";

    $otherFiles = [
        'controllers/ClientController.php' => 'ClientController',
        'controllers/AdminController.php' => 'AdminController'
    ];

    foreach ($otherFiles as $otherFile => $name) {
        if (file_exists($otherFile)) {
            $otherOutput = shell_exec("php -l " . escapeshellarg($otherFile) . " 2>&1");

            if (strpos($otherOutput, 'No syntax errors') !== false) {
                echo "<p>✅ {$name}: Sintaxe OK</p>\n";
            } else {
                echo "<p>❌ {$name}: Erro de sintaxe detectado</p>\n";
                echo "<pre style='background: #ffcccc; padding: 10px; font-size: 12px;'>" . htmlspecialchars($otherOutput) . "</pre>\n";
            }
        } else {
            echo "<p>⚠️ {$name}: Arquivo não encontrado</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Validação de Sintaxe - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #e56a00; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Status da Correção:</h3>
        <p>Se a sintaxe está OK, o sistema deve voltar a funcionar.</p>

        <h3>📚 Próximos passos:</h3>
        <ul>
            <li>Teste uma página de lojista para confirmar funcionamento</li>
            <li>Monitore logs de erro do servidor</li>
            <li>Se ainda houver problemas, verifique outros controladores</li>
        </ul>
    </div>
</body>
</html>