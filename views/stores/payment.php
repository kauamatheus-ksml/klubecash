<?php
// views/stores/payment.php

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

// Verificar autenticação
AuthController::checkAuth();
if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

$paymentId = $_GET['id'] ?? 0;

if (!$paymentId) {
    header('Location: ' . SITE_URL . '/store/transacoes-pendentes');
    exit;
}

$db = Database::getConnection();

// Buscar dados do pagamento
$stmt = $db->prepare("
    SELECT p.*, l.nome_fantasia 
    FROM pagamentos_comissao p
    JOIN lojas l ON p.loja_id = l.id
    WHERE p.id = ? AND l.usuario_id = ?
");
$stmt->execute([$paymentId, $_SESSION['user_id']]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: ' . SITE_URL . '/store/transacoes-pendentes');
    exit;
}

// Buscar transações do pagamento
$stmtTrans = $db->prepare("
    SELECT t.*, pt.transacao_id
    FROM pagamento_transacoes pt
    JOIN transacoes_cashback t ON pt.transacao_id = t.id
    WHERE pt.pagamento_id = ?
");
$stmtTrans->execute([$paymentId]);
$transactions = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Pagamento de Comissão';
require_once '../components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Pagamento de Comissão #<?php echo $paymentId; ?></h1>
                <a href="<?php echo SITE_URL; ?>/store/transacoes-pendentes" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>

            <?php if ($payment['status'] === 'pendente'): ?>
                <!-- Área de Pagamento PIX -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-qr-code"></i> Pagamento via PIX
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="pix-area" class="text-center">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">Gerando PIX...</span>
                                    </div>
                                    <p>Gerando código PIX...</p>
                                </div>
                                
                                <div id="pix-info" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 text-center">
                                            <h6>QR Code PIX</h6>
                                            <div id="qr-code-image" class="mb-3"></div>
                                            <button class="btn btn-sm btn-outline-primary" onclick="copyPixCode()">
                                                <i class="bi bi-clipboard"></i> Copiar código PIX
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Instruções de Pagamento</h6>
                                            <ol class="text-start">
                                                <li>Abra o app do seu banco</li>
                                                <li>Escolha a opção PIX</li>
                                                <li>Escaneie o QR Code ou copie o código</li>
                                                <li>Confirme o pagamento de <strong>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></strong></li>
                                                <li>Aguarde a confirmação automática</li>
                                            </ol>
                                            
                                            <div class="alert alert-info mt-3">
                                                <i class="bi bi-info-circle"></i>
                                                O pagamento será confirmado automaticamente em alguns segundos após a transação.
                                            </div>
                                            
                                            <div id="pix-expiration" class="alert alert-warning mt-3" style="display: none;">
                                                <i class="bi bi-clock"></i>
                                                <span id="expiration-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Código PIX para cópia -->
                                    <div class="mt-4">
                                        <label class="form-label">Código PIX Copia e Cola:</label>
                                        <div class="input-group">
                                            <input type="text" id="pix-code" class="form-control" readonly>
                                            <button class="btn btn-primary" onclick="copyPixCode()">
                                                <i class="bi bi-clipboard"></i> Copiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status do pagamento -->
                                <div id="payment-status" class="mt-4" style="display: none;">
                                    <div class="alert alert-success">
                                        <h5><i class="bi bi-check-circle"></i> Pagamento Confirmado!</h5>
                                        <p>Seu pagamento foi processado com sucesso. O cashback será liberado para os clientes.</p>
                                        <a href="<?php echo SITE_URL; ?>/store/historico-pagamentos" class="btn btn-success">
                                            Ver Histórico de Pagamentos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Resumo do Pagamento -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Resumo do Pagamento</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Número de transações:</td>
                                        <td class="text-end"><strong><?php echo count($transactions); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Valor total das vendas:</td>
                                        <td class="text-end">
                                            R$ <?php 
                                            $totalVendas = array_sum(array_column($transactions, 'valor_total'));
                                            echo number_format($totalVendas, 2, ',', '.'); 
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Comissão (10%):</td>
                                        <td class="text-end">
                                            <strong class="text-danger">
                                                R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?>
                                            </strong>
                                        </td>
                                    </tr>
                                </table>
                                
                                <hr>
                                
                                <h6>Transações incluídas:</h6>
                                <div class="small" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($transactions as $trans): ?>
                                        <div class="mb-2 p-2 border rounded">
                                            <div>ID: #<?php echo $trans['id']; ?></div>
                                            <div>Valor: R$ <?php echo number_format($trans['valor_total'], 2, ',', '.'); ?></div>
                                            <div>Data: <?php echo date('d/m/Y', strtotime($trans['data_transacao'])); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($payment['status'] === 'aprovado'): ?>
                <!-- Pagamento já aprovado -->
                <div class="alert alert-success">
                    <h4><i class="bi bi-check-circle"></i> Pagamento Aprovado</h4>
                    <p>Este pagamento já foi processado e aprovado em <?php echo date('d/m/Y H:i', strtotime($payment['data_aprovacao'])); ?></p>
                    <a href="<?php echo SITE_URL; ?>/store/historico-pagamentos" class="btn btn-primary">
                        Ver Histórico de Pagamentos
                    </a>
                </div>
            <?php else: ?>
                <!-- Outros status -->
                <div class="alert alert-warning">
                    <h4><i class="bi bi-exclamation-triangle"></i> Status: <?php echo ucfirst($payment['status']); ?></h4>
                    <p>Entre em contato com o suporte se tiver dúvidas sobre este pagamento.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Variáveis globais
let pixChargeId = null;
let checkStatusInterval = null;

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($payment['status'] === 'pendente'): ?>
        generatePixPayment();
    <?php endif; ?>
});

