<?php
// views/stores/payment.php
// Definir o menu ativo na sidebar
$activeMenu = 'payment';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../utils/FileUpload.php';

// Mostrar erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<div style="background: yellow; padding: 20px; position: fixed; top: 0; left: 0; z-index: 9999; width: 100%;">';
    echo '<h3>DEBUG POST RECEBIDO:</h3>';
    echo '<pre>' . print_r($_POST, true) . '</pre>';
    echo '<pre>' . print_r($_FILES, true) . '</pre>';
    echo '</div>';
    echo '<div style="margin-top: 200px;"></div>';
}
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter ID do usuário logado
$userId = $_SESSION['user_id'];

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Inicializar variáveis
$success = false;
$error = '';
$transactionIds = [];
$totalValue = 0;
$transactions = [];

// Verificar se está vindo da página de comissões pendentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'payment_form') {
    if (!isset($_POST['transacoes']) || !is_array($_POST['transacoes']) || count($_POST['transacoes']) === 0) {
        $error = 'Selecione pelo menos uma transação para realizar o pagamento.';
    } else {
        // Obter IDs das transações selecionadas
        $transactionIds = array_map('intval', $_POST['transacoes']);
        
        // Validar se todas as transações pertencem à loja
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        $validationQuery = "
            SELECT t.id, t.codigo_transacao, t.valor_total, t.valor_cashback, t.valor_cliente, 
                   t.data_transacao, u.nome as cliente_nome
            FROM transacoes_cashback t
            JOIN usuarios u ON t.usuario_id = u.id
            WHERE t.id IN ($placeholders) AND t.loja_id = ? AND t.status = ?
        ";
        
        $stmt = $db->prepare($validationQuery);
        $bindParams = array_merge($transactionIds, [$storeId, TRANSACTION_PENDING]);
        
        for ($i = 0; $i < count($bindParams); $i++) {
            $stmt->bindValue($i + 1, $bindParams[$i]);
        }
        
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar se todas as transações foram encontradas
        if (count($transactions) !== count($transactionIds)) {
            $error = 'Algumas transações selecionadas não são válidas para pagamento.';
        } else {
            // Calcular valor total a pagar
            foreach ($transactions as $transaction) {
                $totalValue += floatval($transaction['valor_cashback']);
            }
        }
    }
