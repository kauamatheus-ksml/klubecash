<?php
/**
 * LABORATÓRIO DE DEBUG - Sistema de Notificações de Cashback
 * 
 * Este arquivo testa cada componente do sistema individualmente
 * para identificar exatamente onde está o problema.
 * 
 * Como usar:
 * 1. Coloque este arquivo na RAIZ do projeto
 * 2. Acesse via browser: https://klubecash.com/debug-notifications.php
 * 3. Analise os resultados de cada teste
 */

// Configurar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Começar buffer de saída para capturar qualquer erro
ob_start();

// Função auxiliar para exibir resultados dos testes
function showTestResult($testName, $success, $message, $details = null) {
    $icon = $success ? "✅" : "❌";
    $color = $success ? "#28a745" : "#dc3545";
    
    echo "<div style='margin: 10px 0; padding: 15px; border-left: 4px solid {$color}; background: " . 
         ($success ? "#d4edda" : "#f8d7da") . ";'>";
    echo "<strong>{$icon} {$testName}</strong><br>";
    echo "<span style='color: {$color};'>{$message}</span>";
    
    if ($details) {
        echo "<details style='margin-top: 10px;'>";
        echo "<summary style='cursor: pointer; color: #666;'>Ver detalhes técnicos</summary>";
        echo "<pre style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 4px; overflow-x: auto;'>";
        echo htmlspecialchars(print_r($details, true));
        echo "</pre></details>";
    }
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔬 Debug: Sistema de Notificações Klube Cash</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #333; 
            border-bottom: 3px solid #FF7A00; 
            padding-bottom: 10px; 
        }
        h2 { 
            color: #FF7A00; 
            margin-top: 30px; 
        }
        .summary { 
            background: #e9ecef; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .test-section { 
            margin: 20px 0; 
            padding: 20px; 
            border: 1px solid #dee2e6; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔬 Laboratório de Debug - Sistema de Notificações</h1>
    <div class="summary">
        <strong>📋 O que este debug faz:</strong><br>
        Testa cada componente do sistema de notificações individualmente para identificar onde está o problema.
        Pense nisto como um "check-up médico" completo do sistema.
    </div>

<?php

// ============================================================================
// FASE 1: TESTES DE INFRAESTRUTURA BÁSICA
// ============================================================================

echo "<h2>🔧 FASE 1: Infraestrutura Básica</h2>";
echo "<div class='test-section'>";

// Teste 1.1: Verificar se os arquivos necessários existem
echo "<h3>📁 Teste 1.1: Verificação de Arquivos</h3>";

$requiredFiles = [
    'config/constants.php' => 'Arquivo de constantes do sistema',
    'config/database.php' => 'Arquivo de configuração do banco',
    'classes/CashbackNotifier.php' => 'Classe principal de notificações',
    'utils/NotificationTrigger.php' => 'Utilitário para disparar notificações',
    'api/cashback-notificacao.php' => 'API endpoint para notificações'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        showTestResult(
            "Arquivo: {$file}",
            true,
            "Arquivo encontrado - {$description}"
        );
    } else {
        $missingFiles[] = $file;
        showTestResult(
            "Arquivo: {$file}",
            false,
            "ARQUIVO NÃO ENCONTRADO - {$description}",
            "Caminho verificado: " . realpath('.') . DIRECTORY_SEPARATOR . $file
        );
    }
}

// Teste 1.2: Carregar configurações básicas
echo "<h3>⚙️ Teste 1.2: Carregamento de Configurações</h3>";

try {
    require_once 'config/constants.php';
    require_once 'config/database.php';
    
    showTestResult(
        "Carregamento de configurações",
        true,
        "Arquivos de configuração carregados com sucesso"
    );
    
    // Verificar constantes específicas das notificações
    $requiredConstants = [
        'WHATSAPP_BOT_URL',
        'WHATSAPP_BOT_SECRET', 
        'WHATSAPP_ENABLED',
        'SITE_URL'
    ];
    
    $undefinedConstants = [];
    foreach ($requiredConstants as $constant) {
        if (defined($constant)) {
            showTestResult(
                "Constante: {$constant}",
                true,
                "Definida: " . constant($constant)
            );
        } else {
            $undefinedConstants[] = $constant;
            showTestResult(
                "Constante: {$constant}",
                false,
                "CONSTANTE NÃO DEFINIDA"
            );
        }
    }
    
} catch (Exception $e) {
    showTestResult(
        "Carregamento de configurações",
        false,
        "ERRO ao carregar configurações: " . $e->getMessage(),
        $e->getTraceAsString()
    );
}

