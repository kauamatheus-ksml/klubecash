<?php
// views/stores/register-transaction.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../controllers/CommissionController.php';

// Iniciar sessão e verificar autenticação
session_start();

// Verificar se o usuário está logado
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

// Verificar se o usuário é do tipo loja
if (!AuthController::isStore()) {
    header('Location: ' . CLIENT_DASHBOARD_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

// Obter ID do usuário logado
$userId = AuthController::getCurrentUserId();

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
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

// Verificar se o formulário foi enviado
$success = false;
$error = '';
$transactionData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $clientEmail = $_POST['cliente_email'] ?? '';
    $valorTotal = $_POST['valor_total'] ?? '';
    $codigoTransacao = $_POST['codigo_transacao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $dataTransacao = $_POST['data_transacao'] ?? date('Y-m-d H:i:s');
    
    // Buscar usuário pelo email
    $userQuery = $db->prepare("SELECT id, nome FROM usuarios WHERE email = :email AND tipo = :tipo AND status = :status");
    $userQuery->bindParam(':email', $clientEmail);
    $tipoCliente = USER_TYPE_CLIENT;
    $userQuery->bindParam(':tipo', $tipoCliente);
    $status = USER_ACTIVE;
    $userQuery->bindParam(':status', $status);
    $userQuery->execute();
    
    if ($userQuery->rowCount() === 0) {
        $error = 'Cliente não encontrado ou não está ativo. Verifique o email informado.';
    } else {
        $client = $userQuery->fetch(PDO::FETCH_ASSOC);
        
        // Preparar dados da transação
        $transactionData = [
            'usuario_id' => $client['id'],
            'loja_id' => $storeId,
            'valor_total' => $valorTotal,
            'codigo_transacao' => $codigoTransacao,
            'descricao' => $descricao,
            'data_transacao' => $dataTransacao
        ];
        
        // Registrar transação
        $result = TransactionController::registerTransaction($transactionData);
        
        if ($result['status']) {
            $success = true;
            $transactionData = [];
        } else {
            $error = $result['message'];
        }
    }
}

// Definir menu ativo
$activeMenu = 'register-transaction';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venda - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/sidebar-styles.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/register-transaction.css">
    <style>
        /* Estilos específicos para a página de registro de transação */
.content-card {
    background-color: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
    padding: 30px;
    width: 100%;
}

.form-wrapper {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}

.form-row {
    margin-bottom: 20px;
    width: 100%;
}

.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #404040;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    background-color: #f9f9f9;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #FF7A00;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.15);
    background-color: #fff;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #666;
    font-size: 12px;
    font-style: italic;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.form-actions .btn {
    padding: 12px 25px;
    font-weight: 600;
}

/* Calculadora de Cashback */
.cashback-calculator {
    background-color: #f7f9fc;
    border-radius: 12px;
    padding: 25px;
    margin-top: 30px;
    border: 1px solid #e6e9ef;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.03);
}

.cashback-calculator h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
    color: #333;
    padding-bottom: 10px;
    border-bottom: 1px solid #e6e9ef;
}

.cashback-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.cashback-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #ddd;
}

.cashback-item.total {
    border-bottom: none;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #ddd;
    font-weight: bold;
    color: #FF7A00;
}

.cashback-label {
    color: #555;
    font-size: 15px;
}

.cashback-value {
    font-weight: 600;
    font-size: 15px;
}

.cashback-note {
    margin-top: 15px;
    font-size: 13px;
    color: #666;
    background-color: #fff;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid #FF7A00;
}

/* Seção de ajuda */
.help-section {
    margin-top: 40px;
    margin-bottom: 40px;
    width: 100%;
}

.help-section h3 {
    margin-bottom: 20px;
    font-size: 18px;
    color: #333;
}

.accordion {
    border: 1px solid #e6e9ef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.accordion-item {
    border-bottom: 1px solid #e6e9ef;
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-header {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 18px 20px;
    font-size: 16px;
    font-weight: 600;
    color: #404040;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.accordion-header:hover {
    background-color: #f7f9fc;
}

.accordion-icon {
    font-size: 18px;
    color: #FF7A00;
    font-weight: bold;
}

.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    padding: 0 20px;
    background-color: #f9f9f9;
}

.accordion-item.active .accordion-content {
    padding: 15px 20px 20px;
}

.accordion-content p {
    margin: 0;
    color: #555;
    line-height: 1.6;
    font-size: 15px;
}

/* Alerta */
.alert {
    display: flex;
    align-items: flex-start;
    padding: 18px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    gap: 15px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.alert svg {
    flex-shrink: 0;
    margin-top: 3px;
    width: 22px;
    height: 22px;
}

.alert div {
    flex-grow: 1;
}

.alert h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
}

