<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 CORREÇÃO DEFINITIVA - FUNCIONÁRIOS</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Faça login primeiro</p>";
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? '';

echo "<h3>🔍 Usuário atual: {$_SESSION['user_name']} (Tipo: {$userType})</h3>";

if ($userType === 'funcionario') {
    try {
        $db = Database::getConnection();
        
        // Buscar dados completos do funcionário
        $stmt = $db->prepare("
            SELECT u.*, l.id as loja_id, l.nome_fantasia, l.status as loja_status
            FROM usuarios u 
            JOIN lojas l ON u.loja_vinculada_id = l.id 
            WHERE u.id = ? AND u.tipo = 'funcionario'
        ");
        $stmt->execute([$userId]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($funcionario) {
            echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
            echo "<h4>✅ CORRIGINDO SESSÃO DO FUNCIONÁRIO</h4>";
            echo "<p><strong>Funcionário:</strong> {$funcionario['nome']}</p>";
            echo "<p><strong>Loja:</strong> {$funcionario['nome_fantasia']} (ID: {$funcionario['loja_id']})</p>";
            echo "<p><strong>Subtipo:</strong> {$funcionario['subtipo_funcionario']}</p>";
            
            // FORÇAR CORREÇÃO COMPLETA DA SESSÃO
            $_SESSION['employee_subtype'] = $funcionario['subtipo_funcionario'] ?? 'funcionario';
            $_SESSION['store_id'] = intval($funcionario['loja_id']);
            $_SESSION['store_name'] = $funcionario['nome_fantasia'];
            $_SESSION['loja_vinculada_id'] = intval($funcionario['loja_id']);
            $_SESSION['subtipo_funcionario'] = $funcionario['subtipo_funcionario'] ?? 'funcionario';
            
            // Salvar sessão
            session_write_close();
            session_start();
            
            echo "<h5>✅ Sessão corrigida:</h5>";
            echo "<ul>";
            echo "<li><strong>store_id:</strong> {$_SESSION['store_id']}</li>";
            echo "<li><strong>store_name:</strong> {$_SESSION['store_name']}</li>";
            echo "<li><strong>employee_subtype:</strong> {$_SESSION['employee_subtype']}</li>";
            echo "<li><strong>loja_vinculada_id:</strong> {$_SESSION['loja_vinculada_id']}</li>";
            echo "</ul>";
            echo "</div>";
            
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
            echo "<h4>❌ FUNCIONÁRIO OU LOJA NÃO ENCONTRADOS</h4>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ ERRO:</h4>";
        echo "<p>{$e->getMessage()}</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Tipo atual: {$userType}</p>";
    echo "</div>";
}

echo "<h3>🧪 TESTES:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='test-redirect-fix.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Testar Redirecionamento</a><br><br>";
echo "<a href='store/dashboard/' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard Loja</a><br><br>";
echo "<a href='debug-employee-system.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Completo</a>";
echo "</div>";

echo "<h3>📋 Sessão atual:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px;'>";
print_r($_SESSION);
echo "</pre>";
?>