// Após linha ~87, substitua toda a seção de processamento POST por:
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0;">';
    echo '<h3>DEBUG - Dados recebidos:</h3>';
    echo '<pre>POST: ' . print_r($_POST, true) . '</pre>';
    echo '<pre>FILES: ' . print_r($_FILES, true) . '</pre>';

    // Validar campos obrigatórios
    if (!isset($_POST['transacoes']) || !isset($_POST['valor_total']) || !isset($_POST['metodo_pagamento'])) {
        echo '<p style="color: red;">ERRO: Dados obrigatórios ausentes</p>';
        $error = 'Dados de pagamento incompletos. Tente novamente.';
    } else {
        $transactionIds = explode(',', $_POST['transacoes']);
        echo '<p>Transações: ' . print_r($transactionIds, true) . '</p>';
        
        $paymentData = [
            'loja_id' => $storeId,
            'transacoes' => $transactionIds,
            'valor_total' => floatval($_POST['valor_total']),
            'metodo_pagamento' => $_POST['metodo_pagamento'],
            'numero_referencia' => $_POST['numero_referencia'] ?? '',
            'observacao' => $_POST['observacao'] ?? ''
        ];
        
        echo '<p>Dados para enviar: ' . print_r($paymentData, true) . '</p>';
        
        $result = TransactionController::registerPayment($paymentData);
        echo '<p>Resultado: ' . print_r($result, true) . '</p>';
        
        if ($result['status']) {
            $success = true;
        } else {
            $error = $result['message'];
        }
    }
    echo '</div>';
} else {
    // Acesso direto à página - redirecionar para comissões pendentes
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

// Métodos de pagamento disponíveis
$metodosPagamento = [
    'pix' => 'PIX',
    'transferencia' => 'Transferência Bancária',
    'boleto' => 'Boleto',
    'cartao' => 'Cartão de Crédito',
    'outro' => 'Outro'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Realizar Pagamento - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/payment.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Realizar Pagamento</h1>
                <p class="subtitle">Pague as comissões devidas para liberar o cashback aos seus clientes</p>
            </div>
            
            <?php if ($success): ?>
                <!-- Mensagem de sucesso -->
                <div class="success-container">
                    <div class="success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h2>Pagamento Registrado com Sucesso!</h2>
                    <p>Seu pagamento foi registrado e está aguardando aprovação do administrador. O cashback será liberado para os clientes assim que o pagamento for aprovado.</p>
                    
                    <div class="success-actions">
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-primary">Ver Histórico de Pagamentos</a>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Voltar para Comissões Pendentes</a>
                    </div>
                </div>
            <?php elseif (!empty($error)): ?>
                <!-- Mensagem de erro -->
                <div class="alert error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div>
                        <h4>Erro ao processar pagamento</h4>
                        <p><?php echo $error; ?></p>
                    </div>
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Voltar</a>
                </div>
            <?php else: ?>
                <!-- Formulário de pagamento -->
                <div class="payment-container">
                    <div class="card transactions-summary">
                        <div class="card-header">
                            <div class="card-title">Resumo das Transações</div>
                        </div>
                        <div class="summary-content">
                            <p class="summary-text">Você selecionou <strong><?php echo count($transactions); ?></strong> transações para pagamento, totalizando <strong>R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></strong></p>
                            
                            <!-- Lista de transações -->
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Data</th>
                                            <th>Valor Venda</th>
                                            <th>Comissão</th>
                                            <th>Cashback Cliente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                                <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                                <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                                <td>R$ <?php echo number_format($transaction['valor_cliente'], 2, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card payment-form-container">
                        <div class="card-header">
                            <div class="card-title">Formulário de Pagamento</div>
                        </div>
                        <form method="POST" action="" enctype="multipart/form-data" class="payment-form">
                            <input type="hidden" name="transacoes" value="<?php echo implode(',', $transactionIds); ?>">
                            <input type="hidden" name="valor_total" value="<?php echo $totalValue; ?>">
                            
                            <div class="form-group">
                                <label for="metodo_pagamento">Método de Pagamento*</label>
                                <select id="metodo_pagamento" name="metodo_pagamento" required>
                                    <option value="">Selecione um método</option>
                                    <?php foreach ($metodosPagamento as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="numeroReferenciaContainer">
                                <label for="numero_referencia">Número de Referência</label>
                                <input type="text" id="numero_referencia" name="numero_referencia" placeholder="Ex: ID da transação, número do comprovante">
                                <small>Opcional. Use para rastrear seu pagamento internamente.</small>
                            </div>
                            
                            <div class="form-group" id="pixInstructions" style="display: none;">
                                <div class="payment-instructions">
                                    <h3>Instruções para Pagamento via PIX</h3>
                                    <p>Faça um PIX para as seguintes informações:</p>
                                    <p><strong>Chave PIX:</strong> (11) 98765-4321 (Telefone)</p>
                                    <p><strong>Nome:</strong> Klube Cash Serviços Financeiros Ltda</p>
                                    <p><strong>Banco:</strong> Banco Digital</p>
                                    <p><strong>Valor:</strong> R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></p>
                                    <p class="payment-note">Após realizar o pagamento, anexe o comprovante no campo abaixo.</p>
                                </div>
                            </div>
                            
                            <div class="form-group" id="transferenciaInstructions" style="display: none;">
                                <div class="payment-instructions">
                                    <h3>Instruções para Transferência Bancária</h3>
                                    <p>Faça uma transferência para a seguinte conta:</p>
                                    <p><strong>Banco:</strong> 123 - Banco Digital</p>
                                    <p><strong>Agência:</strong> 0001</p>
                                    <p><strong>Conta:</strong> 12345-6</p>
                                    <p><strong>CNPJ:</strong> 12.345.678/0001-90</p>
                                    <p><strong>Nome:</strong> Klube Cash Serviços Financeiros Ltda</p>
                                    <p><strong>Valor:</strong> R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></p>
                                    <p class="payment-note">Após realizar a transferência, anexe o comprovante no campo abaixo.</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comprovante">Comprovante de Pagamento</label>
                                <div class="file-upload-container">
                                    <input type="file" id="comprovante" name="comprovante" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                                    <label for="comprovante" class="file-upload-button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        Selecionar Arquivo
                                    </label>
                                    <span id="file-name" class="file-name">Nenhum arquivo selecionado</span>
                                </div>
                                <small>Formatos permitidos: JPG, PNG, PDF. Tamanho máximo: 5MB</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="observacao">Observações (opcional)</label>
                                <textarea id="observacao" name="observacao" rows="3" placeholder="Informações adicionais sobre o pagamento"></textarea>
                            </div>
                            
                            <div class="payment-summary-box">
                                <div class="summary-header">Resumo do Pagamento</div>
                                <div class="summary-item">
                                    <span class="item-label">Total de Transações:</span>
                                    <span class="item-value"><?php echo count($transactions); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="item-label">Valor Total:</span>
                                    <span class="item-value">R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></span>
                                </div>
                                <div class="summary-item payment-method">
                                    <span class="item-label">Método de Pagamento:</span>
                                    <span class="item-value" id="paymentMethodDisplay">-</span>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                            <button type="submit" name="submit_payment" value="1" class="btn btn-primary">Confirmar Pagamento</button>
                                <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Informações Adicionais -->
                    <div class="card info-card">
                        <div class="card-header">
                            <div class="card-title">Informações Importantes</div>
                        </div>
                        <div class="info-content">
                            <p>O pagamento das comissões é necessário para que o cashback seja liberado para seus clientes. Após o envio deste formulário, nossa equipe validará o pagamento em até 24 horas úteis.</p>
                            <p>Após a aprovação, o cashback será automaticamente liberado para os clientes, que receberão uma notificação por email.</p>
                            <p>Em caso de dúvidas ou problemas, entre em contato com nosso suporte pelo email <a href="mailto:suporte@klubecash.com">suporte@klubecash.com</a>.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar nome do arquivo selecionado
            const fileInput = document.getElementById('comprovante');
            const fileNameDisplay = document.getElementById('file-name');
            
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        fileNameDisplay.textContent = this.files[0].name;
                        
                        // Validar tamanho do arquivo (máximo 5MB)
                        const fileSize = this.files[0].size / 1024 / 1024; // em MB
                        if (fileSize > 5) {
                            alert('O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
                            this.value = '';
                            fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                        }
                    } else {
                        fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                    }
                });
            }
            
            // Mostrar/ocultar instruções de pagamento com base no método selecionado
            const metodoPagamento = document.getElementById('metodo_pagamento');
            const pixInstructions = document.getElementById('pixInstructions');
            const transferenciaInstructions = document.getElementById('transferenciaInstructions');
            const paymentMethodDisplay = document.getElementById('paymentMethodDisplay');
            
            if (metodoPagamento) {
                metodoPagamento.addEventListener('change', function() {
                    // Atualizar método exibido no resumo
                    paymentMethodDisplay.textContent = this.options[this.selectedIndex].text;
                    
                    // Mostrar instruções correspondentes
                    if (this.value === 'pix') {
                        pixInstructions.style.display = 'block';
                        transferenciaInstructions.style.display = 'none';
                    } else if (this.value === 'transferencia') {
                        pixInstructions.style.display = 'none';
                        transferenciaInstructions.style.display = 'block';
                    } else {
                        pixInstructions.style.display = 'none';
                        transferenciaInstructions.style.display = 'none';
                    }
                });
            }
            
            // Validação do formulário antes de enviar
            const paymentForm = document.querySelector('.payment-form');
            
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    const method = metodoPagamento.value;
                    
                    if (!method) {
                        e.preventDefault();
                        alert('Por favor, selecione um método de pagamento.');
                        return false;
                    }
                    
                    // Se estiver enviando o formulário, mostrar mensagem de carregamento
                    if (confirm('Confirmar o pagamento de R$ <?php echo number_format($totalValue, 2, ',', '.'); ?>?')) {
                        // Adicionar mensagem de processamento
                        const submitBtn = document.querySelector('button[type="submit"]');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processando...';
                        return true;
                    } else {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>