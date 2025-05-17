<?php
// views/stores/payment.php - VERSÃO CORRIGIDA

// Habilitar logs de erro
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Definir o menu ativo na sidebar
$activeMenu = 'payment';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';

// Verificar se ROOT_DIR está definido
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__DIR__)));
}

// Iniciar sessão
session_start();

// ADICIONAR TOKEN CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log inicial
error_log("payment.php - Method: " . $_SERVER['REQUEST_METHOD']);

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = $_SESSION['user_id'];

// Obter dados da loja
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Loja não encontrada.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Inicializar variáveis
$success = false;
$error = '';
$transactionIds = [];
$totalValue = 0;
$transactions = [];

// PRIMEIRO POST - Vindo da página de comissões pendentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'payment_form') {
    error_log("payment.php - Primeiro POST recebido");
    
    if (!isset($_POST['transacoes']) || !is_array($_POST['transacoes']) || count($_POST['transacoes']) === 0) {
        $error = 'Selecione pelo menos uma transação para realizar o pagamento.';
        error_log("payment.php - Erro: Nenhuma transação selecionada");
    } else {
        $transactionIds = array_map('intval', $_POST['transacoes']);
        error_log("payment.php - Transações selecionadas: " . implode(',', $transactionIds));
        
        // Validar transações
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
        
        if (count($transactions) !== count($transactionIds)) {
            $error = 'Algumas transações selecionadas não são válidas.';
            error_log("payment.php - Erro: Transações inválidas");
        } else {
            foreach ($transactions as $transaction) {
                $totalValue += floatval($transaction['valor_cashback']);
            }
            error_log("payment.php - Valor total calculado: $totalValue");
        }
    }
}
// SEGUNDO POST - Enviando formulário de pagamento
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    error_log("payment.php - Segundo POST recebido");
    error_log("payment.php - POST data: " . print_r($_POST, true));
    
    // VALIDAR CSRF TOKEN
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token de segurança inválido. Recarregue a página.';
        error_log("payment.php - Erro: CSRF token inválido");
    }
    // Validar campos obrigatórios
    elseif (!isset($_POST['transacoes']) || !isset($_POST['valor_total']) || !isset($_POST['metodo_pagamento'])) {
        $error = 'Dados de pagamento incompletos.';
        error_log("payment.php - Erro: Dados incompletos");
    } else {
        // Processar dados
        $transactionIds = explode(',', $_POST['transacoes']);
        $transactionIds = array_map('intval', array_filter($transactionIds));
        $totalValue = floatval($_POST['valor_total']);
        $metodoPagamento = trim($_POST['metodo_pagamento']);
        $numeroReferencia = trim($_POST['numero_referencia'] ?? '');
        $observacao = trim($_POST['observacao'] ?? '');
        
        error_log("payment.php - Processando: IDs=" . implode(',', $transactionIds) . ", Valor=$totalValue");
        
        // Validações adicionais
        if (empty($transactionIds)) {
            $error = 'Nenhuma transação válida encontrada.';
            error_log("payment.php - Erro: Nenhuma transação válida");
        } elseif ($totalValue <= 0) {
            $error = 'Valor total inválido.';
            error_log("payment.php - Erro: Valor inválido");
        } elseif (empty($metodoPagamento)) {
            $error = 'Método de pagamento é obrigatório.';
            error_log("payment.php - Erro: Método de pagamento vazio");
        } else {
            // Processar upload (se houver)
            $comprovante = '';
            if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                error_log("payment.php - Processando upload");
                
                $uploadDir = ROOT_DIR . '/uploads/comprovantes';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    error_log("payment.php - Diretório criado: $uploadDir");
                }
                
                $fileInfo = pathinfo($_FILES['comprovante']['name']);
                $extension = strtolower($fileInfo['extension']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $fileName = 'comprovante_' . $storeId . '_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
                    $filePath = $uploadDir . '/' . $fileName;
                    
                    if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $filePath)) {
                        $comprovante = $fileName;
                        error_log("payment.php - Upload realizado: $fileName");
                    } else {
                        $error = 'Erro ao fazer upload do comprovante.';
                        error_log("payment.php - Erro no upload");
                    }
                } else {
                    $error = 'Formato de arquivo não permitido. Use JPG, PNG ou PDF.';
                    error_log("payment.php - Erro: Formato inválido");
                }
            }
            
            // Se não houve erro, registrar pagamento
            if (empty($error)) {
                $paymentData = [
                    'loja_id' => $storeId,
                    'transacoes' => $transactionIds,
                    'valor_total' => $totalValue,
                    'metodo_pagamento' => $metodoPagamento,
                    'numero_referencia' => $numeroReferencia,
                    'comprovante' => $comprovante,
                    'observacao' => $observacao
                ];
                
                error_log("payment.php - Chamando registerPayment");
                $result = TransactionController::registerPayment($paymentData);
                error_log("payment.php - Resultado: " . print_r($result, true));
                
                if ($result['status']) {
                    $success = true;
                    // Redirecionar após sucesso
                    header('Location: ' . STORE_PAYMENT_HISTORY_URL . '?success=1');
                    exit;
                } else {
                    $error = $result['message'];
                    error_log("payment.php - Erro no registerPayment: " . $result['message']);
                }
            }
            
            // Se houve erro, recarregar transações
            if (!empty($error)) {
                // Revalidar transações para mostrar formulário novamente
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                $reloadQuery = "
                    SELECT t.id, t.codigo_transacao, t.valor_total, t.valor_cashback, t.valor_cliente, 
                           t.data_transacao, u.nome as cliente_nome
                    FROM transacoes_cashback t
                    JOIN usuarios u ON t.usuario_id = u.id
                    WHERE t.id IN ($placeholders) AND t.loja_id = ? AND t.status = ?
                ";
                
                $stmt = $db->prepare($reloadQuery);
                $bindParams = array_merge($transactionIds, [$storeId, TRANSACTION_PENDING]);
                
                for ($i = 0; $i < count($bindParams); $i++) {
                    $stmt->bindValue($i + 1, $bindParams[$i]);
                }
                
                $stmt->execute();
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Recalcular total
                $totalValue = 0;
                foreach ($transactions as $transaction) {
                    $totalValue += floatval($transaction['valor_cashback']);
                }
            }
        }
    }
} else {
    // Acesso direto - redirecionar
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

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
    <style>
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background-color: #FF7A00;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .file-upload-container {
            position: relative;
        }
        .file-input {
            position: absolute;
            left: -9999px;
        }
        .file-upload-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }
        .payment-instructions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .payment-summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .form-actions {
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Realizar Pagamento</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert error">
                <strong>Erro:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success">
                <strong>Sucesso!</strong> Pagamento registrado com sucesso!
            </div>
        <?php elseif (!empty($transactions)): ?>
            <!-- Resumo das Transações -->
            <div class="card">
                <h3>Resumo das Transações</h3>
                <p>Você selecionou <strong><?php echo count($transactions); ?></strong> transações para pagamento, totalizando <strong>R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></strong></p>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor Venda</th>
                                <th>Comissão</th>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Formulário de Pagamento -->
            <div class="card">
                <h3>Formulário de Pagamento</h3>
                <form method="POST" action="" enctype="multipart/form-data" id="paymentForm">
                    <!-- ADICIONAR CSRF TOKEN -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="transacoes" value="<?php echo implode(',', $transactionIds); ?>">
                    <input type="hidden" name="valor_total" value="<?php echo $totalValue; ?>">
                    
                    <div class="form-group">
                        <label for="metodo_pagamento">Método de Pagamento *</label>
                        <select id="metodo_pagamento" name="metodo_pagamento" required>
                            <option value="">Selecione um método</option>
                            <?php foreach ($metodosPagamento as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_referencia">Número de Referência</label>
                        <input type="text" id="numero_referencia" name="numero_referencia" 
                               value="<?php echo htmlspecialchars($_POST['numero_referencia'] ?? ''); ?>"
                               placeholder="Ex: ID da transação, número do comprovante">
                        <small>Opcional. Use para rastrear seu pagamento internamente.</small>
                    </div>
                    
                    <!-- Instruções PIX -->
                    <div id="pixInstructions" style="display: none;">
                        <div class="payment-instructions">
                            <h4>Instruções para Pagamento via PIX</h4>
                            <p><strong>Chave PIX:</strong> (11) 98765-4321 (Telefone)</p>
                            <p><strong>Nome:</strong> Klube Cash Serviços Financeiros Ltda</p>
                            <p><strong>Valor:</strong> R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Instruções Transferência -->
                    <div id="transferenciaInstructions" style="display: none;">
                        <div class="payment-instructions">
                            <h4>Instruções para Transferência Bancária</h4>
                            <p><strong>Banco:</strong> 123 - Banco Digital</p>
                            <p><strong>Agência:</strong> 0001</p>
                            <p><strong>Conta:</strong> 12345-6</p>
                            <p><strong>CNPJ:</strong> 12.345.678/0001-90</p>
                            <p><strong>Valor:</strong> R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comprovante">Comprovante de Pagamento</label>
                        <div class="file-upload-container">
                            <input type="file" id="comprovante" name="comprovante" accept=".jpg,.jpeg,.png,.pdf" class="file-input">
                            <label for="comprovante" class="file-upload-button">
                                Selecionar Arquivo
                            </label>
                            <span id="file-name">Nenhum arquivo selecionado</span>
                        </div>
                        <small>Formatos permitidos: JPG, PNG, PDF. Tamanho máximo: 5MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacao">Observações (opcional)</label>
                        <textarea id="observacao" name="observacao" rows="3" 
                                  placeholder="Informações adicionais sobre o pagamento"><?php echo htmlspecialchars($_POST['observacao'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="payment-summary-box">
                        <h4>Resumo do Pagamento</h4>
                        <div class="summary-item">
                            <span>Total de Transações:</span>
                            <span><?php echo count($transactions); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Valor Total:</span>
                            <span>R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Método de Pagamento:</span>
                            <span id="paymentMethodDisplay">-</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit_payment" value="1" class="btn btn-primary" id="submitBtn">
                            Confirmar Pagamento
                        </button>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('comprovante');
            const fileDisplay = document.getElementById('file-name');
            const methodSelect = document.getElementById('metodo_pagamento');
            const pixInstructions = document.getElementById('pixInstructions');
            const transferenciaInstructions = document.getElementById('transferenciaInstructions');
            const paymentMethodDisplay = document.getElementById('paymentMethodDisplay');
            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitBtn');
            
            // Upload de arquivo
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        const file = this.files[0];
                        fileDisplay.textContent = file.name;
                        
                        // Validar tamanho (5MB = 5 * 1024 * 1024 bytes)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Arquivo muito grande. Máximo permitido: 5MB');
                            this.value = '';
                            fileDisplay.textContent = 'Nenhum arquivo selecionado';
                        }
                    } else {
                        fileDisplay.textContent = 'Nenhum arquivo selecionado';
                    }
                });
            }
            
            // Método de pagamento
            if (methodSelect) {
                methodSelect.addEventListener('change', function() {
                    const method = this.value;
                    const methodText = this.options[this.selectedIndex].text;
                    
                    paymentMethodDisplay.textContent = methodText;
                    
                    // Mostrar/ocultar instruções
                    pixInstructions.style.display = method === 'pix' ? 'block' : 'none';
                    transferenciaInstructions.style.display = method === 'transferencia' ? 'block' : 'none';
                });
                
                // Trigger inicial se já houver valor selecionado
                if (methodSelect.value) {
                    methodSelect.dispatchEvent(new Event('change'));
                }
            }
            
            // Validação do formulário
            if (form) {
                form.addEventListener('submit', function(e) {
                    const method = methodSelect.value;
                    
                    if (!method) {
                        e.preventDefault();
                        alert('Por favor, selecione um método de pagamento.');
                        methodSelect.focus();
                        return false;
                    }
                    
                    // Confirmação
                    const totalValue = '<?php echo number_format($totalValue, 2, ",", "."); ?>';
                    if (confirm(`Confirmar o pagamento de R$ ${totalValue}?`)) {
                        // Desabilitar botão para evitar duplo envio
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