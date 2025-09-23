<?php
require_once '../../config/auth_check.php';
require_once '../../config/database.php';

if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    header('Location: /login');
    exit;
}

$pageTitle = 'Logs de Webhook e WhatsApp';

// Buscar logs N8N
$db = Database::getConnection();

$filterPeriod = $_GET['period'] ?? '24h';
$filterType = $_GET['type'] ?? 'all';

$whereConditions = ['1=1'];
$params = [];

// Filtro de período
switch ($filterPeriod) {
    case '1h':
        $whereConditions[] = 'created_at >= NOW() - INTERVAL 1 HOUR';
        break;
    case '24h':
        $whereConditions[] = 'created_at >= NOW() - INTERVAL 24 HOUR';
        break;
    case '7d':
        $whereConditions[] = 'created_at >= NOW() - INTERVAL 7 DAY';
        break;
    case '30d':
        $whereConditions[] = 'created_at >= NOW() - INTERVAL 30 DAY';
        break;
}

// Filtro de tipo
if ($filterType !== 'all') {
    $whereConditions[] = 'event_type = ?';
    $params[] = $filterType;
}

$whereClause = implode(' AND ', $whereConditions);

// Buscar logs N8N
$n8nStmt = $db->prepare("
    SELECT 
        id, transaction_id, event_type, success, 
        LEFT(response, 200) as response_preview,
        created_at
    FROM n8n_webhook_logs 
    WHERE {$whereClause}
    ORDER BY created_at DESC 
    LIMIT 100
");
$n8nStmt->execute($params);
$n8nLogs = $n8nStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar logs Evolution
$evolutionStmt = $db->prepare("
    SELECT 
        id, phone, success, event_type,
        LEFT(message, 100) as message_preview,
        transaction_id, created_at
    FROM whatsapp_evolution_logs 
    WHERE {$whereClause}
    ORDER BY created_at DESC 
    LIMIT 100
");
$evolutionStmt->execute($params);
$evolutionLogs = $evolutionStmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_n8n,
        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_n8n
    FROM n8n_webhook_logs 
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
");
$statsStmt->execute();
$n8nStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$statsEvolutionStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_evolution,
        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_evolution
    FROM whatsapp_evolution_logs 
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
");
$statsEvolutionStmt->execute();
$evolutionStats = $statsEvolutionStmt->fetch(PDO::FETCH_ASSOC);

include '../../views/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../views/components/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/api/evolution-test" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-vial"></i> Testar Evolution API
                    </a>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">N8N Webhook (24h)</h5>
                            <div class="row">
                                <div class="col-6">
                                    <h3 class="text-primary"><?= $n8nStats['total_n8n'] ?></h3>
                                    <small>Total</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-success"><?= $n8nStats['success_n8n'] ?></h3>
                                    <small>Sucessos</small>
                                </div>
                            </div>
                            <div class="progress mt-2">
                                <?php 
                                $n8nSuccessRate = $n8nStats['total_n8n'] > 0 ? 
                                    ($n8nStats['success_n8n'] / $n8nStats['total_n8n']) * 100 : 0; 
                                ?>
                                <div class="progress-bar bg-success" style="width: <?= $n8nSuccessRate ?>%">
                                    <?= number_format($n8nSuccessRate, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Evolution API (24h)</h5>
                            <div class="row">
                                <div class="col-6">
                                    <h3 class="text-primary"><?= $evolutionStats['total_evolution'] ?></h3>
                                    <small>Total</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-success"><?= $evolutionStats['success_evolution'] ?></h3>
                                    <small>Sucessos</small>
                                </div>
                            </div>
                            <div class="progress mt-2">
                                <?php 
                                $evolutionSuccessRate = $evolutionStats['total_evolution'] > 0 ? 
                                    ($evolutionStats['success_evolution'] / $evolutionStats['total_evolution']) * 100 : 0; 
                                ?>
                                <div class="progress-bar bg-info" style="width: <?= $evolutionSuccessRate ?>%">
                                    <?= number_format($evolutionSuccessRate, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Período</label>
                            <select name="period" class="form-select">
                                <option value="1h" <?= $filterPeriod === '1h' ? 'selected' : '' ?>>Última hora</option>
                                <option value="24h" <?= $filterPeriod === '24h' ? 'selected' : '' ?>>Últimas 24 horas</option>
                                <option value="7d" <?= $filterPeriod === '7d' ? 'selected' : '' ?>>Últimos 7 dias</option>
                                <option value="30d" <?= $filterPeriod === '30d' ? 'selected' : '' ?>>Últimos 30 dias</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Evento</label>
                            <select name="type" class="form-select">
                                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Todos</option>
                                <option value="nova_transacao" <?= $filterType === 'nova_transacao' ? 'selected' : '' ?>>Nova Transação</option>
                                <option value="cashback_liberado" <?= $filterType === 'cashback_liberado' ? 'selected' : '' ?>>Cashback Liberado</option>
                                <option value="test_connection" <?= $filterType === 'test_connection' ? 'selected' : '' ?>>Teste de Conexão</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs N8N -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Logs N8N Webhook</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Transação</th>
                                    <th>Evento</th>
                                    <th>Status</th>
                                    <th>Resposta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($n8nLogs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['transaction_id']): ?>
                                            <a href="/admin/transacao/<?= $log['transaction_id'] ?>" target="_blank">
                                                #<?= $log['transaction_id'] ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $log['event_type'] === 'nova_transacao' ? 'primary' : 'success' ?>">
                                            <?= $log['event_type'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $log['success'] ? 'success' : 'danger' ?>">
                                            <?= $log['success'] ? 'Sucesso' : 'Falha' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['response_preview']) ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($n8nLogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum log encontrado</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Logs Evolution API -->
            <div class="card">
                <div class="card-header">
                    <h5>Logs Evolution API WhatsApp</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Telefone</th>
                                    <th>Transação</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evolutionLogs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($log['phone']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['transaction_id']): ?>
                                            <a href="/admin/transacao/<?= $log['transaction_id'] ?>" target="_blank">
                                                #<?= $log['transaction_id'] ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $log['event_type'] ?? 'direct' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $log['success'] ? 'success' : 'danger' ?>">
                                            <?= $log['success'] ? 'Enviado' : 'Falha' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['message_preview']) ?>...
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($evolutionLogs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Nenhum log encontrado</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../views/components/footer.php'; ?>