.alert p {
    margin: 0;
    color: inherit;
    font-size: 14px;
    line-height: 1.5;
}

.alert.success {
    background-color: #ecf9f0;
    color: #0d6832;
    border-left: 4px solid #0d6832;
}

.alert.success svg {
    color: #0d6832;
}

.alert.error {
    background-color: #ffeef0;
    color: #cc0022;
    border-left: 4px solid #cc0022;
}

.alert.error svg {
    color: #cc0022;
}

.alert .btn {
    margin-left: auto;
    padding: 8px 15px;
    font-size: 14px;
}

/* Assegurar que a página funcione corretamente com a sidebar */
.dashboard-container {
    display: flex;
    min-height: 100vh;
    width: 100%;
    background-color: #f8f9fa;
}

.main-content {
    flex: 1;
    padding: 30px;
    transition: padding-left 0.3s ease;
    width: calc(100% - 250px);
    margin-left: 250px;
    overflow-x: hidden;
}

.dashboard-header {
    margin-bottom: 25px;
}

.dashboard-title {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.welcome-user {
    margin: 5px 0 0;
    color: #666;
    font-size: 15px;
}

/* Responsivo */
@media (max-width: 1200px) {
    .main-content {
        padding: 25px 20px;
    }
    
    .content-card {
        padding: 25px;
    }
}

@media (max-width: 992px) {
    .two-columns {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    
    .content-card {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
    .cashback-calculator {
        padding: 20px;
    }
    
    .alert {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .alert .btn {
        margin-left: 0;
        margin-top: 15px;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .dashboard-title {
        font-size: 20px;
    }
    
    .welcome-user {
        font-size: 14px;
    }
    
    .content-card {
        padding: 15px;
    }
    
    .form-group label {
        font-size: 13px;
    }
    
    .form-group input, 
    .form-group textarea,
    .form-group select {
        padding: 10px;
        font-size: 14px;
    }
    
    .accordion-header {
        padding: 15px;
        font-size: 15px;
    }
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar/menu lateral -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Registrar Venda</h1>
                    <p class="welcome-user">Registre suas vendas para oferecer cashback aos clientes do Klube Cash</p>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <h4>Transação registrada com sucesso!</h4>
                    <p>O cashback será liberado para o cliente assim que o pagamento da comissão for realizado e aprovado.</p>
                </div>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-success">Registrar Nova</a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <h4>Erro ao registrar transação</h4>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="form-wrapper">
                    <form id="transactionForm" method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cliente_email">Email do Cliente*</label>
                                <input type="email" id="cliente_email" name="cliente_email" placeholder="Email do cliente cadastrado no Klube Cash" required
                                value="<?php echo isset($transactionData['cliente_email']) ? htmlspecialchars($transactionData['cliente_email']) : ''; ?>">
                                <small>O cliente deve estar cadastrado no Klube Cash</small>
                            </div>
                        </div>
                        
                        <div class="form-row two-columns">
                            <div class="form-group">
                                <label for="valor_total">Valor Total da Venda (R$)*</label>
                                <input type="number" id="valor_total" name="valor_total" min="<?php echo MIN_TRANSACTION_VALUE; ?>" step="0.01" required
                                value="<?php echo isset($transactionData['valor_total']) ? htmlspecialchars($transactionData['valor_total']) : ''; ?>"
                                placeholder="Valor total da compra">
                                <small>Valor mínimo: R$ <?php echo number_format(MIN_TRANSACTION_VALUE, 2, ',', '.'); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="codigo_transacao">Código da Transação*</label>
                                <input type="text" id="codigo_transacao" name="codigo_transacao" required
                                value="<?php echo isset($transactionData['codigo_transacao']) ? htmlspecialchars($transactionData['codigo_transacao']) : ''; ?>"
                                placeholder="Código/número da venda no seu sistema">
                                <small>Identificador único da venda no seu sistema</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_transacao">Data da Venda</label>
                                <input type="datetime-local" id="data_transacao" name="data_transacao"
                                value="<?php echo isset($transactionData['data_transacao']) ? date('Y-m-d\TH:i', strtotime($transactionData['data_transacao'])) : date('Y-m-d\TH:i'); ?>">
                                <small>Deixe em branco para usar a data/hora atual</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="descricao">Descrição (opcional)</label>
                                <textarea id="descricao" name="descricao" rows="3" placeholder="Detalhes adicionais sobre a venda"><?php echo isset($transactionData['descricao']) ? htmlspecialchars($transactionData['descricao']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="cashback-calculator">
                            <h3>Simulação de Cashback</h3>
                            <div class="cashback-details">
                                <div class="cashback-item">
                                    <span class="cashback-label">Valor da Venda:</span>
                                    <span class="cashback-value" id="display-valor-venda">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Cashback do Cliente (<?php echo DEFAULT_CASHBACK_CLIENT; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-cliente">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Comissão Klube Cash (<?php echo DEFAULT_CASHBACK_ADMIN; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-admin">R$ 0,00</span>
                                </div>
                                <div class="cashback-item total">
                                    <span class="cashback-label">Valor Total a Pagar (<?php echo DEFAULT_CASHBACK_TOTAL; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-total">R$ 0,00</span>
                                </div>
                            </div>
                            <div class="cashback-note">
                                <p>* O valor de cashback será liberado para o cliente após o pagamento da comissão.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Registrar Venda</button>
                            <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="help-section">
                <h3>Dúvidas Frequentes</h3>
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Como o cliente recebe o cashback?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Após o registro da venda, o cliente visualizará o cashback como "pendente" em seu painel. Quando sua loja realizar o pagamento da comissão para o Klube Cash, o valor será liberado automaticamente para o cliente.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>E se o cliente não estiver cadastrado?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Apenas clientes já cadastrados no Klube Cash podem receber cashback. Você pode convidar seus clientes a se cadastrarem em nossa plataforma para começarem a ganhar cashback em suas compras.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Como pagar as comissões?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Você pode pagar as comissões através da página "Comissões Pendentes", onde verá todas as vendas registradas que ainda não foram pagas. Selecione as transações desejadas e clique em "Pagar Comissões" para gerar um pagamento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Calculadora de Cashback
        document.addEventListener('DOMContentLoaded', function() {
            const valorInput = document.getElementById('valor_total');
            const displayValorVenda = document.getElementById('display-valor-venda');
            const displayValorCliente = document.getElementById('display-valor-cliente');
            const displayValorAdmin = document.getElementById('display-valor-admin');
            const displayValorTotal = document.getElementById('display-valor-total');
            
            // Porcentagens de cashback (do PHP para JavaScript)
            const porcentagemCliente = <?php echo DEFAULT_CASHBACK_CLIENT; ?>;
            const porcentagemAdmin = <?php echo DEFAULT_CASHBACK_ADMIN; ?>;
            const porcentagemTotal = <?php echo DEFAULT_CASHBACK_TOTAL; ?>;
            
            // Função para formatar valores como moeda
            function formatCurrency(value) {
                return value.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            // Função para calcular e atualizar a simulação
            function atualizarSimulacao() {
                const valor = parseFloat(valorInput.value) || 0;
                
                const valorCliente = valor * porcentagemCliente / 100;
                const valorAdmin = valor * porcentagemAdmin / 100;
                const valorTotal = valor * porcentagemTotal / 100;
                
                displayValorVenda.textContent = `R$ ${formatCurrency(valor)}`;
                displayValorCliente.textContent = `R$ ${formatCurrency(valorCliente)}`;
                displayValorAdmin.textContent = `R$ ${formatCurrency(valorAdmin)}`;
                displayValorTotal.textContent = `R$ ${formatCurrency(valorTotal)}`;
            }
            
            // Atualizar quando o valor mudar
            valorInput.addEventListener('input', atualizarSimulacao);
            
            // Executa uma vez ao carregar para inicializar os valores
            atualizarSimulacao();
            
            // Accordion para a seção de ajuda
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                const icon = item.querySelector('.accordion-icon');
                
                header.addEventListener('click', () => {
                    // Toggle active class
                    const isActive = item.classList.contains('active');
                    
                    // Fechar todos os itens
                    accordionItems.forEach(i => {
                        i.classList.remove('active');
                        i.querySelector('.accordion-content').style.maxHeight = '0';
                        i.querySelector('.accordion-icon').textContent = '+';
                    });
                    
                    // Se o item clicado não estava ativo, abri-lo
                    if (!isActive) {
                        item.classList.add('active');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        icon.textContent = '-';
                    }
                });
            });
            
            // Validação de formulário
            const form = document.getElementById('transactionForm');
            
            form.addEventListener('submit', function(e) {
                const valorTotal = parseFloat(valorInput.value) || 0;
                const minValue = <?php echo MIN_TRANSACTION_VALUE; ?>;
                
                if (valorTotal < minValue) {
                    e.preventDefault();
                    alert(`O valor mínimo para transação é R$ ${minValue.toFixed(2).replace('.', ',')}`);
                    valorInput.focus();
                }
            });
        });
    </script>
</body>
</html>