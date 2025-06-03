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
    WHERE id = ? AND loja_id = ? AND status IN ('pendente', 'pix_aguardando')
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
            <h1>Pagamento PIX via Mercado Pago</h1>
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
                <h3>QR Code PIX - Mercado Pago</h3>
                <div class="qr-container">
                    <img id="qrCodeImage" src="" alt="QR Code PIX" style="display: none; max-width: 300px;">
                    <div class="qr-code-text">
                        <label for="pixCode">Código PIX:</label>
                        <textarea id="pixCode" readonly style="width: 100%; height: 80px; font-family: monospace; font-size: 12px;"></textarea>
                    </div>
                    <div class="qr-actions">
                        <button class="btn btn-secondary" onclick="copyPixCode()">Copiar Código PIX</button>
                        <button class="btn btn-primary" onclick="checkPaymentStatus()">Verificar Pagamento</button>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="generatePix()" id="generatePixBtn">
                    Gerar PIX via Mercado Pago
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
                        <p>QR Code criado via Mercado Pago</p>
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
                <h3>Como Funciona o PIX via Mercado Pago</h3>
            </div>
            <div class="info-content">
                <ul>
                    <li>✅ <strong>Gere o PIX:</strong> Clique em "Gerar PIX" para criar o QR Code via Mercado Pago</li>
                    <li>📱 <strong>Pague pelo App:</strong> Use qualquer app bancário para pagar</li>
                    <li>⚡ <strong>Aprovação Automática:</strong> Em até 2 minutos após o pagamento</li>
                    <li>🎉 <strong>Cashback Liberado:</strong> Clientes recebem notificação automática</li>
                    <li>🔒 <strong>Segurança MP:</strong> Transação protegida pelo Mercado Pago</li>
                </ul>
            </div>
        </div>
    </div>
    
    <input type="hidden" id="mpPaymentId" value="">
    
    <script>
        const paymentId = <?php echo $paymentId; ?>;
        let pollingInterval = null;
        
        // Gerar PIX via Mercado Pago
        async function generatePix() {
            const btn = document.getElementById('generatePixBtn');
            btn.disabled = true;
            btn.textContent = 'Gerando PIX...';
            
            console.log('Iniciando geração PIX para payment_id:', paymentId);
            
            try {
                const response = await fetch('<?php echo MP_CREATE_PAYMENT_URL; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_id: paymentId
                    })
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Erro ao fazer parse da resposta:', e);
                    alert('Erro: Resposta inválida do servidor');
                    btn.disabled = false;
                    btn.textContent = 'Gerar PIX via Mercado Pago';
                    return;
                }
                
                console.log('Parsed result:', result);
                
                if (result.status) {
                    // Exibir QR Code
                    const qrImg = document.getElementById('qrCodeImage');
                    qrImg.src = 'data:image/png;base64,' + result.data.qr_code_base64;
                    qrImg.style.display = 'block';
                    
                    document.getElementById('pixCode').value = result.data.qr_code;
                    document.getElementById('mpPaymentId').value = result.data.mp_payment_id;
                    document.getElementById('pixSection').style.display = 'block';
                    
                    updateTimelineStep(1);
                    btn.style.display = 'none';
                    
                    // Iniciar polling automático
                    startPaymentPolling();
                    
                } else {
                    console.error('Erro na API:', result);
                    alert('Erro ao gerar PIX: ' + result.message + (result.details ? '\n\nDetalhes: ' + JSON.stringify(result.details) : ''));
                    btn.disabled = false;
                    btn.textContent = 'Gerar PIX via Mercado Pago';
                }
                
            } catch (error) {
                console.error('Erro de conexão:', error);
                alert('Erro de conexão: ' + error.message);
                btn.disabled = false;
                btn.textContent = 'Gerar PIX via Mercado Pago';
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
            const mpPaymentId = document.getElementById('mpPaymentId').value;
            
            if (!mpPaymentId) {
                alert('PIX não foi gerado ainda');
                return;
            }
            
            try {
                const response = await fetch(`<?php echo MP_CHECK_STATUS_URL; ?>&mp_payment_id=${mpPaymentId}`);
                const result = await response.json();
                
                if (result.status && result.data.status === 'approved') {
                    handlePaymentCompleted();
                } else if (result.data.status === 'rejected') {
                    clearInterval(pollingInterval);
                    alert('❌ Pagamento foi rejeitado. Tente novamente.');
                    window.location.reload();
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
            
            updateTimelineStep(2);
            setTimeout(() => updateTimelineStep(3), 2000);
            
            document.querySelector('.status-badge').textContent = 'Pago via PIX';
            document.querySelector('.status-badge').className = 'value status-badge status-success';
            
            alert('✅ Pagamento PIX confirmado! O cashback foi liberado para os clientes.');
            
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
                const response = await fetch(`../../controllers/TransactionController.php?action=payment_details&payment_id=${paymentId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'payment_id=' + paymentId
                });
                const result = await response.json();
                
                if (result.status) {
                    document.getElementById('transactionCount').textContent = result.data.totais.total_transacoes;
                }
            } catch (error) {
                console.error('Erro ao buscar detalhes:', error);
            }
        });
    </script>
</body>
</html>