<?php
// views/stores/pending-commissions.php
// ATUALIZADO: Transações pendentes ficam visíveis até aprovação
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

session_start();

if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = AuthController::getCurrentUserId();
$db = Database::getConnection();

// Obter dados da loja
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = ?");
$storeQuery->execute([$userId]);
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header('Location: ' . LOGIN_URL . '?error=loja_nao_encontrada');
    exit;
}

$storeId = $store['id'];

// IMPORTANTE: Buscar TODAS as transações pendentes (incluindo pagamento_pendente)
$pendingQuery = $db->prepare("
    SELECT t.*, u.nome as cliente_nome, u.email as cliente_email,
           p.id as payment_id, p.valor_total as payment_total, p.status as payment_status,
           p.mp_payment_id, p.mp_status, p.data_registro as payment_date
    FROM transacoes_cashback t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    LEFT JOIN transacoes_comissao tc ON t.id = tc.transacao_id AND tc.tipo = 'admin'
    LEFT JOIN pagamentos_comissao p ON t.id IN (
        SELECT JSON_EXTRACT(transacoes_incluidas, '$.transaction_ids[*]')
        FROM pagamentos_comissao 
        WHERE loja_id = ? AND status IN ('pendente', 'pix_aguardando', 'pix_expirado')
    )
    WHERE t.loja_id = ? 
    AND t.status IN ('pendente', 'pagamento_pendente')
    ORDER BY t.data_transacao DESC
");
$pendingQuery->execute([$storeId, $storeId]);
$pendingTransactions = $pendingQuery->fetchAll(PDO::FETCH_ASSOC);

// Buscar pagamentos pendentes agrupados
$paymentsQuery = $db->prepare("
    SELECT p.*, COUNT(DISTINCT t.id) as transactions_count
    FROM pagamentos_comissao p
    LEFT JOIN transacoes_cashback t ON FIND_IN_SET(t.id, REPLACE(REPLACE(JSON_EXTRACT(p.transacoes_incluidas, '$.transaction_ids'), '[', ''), ']', ''))
    WHERE p.loja_id = ? 
    AND p.status IN ('pendente', 'pix_aguardando', 'pix_expirado')
    GROUP BY p.id
    ORDER BY p.data_registro DESC
");
$paymentsQuery->execute([$storeId]);
$pendingPayments = $paymentsQuery->fetchAll(PDO::FETCH_ASSOC);

$activeMenu = 'pending-commissions';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações Pendentes - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/stores/pending-commissions.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    Transações Pendentes de Pagamento
                </h1>
                <p>Gerencie suas comissões pendentes - Transações ficam visíveis até aprovação</p>
            </div>
            
            <?php if (!empty($pendingTransactions)): ?>
            <div class="quick-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($pendingTransactions); ?></span>
                    <span class="stat-label">Transações Pendentes</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">R$ <?php echo number_format(array_sum(array_column($pendingTransactions, 'valor_cashback_admin')), 2, ',', '.'); ?></span>
                    <span class="stat-label">Total a Pagar</span>
                </div>
                <div class="stat-card warning">
                    <span class="stat-number"><?php echo count($pendingPayments); ?></span>
                    <span class="stat-label">Pagamentos PIX Pendentes</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- NOVO: Seção de Pagamentos Pendentes (Fica sempre visível) -->
        <?php if (!empty($pendingPayments)): ?>
        <div class="pending-payments-section">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                Pagamentos PIX Aguardando Confirmação
            </h2>
            <p class="section-description">
                💡 <strong>Importante:</strong> Estes pagamentos permanecem visíveis até que sejam confirmados. 
                Você pode gerar um novo PIX clicando em "Pagar PIX" novamente.
            </p>
            
            <div class="payments-grid">
                <?php foreach ($pendingPayments as $payment): ?>
                <div class="payment-card <?php echo $payment['status']; ?>">
                    <div class="payment-header">
                        <div class="payment-info">
                            <h3>Pagamento #<?php echo $payment['id']; ?></h3>
                            <span class="payment-status <?php echo $payment['status']; ?>">
                                <?php
                                switch($payment['status']) {
                                    case 'pix_aguardando': echo '⏳ PIX Aguardando'; break;
                                    case 'pix_expirado': echo '⏰ PIX Expirado'; break;
                                    case 'pendente': echo '📋 Pendente'; break;
                                    default: echo ucfirst($payment['status']);
                                }
                                ?>
                            </span>
                        </div>
                        <div class="payment-amount">
                            <span class="amount">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="payment-details">
                        <div class="detail-row">
                            <span>Transações incluídas:</span>
                            <span><?php echo $payment['transactions_count']; ?> transação(ões)</span>
                        </div>
                        <div class="detail-row">
                            <span>Data do pagamento:</span>
                            <span><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></span>
                        </div>
                        <?php if (!empty($payment['mp_payment_id'])): ?>
                        <div class="detail-row">
                            <span>ID Mercado Pago:</span>
                            <span class="mp-id"><?php echo $payment['mp_payment_id']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="payment-actions">
                        <!-- BOTÃO PRINCIPAL: Sempre disponível para gerar novo PIX -->
                        <a href="<?php echo STORE_PAYMENT_PIX_URL; ?>?payment_id=<?php echo $payment['id']; ?>" 
                           class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                            <?php echo ($payment['status'] === 'pix_expirado') ? 'Gerar Novo PIX' : 'Pagar PIX'; ?>
                        </a>
                        
                        <!-- Botão de verificar status (se já tem PIX) -->
                        <?php if (!empty($payment['mp_payment_id'])): ?>
                        <button onclick="checkPaymentStatus(<?php echo $payment['id']; ?>, '<?php echo $payment['mp_payment_id']; ?>')" 
                                class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"/>
                                <polyline points="1 20 1 14 7 14"/>
                                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                            </svg>
                            Verificar Status
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transações individuais pendentes -->
        <div class="transactions-section">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Transações Aguardando Pagamento de Comissão
            </h2>
            
            <?php if (!empty($pendingTransactions)): ?>
            <div class="table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Valor da Venda</th>
                            <th>Comissão (10%)</th>
                            <th>Cashback Cliente</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th class="text-center">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                Selecionar
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingTransactions as $transaction): ?>
                        <tr class="transaction-row">
                            <td>
                                <div class="client-info">
                                    <span class="client-name"><?php echo htmlspecialchars($transaction['cliente_nome']); ?></span>
                                    <span class="client-email"><?php echo htmlspecialchars($transaction['cliente_email']); ?></span>
                                </div>
                            </td>
                            <td class="amount">R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                            <td class="commission">R$ <?php echo number_format($transaction['valor_cashback_admin'], 2, ',', '.'); ?></td>
                            <td class="cashback">R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                            <td class="date"><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $transaction['status']; ?>">
                                    <?php
                                    switch($transaction['status']) {
                                        case 'pendente': echo '⏳ Pendente'; break;
                                        case 'pagamento_pendente': echo '💳 Aguardando Pagamento'; break;
                                        default: echo ucfirst($transaction['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" 
                                       name="selected_transactions[]" 
                                       value="<?php echo $transaction['id']; ?>"
                                       data-commission="<?php echo $transaction['valor_cashback_admin']; ?>"
                                       onchange="updateSelectedTotal()">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="bulk-actions">
                <div class="selected-info">
                    <span id="selectedCount">0</span> transação(ões) selecionada(s) - 
                    Total: R$ <span id="selectedTotal">0,00</span>
                </div>
                <button onclick="paySelectedTransactions()" class="btn btn-primary" id="payButton" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Pagar Comissões Selecionadas via PIX
                </button>
            </div>
            
            <?php else: ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
                <h3>Nenhuma transação pendente!</h3>
                <p>Todas as suas comissões estão em dia. Continue vendendo para gerar mais cashback!</p>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-primary">
                    Registrar Nova Venda
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- NOVO: Informações importantes sobre o processo -->
        <div class="info-section">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                Como funciona o pagamento de comissões
            </h3>
            <div class="info-grid">
                <div class="info-card">
                    <span class="info-number">1</span>
                    <div>
                        <h4>Selecione as transações</h4>
                        <p>Escolha quais transações quer pagar de uma vez. Pode selecionar todas ou algumas específicas.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-number">2</span>
                    <div>
                        <h4>Gere um PIX novo</h4>
                        <p><strong>Cada clique gera um PIX novo</strong> - isso garante que você sempre tenha um pagamento válido.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-number">3</span>
                    <div>
                        <h4>Transações ficam visíveis</h4>
                        <p><strong>Transações pendentes não somem</strong> até serem aprovadas - você pode acompanhar o status.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-number">4</span>
                    <div>
                        <h4>Aprovação automática</h4>
                        <p>Assim que o PIX for confirmado, o cashback é liberado automaticamente para seus clientes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para verificar status de pagamento PIX
        async function checkPaymentStatus(paymentId, mpPaymentId) {
            try {
                const response = await fetch(`../../api/payment-status.php?payment_id=${paymentId}&mp_payment_id=${mpPaymentId}`);
                const result = await response.json();
                
                if (result.status && result.data.status === 'approved') {
                    alert('✅ Pagamento PIX confirmado! A página será recarregada.');
                    window.location.reload();
                } else {
                    alert(`ℹ️ Status atual: ${result.data ? result.data.status : 'Desconhecido'}`);
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
                alert('Erro ao verificar status. Tente novamente.');
            }
        }

        // Funções existentes para seleção múltipla
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedTotal();
        }

        function updateSelectedTotal() {
            const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
            const selectedCount = checkboxes.length;
            let total = 0;
            
            checkboxes.forEach(checkbox => {
                total += parseFloat(checkbox.dataset.commission);
            });
            
            document.getElementById('selectedCount').textContent = selectedCount;
            document.getElementById('selectedTotal').textContent = total.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            document.getElementById('payButton').disabled = selectedCount === 0;
        }

        function paySelectedTransactions() {
            const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
            
            if (checkboxes.length === 0) {
                alert('Selecione pelo menos uma transação para pagar.');
                return;
            }
            
            const transactionIds = Array.from(checkboxes).map(cb => cb.value);
            
            // Redirecionar para API de criação de pagamento
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../api/store-payment.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'transaction_ids';
            input.value = JSON.stringify(transactionIds);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'create_payment';
            
            form.appendChild(input);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-refresh para verificar status dos pagamentos PIX
        if (<?php echo count($pendingPayments); ?> > 0) {
            setInterval(() => {
                // Verificar silenciosamente se algum pagamento foi aprovado
                fetch('../../api/payment-status.php?check_all=1&store_id=<?php echo $storeId; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_updates) {
                            // Mostrar notificação e recarregar página
                            alert('✅ Novo pagamento aprovado! A página será recarregada.');
                            window.location.reload();
                        }
                    })
                    .catch(error => console.log('Status check error:', error));
            }, <?php echo PIX_AUTO_REFRESH_SECONDS * 1000; ?>); // Converter para milissegundos
        }
    </script>
</body>
</html>