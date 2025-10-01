<?php
/**
 * TESTE RÁPIDO DO SISTEMA CORRIGIDO - KLUBE CASH
 *
 * Script para testar se o FixedBrutalNotificationSystem está funcionando
 */

echo "<h2>🧪 TESTE DO SISTEMA CORRIGIDO</h2>\n";

try {
    // 1. Verificar se o arquivo existe
    echo "<h3>1. Verificando arquivo FixedBrutalNotificationSystem.php...</h3>\n";

    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        echo "<p>✅ Arquivo encontrado!</p>\n";

        // 2. Tentar incluir o arquivo
        echo "<h3>2. Carregando sistema...</h3>\n";
        require_once 'classes/FixedBrutalNotificationSystem.php';
        echo "<p>✅ Sistema carregado com sucesso!</p>\n";

        // 3. Verificar se a classe existe
        if (class_exists('FixedBrutalNotificationSystem')) {
            echo "<p>✅ Classe FixedBrutalNotificationSystem disponível!</p>\n";

            // 4. Tentar instanciar
            echo "<h3>3. Testando instanciação...</h3>\n";
            $system = new FixedBrutalNotificationSystem();
            echo "<p>✅ Sistema instanciado com sucesso!</p>\n";

            // 5. Testar método público
            echo "<h3>4. Testando método de verificação...</h3>\n";
            $result = $system->checkAndProcessNewTransactions();

            echo "<p><strong>Resultado:</strong></p>\n";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>\n";

            if (isset($result['processed'])) {
                echo "<p>✅ Sistema funcionando! Processadas: {$result['processed']} transações</p>\n";
            } else {
                echo "<p>⚠️ Sistema rodou mas resultado inesperado</p>\n";
            }

        } else {
            echo "<p>❌ Classe não encontrada após include</p>\n";
        }

    } else {
        echo "<p>❌ Arquivo não encontrado!</p>\n";
    }

    // 6. Verificar outros arquivos necessários
    echo "<h3>5. Verificando arquivos de apoio...</h3>\n";

    $files = [
        'utils/AutoNotificationTrigger.php' => 'Trigger automático',
        'run_single_notification.php' => 'Script de background',
        'config/database.php' => 'Configuração do banco',
        'config/constants.php' => 'Constantes do sistema'
    ];

    foreach ($files as $file => $desc) {
        if (file_exists($file)) {
            echo "<p>✅ {$desc}: OK</p>\n";
        } else {
            echo "<p>⚠️ {$desc}: Não encontrado ({$file})</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<h3>❌ ERRO NO TESTE</h3>\n";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

// 7. Status de arquivos de log
echo "<h3>6. Verificando logs...</h3>\n";

$logFiles = [
    'logs/brutal_notifications.log' => 'Log do sistema brutal',
    'logs/auto_trigger.log' => 'Log do trigger automático',
    'logs/last_notification_check.json' => 'Última verificação'
];

foreach ($logFiles as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<p>📋 {$desc}: {$size} bytes (modificado: {$modified})</p>\n";
    } else {
        echo "<p>⚠️ {$desc}: Não existe ainda</p>\n";
    }
}

echo "<h3>✅ TESTE CONCLUÍDO</h3>\n";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste do Sistema Corrigido - Klube Cash</title>
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
        <h3>Links úteis:</h3>
        <ul>
            <li><a href="install_auto_simple.php">Instalador simplificado</a></li>
            <li><a href="install_auto_simple.php?action=install">🚀 Executar instalação</a></li>
            <li><a href="install_auto_simple.php?action=test">🧪 Testar webhook</a></li>
            <li><a href="test_auto_notifications.php?run=1">Teste completo</a></li>
            <li><a href="debug_notificacoes.php?run=1">Debug do sistema</a></li>
        </ul>
    </div>
</body>
</html>