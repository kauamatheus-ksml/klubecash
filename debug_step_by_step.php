<?php
// Debug passo a passo da função registerTransaction
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

echo "<h2>🔍 Debug Passo a Passo - TransactionController</h2>";

try {
    $db = Database::getConnection();
    
    // Simular sessão de loja MVP
    $storeQuery = "
        SELECT l.*, u.id as user_id, u.email, u.mvp
        FROM lojas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.status = 'aprovado' AND u.mvp = 'sim'
        LIMIT 1
    ";
    $storeStmt = $db->prepare($storeQuery);
    $storeStmt->execute();
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "<p style='color: red;'>❌ Nenhuma loja MVP encontrada!</p>";
        exit;
    }
    
    // Simular sessão
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    echo "<p>🏪 Loja MVP: {$store['nome_fantasia']} (ID: {$store['id']})</p>";
    echo "<p>👤 Usuário: {$store['email']}</p>";
    
    // Buscar cliente
    $clientQuery = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo "<p style='color: red;'>❌ Nenhum cliente encontrado!</p>";
        exit;
    }
    
    echo "<p>👤 Cliente: {$client['nome']} (ID: {$client['id']})</p>";
    
    // Dados da transação
    $data = [
        'loja_id' => $store['id'],
        'usuario_id' => $client['id'],
        'valor_total' => 50.00,
        'codigo_transacao' => 'DEBUG_STEP_' . time(),
        'descricao' => 'Teste step by step - ' . date('Y-m-d H:i:s')
    ];
    
    echo "<h3>1️⃣ Dados da transação:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    // PASSO 1: Validação de campos obrigatórios
    echo "<h3>2️⃣ Validando campos obrigatórios...</h3>";
    $requiredFields = ['loja_id', 'usuario_id', 'valor_total', 'codigo_transacao'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo "<p style='color: red;'>❌ Campo {$field} faltante ou vazio</p>";
            exit;
        } else {
            echo "<p>✅ Campo {$field}: {$data[$field]}</p>";
        }
    }
    
    // PASSO 2: Verificar autenticação
    echo "<h3>3️⃣ Verificando autenticação...</h3>";
    if (!AuthController::isAuthenticated()) {
        echo "<p style='color: red;'>❌ Usuário não autenticado</p>";
        exit;
    }
    echo "<p>✅ Usuário autenticado</p>";
    
    if (!AuthController::isStore() && !AuthController::isAdmin()) {
        echo "<p style='color: red;'>❌ Não é loja nem admin</p>";
        exit;
    }
    echo "<p>✅ Tipo de usuário autorizado</p>";
    
    // PASSO 3: Verificar cliente
    echo "<h3>4️⃣ Verificando cliente no banco...</h3>";
    $userStmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = :usuario_id AND tipo = :tipo AND status = :status");
    $userStmt->bindParam(':usuario_id', $data['usuario_id']);
    $tipoCliente = USER_TYPE_CLIENT;
    $userStmt->bindParam(':tipo', $tipoCliente);
    $statusAtivo = USER_ACTIVE;
    $userStmt->bindParam(':status', $statusAtivo);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>❌ Cliente não encontrado ou inativo</p>";
        exit;
    }
    echo "<p>✅ Cliente validado: {$user['nome']}</p>";
    
    // PASSO 4: Verificar loja e MVP
    echo "<h3>5️⃣ Verificando loja e status MVP...</h3>";
    $isStoreMvp = false;
    
    try {
        $storeStmt = $db->prepare("
            SELECT l.*, 
                   COALESCE(u.mvp, 'nao') as store_mvp 
            FROM lojas l 
            JOIN usuarios u ON l.usuario_id = u.id 
            WHERE l.id = :loja_id AND l.status = :status
        ");
        $storeStmt->bindParam(':loja_id', $data['loja_id']);
        $statusAprovado = STORE_APPROVED;
        $storeStmt->bindParam(':status', $statusAprovado);
        $storeStmt->execute();
        $storeData = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($storeData) {
            $isStoreMvp = (isset($storeData['store_mvp']) && $storeData['store_mvp'] === 'sim');
            echo "<p>✅ Loja encontrada: {$storeData['nome_fantasia']}</p>";
            echo "<p>🏆 Status MVP: " . ($isStoreMvp ? 'SIM' : 'NÃO') . " (store_mvp: {$storeData['store_mvp']})</p>";
        } else {
            echo "<p style='color: red;'>❌ Loja não encontrada ou não aprovada</p>";
            exit;
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erro na query MVP: " . $e->getMessage() . "</p>";
        exit;
    }
    
    // PASSO 5: Validar valor da transação
    echo "<h3>6️⃣ Validando valor da transação...</h3>";
    if (!is_numeric($data['valor_total']) || $data['valor_total'] <= 0) {
        echo "<p style='color: red;'>❌ Valor da transação inválido: {$data['valor_total']}</p>";
        exit;
    }
    echo "<p>✅ Valor válido: R$ " . number_format($data['valor_total'], 2, ',', '.') . "</p>";
    
    // PASSO 6: Verificar código de transação duplicado
    echo "<h3>7️⃣ Verificando código de transação duplicado...</h3>";
    $checkStmt = $db->prepare("
        SELECT id FROM transacoes_cashback 
        WHERE codigo_transacao = :codigo_transacao AND loja_id = :loja_id
    ");
    $checkStmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
    $checkStmt->bindParam(':loja_id', $data['loja_id']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo "<p style='color: red;'>❌ Já existe transação com este código</p>";
        exit;
    }
    echo "<p>✅ Código único: {$data['codigo_transacao']}</p>";
    
    // PASSO 7: Carregar modelo CashbackBalance
    echo "<h3>8️⃣ Carregando CashbackBalance...</h3>";
    require_once __DIR__ . '/models/CashbackBalance.php';
    if (class_exists('CashbackBalance')) {
        $balanceModel = new CashbackBalance();
        echo "<p>✅ CashbackBalance carregado</p>";
    } else {
        echo "<p style='color: red;'>❌ Classe CashbackBalance não encontrada</p>";
        exit;
    }
    
    // PASSO 8: Calcular cashback
    echo "<h3>9️⃣ Calculando valores de cashback...</h3>";
    $porcentagemCliente = DEFAULT_CASHBACK_CLIENT; // 5%
    $porcentagemAdmin = DEFAULT_CASHBACK_ADMIN; // 5%
    $porcentagemTotal = DEFAULT_CASHBACK_TOTAL; // 10%
    
    $valorCashbackTotal = ($data['valor_total'] * $porcentagemTotal) / 100;
    $valorCashbackCliente = ($data['valor_total'] * $porcentagemCliente) / 100;
    $valorCashbackAdmin = ($data['valor_total'] * $porcentagemAdmin) / 100;
    $valorLoja = 0.00;
    
    echo "<p>✅ Cashback Cliente: R$ " . number_format($valorCashbackCliente, 2, ',', '.') . " ({$porcentagemCliente}%)</p>";
    echo "<p>✅ Cashback Admin: R$ " . number_format($valorCashbackAdmin, 2, ',', '.') . " ({$porcentagemAdmin}%)</p>";
    echo "<p>✅ Cashback Total: R$ " . number_format($valorCashbackTotal, 2, ',', '.') . " ({$porcentagemTotal}%)</p>";
    
    // PASSO 9: Definir status da transação
    echo "<h3>🔟 Definindo status da transação...</h3>";
    if ($isStoreMvp) {
        $transactionStatus = TRANSACTION_APPROVED;
        echo "<p>🏆 Status MVP: APROVADO automaticamente</p>";
    } else {
        $transactionStatus = TRANSACTION_PENDING;
        echo "<p>📝 Status Normal: PENDENTE</p>";
    }
    
    // PASSO 10: Preparar descrição
    echo "<h3>1️⃣1️⃣ Preparando descrição...</h3>";
    $descricao = isset($data['descricao']) ? $data['descricao'] : 'Compra na ' . $storeData['nome_fantasia'];
    echo "<p>✅ Descrição: {$descricao}</p>";
    
    // PASSO 11: Tentar inserir no banco
    echo "<h3>1️⃣2️⃣ Inserindo transação no banco...</h3>";
    
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO transacoes_cashback (
                usuario_id, loja_id, valor_total, valor_cashback,
                valor_cliente, valor_admin, valor_loja, codigo_transacao, 
                data_transacao, status, descricao
            ) VALUES (
                :usuario_id, :loja_id, :valor_total, :valor_cashback,
                :valor_cliente, :valor_admin, :valor_loja, :codigo_transacao, 
                :data_transacao, :status, :descricao
            )
        ");
        
        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        $stmt->bindParam(':loja_id', $data['loja_id']);
        $stmt->bindParam(':valor_total', $data['valor_total']);
        $stmt->bindParam(':valor_cashback', $valorCashbackTotal);
        $stmt->bindParam(':valor_cliente', $valorCashbackCliente);
        $stmt->bindParam(':valor_admin', $valorCashbackAdmin);
        $stmt->bindParam(':valor_loja', $valorLoja);
        $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
        
        $dataTransacao = date('Y-m-d H:i:s');
        $stmt->bindParam(':data_transacao', $dataTransacao);
        $stmt->bindParam(':status', $transactionStatus);
        $stmt->bindParam(':descricao', $descricao);
        
        $result = $stmt->execute();
        
        if ($result) {
            $transactionId = $db->lastInsertId();
            echo "<p>✅ Transação inserida com ID: {$transactionId}</p>";
            
            // PASSO 12: Creditar cashback se MVP
            if ($isStoreMvp) {
                echo "<h3>1️⃣3️⃣ Creditando cashback instantâneo (MVP)...</h3>";
                
                $descricaoCashback = "Cashback da compra #{$transactionId} - " . $storeData['nome_fantasia'];
                $creditResult = $balanceModel->addBalance($data['usuario_id'], $data['loja_id'], $valorCashbackCliente, $descricaoCashback, $transactionId);
                
                if ($creditResult) {
                    echo "<p>✅ Cashback creditado instantaneamente</p>";
                } else {
                    echo "<p>⚠️ Erro ao creditar cashback instantâneo</p>";
                }
            }
            
            $db->commit();
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
            echo "<h4>🎉 SUCESSO TOTAL!</h4>";
            echo "<p><strong>Transaction ID:</strong> {$transactionId}</p>";
            echo "<p><strong>Status:</strong> {$transactionStatus}</p>";
            echo "<p><strong>É MVP:</strong> " . ($isStoreMvp ? '🏆 SIM' : '❌ NÃO') . "</p>";
            echo "<p><strong>Cashback Cliente:</strong> R$ " . number_format($valorCashbackCliente, 2, ',', '.') . "</p>";
            echo "<p><strong>Cashback Creditado:</strong> " . ($isStoreMvp ? '✅ SIM' : '❌ NÃO') . "</p>";
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>❌ Falha ao executar INSERT</p>";
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p style='color: red;'>❌ Erro na transação do banco: " . $e->getMessage() . "</p>";
        throw $e;
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ ERRO FATAL</h4>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
div { margin: 1rem 0; }
h3 { color: #333; }
</style>