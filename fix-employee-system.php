<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 CORREÇÃO COMPLETA - SISTEMA DE FUNCIONÁRIOS</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Usuário não logado</p>";
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? '';

try {
    $db = Database::getConnection();
    
    // Buscar dados do usuário
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        echo "<p>❌ Usuário não encontrado</p>";
        exit;
    }
    
    echo "<h3>🔍 Usuário: {$userData['nome']} (Tipo: {$userData['tipo']})</h3>";
    
    if ($userData['tipo'] === 'funcionario') {
        echo "<h4>🔧 Corrigindo funcionário...</h4>";
        
        $lojaVinculadaId = $userData['loja_vinculada_id'];
        
        if (empty($lojaVinculadaId)) {
            echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
            echo "<h4>❌ ERRO CRÍTICO</h4>";
            echo "<p>Funcionário não tem loja_vinculada_id definida</p>";
            echo "<p><strong>Solução:</strong> Associar funcionário a uma loja</p>";
            
            // Listar lojas disponíveis
            $lojas = $db->query("SELECT id, nome_fantasia, status FROM lojas WHERE status = 'aprovado' ORDER BY nome_fantasia");
            echo "<h5>Lojas disponíveis:</h5>";
            while ($loja = $lojas->fetch(PDO::FETCH_ASSOC)) {
                echo "<p>ID: {$loja['id']} - {$loja['nome_fantasia']}</p>";
            }
            echo "</div>";
        } else {
            // Buscar dados da loja
            $lojaStmt = $db->prepare("SELECT * FROM lojas WHERE id = ? AND status = 'aprovado'");
            $lojaStmt->execute([$lojaVinculadaId]);
            $lojaData = $lojaStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lojaData) {
                echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
                echo "<h4>✅ CORRIGINDO SESSÃO DO FUNCIONÁRIO</h4>";
                echo "<p>Loja vinculada: {$lojaData['nome_fantasia']} (ID: {$lojaData['id']})</p>";
                
                // FORÇAR CORREÇÃO DA SESSÃO
                $_SESSION['employee_subtype'] = $userData['subtipo_funcionario'] ?? 'funcionario';
                $_SESSION['store_id'] = intval($lojaData['id']);
                $_SESSION['store_name'] = $lojaData['nome_fantasia'];
                $_SESSION['loja_vinculada_id'] = intval($lojaData['id']);
                $_SESSION['subtipo_funcionario'] = $userData['subtipo_funcionario'] ?? 'funcionario';
                
                echo "<p><strong>✅ Sessão corrigida:</strong></p>";
                echo "<ul>";
                echo "<li>store_id: {$_SESSION['store_id']}</li>";
                echo "<li>store_name: {$_SESSION['store_name']}</li>";
                echo "<li>employee_subtype: {$_SESSION['employee_subtype']}</li>";
                echo "</ul>";
                echo "</div>";
                
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
                echo "<h4>❌ LOJA VINCULADA NÃO ENCONTRADA</h4>";
                echo "<p>ID da loja: {$lojaVinculadaId}</p>";
                echo "<p>Verificar se a loja está aprovada</p>";
                echo "</div>";
            }
        }
        
    } else if ($userData['tipo'] === 'loja') {
        echo "<h4>🔧 Verificando lojista...</h4>";
        
        // Verificar se já tem store_id
        if (!isset($_SESSION['store_id']) || empty($_SESSION['store_id'])) {
            $lojaStmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ?");
            $lojaStmt->execute([$userId]);
            $lojaData = $lojaStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lojaData) {
                $_SESSION['store_id'] = intval($lojaData['id']);
                $_SESSION['store_name'] = $lojaData['nome_fantasia'];
                $_SESSION['loja_vinculada_id'] = intval($lojaData['id']);
                
                echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
                echo "<h4>✅ SESSÃO DE LOJISTA CORRIGIDA</h4>";
                echo "<p>Store ID: {$_SESSION['store_id']}</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
            echo "<h4>✅ LOJISTA JÁ CONFIGURADO</h4>";
            echo "<p>Store ID: {$_SESSION['store_id']}</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
        echo "<h4>ℹ️ USUÁRIO NÃO É LOJA NEM FUNCIONÁRIO</h4>";
        echo "<p>Tipo: {$userData['tipo']}</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ ERRO:</h4>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "<h3>🧪 TESTES APÓS CORREÇÃO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='debug-employee-system.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Completo</a><br><br>";
echo "<a href='store/dashboard/' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Testar Dashboard</a><br><br>";
echo "<a href='views/stores/funcionarios.php' style='background: #6f42c1; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Testar Funcionários</a>";
echo "</div>";

echo "<h3>📋 Sessão atual após correção:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
print_r($_SESSION);
echo "</pre>";
?>