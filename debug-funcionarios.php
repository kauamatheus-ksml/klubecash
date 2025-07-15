<?php
/**
 * Script de Debug para Sistema de Funcionários
 * Este arquivo deve ser removido em produção
 */
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'utils/PermissionManager.php';

// Função para mostrar informações de debug
function debugInfo($label, $data) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3 style='color: #333;'>{$label}</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    if (is_array($data) || is_object($data)) {
        print_r($data);
    } else {
        echo $data;
    }
    echo "</pre></div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Sistema de Funcionários</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; margin: 5px; border: none; cursor: pointer; }
        .test-button:hover { background: #005a8b; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Debug - Sistema de Funcionários Klube Cash</h1>
    
    <?php
    // Verificar se há usuário logado
    if (!isset($_SESSION['user_id'])) {
        echo "<p class='error'>❌ Nenhum usuário logado. <a href='/login'>Faça login primeiro</a></p>";
    } else {
        debugInfo("👤 Dados da Sessão Atual", $_SESSION);
        
        // Verificar tipo de usuário
        $userType = $_SESSION['user_type'] ?? 'não definido';
        $userId = $_SESSION['user_id'];
        
        echo "<h2>🔍 Verificações de Acesso</h2>";
        
        // Teste 1: Verificar acesso à loja
        $hasStoreAccess = AuthController::hasStoreAccess();
        echo "<p>✅ Acesso à área da loja: <span class='" . ($hasStoreAccess ? 'success' : 'error') . "'>" . 
             ($hasStoreAccess ? 'PERMITIDO' : 'NEGADO') . "</span></p>";
        
        // Teste 2: Se for funcionário, verificar permissões
        if ($userType === USER_TYPE_EMPLOYEE) {
            echo "<h3>👨‍💼 Testes Específicos para Funcionários</h3>";
            
            $employeeSubtype = $_SESSION['employee_subtype'] ?? 'não definido';
            echo "<p>📋 Subtipo: <span class='info'>{$employeeSubtype}</span></p>";
            
            $storeId = $_SESSION['store_id'] ?? 'não definido';
            echo "<p>🏪 Loja vinculada: <span class='info'>{$storeId}</span></p>";
            
            // Testar permissões específicas
            $testPermissions = [
                ['dashboard', 'ver', 'Visualizar Dashboard'],
                ['transacoes', 'ver', 'Visualizar Transações'],
                ['transacoes', 'criar', 'Criar Transações'],
                ['comissoes', 'ver', 'Visualizar Comissões'],
                ['comissoes', 'pagar', 'Pagar Comissões'],
                ['funcionarios', 'ver', 'Visualizar Funcionários'],
                ['funcionarios', 'criar', 'Criar Funcionários'],
                ['relatorios', 'ver', 'Visualizar Relatórios']
            ];
            
            echo "<h4>🛡️ Teste de Permissões:</h4>";
            foreach ($testPermissions as $test) {
                [$modulo, $acao, $descricao] = $test;
                $hasPermission = PermissionManager::checkAccess($modulo, $acao);
                echo "<p>• {$descricao}: <span class='" . ($hasPermission ? 'success' : 'error') . "'>" . 
                     ($hasPermission ? 'PERMITIDO' : 'NEGADO') . "</span></p>";
            }
            
            // Verificar se pode gerenciar funcionários
            $canManageEmployees = AuthController::canManageEmployees();
            echo "<p>👥 Pode gerenciar funcionários: <span class='" . ($canManageEmployees ? 'success' : 'error') . "'>" . 
                 ($canManageEmployees ? 'SIM' : 'NÃO') . "</span></p>";
        }
        
        // Teste 3: URLs de redirecionamento
        echo "<h3>🔗 Teste de URLs</h3>";
        $testUrls = [
            'Dashboard da Loja' => STORE_DASHBOARD_URL,
            'Registrar Transação' => STORE_REGISTER_TRANSACTION_URL,
            'Comissões Pendentes' => STORE_PENDING_TRANSACTIONS_URL,
            'Funcionários' => STORE_EMPLOYEES_URL
        ];
        
        foreach ($testUrls as $label => $url) {
            echo "<p>• {$label}: <a href='{$url}' target='_blank'>{$url}</a></p>";
        }
        
        // Teste 4: Informações do banco de dados
        echo "<h3>💾 Dados do Banco</h3>";
        try {
            $db = Database::getConnection();
            
            // Buscar dados do usuário atual
            $stmt = $db->prepare("
                SELECT id, nome, email, tipo, status, loja_vinculada_id, subtipo_funcionario, ultimo_login
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                debugInfo("📊 Dados do Usuário no Banco", $userData);
                
                // Se for funcionário, buscar dados da loja vinculada
                if ($userData['tipo'] === 'funcionario' && $userData['loja_vinculada_id']) {
                    $storeStmt = $db->prepare("
                        SELECT id, nome_fantasia, razao_social, status, email
                        FROM lojas 
                        WHERE id = ?
                    ");
                    $storeStmt->execute([$userData['loja_vinculada_id']]);
                    $storeData = $storeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($storeData) {
                        debugInfo("🏪 Dados da Loja Vinculada", $storeData);
                    } else {
                        echo "<p class='error'>❌ Loja vinculada não encontrada!</p>";
                    }
                }
            } else {
                echo "<p class='error'>❌ Usuário não encontrado no banco de dados!</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao acessar banco: " . $e->getMessage() . "</p>";
        }
    }
    ?>
    
    <h3>🧪 Ações de Teste</h3>
    <button class="test-button" onclick="testDashboardAccess()">Testar Acesso ao Dashboard</button>
    <button class="test-button" onclick="testTransactionAccess()">Testar Acesso às Transações</button>
    <button class="test-button" onclick="testEmployeeAccess()">Testar Acesso aos Funcionários</button>
    <button class="test-button" onclick="window.location.reload()">Recarregar Página</button>
    
    <div id="test-results" style="margin-top: 20px;"></div>
</div>

<script>
function testDashboardAccess() {
    fetch('/store/dashboard')
        .then(response => {
            if (response.ok) {
                showResult('✅ Dashboard acessível', 'success');
            } else {
                showResult('❌ Dashboard inacessível (Status: ' + response.status + ')', 'error');
            }
        })
        .catch(error => {
            showResult('❌ Erro ao testar dashboard: ' + error.message, 'error');
        });
}

function testTransactionAccess() {
    fetch('/store/registrar-transacao')
        .then(response => {
            if (response.ok) {
                showResult('✅ Página de transações acessível', 'success');
            } else {
                showResult('❌ Página de transações inacessível (Status: ' + response.status + ')', 'error');
            }
        })
        .catch(error => {
            showResult('❌ Erro ao testar transações: ' + error.message, 'error');
        });
}

function testEmployeeAccess() {
    fetch('/store/funcionarios')
        .then(response => {
            if (response.ok) {
                showResult('✅ Página de funcionários acessível', 'success');
            } else {
                showResult('❌ Página de funcionários inacessível (Status: ' + response.status + ')', 'error');
            }
        })
        .catch(error => {
            showResult('❌ Erro ao testar funcionários: ' + error.message, 'error');
        });
}

function showResult(message, type) {
    const resultsDiv = document.getElementById('test-results');
    const p = document.createElement('p');
    p.className = type;
    p.textContent = message;
    resultsDiv.appendChild(p);
    
    // Auto-remove após 5 segundos
    setTimeout(() => {
        if (p.parentNode) {
            p.parentNode.removeChild(p);
        }
    }, 5000);
}
</script>
</body>
</html>