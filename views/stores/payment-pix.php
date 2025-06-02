<?php
// views/stores/payment-pix.php
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

$paymentId = $_GET['payment_id'] ?? 0;
$storeId = $store['id'];

// Buscar dados do pagamento
$paymentStmt = $db->prepare("
    SELECT * FROM pagamentos_comissao 
    WHERE id = ? AND loja_id = ? AND status = 'pendente'
");
$paymentStmt->execute([$paymentId, $storeId]);
$payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL . '?error=pagamento_nao_encontrado');
    exit;
}

$activeMenu = 'payment-pix';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/stores/payment-pix.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <h1>Pagamento PIX Automático</h1>
            <p class="subtitle">Pague suas comissões via PIX e tenha aprovação automática</p>
        </div>
        
        <!-- Card do Pagamento -->
        <div class="card payment-card">
            <div class="card-header">
                <h2>Detalhes do Pagamento</h2>
            </div>
            
            <div class="payment-details">
                <div class="detail-row">
                    <span class="label">Valor Total:</span>
                    <span class="value">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Número de Transações:</span>
                    <span class="value" id="transactionCount">Carregando...</span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-badge status-pending">Aguardando Pagamento</span>
                </div>
            </div>
            
            <div class="pix-section" id="pixSection" style="display: none;">
                <h3>QR Code PIX</h3>
                <div class="qr-container">
                    <img id="qrCodeImage" src="" alt="QR Code PIX" style="max-width: 300px;">
                    <div class="qr-actions">
                        <button class="btn btn-secondary" onclick="copyPixCode()">Copiar Código PIX</button>
                        <button class="btn btn-primary" onclick="checkPaymentStatus()">Verificar Pagamento</button>
                    </div>
                </div>
                <input type="hidden" id="pixCode" value="">
                <input type="hidden" id="chargeId" value="">
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="generatePix()" id="generatePixBtn">
                    Gerar PIX
                </button>
                <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">
                    Voltar
                </a>
            </div>
        </div>
        
        <!-- Status do Pagamento -->
        <div class="card status-card">
            <div class="card-header">
                <h3>Status do Pagamento</h3>
            </div>
            <div class="status-timeline">
                <div class="timeline-item active" id="step1">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>PIX Gerado</h4>
                        <p>QR Code criado e aguardando pagamento</p>
                    </div>
                </div>
                <div class="timeline-item" id="step2">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Pagamento Confirmado</h4>
                        <p>PIX processado e valor confirmado</p>
                    </div>
                </div>
                <div class="timeline-item" id="step3">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Cashback Liberado</h4>
                        <p>Cashback automaticamente disponibilizado para os clientes</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informações -->
        <div class="card info-card">
            <div class="card-header">
                <h3>Como Funciona o PIX Automático</h3>
            </div>
            <div class="info-content">
                <ul>
                    <li>✅ <strong>Gere o PIX:</strong> Clique em "Gerar PIX" para criar o QR Code</li>
                    <li>📱 <strong>Pague pelo App:</strong> Use qualquer app bancário para pagar</li>
                    <li>⚡ <strong>Aprovação Automática:</strong> Em até 2 minutos após o pagamento</li>
                    <li>🎉 <strong>Cashback Liberado:</strong> Clientes recebem notificação automática</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        const paymentId = <?php echo $paymentId; ?>;
        let pollingInterval = null;
        
        // Gerar PIX
        async function generatePix() {
            const btn = document.getElementById('generatePixBtn');
            btn.disabled = true;
            btn.textContent = 'Gerando PIX...';
            
            try {
                const response = await fetch('<?php echo OPENPIX_CREATE_CHARGE_URL; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        transaction_ids: [] // Será preenchido pelo backend
                    })
                });
                
                const result = await response.json();
                
                if (result.status) {
                    // Mostrar QR Code
                    document.getElementById('qrCodeImage').src = result.data.qr_code_image;
                    document.getElementById('pixCode').value = result.data.qr_code;
                    document.getElementById('chargeId').value = result.data.charge_id;
                    document.getElementById('pixSection').style.display = 'block';
                    
                    // Iniciar polling para verificar pagamento
                    startPaymentPolling();
                    
                    // Atualizar status
                    updateTimelineStep(1);
                    
                    btn.style.display = 'none';
                } else {
                    alert('Erro ao gerar PIX: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Gerar PIX';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro de conexão');
                btn.disabled = false;
                btn.textContent = 'Gerar PIX';
            }
        }
        
        // Copiar código PIX
        function copyPixCode() {
            const pixCode = document.getElementById('pixCode').value;
            navigator.clipboard.writeText(pixCode).then(() => {
                alert('Código PIX copiado!');
            });
        }
        
        // Verificar status do pagamento
        async function checkPaymentStatus() {
            const chargeId = document.getElementById('chargeId').value;
            
            if (!chargeId) {
                alert('PIX não foi gerado ainda');
                return;
            }
            
            try {
                const response = await fetch(`<?php echo OPENPIX_CHECK_STATUS_URL; ?>&charge_id=${chargeId}`);
                const result = await response.json();
                
                if (result.status && result.data.charge.status === 'COMPLETED') {
                    handlePaymentCompleted();
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
            }
        }
        
        // Iniciar polling automático
        function startPaymentPolling() {
            pollingInterval = setInterval(() => {
                checkPaymentStatus();
            }, 10000); // Verificar a cada 10 segundos
        }
        
        // Quando pagamento for confirmado
        function handlePaymentCompleted() {
            clearInterval(pollingInterval);
            
            // Atualizar timeline
            updateTimelineStep(2);
            setTimeout(() => updateTimelineStep(3), 2000);
            
            // Mostrar mensagem de sucesso
            document.querySelector('.status-badge').textContent = 'Pago';
            document.querySelector('.status-badge').className = 'value status-badge status-success';
            
            // Notificação
            alert('✅ Pagamento confirmado! O cashback foi liberado automaticamente para os clientes.');
            
            // Redirecionar após 3 segundos
            setTimeout(() => {
                window.location.href = '<?php echo STORE_PAYMENT_HISTORY_URL; ?>';
            }, 3000);
        }
        
        // Atualizar timeline
        function updateTimelineStep(step) {
            for (let i = 1; i <= step; i++) {
                document.getElementById(`step${i}`).classList.add('active');
            }
        }
        
        // Buscar quantidade de transações
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const response = await fetch(`../../api/payments.php?action=details&id=${paymentId}`);
                const result = await response.json();
                
                if (result.status) {
                    document.getElementById('transactionCount').textContent = result.data.transacoes.length;
                }
            } catch (error) {
                console.error('Erro ao buscar detalhes:', error);
            }
        });
    </script>
</body>
</html>