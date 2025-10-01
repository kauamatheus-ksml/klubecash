<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'utils/WhatsAppLogger.php';

// Verificar se está em modo admin (adicione sua verificação de autenticação aqui)
// if (!isAdmin()) { header('Location: /login'); exit; }

$filters = [
    'type' => $_GET['type'] ?? '',
    'phone' => $_GET['phone'] ?? '',
    'success' => $_GET['success'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$logs = WhatsAppLogger::getRecentLogs(100, $filters);
$stats = WhatsAppLogger::getStatistics('7 days');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor WhatsApp - Klube Cash</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filters form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .logs-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .simulation { color: #ffc107; }
        .message-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-simulation { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Monitor de Atividade WhatsApp - Klube Cash</h1>
            <p>Sistema de monitoramento em tempo real das notificações enviadas aos clientes</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_messages'] ?? 0 ?></div>
                <div class="stat-label">Total de Mensagens (7 dias)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['successful_messages'] ?? 0 ?></div>
                <div class="stat-label">Enviadas com Sucesso</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['failed_messages'] ?? 0 ?></div>
                <div class="stat-label">Falhas de Envio</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['unique_phones'] ?? 0 ?></div>
                <div class="stat-label">Clientes Únicos</div>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label>Tipo de Notificação</label>
                    <select name="type">
                        <option value="">Todos os tipos</option>
                        <option value="nova_transacao" <?= $filters['type'] === 'nova_transacao' ? 'selected' : '' ?>>Nova Transação</option>
                        <option value="cashback_liberado" <?= $filters['type'] === 'cashback_liberado' ? 'selected' : '' ?>>Cashback Liberado</option>
                        <option value="manual_send" <?= $filters['type'] === 'manual_send' ? 'selected' : '' ?>>Envio Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($filters['phone']) ?>" placeholder="Digite o número">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="success">
                        <option value="">Todos</option>
                        <option value="true" <?= $filters['success'] === 'true' ? 'selected' : '' ?>>Sucesso</option>
                        <option value="false" <?= $filters['success'] === 'false' ? 'selected' : '' ?>>Erro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data Inicial</label>
                    <input type="date" name="date_from" value="<?= $filters['date_from'] ?>">
                </div>
                <div class="form-group">
                    <label>Data Final</label>
                    <input type="date" name="date_to" value="<?= $filters['date_to'] ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Telefone</th>
                        <th>Prévia da Mensagem</th>
                        <th>Status</th>
                        <th>ID da Mensagem</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                Nenhuma atividade encontrada para os filtros selecionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?= ucfirst(str_replace('_', ' ', $log['type'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['phone']) ?></td>
                                <td class="message-preview" title="<?= htmlspecialchars($log['message_preview']) ?>">
                                    <?= htmlspecialchars($log['message_preview']) ?>
                                </td>
                                <td>
                                    <?php if ($log['success']): ?>
                                        <span class="badge badge-success">✓ Sucesso</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">✗ Erro</span>
                                    <?php endif; ?>
                                    <?php if ($log['simulation_mode']): ?>
                                        <span class="badge badge-simulation">Simulação</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['message_id'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($log['error_message']): ?>
                                        <span class="error" title="<?= htmlspecialchars($log['error_message']) ?>">
                                            Erro: <?= substr($log['error_message'], 0, 50) ?>...
                                        </span>
                                    <?php else: ?>
                                        <span class="success">OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos se não houver filtros ativos
        const hasFilters = <?= json_encode(array_filter($filters)) ?>;
        if (Object.keys(hasFilters).length === 0) {
            setTimeout(() => {
                window.location.reload();
            }, 30000);
        }
    </script>
</body>
</html>