// Gerar pagamento PIX
function generatePixPayment() {
    fetch('<?php echo SITE_URL; ?>/api/store-payment?action=create_pix', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'payment_id=<?php echo $paymentId; ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            displayPixInfo(data.data);
            startStatusCheck(data.data.charge_id);
        } else {
            showError(data.message || 'Erro ao gerar PIX');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showError('Erro ao processar requisição');
    });
}

// Exibir informações do PIX
function displayPixInfo(pixData) {
    document.getElementById('pix-area').style.display = 'none';
    document.getElementById('pix-info').style.display = 'block';
    
    // QR Code
    document.getElementById('qr-code-image').innerHTML = 
        `<img src="${pixData.qr_code_image}" alt="QR Code PIX" class="img-fluid" style="max-width: 300px;">`;
    
    // Código copia e cola
    document.getElementById('pix-code').value = pixData.qr_code;
    
    // Expiração
    if (pixData.expires_at) {
        const expirationDate = new Date(pixData.expires_at);
        document.getElementById('pix-expiration').style.display = 'block';
        document.getElementById('expiration-text').textContent = 
            `Este código expira em ${expirationDate.toLocaleString('pt-BR')}`;
    }
    
    pixChargeId = pixData.charge_id;
}

// Copiar código PIX
function copyPixCode() {
    const pixCode = document.getElementById('pix-code');
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(pixCode.value).then(() => {
        // Feedback visual
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copiado!';
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
        }, 2000);
    });
}

// Verificar status do pagamento
function startStatusCheck(chargeId) {
    // Verificar a cada 3 segundos
    checkStatusInterval = setInterval(() => {
        checkPaymentStatus(chargeId);
    }, 3000);
    
    // Parar após 10 minutos
    setTimeout(() => {
        if (checkStatusInterval) {
            clearInterval(checkStatusInterval);
        }
    }, 600000);
}

function checkPaymentStatus(chargeId) {
    fetch(`<?php echo SITE_URL; ?>/api/store-payment?action=check_status&charge_id=${chargeId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status && data.paid) {
            // Pagamento confirmado!
            clearInterval(checkStatusInterval);
            showPaymentSuccess();
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status:', error);
    });
}

// Exibir sucesso do pagamento
function showPaymentSuccess() {
    document.getElementById('pix-info').style.display = 'none';
    document.getElementById('payment-status').style.display = 'block';
    
    // Recarregar a página após 5 segundos
    setTimeout(() => {
        window.location.reload();
    }, 5000);
}

// Exibir erro
function showError(message) {
    document.getElementById('pix-area').innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> ${message}
        </div>
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Tentar Novamente
        </button>
    `;
}
</script>

<?php require_once '../components/footer.php'; ?>