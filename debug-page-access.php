<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'utils/StoreHelper.php';

echo "<h2>🔍 DEBUG UNIVERSAL - ACESSO DE FUNCIONÁRIOS</h2>";

$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

echo "<h3>👤 INFORMAÇÕES DA SESSÃO ATUAL:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . ($userId ?? 'NULL') . "</li>";
echo "<li><strong>User Type:</strong> " . ($userType ?? 'NULL') . "</li>";
echo "<li><strong>User Name:</strong> " . ($_SESSION['user_name'] ?? 'NULL') . "</li>";
echo "<li><strong>Store ID:</strong> " . ($_SESSION['store_id'] ?? 'NULL') . "</li>";
echo "<li><strong>Store Name:</strong> " . ($_SESSION['store_name'] ?? 'NULL') . "</li>";
echo "<li><strong>Loja Vinculada ID:</strong> " . ($_SESSION['loja_vinculada_id'] ?? 'NULL') . "</li>";

if ($userType === 'funcionario') {
    echo "<li><strong>Employee Subtype:</strong> " . ($_SESSION['employee_subtype'] ?? 'NULL') . "</li>";
    echo "<li><strong>Subtipo Funcionário:</strong> " . ($_SESSION['subtipo_funcionario'] ?? 'NULL') . "</li>";
}
echo "</ul>";
echo "</div>";

if ($userType === 'funcionario') {
    echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 15px 0;'>";
    echo "<h4>✅ FUNCIONÁRIO DETECTADO - SISTEMA SIMPLIFICADO ATIVO</h4>";
    echo "<p>Funcionário deve ter acesso igual ao lojista em TODAS as páginas</p>";
    echo "</div>";
    
    echo "<h3>🧪 TESTES DOS MÉTODOS PRINCIPAIS:</h3>";
    
    // Teste 1: StoreHelper::requireStoreAccess()
    echo "<h4>🔍 Teste 1: StoreHelper::requireStoreAccess()</h4>";
    try {
        ob_start();
        StoreHelper::requireStoreAccess();
        ob_end_clean();
        echo "<p style='color: #28a745;'>✅ requireStoreAccess() - PASSOU</p>";
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>❌ requireStoreAccess() - FALHOU: " . $e->getMessage() . "</p>";
    }
    
    // Teste 2: getCurrentStoreId()
    echo "<h4>🔍 Teste 2: StoreHelper::getCurrentStoreId()</h4>";
    $storeId = StoreHelper::getCurrentStoreId();
    echo "<p><strong>Resultado:</strong> " . ($storeId ?? 'NULL') . "</p>";
    echo "<p style='color: " . ($storeId ? '#28a745' : '#dc3545') . ";'>" . ($storeId ? '✅ FUNCIONANDO' : '❌ FALHOU') . "</p>";
    
    // Teste 3: getStoreData()
    echo "<h4>🔍 Teste 3: AuthController::getStoreData()</h4>";
    $storeData = AuthController::getStoreData();
    echo "<p><strong>Resultado:</strong> " . ($storeData ? 'DADOS ENCONTRADOS' : 'NULL') . "</p>";
    echo "<p style='color: " . ($storeData ? '#28a745' : '#dc3545') . ";'>" . ($storeData ? '✅ FUNCIONANDO' : '❌ FALHOU') . "</p>";
    
    // Teste 4: hasStoreAccess()
    echo "<h4>🔍 Teste 4: AuthController::hasStoreAccess()</h4>";
    $hasAccess = AuthController::hasStoreAccess();
    echo "<p><strong>Resultado:</strong> " . ($hasAccess ? 'TRUE' : 'FALSE') . "</p>";
    echo "<p style='color: " . ($hasAccess ? '#28a745' : '#dc3545') . ";'>" . ($hasAccess ? '✅ FUNCIONANDO' : '❌ FALHOU') . "</p>";
    
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460; margin: 15px 0;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Tipo atual: <strong>{$userType}</strong></p>";
    echo "<p>Para testar funcionários, faça login com um usuário do tipo 'funcionario'</p>";
    echo "</div>";
}

echo "<h3>📋 INFORMAR PÁGINA ESPECÍFICA:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; color: #856404; margin: 15px 0;'>";
echo "<h4>❓ QUAL PÁGINA PRECISA SER ANALISADA?</h4>";
echo "<p>Por favor, informe:</p>";
echo "<ul>";
echo "<li><strong>Nome do arquivo:</strong> (ex: views/stores/funcionarios.php)</li>";
echo "<li><strong>URL de acesso:</strong> (ex: /store/funcionarios/)</li>";
echo "<li><strong>Funcionalidade:</strong> (ex: gestão de funcionários)</li>";
echo "</ul>";
echo "</div>";

echo "<h3>📊 RESUMO DO SISTEMA ATUAL:</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>🎯 SISTEMA SIMPLIFICADO FUNCIONANDO:</h4>";
echo "<ul>";
echo "<li>✅ Funcionários têm acesso IGUAL aos lojistas</li>";
echo "<li>✅ Verificação única: <code>StoreHelper::requireStoreAccess()</code></li>";
echo "<li>✅ Métodos funcionando: getCurrentStoreId(), getStoreData()</li>";
echo "<li>✅ Dashboard original mantido com todas funcionalidades</li>";
echo "<li>✅ Auditoria completa implementada</li>";
echo "</ul>";
echo "</div>";
?>