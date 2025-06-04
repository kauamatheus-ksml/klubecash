<\?php
// views/stores/payment-pix.php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../utils/MercadoPagoClient.php'; // Incluir o cliente MP

session_start();

if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = AuthController::getCurrentUserId();
$db = Database::getConnection();

// Obter dados da loja
$storeQuery = $db->prepare("SELECT id, nome_fantasia, email FROM lojas WHERE usuario_id = ?"); // Adicionado email
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

// --- LÓGICA DE RENOVAÇÃO AUTOMÁTICA DO PIX --- 
$pixRenewed = false;
$pixRenewalError = null;

if ($payment['status'] === 'pix_aguardando' && !empty($payment['mp_payment_id'])) {
    error_log("[Payment {$paymentId}] Verificando status do PIX existente: {$payment['mp_payment_id']}");
    try {
        $mpClient = new MercadoPagoClient();
        $statusResponse = $mpClient->getPaymentStatus($payment['mp_payment_id']);
        
        if ($statusResponse['status'] && isset($statusResponse['data']['status'])) {
            $currentMpStatus = $statusResponse['data']['status'];
            error_log("[Payment {$paymentId}] Status atual no MP: {$currentMpStatus}");
            
            // Status que indicam que o PIX não é mais pagável
            $invalidStatus = ['rejected', 'cancelled', 'expired']; // 'expired' pode não ser um status real, mas 'cancelled' geralmente cobre isso.
            
            if (in_array($currentMpStatus, $invalidStatus)) {
                error_log("[Payment {$paymentId}] PIX {$payment['mp_payment_id']} está {$currentMpStatus}. Tentando renovar...");
                
                // Preparar dados para novo PIX
                $pixData = [
                    'amount' => $payment['valor_total'],
                    'payer_email' => $store['email'], // Usar email da loja como pagador
                    'payer_name' => $store['nome_fantasia'],
                    'description' => 'Renovação Pagamento Comissão Klube Cash ID: ' . $paymentId,
                    'external_reference' => 'KC_COMISSAO_' . $paymentId . '_' . time(), // Referência única
                    'payment_id' => $paymentId,
                    'store_id' => $storeId
                    // Adicionar outros dados do pagador se disponíveis (CPF/CNPJ, Telefone, Endereço) para melhorar aprovação
                ];
                
                $newPixResponse = $mpClient->createPixPayment($pixData);
                
                if ($newPixResponse['status'] && isset($newPixResponse['data'])) {
                    $newPix = $newPixResponse['data'];
                    error_log("[Payment {$paymentId}] Novo PIX gerado com sucesso: {$newPix['mp_payment_id']}");
                    
                    // Atualizar o pagamento no banco de dados
                    $updateStmt = $db->prepare("
                        UPDATE pagamentos_comissao
                        SET mp_payment_id = :mp_payment_id,
                            mp_qr_code = :mp_qr_code,
                            mp_qr_code_base64 = :mp_qr_code_base64,
                            mp_date_of_expiration = :mp_date_of_expiration, 
                            mp_status = :mp_status,
                            status = 'pix_aguardando', 
                            pix_paid_at = NULL,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    
                    $updateSuccess = $updateStmt->execute([
                        ':mp_payment_id' => $newPix['mp_payment_id'],
                        ':mp_qr_code' => $newPix['qr_code'],
                        ':mp_qr_code_base64' => $newPix['qr_code_base64'],
                        ':mp_date_of_expiration' => $newPix['date_of_expiration'], // Certifique-se que esta coluna existe!
                        ':mp_status' => $newPix['status'], // Geralmente 'pending'
                        ':id' => $paymentId
                    ]);
                    
                    if ($updateSuccess) {
                        error_log("[Payment {$paymentId}] Banco de dados atualizado com o novo PIX.");
                        // Recarregar os dados do pagamento para refletir a atualização
                        $paymentStmt->execute([$paymentId, $storeId]);
                        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
                        $pixRenewed = true;
                    } else {
                        $pixRenewalError = "Erro ao atualizar o banco de dados com o novo PIX.";
                        error_log("[Payment {$paymentId}] ERRO: {$pixRenewalError}");
                    }
                    
                } else {
                    $pixRenewalError = "Erro ao gerar novo PIX no Mercado Pago: " . ($newPixResponse['message'] ?? 'Erro desconhecido');
                    error_log("[Payment {$paymentId}] ERRO: {$pixRenewalError}");
                }
            } else {
                 error_log("[Payment {$paymentId}] PIX {$payment['mp_payment_id']} ainda válido ({$currentMpStatus}). Nenhuma ação necessária.");
                 // Atualizar o status MP no nosso banco se estiver diferente
                 if ($payment['mp_status'] !== $currentMpStatus) {
                     $updateMpStatusStmt = $db->prepare("UPDATE pagamentos_comissao SET mp_status = ? WHERE id = ?");
                     $updateMpStatusStmt->execute([$currentMpStatus, $paymentId]);
                     $payment['mp_status'] = $currentMpStatus; // Atualiza localmente também
                 }
            }
        } else {
            $pixRenewalError = "Erro ao consultar status do PIX no Mercado Pago: " . ($statusResponse['message'] ?? 'Erro desconhecido');
            error_log("[Payment {$paymentId}] ERRO: {$pixRenewalError}");
        }
        
    } catch (Exception $e) {
        $pixRenewalError = "Exceção ao verificar/renovar PIX: " . $e->getMessage();
        error_log("[Payment {$paymentId}] EXCEÇÃO: {$pixRenewalError}");
    }
}
// --- FIM DA LÓGICA DE RENOVAÇÃO --- 

// Verificar novamente se existe PIX (pode ter sido renovado)
$hasExistingPix = !empty($payment['mp_payment_id']) && !empty($payment['mp_qr_code']) && !empty($payment['mp_qr_code_base64']);

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Remover script mercadopago-device.js se não for usado para coletar device_id -->
    <!-- <script src="../../assets/js/mercadopago-device.js?v=2.1.0"></script> -->
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <!-- Header Moderno -->
        <div class="pix-header">
            <!-- ... (código do header sem alterações) ... -->
             <div class="header-content">
                <div class="header-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <div class="header-text">
                    <h1>Pagamento via PIX</h1>
                    <p>Pague suas comissões de forma rápida e segura</p>
                </div>
                <div class="header-amount">
                    <span class="amount-label">Valor total</span>
                    <span class="amount-value">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Mensagem de Status da Renovação -->
        <?php if ($pixRenewed): ?>
            <div class="status-message success">PIX anterior expirado. Um novo código PIX foi gerado automaticamente.</div>
        <?php elseif ($pixRenewalError): ?>
            <div class="status-message error">Erro ao tentar renovar o PIX: <?php echo htmlspecialchars($pixRenewalError); ?>. Tente gerar manualmente.</div>
        <?php endif; ?>

        <!-- Container Principal -->
        <div class="pix-container">
            <!-- Painel de Etapas -->
            <div class="steps-panel">
                 <!-- ... (código das etapas sem alterações) ... -->
                 <div class="step" id="step1" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Gerar PIX</h3>
                        <p>Criar código de pagamento</p>
                    </div>
                </div>
                
                <div class="step" id="step2" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Pagar</h3>
                        <p>Usar app do seu banco</p>
                    </div>
                </div>
                
                <div class="step" id="step3" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Confirmado</h3>
                        <p>Cashback liberado</p>
                    </div>
                </div>
            </div>

            <!-- Painel Principal de Conteúdo -->
            <div class="content-panel">
                <!-- Estado Inicial (se não houver PIX existente ou erro na renovação) -->
                <div class="payment-state" id="initialState" <?php echo ($hasExistingPix && !$pixRenewalError) ? 'style="display: none;"' : ''; ?>>
                    <!-- ... (código do estado inicial sem alterações) ... -->
                     <div class="state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2v4"/>
                            <path d="m16.2 7.8 2.9-2.9"/>
                            <path d="M18 12h4"/>
                            <path d="m16.2 16.2 2.9 2.9"/>
                            <path d="M12 18v4"/>
                            <path d="m4.9 19.1 2.9-2.9"/>
                            <path d="M2 12h4"/>
                            <path d="m4.9 4.9 2.9 2.9"/>
                        </svg>
                    </div>
                    <h2>Vamos gerar seu PIX?</h2>
                    <p class="state-description">
                        Clique no botão abaixo para criar o código PIX. 
                        Em seguida, você poderá pagar usando o app do seu banco.
                    </p>
                    <div class="payment-details-summary">
                        <div class="detail-item">
                            <span class="label">Transações:</span>
                            <span class="value" id="transactionCount">Carregando...</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Método:</span>
                            <span class="value">PIX Instantâneo</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Aprovação:</span>
                            <span class="value">Automática</span>
                        </div>
                    </div>
                    
                    <button class="pix-action-btn primary" onclick="generatePix()" id="generatePixBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        Gerar PIX Agora
                    </button>
                </div>

                <!-- Estado do QR Code (se houver PIX existente e sem erro na renovação) -->
                <div class="payment-state" id="qrCodeState" <?php echo ($hasExistingPix && !$pixRenewalError) ? '' : 'style="display: none;"'; ?>>
                    <!-- ... (código do estado QR Code, adaptado para usar dados do $payment) ... -->
                     <div class="qr-section">
                        <h2>Escaneie o QR Code</h2>
                        <p class="qr-instruction">
                            Abra o app do seu banco e escaneie o código abaixo, 
                            ou copie e cole o código PIX.
                        </p>
                        
                        <div class="qr-display">
                            <div class="qr-image-container">
                                <!-- Usar dados do $payment que pode ter sido renovado -->
                                <img id="qrCodeImage" src="data:image/png;base64,<?php echo $payment['mp_qr_code_base64'] ?? ''; ?>" alt="QR Code PIX" style="<?php echo ($hasExistingPix && !$pixRenewalError) ? '' : 'display: none;'; ?>">
                                <div class="qr-loading" id="qrLoading" style="display: none;"> <!-- Esconder loading inicial -->
                                    <div class="spinner"></div>
                                    <span>Gerando QR Code...</span>
                                </div>
                            </div>
                            
                            <div class="qr-code-section">
                                <label for="pixCode" class="code-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                    </svg>
                                    Código PIX
                                </label>
                                <div class="code-input-container">
                                     <!-- Usar dados do $payment que pode ter sido renovado -->
                                    <textarea id="pixCode" readonly><?php echo $payment['mp_qr_code'] ?? ''; ?></textarea>
                                    <button class="copy-btn" onclick="copyPixCode()" id="copyBtn" <?php echo ($hasExistingPix && !$pixRenewalError) ? '' : 'disabled'; ?>>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                        </svg>
                                        Copiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="qr-actions">
                            <button class="pix-action-btn secondary" onclick="checkPaymentStatus()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                                    <path d="M9 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                                    <path d="M21 12c-1 0-3 1-3 3s2 3 3 3 3-1 3-3-2-3-3-3"/>
                                    <path d="M9 12c1 0 3 1 3 3s-2 3-3 3-3-1-3-3 2-3 3-3"/>
                                </svg>
                                Verificar Pagamento
                            </button>
                        </div>

                        <div class="payment-timer">
                            <div class="timer-icon">⏱️</div>
                            <!-- Exibir data de expiração se disponível -->
                            <span id="expirationInfo">Aguardando pagamento... <?php echo (!empty($payment['mp_date_of_expiration'])) ? 'Expira em: ' . date('d/m/Y H:i', strtotime($payment['mp_date_of_expiration'])) : ''; ?></span>
                            <div class="pulse-indicator"></div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Sucesso (sem alterações) -->
                <div class="payment-state success-state" id="successState" style="display: none;">
                    <!-- ... (código do estado de sucesso sem alterações) ... -->
                     <div class="success-animation">
                        <div class="success-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                    </div>
                    <h2>Pagamento Confirmado!</h2>
                    <p class="success-description">
                        Seu PIX foi processado com sucesso. 
                        O cashback foi liberado automaticamente para seus clientes.
                    </p>
                    <div class="success-actions">
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="pix-action-btn primary">
                            Ver Histórico de Pagamentos
                        </a>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="pix-action-btn secondary">
                            Voltar às Comissões
                        </a>
                    </div>
                </div>
            </div>

            <!-- Painel de Informações (sem alterações) -->
            <div class="info-panel">
                 <!-- ... (código do painel de info sem alterações) ... -->
                 <div class="info-section">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        Como funciona
                    </h3>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-number">1</span>
                            <div class="info-text">
                                <strong>Gere o PIX</strong>
                                <p>Clique para criar o código de pagamento instantâneo</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-number">2</span>
                            <div class="info-text">
                                <strong>Pague pelo app</strong>
                                <p>Use qualquer banco para escanear ou colar o código</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-number">3</span>
                            <div class="info-text">
                                <strong>Aprovação automática</strong>
                                <p>Em até 2 minutos o cashback é liberado</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="security-info">
                    <div class="security-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                        Seguro
                    </div>
                    <span>Transação protegida pelo Mercado Pago</span>
                </div>
            </div>
        </div>

        <!-- Botão de Voltar Fixo (sem alterações) -->
        <div class="fixed-back-btn">
             <!-- ... (código do botão voltar sem alterações) ... -->
             <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="back-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Voltar
            </a>
        </div>
    </div>
    
    <!-- Dados ocultos para JavaScript -->
    <input type="hidden" id="paymentId" value="<?php echo $paymentId; ?>">
    <!-- Passar dados do PIX atual (pode ser o renovado) para o JS -->
    <input type="hidden" id="currentMpPaymentId" value="<?php echo $payment['mp_payment_id'] ?? ''; ?>">
    
    <script>
        // Variáveis globais
        const paymentId = document.getElementById('paymentId').value;
        const currentMpPaymentId = document.getElementById('currentMpPaymentId').value;
        let pollingInterval = null;
        
        // Elementos do DOM
        const initialState = document.getElementById('initialState');
        const qrCodeState = document.getElementById('qrCodeState');
        const successState = document.getElementById('successState');
        const generatePixBtn = document.getElementById('generatePixBtn');
        const qrCodeImage = document.getElementById('qrCodeImage');
        const qrLoading = document.getElementById('qrLoading');
        const pixCodeTextarea = document.getElementById('pixCode');
        const copyBtn = document.getElementById('copyBtn');
        const expirationInfoSpan = document.getElementById('expirationInfo');
        
        // Função para atualizar etapas visuais (sem alterações)
        function updateStep(stepNumber) {
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            for (let i = 1; i < stepNumber; i++) {
                const step = document.getElementById(`step${i}`);
                if (step) step.classList.add('completed');
            }
            const currentStep = document.getElementById(`step${stepNumber}`);
            if (currentStep) currentStep.classList.add('active');
        }

        // Função para copiar código PIX (sem alterações)
        function copyPixCode() {
            pixCodeTextarea.select();
            document.execCommand('copy');
            copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Copiado!';
            setTimeout(() => {
                copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copiar';
            }, 2000);
        }

        // Função para gerar PIX (chamada pelo botão 'Gerar PIX Agora')
        async function generatePix() {
            console.log('Gerando PIX para pagamento ID:', paymentId);
            generatePixBtn.disabled = true;
            generatePixBtn.innerHTML = '<div class="spinner small"></div> Gerando...';
            updateStep(1);
            
            // Mostrar loading no QR Code State
            initialState.style.display = 'none';
            qrCodeState.style.display = 'block';
            qrCodeImage.style.display = 'none';
            qrLoading.style.display = 'flex';
            pixCodeTextarea.value = '';
            copyBtn.disabled = true;

            try {
                const response = await fetch('../../api/generate-pix.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ payment_id: paymentId })
                });

                const result = await response.json();
                console.log('Resposta da API generate-pix:', result);

                if (result.status === 'success' && result.data) {
                    const pixData = result.data;
                    // Atualizar UI com os dados recebidos
                    qrCodeImage.src = 'data:image/png;base64,' + pixData.qr_code_base64;
                    pixCodeTextarea.value = pixData.qr_code;
                    document.getElementById('currentMpPaymentId').value = pixData.mp_payment_id; // Atualiza ID oculto
                    
                    // Atualizar info de expiração se disponível
                    if (pixData.date_of_expiration) {
                        const expirationDate = new Date(pixData.date_of_expiration);
                        expirationInfoSpan.textContent = `Aguardando pagamento... Expira em: ${expirationDate.toLocaleDateString('pt-BR')} ${expirationDate.toLocaleTimeString('pt-BR')}`;
                    } else {
                        expirationInfoSpan.textContent = 'Aguardando pagamento...';
                    }

                    qrLoading.style.display = 'none';
                    qrCodeImage.style.display = 'block';
                    copyBtn.disabled = false;
                    updateStep(2);
                    startPolling(); // Iniciar verificação de status
                } else {
                    console.error('Erro ao gerar PIX:', result.message);
                    alert('Erro ao gerar PIX: ' + result.message);
                    // Voltar ao estado inicial em caso de erro
                    qrCodeState.style.display = 'none';
                    initialState.style.display = 'block';
                    generatePixBtn.disabled = false;
                    generatePixBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Gerar PIX Agora';
                }
            } catch (error) {
                console.error('Erro na requisição generatePix:', error);
                alert('Erro de comunicação ao gerar PIX. Verifique sua conexão.');
                qrCodeState.style.display = 'none';
                initialState.style.display = 'block';
                generatePixBtn.disabled = false;
                generatePixBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Gerar PIX Agora';
            }
        }

        // Função para verificar status do pagamento (chamada pelo botão 'Verificar Pagamento' e pelo polling)
        async function checkPaymentStatus(showLoading = true) {
            const mpIdToCheck = document.getElementById('currentMpPaymentId').value;
            if (!mpIdToCheck) {
                console.log('Nenhum MP Payment ID para verificar.');
                return; 
            }
            
            console.log('Verificando status do pagamento MP ID:', mpIdToCheck);
            if (showLoading) {
                // Adicionar feedback visual de carregamento se necessário
            }

            try {
                const response = await fetch(`../../api/check-payment-status.php?mp_payment_id=${mpIdToCheck}`);
                const result = await response.json();
                console.log('Resposta da API check-payment-status:', result);

                if (result.status === 'success' && result.data) {
                    const paymentStatus = result.data.status;
                    if (paymentStatus === 'approved') {
                        console.log('Pagamento APROVADO!');
                        stopPolling();
                        qrCodeState.style.display = 'none';
                        successState.style.display = 'block';
                        updateStep(3);
                    } else if (['rejected', 'cancelled', 'expired'].includes(paymentStatus)) {
                         console.log(`Pagamento ${paymentStatus}. Recarregando para tentar renovar...`);
                         stopPolling();
                         // Forçar recarregamento da página para ativar a lógica de renovação no PHP
                         window.location.reload(); 
                    } else {
                        console.log('Pagamento ainda pendente:', paymentStatus);
                        // Manter estado QR Code e polling ativo
                    }
                } else {
                    console.error('Erro ao verificar status:', result.message);
                    // Não parar polling por erro de verificação, tentar novamente depois
                }
            } catch (error) {
                console.error('Erro na requisição checkPaymentStatus:', error);
                // Não parar polling por erro de comunicação
            }
        }

        // Função para iniciar polling (verificação periódica)
        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            console.log('Iniciando polling para verificar status do pagamento...');
            // Verificar imediatamente e depois a cada 15 segundos
            checkPaymentStatus(false);
            pollingInterval = setInterval(() => checkPaymentStatus(false), 15000); 
        }

        // Função para parar polling
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                console.log('Polling interrompido.');
            }
        }

        // Inicialização da página
        document.addEventListener('DOMContentLoaded', () => {
            // Se já existe um PIX (válido ou renovado), mostrar o estado QR Code e iniciar polling
            if (currentMpPaymentId && qrCodeState.style.display !== 'none') {
                console.log('PIX existente encontrado ou renovado. Exibindo QR Code e iniciando polling.');
                updateStep(2);
                startPolling();
            } else if (initialState.style.display !== 'none'){
                 console.log('Nenhum PIX válido. Exibindo estado inicial.');
                 updateStep(1);
            }
            
            // Carregar contagem de transações (exemplo, adaptar à sua API)
            fetch(`../../api/get-payment-details.php?payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success' && data.details) {
                        document.getElementById('transactionCount').textContent = data.details.transaction_count || 'N/A';
                    } else {
                         document.getElementById('transactionCount').textContent = 'Erro';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar detalhes do pagamento:', error);
                    document.getElementById('transactionCount').textContent = 'Erro';
                });
        });

        // Limpar polling ao sair da página
        window.addEventListener('beforeunload', stopPolling);

    </script>
</body>
</html>

