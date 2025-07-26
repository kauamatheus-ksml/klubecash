<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'utils/StoreHelper.php';

echo "<h2>🔍 DEBUG ESPECÍFICO - MÉTODOS DA LOJA</h2>";

$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

echo "<h3>👤 Sessão Atual:</h3>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . ($userId ?? 'NULL') . "</li>";
echo "<li><strong>User Type:</strong> " . ($userType ?? 'NULL') . "</li>";
echo "<li><strong>Store ID (sessão):</strong> " . ($_SESSION['store_id'] ?? 'NULL') . "</li>";
echo "<li><strong>Store Name (sessão):</strong> " . ($_SESSION['store_name'] ?? 'NULL') . "</li>";
echo "<li><strong>Loja Vinculada ID:</strong> " . ($_SESSION['loja_vinculada_id'] ?? 'NULL') . "</li>";
if ($userType === 'funcionario') {
    echo "<li><strong>Employee Subtype:</strong> " . ($_SESSION['employee_subtype'] ?? 'NULL') . "</li>";
}
echo "</ul>";

if ($userType === 'funcionario') {
    echo "<div style='background: #fff3cd; padding: 15px; color: #856404;'>";
    echo "<h4>🔍 TESTANDO MÉTODOS PARA FUNCIONÁRIO</h4>";
    echo "</div>";

    echo "<h3>🧪 TESTE 1: StoreHelper::getCurrentStoreId()</h3>";
    try {
        $storeId = StoreHelper::getCurrentStoreId();
        echo "<p><strong>Resultado:</strong> " . ($storeId ?? 'NULL') . "</p>";
        
        if ($storeId) {
            echo "<p style='color: #28a745;'>✅ getCurrentStoreId() funciona</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ getCurrentStoreId() retorna NULL</p>";
            
            // Debug do método
            echo "<h4>🔍 Debug do método getCurrentStoreId():</h4>";
            echo "<p>User Type na sessão: " . ($_SESSION['user_type'] ?? 'NULL') . "</p>";
            
            if ($_SESSION['user_type'] === 'funcionario') {
                echo "<p>Deveria usar loja_vinculada_id: " . ($_SESSION['loja_vinculada_id'] ?? 'NULL') . "</p>";
                echo "<p>Ou store_id: " . ($_SESSION['store_id'] ?? 'NULL') . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>❌ ERRO: " . $e->getMessage() . "</p>";
    }

    echo "<h3>🧪 TESTE 2: AuthController::getStoreData()</h3>";
    try {
        $storeData = AuthController::getStoreData();
        echo "<p><strong>Resultado:</strong> " . ($storeData ? 'DADOS ENCONTRADOS' : 'NULL') . "</p>";
        
        if ($storeData) {
            echo "<p style='color: #28a745;'>✅ getStoreData() funciona</p>";
            echo "<p><strong>Store ID:</strong> " . ($storeData['id'] ?? 'NULL') . "</p>";
            echo "<p><strong>Nome:</strong> " . ($storeData['nome_fantasia'] ?? 'NULL') . "</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ getStoreData() retorna NULL</p>";
            
            // Debug: getStoreData() usa getStoreId() internamente
            echo "<h4>🔍 Debug do método getStoreData():</h4>";
            $debugStoreId = AuthController::getStoreId();
            echo "<p>AuthController::getStoreId() retorna: " . ($debugStoreId ?? 'NULL') . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>❌ ERRO: " . $e->getMessage() . "</p>";
    }

    echo "<h3>🧪 TESTE 3: AuthController::getStoreId()</h3>";
    try {
        $authStoreId = AuthController::getStoreId();
        echo "<p><strong>Resultado:</strong> " . ($authStoreId ?? 'NULL') . "</p>";
        
        if ($authStoreId) {
            echo "<p style='color: #28a745;'>✅ AuthController::getStoreId() funciona</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ AuthController::getStoreId() retorna NULL</p>";
            
            // Verificar o código do método
            echo "<h4>🔍 Verificação do método AuthController::getStoreId():</h4>";
            echo "<p>User Type: " . ($_SESSION['user_type'] ?? 'NULL') . "</p>";
            
            if ($_SESSION['user_type'] === 'funcionario') {
                echo "<p>Para funcionário, deve retornar: \$_SESSION['loja_vinculada_id']</p>";
                echo "<p>Valor atual: " . ($_SESSION['loja_vinculada_id'] ?? 'NULL') . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>❌ ERRO: " . $e->getMessage() . "</p>";
    }

    echo "<h3>🧪 TESTE 4: Busca Direta no Banco</h3>";
    try {
        $db = Database::getConnection();
        
        // Buscar dados do funcionário
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Loja Vinculada ID (banco):</strong> " . ($userData['loja_vinculada_id'] ?? 'NULL') . "</p>";
        
        if ($userData['loja_vinculada_id']) {
            // Buscar dados da loja
            $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = ?");
            $storeStmt->execute([$userData['loja_vinculada_id']]);
            $storeInfo = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($storeInfo) {
                echo "<p style='color: #28a745;'>✅ Loja encontrada no banco:</p>";
                echo "<ul>";
                echo "<li><strong>ID:</strong> {$storeInfo['id']}</li>";
                echo "<li><strong>Nome:</strong> {$storeInfo['nome_fantasia']}</li>";
                echo "<li><strong>Status:</strong> {$storeInfo['status']}</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: #dc3545;'>❌ Loja não encontrada no banco</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>❌ ERRO: " . $e->getMessage() . "</p>";
    }

} else {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Este debug é específico para funcionários. Tipo atual: {$userType}</p>";
    echo "</div>";
}

echo "<h3>🔧 AÇÕES DE CORREÇÃO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix-store-methods.php' style='background: #dc3545; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 CORRIGIR MÉTODOS</a>";
echo "</div>";
?>