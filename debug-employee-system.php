<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

echo "<h2>🔍 DEBUG COMPLETO - SISTEMA DE FUNCIONÁRIOS</h2>";

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h3>❌ USUÁRIO NÃO LOGADO</h3>";
    echo "<p><a href='views/auth/login.php'>Fazer Login</a></p>";
    echo "</div>";
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'não definido';

echo "<h3>1. 📋 INFORMAÇÕES DA SESSÃO ATUAL:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
foreach ($_SESSION as $key => $value) {
    $displayValue = is_array($value) ? json_encode($value) : htmlspecialchars($value);
    echo "<tr><td><strong>{$key}</strong></td><td>{$displayValue}</td></tr>";
}
echo "</table>";

echo "<h3>2. 🔍 DADOS DO USUÁRIO NO BANCO:</h3>";
try {
    $db = Database::getConnection();
    
    // Dados do usuário
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo "<h4>👤 Dados do Usuário:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        foreach ($userData as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        // Se for funcionário, buscar dados da loja vinculada
        if ($userData['tipo'] === 'funcionario') {
            echo "<h4>🏪 ANÁLISE DE FUNCIONÁRIO:</h4>";
            
            $lojaVinculadaId = $userData['loja_vinculada_id'];
            echo "<p><strong>Loja Vinculada ID:</strong> " . ($lojaVinculadaId ?: 'NÃO DEFINIDO') . "</p>";
            
            if ($lojaVinculadaId) {
                // Buscar dados da loja
                $lojaStmt = $db->prepare("SELECT * FROM lojas WHERE id = ?");
                $lojaStmt->execute([$lojaVinculadaId]);
                $lojaData = $lojaStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lojaData) {
                    echo "<h5>✅ Dados da Loja Vinculada:</h5>";
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    foreach ($lojaData as $key => $value) {
                        echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
                    }
                    echo "</table>";
                    
                    echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 10px 0;'>";
                    echo "<h4>✅ LOJA VINCULADA ENCONTRADA</h4>";
                    echo "<p><strong>ID:</strong> {$lojaData['id']}</p>";
                    echo "<p><strong>Nome:</strong> {$lojaData['nome_fantasia']}</p>";
                    echo "<p><strong>Status:</strong> {$lojaData['status']}</p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24; margin: 10px 0;'>";
                    echo "<h4>❌ LOJA VINCULADA NÃO ENCONTRADA</h4>";
                    echo "<p>ID da loja: {$lojaVinculadaId}</p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #fff3cd; padding: 15px; color: #856404; margin: 10px 0;'>";
                echo "<h4>⚠️ FUNCIONÁRIO SEM LOJA VINCULADA</h4>";
                echo "<p>Campo loja_vinculada_id está vazio ou nulo</p>";
                echo "</div>";
            }
            
            // Verificar subtipo
            $subtipo = $userData['subtipo_funcionario'] ?? 'não definido';
            echo "<p><strong>Subtipo Funcionário:</strong> {$subtipo}</p>";
        }
        
        // Se for lojista, buscar loja
        if ($userData['tipo'] === 'loja') {
            echo "<h4>🏪 ANÁLISE DE LOJISTA:</h4>";
            
            $lojaStmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ?");
            $lojaStmt->execute([$userId]);
            $lojaData = $lojaStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lojaData) {
                echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
                echo "<h4>✅ LOJA ASSOCIADA ENCONTRADA</h4>";
                echo "<p><strong>ID:</strong> {$lojaData['id']}</p>";
                echo "<p><strong>Nome:</strong> {$lojaData['nome_fantasia']}</p>";
                echo "<p><strong>Status:</strong> {$lojaData['status']}</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
                echo "<h4>❌ LOJA NÃO ENCONTRADA PARA LOJISTA</h4>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h3>❌ USUÁRIO NÃO ENCONTRADO NO BANCO</h3>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h3>❌ ERRO:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h3>3. 🧪 TESTES DE MÉTODOS AuthController:</h3>";

// Testar métodos do AuthController
$tests = [
    'isAuthenticated' => AuthController::isAuthenticated(),
    'isStore' => AuthController::isStore(),
    'isEmployee' => AuthController::isEmployee(),
    'hasStoreAccess' => AuthController::hasStoreAccess(),
    'getStoreId' => AuthController::getStoreId(),
    'canManageEmployees' => AuthController::canManageEmployees()
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Método</th><th>Resultado</th></tr>";
foreach ($tests as $method => $result) {
    $displayResult = is_bool($result) ? ($result ? 'SIM' : 'NÃO') : ($result ?? 'NULL');
    $color = ($result === true || !empty($result)) ? '#d4edda' : '#f8d7da';
    echo "<tr style='background: {$color};'><td><strong>{$method}</strong></td><td>{$displayResult}</td></tr>";
}
echo "</table>";

echo "<h3>4. 🚨 DIAGNÓSTICO DO PROBLEMA:</h3>";

// Diagnosticar problema específico
if ($userType === 'funcionario') {
    echo "<div style='background: #cce5ff; padding: 15px; margin: 10px 0;'>";
    echo "<h4>🔍 DIAGNÓSTICO PARA FUNCIONÁRIO:</h4>";
    
    // Verificar se tem store_id na sessão
    if (!isset($_SESSION['store_id']) || empty($_SESSION['store_id'])) {
        echo "<p>❌ <strong>PROBLEMA:</strong> store_id não está definido na sessão</p>";
        echo "<p>📝 <strong>CAUSA:</strong> Método login() não configurou dados da loja para funcionário</p>";
    } else {
        echo "<p>✅ store_id está definido: {$_SESSION['store_id']}</p>";
    }
    
    // Verificar redirecionamento
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario') {
        echo "<p>⚠️ <strong>ATENÇÃO:</strong> Funcionário sendo redirecionado para cliente/dashboard</p>";
        echo "<p>📝 <strong>SOLUÇÃO:</strong> Deve ir para store/dashboard (mesmo que lojista)</p>";
    }
    
    echo "</div>";
}

echo "<h3>5. 🔧 AÇÕES DE CORREÇÃO:</h3>";
echo "<div style='background: #fff3cd; padding: 15px;'>";
echo "<h4>📋 Para corrigir o sistema:</h4>";
echo "<ol>";
echo "<li><strong>AuthController::login()</strong> - Configurar dados da loja para funcionários</li>";
echo "<li><strong>Redirecionamento</strong> - Funcionários devem ir para /store/dashboard/</li>";
echo "<li><strong>Verificações</strong> - hasStoreAccess() deve funcionar para funcionários</li>";
echo "<li><strong>Sessão</strong> - store_id deve ser definido para funcionários</li>";
echo "</ol>";
echo "</div>";

echo "<h3>6. 🧪 TESTES RÁPIDOS:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix-employee-system.php' style='background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 EXECUTAR CORREÇÃO</a><br><br>";
echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Testar Dashboard Loja</a><br><br>";
echo "<a href='views/stores/funcionarios.php' style='background: #6f42c1; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Testar Funcionários</a><br><br>";
echo "<a href='test-employee-login.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Testar Login Funcionário</a>";
echo "</div>";

echo "<h3>7. 📊 RELATÓRIO FINAL:</h3>";
if ($userType === 'funcionario') {
    $hasStoreId = isset($_SESSION['store_id']) && !empty($_SESSION['store_id']);
    $hasStoreAccess = AuthController::hasStoreAccess();
    
    echo "<div style='background: " . ($hasStoreId && $hasStoreAccess ? '#d4edda' : '#f8d7da') . "; padding: 15px;'>";
    echo "<h4>" . ($hasStoreId && $hasStoreAccess ? '✅ SISTEMA FUNCIONANDO' : '❌ SISTEMA COM PROBLEMAS') . "</h4>";
    echo "<p><strong>Store ID na sessão:</strong> " . ($hasStoreId ? 'SIM' : 'NÃO') . "</p>";
    echo "<p><strong>Acesso à loja:</strong> " . ($hasStoreAccess ? 'SIM' : 'NÃO') . "</p>";
    echo "</div>";
} else {
    echo "<div style='background: #d1ecf1; padding: 15px;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Tipo atual: {$userType}</p>";
    echo "</div>";
}
?>