echo "</div>";

// ============================================================================
// FASE 2: TESTES DE BANCO DE DADOS
// ============================================================================

echo "<h2>🗄️ FASE 2: Conectividade com Banco de Dados</h2>";
echo "<div class='test-section'>";

// Teste 2.1: Conexão com banco
echo "<h3>🔌 Teste 2.1: Conexão com Banco</h3>";

try {
    $db = Database::getConnection();
    showTestResult(
        "Conexão com banco de dados",
        true,
        "Conexão estabelecida com sucesso"
    );
    
    // Teste 2.2: Verificar tabelas necessárias
    echo "<h3>📊 Teste 2.2: Verificação de Tabelas</h3>";
    
    $requiredTables = [
        'usuarios' => 'Tabela de usuários',
        'transacoes_cashback' => 'Tabela de transações de cashback',
        'lojas' => 'Tabela de lojas'
    ];
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                showTestResult(
                    "Tabela: {$table}",
                    true,
                    "Tabela existe - {$description}"
                );
            } else {
                showTestResult(
                    "Tabela: {$table}",
                    false,
                    "TABELA NÃO ENCONTRADA - {$description}"
                );
            }
        } catch (Exception $e) {
            showTestResult(
                "Tabela: {$table}",
                false,
                "ERRO ao verificar tabela: " . $e->getMessage()
            );
        }
    }
    
    // Teste 2.3: Buscar transação de exemplo para teste
    echo "<h3>🔍 Teste 2.3: Busca de Dados de Teste</h3>";
    
    try {
        $stmt = $db->prepare("
            SELECT t.id, t.usuario_id, t.loja_id, t.valor_total, t.valor_cliente, 
                   u.nome, u.telefone, l.nome_fantasia
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id  
            JOIN lojas l ON t.loja_id = l.id
            WHERE t.status = 'pendente'
            ORDER BY t.data_transacao DESC
            LIMIT 1
        ");
        $stmt->execute();
        $testTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testTransaction) {
            showTestResult(
                "Dados de teste disponíveis",
                true,
                "Encontrada transação ID #{$testTransaction['id']} para teste",
                $testTransaction
            );
            
            // Guardar dados para testes seguintes
            $GLOBALS['test_transaction'] = $testTransaction;
        } else {
            showTestResult(
                "Dados de teste disponíveis", 
                false,
                "Nenhuma transação pendente encontrada para teste"
            );
        }
    } catch (Exception $e) {
        showTestResult(
            "Busca de dados de teste",
            false,
            "ERRO ao buscar dados: " . $e->getMessage(),
            $e->getTraceAsString()
        );
    }
    
} catch (Exception $e) {
    showTestResult(
        "Conexão com banco de dados",
        false,
        "ERRO de conexão: " . $e->getMessage(),
        $e->getTraceAsString()
    );
}

echo "</div>";

// ============================================================================
// FASE 3: TESTES DAS CLASSES DE NOTIFICAÇÃO
// ============================================================================

echo "<h2>📱 FASE 3: Classes de Notificação</h2>";
echo "<div class='test-section'>";

// Teste 3.1: Carregar classe CashbackNotifier
echo "<h3>📋 Teste 3.1: Classe CashbackNotifier</h3>";

try {
    if (file_exists('classes/CashbackNotifier.php')) {
        require_once 'classes/CashbackNotifier.php';
        
        if (class_exists('CashbackNotifier')) {
            showTestResult(
                "Carregamento da CashbackNotifier",
                true,
                "Classe carregada com sucesso"
            );
            
            // Teste de instanciação
            $notifier = new CashbackNotifier();
            showTestResult(
                "Instanciação da CashbackNotifier",
                true,
                "Objeto criado com sucesso"
            );
            
            // Teste com transação real se disponível
            if (isset($GLOBALS['test_transaction'])) {
                echo "<h4>🧪 Teste com Dados Reais</h4>";
                
                $testId = $GLOBALS['test_transaction']['id'];
                $result = $notifier->notifyNewTransaction($testId);
                
                showTestResult(
                    "Notificação de teste (ID: {$testId})",
                    $result['success'],
                    $result['message'],
                    $result
                );
            }
            
        } else {
            showTestResult(
                "Carregamento da CashbackNotifier",
                false,
                "CLASSE NÃO ENCONTRADA após include"
            );
        }
    } else {
        showTestResult(
            "Carregamento da CashbackNotifier",
            false,
            "ARQUIVO classes/CashbackNotifier.php não encontrado"
        );
    }
} catch (Exception $e) {
    showTestResult(
        "Teste da CashbackNotifier",
        false,
        "ERRO: " . $e->getMessage(),
        $e->getTraceAsString()
    );
}

