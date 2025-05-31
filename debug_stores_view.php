<?php
// debug_stores_view.php - Debug específico da view stores

$activeMenu = 'lojas';

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/AdminController.php';

session_start();

echo "<h2>🔍 Debug da View Stores.php</h2>";

// Verificar autenticação e permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    die("❌ Não está logado como admin");
}

echo "✅ Usuário autenticado como admin<br><br>";

// Obter parâmetros (igual ao stores.php original)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$category = trim($_GET['category'] ?? '');

echo "<h3>📋 Parâmetros recebidos:</h3>";
echo "• Página: $page<br>";
echo "• Busca: '$search'<br>";
echo "• Status: '$status'<br>";
echo "• Categoria: '$category'<br><br>";

// Preparar filtros (igual ao stores.php original)
$filters = [];
if (!empty($search)) $filters['busca'] = $search;
if (!empty($status)) $filters['status'] = strtolower($status);
if (!empty($category)) $filters['categoria'] = $category;

echo "<h3>🔧 Filtros preparados:</h3>";
echo "<pre>" . print_r($filters, true) . "</pre>";

try {
    // Tentar carregar dados - EXATO COMO NO STORES.PHP
    echo "<h3>🚀 Chamando AdminController::manageStoresWithBalance...</h3>";
    
    $result = AdminController::manageStoresWithBalance($filters, $page);
    
    echo "✅ Método executado!<br>";
    echo "Status do resultado: " . ($result['status'] ? 'TRUE' : 'FALSE') . "<br><br>";
    
    if (!$result['status']) {
        throw new Exception($result['message']);
    }
    
    // Extrair dados EXATO COMO NO STORES.PHP
    $stores = $result['data']['lojas'] ?? [];
    $statistics = $result['data']['estatisticas'] ?? [];
    $categories = $result['data']['categorias'] ?? [];
    $pagination = $result['data']['paginacao'] ?? [];
    
    echo "<h3>📊 Dados extraídos:</h3>";
    echo "• Número de lojas: " . count($stores) . "<br>";
    echo "• Estatísticas extraídas: " . (empty($statistics) ? 'VAZIO' : 'OK') . "<br>";
    echo "• Categorias extraídas: " . count($categories) . "<br>";
    echo "• Paginação extraída: " . (empty($pagination) ? 'VAZIO' : 'OK') . "<br><br>";
    
    // MOSTRAR ESTATÍSTICAS DETALHADAS
    echo "<h3>📈 Estatísticas recebidas:</h3>";
    if (!empty($statistics)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($statistics as $key => $value) {
            echo "<tr>";
            echo "<td style='padding: 5px; font-weight: bold;'>$key</td>";
            echo "<td style='padding: 5px;'>$value</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // TESTAR OS VALORES ESPECÍFICOS QUE APARECEM NA TELA
        echo "<h4>🎯 Valores específicos da tela:</h4>";
        echo "• Total de Lojas: <strong>" . ($statistics['total_lojas'] ?? 'UNDEFINED') . "</strong><br>";
        echo "• Lojas com Saldo Ativo: <strong>" . ($statistics['lojas_com_saldo'] ?? 'UNDEFINED') . "</strong><br>";
        echo "• Saldo Total Acumulado: <strong>R$ " . number_format($statistics['total_saldo_acumulado'] ?? 0, 2, ',', '.') . "</strong><br>";
        echo "• Saldo Total Usado: <strong>R$ " . number_format($statistics['total_saldo_usado'] ?? 0, 2, ',', '.') . "</strong><br><br>";
    } else {
        echo "❌ <strong>PROBLEMA:</strong> Array de estatísticas está vazio!<br><br>";
    }
    
    // MOSTRAR LOJAS DETALHADAS
    echo "<h3>🏪 Lojas recebidas:</h3>";
    if (!empty($stores)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>Clientes c/ Saldo</th><th>Total Saldo Clientes</th><th>Total Trans</th></tr>";
        
        foreach ($stores as $store) {
            $cor = ($store['total_saldo_clientes'] ?? 0) > 0 ? 'style="background: #e8f5e9;"' : '';
            echo "<tr $cor>";
            echo "<td>{$store['id']}</td>";
            echo "<td>{$store['nome_fantasia']}</td>";
            echo "<td>{$store['status']}</td>";
            echo "<td><strong>" . ($store['clientes_com_saldo'] ?? 'UNDEFINED') . "</strong></td>";
            echo "<td><strong>R$ " . number_format($store['total_saldo_clientes'] ?? 0, 2, ',', '.') . "</strong></td>";
            echo "<td>" . ($store['total_transacoes'] ?? 'UNDEFINED') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // TESTE ESPECÍFICO PARA A LOJA QUE DEVERIA TER SALDO
        echo "<h4>🔍 Verificação específica da Loja ID 34:</h4>";
        $loja34 = null;
        foreach ($stores as $store) {
            if ($store['id'] == 34) {
                $loja34 = $store;
                break;
            }
        }
        
        if ($loja34) {
            echo "✅ Loja 34 encontrada:<br>";
            echo "• Nome: {$loja34['nome_fantasia']}<br>";
            echo "• total_saldo_clientes: " . ($loja34['total_saldo_clientes'] ?? 'UNDEFINED') . "<br>";
            echo "• clientes_com_saldo: " . ($loja34['clientes_com_saldo'] ?? 'UNDEFINED') . "<br>";
            echo "• Condição (total_saldo_clientes > 0): " . (($loja34['total_saldo_clientes'] ?? 0) > 0 ? 'TRUE' : 'FALSE') . "<br>";
        } else {
            echo "❌ Loja 34 não encontrada no array!<br>";
        }
        
    } else {
        echo "❌ <strong>PROBLEMA:</strong> Array de lojas está vazio!<br><br>";
    }
    
    $hasError = false;
    $errorMessage = '';
    
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = $e->getMessage();
    
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
    echo "<strong>❌ EXCEÇÃO CAPTURADA:</strong> $errorMessage";
    echo "</div>";
}

// RESULTADO FINAL
echo "<hr><h3>🏁 Diagnóstico Final:</h3>";

if (!$hasError && !empty($statistics) && !empty($stores)) {
    echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ DADOS OK:</strong> O método está retornando dados corretos para a view.<br>";
    echo "Se a tela stores.php ainda mostra dados incorretos, pode ser:<br>";
    echo "1. Problema no cache do navegador<br>";
    echo "2. A tela está usando dados antigos<br>";
    echo "3. Erro na renderização HTML<br>";
    echo "4. JavaScript sobrescrevendo os dados<br>";
    echo "</div>";
} else {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ PROBLEMA IDENTIFICADO:</strong><br>";
    if ($hasError) {
        echo "• Erro: $errorMessage<br>";
    }
    if (empty($statistics)) {
        echo "• Estatísticas vazias<br>";
    }
    if (empty($stores)) {
        echo "• Array de lojas vazio<br>";
    }
    echo "</div>";
}
?>

<br><br>
<a href="views/admin/stores.php" style="background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Testar Tela Stores</a>
<br><br>
<a href="teste_admin_controller.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Voltar ao Teste Controller</a>