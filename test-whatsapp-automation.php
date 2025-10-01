<?php
// test-whatsapp-automation.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once 'config/database.php';
require_once 'config/constants.php';

// Estilo básico para melhor visualização
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste WhatsApp Evolution API</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { 
            color: #333; 
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .success { 
            color: green; 
            font-weight: bold;
        }
        .error { 
            color: red; 
            font-weight: bold;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        th { 
            background: #4CAF50; 
            color: white; 
            padding: 10px; 
            text-align: left;
        }
        td { 
            padding: 8px; 
            border: 1px solid #ddd; 
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .test-form {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .test-form input {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .test-form button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .test-form button:hover {
            background: #45a049;
        }
        .status-box {
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .status-connected {
            background: #d4edda;
            border: 1px solid #28a745;
        }
        .status-disconnected {
            background: #f8d7da;
            border: 1px solid #dc3545;
        }
        .debug-info {
            background: #fff3cd;
            padding: 15px;
            border: 1px solid #ffc107;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🤖 Teste WhatsApp Evolution API - Klube Cash</h1>
    
    <?php
    // Verificar se a classe existe
    $classPath = __DIR__ . '/classes/WhatsAppEvolutionAutomation.php';
    if (!file_exists($classPath)) {
        echo '<div class="warning">⚠️ <strong>AVISO:</strong> Arquivo WhatsAppEvolutionAutomation.php não encontrado em: ' . $classPath . '</div>';
        
        // Tentar criar a classe se não existir
        echo '<h2>📝 Criando arquivo da classe...</h2>';
        
        // Criar diretório se não existir
        if (!is_dir(__DIR__ . '/classes')) {
            mkdir(__DIR__ . '/classes', 0755, true);
            echo '<p class="success">✅ Diretório /classes criado</p>';
        }
        
        // Criar arquivo da classe
        $classContent = '<?php
/**
 * Sistema de Automação WhatsApp com Evolution API
 * Envia mensagens automaticamente após registro de cashback
 */

class WhatsAppEvolutionAutomation {
    
    private $evolutionConfig;
    private $db;
    private $logFile;
    
    public function __construct() {
        // Configurações da Evolution API
        $this->evolutionConfig = [
            \'base_url\' => \'https://api.klubecash.com/evolution\',
            \'instance_name\' => \'klubecash\',
            \'api_key\' => \'B6D711FCDE4D4FD5936544120E713976\'
        ];
        
        // Conectar ao banco
        require_once __DIR__ . \'/../config/database.php\';
        $this->db = Database::getConnection();
        
        // Arquivo de log
        $this->logFile = __DIR__ . \'/../logs/whatsapp_automation.log\';
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function verificarStatusInstancia() {
        try {
            $url = "{$this->evolutionConfig[\'base_url\']}/instance/connectionState/{$this->evolutionConfig[\'instance_name\']}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                \'apikey: \' . $this->evolutionConfig[\'api_key\']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [\'connected\' => false, \'error\' => $error];
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    \'connected\' => isset($data[\'state\']) && ($data[\'state\'] === \'open\'),
                    \'status\' => $data
                ];
            }
            
            return [
                \'connected\' => false, 
                \'http_code\' => $httpCode,
                \'response\' => $response
            ];
            
        } catch (Exception $e) {
            return [\'connected\' => false, \'error\' => $e->getMessage()];
        }
    }
    
    public function notificarCashback($transactionId) {
        try {
            // Buscar dados da transação
            $query = "
                SELECT 
                    t.id,
                    t.valor_total,
                    t.valor_cashback,
                    t.valor_cliente,
                    t.status,
                    t.data_transacao,
                    u.id as usuario_id,
                    u.nome as cliente_nome,
                    u.telefone as cliente_telefone,
                    l.nome_fantasia as loja_nome,
                    l.porcentagem_cashback,
                    usr_loja.mvp as loja_mvp
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN lojas l ON t.loja_id = l.id
                JOIN usuarios usr_loja ON l.usuario_id = usr_loja.id
                WHERE t.id = :transaction_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(\':transaction_id\', $transactionId);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Transação não encontrada: {$transactionId}");
            }
            
            // Formatar telefone
            $phone = $this->formatarTelefone($transaction[\'cliente_telefone\']);
            
            if (!$phone) {
                throw new Exception("Telefone inválido: {$transaction[\'cliente_telefone\']}");
            }
            
            // Criar mensagem
            $mensagem = $this->criarMensagemCashback($transaction);
            
            // Enviar via Evolution API
            $resultado = $this->enviarViaEvolution($phone, $mensagem);
            
            // Registrar no banco
            $this->registrarEnvio(
                $transactionId,
                $phone,
                $mensagem,
                $resultado[\'success\'],
                $resultado[\'response\'] ?? null,
                $resultado[\'error\'] ?? null
            );
            
            return $resultado;
            
        } catch (Exception $e) {
            return [
                \'success\' => false,
                \'error\' => $e->getMessage()
            ];
        }
    }
    
    private function formatarTelefone($telefone) {
        $telefone = preg_replace(\'/[^0-9]/\', \'\', $telefone);
        
        if (strlen($telefone) == 11) {
            $telefone = \'55\' . $telefone;
        } else if (strlen($telefone) == 10) {
            $telefone = \'55\' . substr($telefone, 0, 2) . \'9\' . substr($telefone, 2);
        } else if (strlen($telefone) == 13 && substr($telefone, 0, 2) == \'55\') {
            // Já está no formato correto
        } else {
            return false;
        }
        
        return $telefone;
    }
    
    private function criarMensagemCashback($transaction) {
        $nomeCliente = explode(\' \', $transaction[\'cliente_nome\'])[0];
        $valorCompra = number_format($transaction[\'valor_total\'], 2, \',\', \'.\');
        $valorCashback = number_format($transaction[\'valor_cliente\'], 2, \',\', \'.\');
        $nomeLoja = $transaction[\'loja_nome\'];
        $isInstantaneo = ($transaction[\'loja_mvp\'] === \'sim\');
        
        if ($isInstantaneo) {
            $mensagem = "🎉 *Parabéns, {$nomeCliente}!*\\n\\n";
            $mensagem .= "✅ Seu cashback foi *creditado instantaneamente!*\\n\\n";
            $mensagem .= "🏪 *Loja:* {$nomeLoja}\\n";
            $mensagem .= "💳 *Valor da compra:* R$ {$valorCompra}\\n";
            $mensagem .= "💰 *Cashback recebido:* R$ {$valorCashback}\\n\\n";
            $mensagem .= "✨ *Saldo já disponível para uso!*\\n\\n";
            $mensagem .= "📱 Acesse sua conta: https://klubecash.com\\n\\n";
            $mensagem .= "_Klube Cash - Suas compras valem mais!_";
        } else {
            $mensagem = "⭐ *{$nomeCliente}, sua compra foi registrada!*\\n\\n";
            $mensagem .= "⏰ Liberação em até 7 dias úteis.\\n\\n";
            $mensagem .= "🏪 {$nomeLoja}\\n";
            $mensagem .= "💰 Compra: R$ {$valorCompra}\\n";
            $mensagem .= "🎁 Cashback: R$ {$valorCashback}\\n\\n";
            $mensagem .= "💳 Acesse: https://klubecash.com\\n\\n";
            $mensagem .= "🔔 *Klube Cash - Dinheiro de volta no seu bolso!*";
        }
        
        return $mensagem;
    }
    
    private function enviarViaEvolution($phone, $message) {
        try {
            $url = "{$this->evolutionConfig[\'base_url\']}/message/sendText/{$this->evolutionConfig[\'instance_name\']}";
            
            $data = [
                \'number\' => $phone,
                \'options\' => [
                    \'delay\' => 1200,
                    \'presence\' => \'composing\',
                    \'linkPreview\' => true
                ],
                \'textMessage\' => [
                    \'text\' => $message
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                \'Content-Type: application/json\',
                \'apikey: \' . $this->evolutionConfig[\'api_key\']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Erro CURL: " . $error);
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 201 || $httpCode === 200) {
                return [
                    \'success\' => true,
                    \'response\' => $responseData,
                    \'message_id\' => $responseData[\'key\'][\'id\'] ?? null
                ];
            } else {
                return [
                    \'success\' => false,
                    \'error\' => "HTTP {$httpCode}: " . ($responseData[\'message\'] ?? $response)
                ];
            }
            
        } catch (Exception $e) {
            return [
                \'success\' => false,
                \'error\' => $e->getMessage()
            ];
        }
    }
    
    private function registrarEnvio($transactionId, $phone, $message, $success, $response, $error) {
        try {
            // Verificar se a tabela existe
            $checkTable = $this->db->query("SHOW TABLES LIKE \'whatsapp_evolution_logs\'");
            if ($checkTable->rowCount() == 0) {
                // Criar tabela se não existir
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS whatsapp_evolution_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        phone VARCHAR(20),
                        message TEXT,
                        success TINYINT(1),
                        response TEXT,
                        transaction_id INT,
                        event_type VARCHAR(50),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_phone (phone),
                        INDEX idx_transaction (transaction_id),
                        INDEX idx_created_at (created_at)
                    )
                ");
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_evolution_logs (
                    transaction_id,
                    phone,
                    message,
                    success,
                    response,
                    event_type,
                    created_at
                ) VALUES (
                    :transaction_id,
                    :phone,
                    :message,
                    :success,
                    :response,
                    \'cashback_notification\',
                    NOW()
                )
            ");
            
            $stmt->bindParam(\':transaction_id\', $transactionId);
            $stmt->bindParam(\':phone\', $phone);
            $stmt->bindParam(\':message\', $message);
            $stmt->bindParam(\':success\', $success, PDO::PARAM_INT);
            $responseJson = json_encode($response ?? [\'error\' => $error]);
            $stmt->bindParam(\':response\', $responseJson);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Erro ao registrar envio: " . $e->getMessage());
        }
    }
    
    private function log($message) {
        $timestamp = date(\'Y-m-d H:i:s\');
        $logMessage = "[{$timestamp}] {$message}\\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        error_log("WhatsApp Automation: {$message}");
    }
}';
        
        file_put_contents($classPath, $classContent);
        echo '<p class="success">✅ Arquivo WhatsAppEvolutionAutomation.php criado com sucesso!</p>';
        echo '<p><a href="">🔄 Recarregar página</a></p>';
        
    } else {
        require_once $classPath;
        
        if (!class_exists('WhatsAppEvolutionAutomation')) {
            echo '<div class="error">❌ Classe WhatsAppEvolutionAutomation não foi carregada corretamente!</div>';
        } else {
            $automation = new WhatsAppEvolutionAutomation();
            
            echo '<h2>🔌 Status da Instância Evolution</h2>';
            
            // Debug info
            echo '<div class="debug-info">';
            echo '<strong>🔍 Informações de Debug:</strong><br>';
            echo 'PHP Version: ' . phpversion() . '<br>';
            echo 'CURL Enabled: ' . (function_exists('curl_init') ? '✅ Sim' : '❌ Não') . '<br>';
            echo 'JSON Enabled: ' . (function_exists('json_decode') ? '✅ Sim' : '❌ Não') . '<br>';
            echo '</div>';
            
            $status = $automation->verificarStatusInstancia();
            
            if (isset($status['connected'])) {
                if ($status['connected']) {
                    echo '<div class="status-box status-connected">';
                    echo '<h3 class="success">✅ WhatsApp Conectado!</h3>';
                    echo '<p>A instância Evolution está conectada e pronta para enviar mensagens.</p>';
                } else {
                    echo '<div class="status-box status-disconnected">';
                    echo '<h3 class="error">❌ WhatsApp Desconectado</h3>';
                    echo '<p>A instância Evolution não está conectada. Verifique:</p>';
                    echo '<ul>';
                    echo '<li>Se a Evolution API está rodando</li>';
                    echo '<li>Se a instância "klubecash" existe</li>';
                    echo '<li>Se o WhatsApp está conectado via QR Code</li>';
                    echo '</ul>';
                }
                echo '</div>';
                
                echo '<h3>📊 Detalhes da Resposta:</h3>';
                echo '<pre>';
                print_r($status);
                echo '</pre>';
            } else {
                echo '<div class="error">❌ Não foi possível obter o status da instância</div>';
            }
            
            // Formulário de teste
            ?>
            <div class="test-form">
                <h2>🧪 Testar Envio de Notificação</h2>
                <form method="GET">
                    <label>ID da Transação: 
                        <input type="number" name="test_transaction" placeholder="Ex: 680" required>
                    </label>
                    <button type="submit">📤 Enviar Notificação WhatsApp</button>
                </form>
            </div>
            <?php
            
            // Testar envio
            if (isset($_GET['test_transaction'])) {
                $transactionId = intval($_GET['test_transaction']);
                echo "<h2>📤 Testando Envio para Transação #{$transactionId}</h2>";
                
                $result = $automation->notificarCashback($transactionId);
                
                if ($result['success']) {
                    echo '<div class="success">✅ Mensagem enviada com sucesso!</div>';
                } else {
                    echo '<div class="error">❌ Erro ao enviar: ' . htmlspecialchars($result['error']) . '</div>';
                }
                
                echo '<h3>Resposta completa:</h3>';
                echo '<pre>';
                print_r($result);
                echo '</pre>';
            }
            
            // Listar últimas notificações
            echo '<h2>📜 Últimas Notificações Enviadas</h2>';
            
            try {
                $db = Database::getConnection();
                
                // Verificar se a tabela existe
                $checkTable = $db->query("SHOW TABLES LIKE 'whatsapp_evolution_logs'");
                if ($checkTable->rowCount() > 0) {
                    $stmt = $db->query("
                        SELECT wel.*, t.valor_cliente, u.nome as cliente_nome 
                        FROM whatsapp_evolution_logs wel
                        LEFT JOIN transacoes_cashback t ON wel.transaction_id = t.id
                        LEFT JOIN usuarios u ON t.usuario_id = u.id
                        ORDER BY wel.created_at DESC 
                        LIMIT 10
                    ");
                    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($logs) > 0) {
                        echo '<table>';
                        echo '<tr>';
                        echo '<th>ID</th>';
                        echo '<th>Transação</th>';
                        echo '<th>Cliente</th>';
                        echo '<th>Telefone</th>';
                        echo '<th>Status</th>';
                        echo '<th>Data/Hora</th>';
                        echo '</tr>';
                        
                        foreach ($logs as $log) {
                            echo '<tr>';
                            echo '<td>' . $log['id'] . '</td>';
                            echo '<td>#' . $log['transaction_id'] . '</td>';
                            echo '<td>' . htmlspecialchars($log['cliente_nome'] ?? 'N/A') . '</td>';
                            echo '<td>' . htmlspecialchars($log['phone']) . '</td>';
                            echo '<td>' . ($log['success'] ? '✅ Enviado' : '❌ Falhou') . '</td>';
                            echo '<td>' . $log['created_at'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p>Nenhuma notificação enviada ainda.</p>';
                    }
                } else {
                    echo '<p>Tabela de logs ainda não foi criada. Envie uma notificação de teste para criar automaticamente.</p>';
                }
            } catch (Exception $e) {
                echo '<div class="error">Erro ao buscar logs: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
    ?>
</div>
</body>
</html>