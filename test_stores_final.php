<?php
// test_stores_final.php - Teste final da tela

$activeMenu = 'lojas';
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/AdminController.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    die('Não autorizado');
}

// Parâmetros
$page = 1;
$filters = [];

// Carregar dados
$result = AdminController::manageStoresWithBalance($filters, $page);
$stores = $result['data']['lojas'] ?? [];
$statistics = $result['data']['estatisticas'] ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Final - Lojas</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .stat-card { background: #f0f0f0; padding: 15px; margin: 10px; border-radius: 5px; display: inline-block; }
        .store-row { padding: 10px; border-bottom: 1px solid #ddd; }
        .saldo-ok { background: #e8f5e9; }
    </style>
</head>
<body>
    <h1>🧪 Teste Final - Tela de Lojas</h1>
    
    <h2>📊 Estatísticas (devem estar corretas):</h2>
    <div class="stat-card">
        <strong>Total de Lojas:</strong><br>
        <?php echo $statistics['total_lojas'] ?? 'ERRO'; ?>
    </div>
    <div class="stat-card">
        <strong>Lojas com Saldo Ativo:</strong><br>
        <?php echo $statistics['lojas_com_saldo'] ?? 'ERRO'; ?>
    </div>
    <div class="stat-card">
        <strong>Saldo Total Acumulado:</strong><br>
        R$ <?php echo number_format($statistics['total_saldo_acumulado'] ?? 0, 2, ',', '.'); ?>
    </div>
    <div class="stat-card">
        <strong>Saldo Total Usado:</strong><br>
        R$ <?php echo number_format($statistics['total_saldo_usado'] ?? 0, 2, ',', '.'); ?>
    </div>
    
    <h2>🏪 Lista de Lojas:</h2>
    <?php foreach ($stores as $store): ?>
        <div class="store-row <?php echo ($store['total_saldo_clientes'] > 0) ? 'saldo-ok' : ''; ?>">
            <strong><?php echo htmlspecialchars($store['nome_fantasia']); ?></strong>
            (ID: <?php echo $store['id']; ?>) - 
            Status: <?php echo $store['status']; ?><br>
            
            <strong>Saldo de Clientes:</strong>
            <?php if ($store['total_saldo_clientes'] > 0): ?>
                R$ <?php echo number_format($store['total_saldo_clientes'], 2, ',', '.'); ?>
                (<?php echo $store['clientes_com_saldo']; ?> cliente<?php echo $store['clientes_com_saldo'] != 1 ? 's' : ''; ?>)
            <?php else: ?>
                Sem saldo
            <?php endif; ?>
            
            <br><strong>Taxa de Uso:</strong>
            <?php if ($store['total_transacoes'] > 0): ?>
                <?php echo number_format(($store['transacoes_com_saldo'] / $store['total_transacoes']) * 100, 1); ?>%
                (<?php echo $store['transacoes_com_saldo']; ?>/<?php echo $store['total_transacoes']; ?> transações)
            <?php else: ?>
                0%
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <br><br>
    <strong>✅ Se estes dados estão corretos, a correção funcionou!</strong><br>
    <strong>❌ Se ainda estão errados, há outro problema.</strong>
    
    <br><br>
    <a href="views/admin/stores.php?v=<?php echo time(); ?>" style="background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none;">
        → Testar Stores.php Corrigido
    </a>
</body>
</html>