// Teste 3.2: Carregar classe NotificationTrigger
echo "<h3>🚀 Teste 3.2: Classe NotificationTrigger</h3>";

try {
    if (file_exists('utils/NotificationTrigger.php')) {
        require_once 'utils/NotificationTrigger.php';
        
        if (class_exists('NotificationTrigger')) {
            showTestResult(
                "Carregamento da NotificationTrigger",
                true,
                "Classe carregada com sucesso"
            );
            
            // Teste método estático
            if (isset($GLOBALS['test_transaction'])) {
                echo "<h4>🧪 Teste de Disparo</h4>";
                
                $testId = $GLOBALS['test_transaction']['id'];
                $result = NotificationTrigger::send($testId, ['debug' => true, 'async' => false]);
                
                showTestResult(
                    "Disparo de notificação (ID: {$testId})",
                    $result['success'],
                    $result['message'],
                    $result
                );
            }
            
        } else {
            showTestResult(
                "Carregamento da NotificationTrigger",
                false,
                "CLASSE NÃO ENCONTRADA após include"
            );
        }
    } else {
        showTestResult(
            "Carregamento da NotificationTrigger",
            false,
            "ARQUIVO utils/NotificationTrigger.php não encontrado"
        );
    }
} catch (Exception $e) {
    showTestResult(
        "Teste da NotificationTrigger",
        false,
        "ERRO: " . $e->getMessage(),
        $e->getTraceAsString()
    );
}

echo "</div>";

// ============================================================================
// FASE 4: TESTE DE CONECTIVIDADE WHATSAPP
// ============================================================================

echo "<h2>📞 FASE 4: Conectividade WhatsApp</h2>";
echo "<div class='test-section'>";

echo "<h3>🌐 Teste 4.1: Conectividade com Bot</h3>";

if (defined('WHATSAPP_BOT_URL') && defined('WHATSAPP_BOT_SECRET')) {
    
    // Teste de ping básico
    $botUrl = WHATSAPP_BOT_URL;
    $secret = WHATSAPP_BOT_SECRET;
    
    try {
        $testData = [
            'secret' => $secret,
            'phone' => '5511999999999', // Número de teste
            'message' => '🧪 TESTE DEBUG - Sistema Klube Cash funcionando!',
            'test' => true
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $botUrl . '/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($testData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: KlubeCash-Debug/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            showTestResult(
                "Conectividade WhatsApp",
                false,
                "ERRO de conexão: " . $curlError,
                [
                    'bot_url' => $botUrl,
                    'curl_error' => $curlError
                ]
            );
        } elseif ($httpCode !== 200) {
            showTestResult(
                "Conectividade WhatsApp",
                false,
                "HTTP Error: " . $httpCode,
                [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'bot_url' => $botUrl
                ]
            );
        } else {
            $responseData = json_decode($response, true);
            showTestResult(
                "Conectividade WhatsApp",
                true,
                "Conexão com bot estabelecida com sucesso",
                [
                    'http_code' => $httpCode,
                    'response_data' => $responseData
                ]
            );
        }
        
    } catch (Exception $e) {
        showTestResult(
            "Conectividade WhatsApp",
            false,
            "EXCEÇÃO: " . $e->getMessage(),
            $e->getTraceAsString()
        );
    }
    
} else {
    showTestResult(
        "Conectividade WhatsApp",
        false,
        "CONSTANTES DE WHATSAPP NÃO DEFINIDAS",
        [
            'WHATSAPP_BOT_URL' => defined('WHATSAPP_BOT_URL') ? 'Definida' : 'NÃO DEFINIDA',
            'WHATSAPP_BOT_SECRET' => defined('WHATSAPP_BOT_SECRET') ? 'Definida' : 'NÃO DEFINIDA'
        ]
    );
}

echo "</div>";

// ============================================================================
// FASE 5: TESTE INTEGRADO COMPLETO
// ============================================================================

echo "<h2>🎯 FASE 5: Teste Integrado Completo</h2>";
echo "<div class='test-section'>";

