<?php
// debug-api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

echo "<h1>🔍 DEBUG API - Passo a Passo</h1>";

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 1;

echo "<div style='color: green;'>✅ Sessão iniciada</div>";

// Teste 1: Verificar includes
echo "<h2>1. Testando Includes</h2>";

try {
    require_once 'config/database.php';
    echo "<div style='color: green;'>✅ database.php carregado</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ database.php: " . $e->getMessage() . "</div>";
}

try {
    require_once 'config/constants.php';
    echo "<div style='color: green;'>✅ constants.php carregado</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ constants.php: " . $e->getMessage() . "</div>";
}

try {
    require_once 'controllers/AuthController.php';
    echo "<div style='color: green;'>✅ AuthController.php carregado</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ AuthController.php: " . $e->getMessage() . "</div>";
}

try {
    require_once 'models/CashbackBalance.php';
    echo "<div style='color: green;'>✅ CashbackBalance.php carregado</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ CashbackBalance.php: " . $e->getMessage() . "</div>";
}

// Teste 2: Verificar AuthController
echo "<h2>2. Testando AuthController</h2>";

try {
    $isAuth = AuthController::isAuthenticated();
    echo "<div style='color: green;'>✅ isAuthenticated(): " . ($isAuth ? 'true' : 'false') . "</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ isAuthenticated(): " . $e->getMessage() . "</div>";
}

try {
    $isStore = AuthController::isStore();
    echo "<div style='color: green;'>✅ isStore(): " . ($isStore ? 'true' : 'false') . "</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ isStore(): " . $e->getMessage() . "</div>";
}

// Teste 3: Conexão com banco
echo "<h2>3. Testando Banco de Dados</h2>";

try {
    $db = Database::getConnection();
    echo "<div style='color: green;'>✅ Conexão com banco OK</div>";
    
    // Testar query simples
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "<div style='color: green;'>✅ Query teste: " . $result['total'] . " usuários no banco</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Banco: " . $e->getMessage() . "</div>";
}

// Teste 4: Simular criação de cliente visitante SEM API
echo "<h2>4. Teste Direto no Banco (sem API)</h2>";

try {
    $nome = 'Teste Direto ' . date('H:i:s');
    $telefone = '11' . rand(100000000, 999999999);
    $storeId = 1;
    
    echo "<div style='color: blue;'>📤 Testando: nome=$nome, telefone=$telefone</div>";
    
    $db = Database::getConnection();
    
    // Verificar se existe
    $checkStmt = $db->prepare("
        SELECT id FROM usuarios 
        WHERE telefone = :telefone 
        AND tipo = :tipo 
        AND tipo_cliente = :tipo_cliente 
        AND loja_criadora_id = :loja_id
    ");
    $checkStmt->bindParam(':telefone', $telefone);
    $tipo = USER_TYPE_CLIENT;
    $checkStmt->bindParam(':tipo', $tipo);
    $tipoCliente = CLIENT_TYPE_VISITOR;
    $checkStmt->bindParam(':tipo_cliente', $tipoCliente);
    $checkStmt->bindParam(':loja_id', $storeId);
    $checkStmt->execute();
    
    echo "<div style='color: green;'>✅ Check query executada - encontrados: " . $checkStmt->rowCount() . "</div>";
    
    if ($checkStmt->rowCount() == 0) {
        // Criar email fictício
        $emailFicticio = 'visitante_' . $telefone . '_loja_' . $storeId . '@klubecash.local';
        
        // Inserir
        $insertStmt = $db->prepare("
            INSERT INTO usuarios (nome, email, telefone, tipo, tipo_cliente, loja_criadora_id, status, data_criacao)
            VALUES (:nome, :email, :telefone, :tipo, :tipo_cliente, :loja_id, :status, NOW())
        ");
        $insertStmt->bindParam(':nome', $nome);
        $insertStmt->bindParam(':email', $emailFicticio);
        $insertStmt->bindParam(':telefone', $telefone);
        $insertStmt->bindParam(':tipo', $tipo);
        $insertStmt->bindParam(':tipo_cliente', $tipoCliente);
        $insertStmt->bindParam(':loja_id', $storeId);
        $status = USER_ACTIVE;
        $insertStmt->bindParam(':status', $status);
        
        $result = $insertStmt->execute();
        
        if ($result) {
            $clientId = $db->lastInsertId();
            echo "<div style='color: green;'>✅ SUCESSO! Cliente visitante criado com ID: $clientId</div>";
            
            // Verificar se foi criado
            $verifyStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $verifyStmt->execute([$clientId]);
            $createdClient = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($createdClient) {
                echo "<div style='color: green;'>✅ Cliente verificado no banco:</div>";
                echo "<pre style='background: #f0f0f0; padding: 10px;'>" . print_r($createdClient, true) . "</pre>";
            }
            
        } else {
            echo "<div style='color: red;'>❌ Falha no INSERT: " . print_r($insertStmt->errorInfo(), true) . "</div>";
        }
    } else {
        echo "<div style='color: orange;'>⚠️ Cliente já existe</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Erro no teste direto: " . $e->getMessage() . "</div>";
    echo "<div style='color: red;'>Stack trace: " . $e->getTraceAsString() . "</div>";
}

// Teste 5: Verificar se CashbackBalance causa problema
echo "<h2>5. Testando CashbackBalance</h2>";

try {
    $balanceModel = new CashbackBalance();
    echo "<div style='color: green;'>✅ CashbackBalance instanciado</div>";
    
    // Testar método getStoreBalance com ID fictício
    $saldo = $balanceModel->getStoreBalance(1, 1);
    echo "<div style='color: green;'>✅ getStoreBalance executado: R$ $saldo</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ CashbackBalance erro: " . $e->getMessage() . "</div>";
    echo "<div style='color: red;'>Linha: " . $e->getLine() . " - Arquivo: " . $e->getFile() . "</div>";
}

echo "<h2>🏁 Fim do Debug</h2>";
echo "<p>Se todos os testes acima passaram, o problema está na API. Caso contrário, vemos onde está travando.</p>";

?>