if (isset($GLOBALS['test_transaction']) && 
    class_exists('NotificationTrigger') && 
    defined('WHATSAPP_BOT_URL')) {
    
    echo "<h3>🔄 Teste 5.1: Fluxo Completo de Notificação</h3>";
    
    $testTransaction = $GLOBALS['test_transaction'];
    
    try {
        // Simular o fluxo completo
        $result = NotificationTrigger::send($testTransaction['id'], [
            'debug' => true,
            'async' => false
        ]);
        
        showTestResult(
            "Fluxo completo de notificação",
            $result['success'],
            $result['message'],
            [
                'transaction_data' => $testTransaction,
                'notification_result' => $result
            ]
        );
        
    } catch (Exception $e) {
        showTestResult(
            "Fluxo completo de notificação",
            false,
            "ERRO no fluxo: " . $e->getMessage(),
            $e->getTraceAsString()
        );
    }
    
} else {
    showTestResult(
        "Teste integrado completo",
        false,
        "PRÉ-REQUISITOS NÃO ATENDIDOS",
        [
            'transaction_available' => isset($GLOBALS['test_transaction']),
            'notification_trigger_loaded' => class_exists('NotificationTrigger'),
            'whatsapp_configured' => defined('WHATSAPP_BOT_URL')
        ]
    );
}

echo "</div>";

// ============================================================================
// RESUMO E DIAGNÓSTICO
// ============================================================================

echo "<h2>📊 RESUMO E DIAGNÓSTICO</h2>";
echo "<div class='test-section'>";

$buffer = ob_get_contents();
$successCount = substr_count($buffer, '✅');
$errorCount = substr_count($buffer, '❌');
$totalTests = $successCount + $errorCount;

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📈 Estatísticas dos Testes</h3>";
echo "<p><strong>Total de testes:</strong> {$totalTests}</p>";
echo "<p><strong>Testes bem-sucedidos:</strong> ✅ {$successCount}</p>";
echo "<p><strong>Testes com erro:</strong> ❌ {$errorCount}</p>";
echo "<p><strong>Taxa de sucesso:</strong> " . round(($successCount / $totalTests) * 100, 1) . "%</p>";
echo "</div>";

if ($errorCount > 0) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h3>🔧 Próximos Passos para Correção</h3>";
    echo "<p>Foram encontrados <strong>{$errorCount} problemas</strong> que precisam ser corrigidos.</p>";
    echo "<p><strong>Recomendação:</strong> Comece corrigindo os erros da <strong>FASE 1</strong> primeiro, depois prossiga para as fases seguintes.</p>";
    echo "<p>Os erros mais comuns são:</p>";
    echo "<ul>";
    echo "<li>📁 Arquivos não encontrados ou em local incorreto</li>";
    echo "<li>⚙️ Constantes não definidas no config/constants.php</li>";
    echo "<li>🔧 Erros de sintaxe nos arquivos PHP</li>";
    echo "<li>🌐 Problemas de conectividade com o bot WhatsApp</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3>🎉 Sistema Funcionando Perfeitamente!</h3>";
    echo "<p>Todos os testes passaram com sucesso. O sistema de notificações está configurado corretamente.</p>";
    echo "<p>Se as notificações ainda não estão chegando, verifique:</p>";
    echo "<ul>";
    echo "<li>📱 Se o número de WhatsApp do cliente está correto</li>";
    echo "<li>🔄 Se a integração foi adicionada nos pontos corretos do código</li>";
    echo "<li>⏰ Se há delay no processamento das mensagens</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";

?>

    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <h3>🎓 Como Usar Este Debug</h3>
        <p><strong>Para desenvolvedores:</strong> Este arquivo te ensina a metodologia de debug sistemático. 
        Cada teste é independente e te mostra exatamente onde está o problema.</p>
        
        <p><strong>Dica importante:</strong> Execute este debug sempre que fizer mudanças no sistema para 
        garantir que tudo continua funcionando.</p>
        
        <p><strong>Segurança:</strong> Lembre-se de remover este arquivo do servidor de produção após o debug!</p>
    </div>
</div>
</body>
</html>

Este arquivo de debug é como uma **aula prática de diagnóstico de sistemas**. Ele testa cada componente individualmente, mostrando exatamente onde está o problema. Execute este arquivo e me mostre os resultados - assim poderemos identificar e corrigir o problema de forma cirúrgica.

A metodologia que estou te ensinando aqui é a mesma que desenvolvedores seniores usam em sistemas complexos: **dividir para conquistar**, testando cada parte isoladamente até encontrar a raiz do problema.