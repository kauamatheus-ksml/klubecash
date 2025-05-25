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
    // Debug: Log dos dados recebidos
    error_log("FORM DEBUG: Dados POST recebidos: " . print_r($_POST, true));
    
    // Obter dados do formulário
    $clientEmail = $_POST['cliente_email'] ?? '';
    $valorTotal = floatval($_POST['valor_total'] ?? 0);
    $codigoTransacao = $_POST['codigo_transacao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $dataTransacao = $_POST['data_transacao'] ?? date('Y-m-d H:i:s');
    
    // CORREÇÃO CRÍTICA: Como o JavaScript envia
    $usarSaldo = isset($_POST['usar_saldo']) && $_POST['usar_saldo'] === 'sim';
    $valorSaldoUsado = floatval($_POST['valor_saldo_usado'] ?? 0);
    
    // Debug dos valores de saldo
    error_log("FORM DEBUG: usar_saldo = " . ($_POST['usar_saldo'] ?? 'undefined'));
    error_log("FORM DEBUG: usarSaldo (bool) = " . ($usarSaldo ? 'true' : 'false'));
    error_log("FORM DEBUG: valorSaldoUsado = " . $valorSaldoUsado);
    
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
        
        // Se vai usar saldo, verificar se tem saldo suficiente
        if ($usarSaldo && $valorSaldoUsado > 0) {
            require_once '../../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            $saldoDisponivel = $balanceModel->getStoreBalance($client['id'], $storeId);
            
            if ($saldoDisponivel < $valorSaldoUsado) {
                $error = 'Saldo insuficiente. Cliente possui R$ ' . number_format($saldoDisponivel, 2, ',', '.') . ' disponível.';
            } else if ($valorSaldoUsado > $valorTotal) {
                $error = 'O valor do saldo usado não pode ser maior que o valor total da venda.';
            }
        }
        
        if (empty($error)) {
            // Preparar dados da transação
            $transactionData = [
                'usuario_id' => $client['id'],
                'loja_id' => $storeId,
                'valor_total' => $valorTotal,
                'codigo_transacao' => $codigoTransacao,
                'descricao' => $descricao,
                'data_transacao' => $dataTransacao,
                'usar_saldo' => $usarSaldo,  // BOOLEAN, não string
                'valor_saldo_usado' => $valorSaldoUsado
            ];
            
            // Debug dos dados enviados
            error_log("FORM DEBUG: Dados para TransactionController: " . print_r($transactionData, true));
            
            // Registrar transação
            $result = TransactionController::registerTransaction($transactionData);
            
            if ($result['status']) {
                $success = true;
                $transactionData = [];
                error_log("FORM DEBUG: Transação registrada com sucesso - ID: " . $result['data']['transaction_id']);
            } else {
                $error = $result['message'];
                error_log("FORM DEBUG: Erro ao registrar - " . $result['message']);
            }
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
    
    <link rel="stylesheet" href="../../assets/css/views/stores/register-transaction.css">
    
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
                                <div class="client-search-container">
                                    <div class="email-input-group">
                                        <div class="email-input-wrapper">
                                            <input type="email" id="cliente_email" name="cliente_email" 
                                                placeholder="Email do cliente cadastrado no Klube Cash" required
                                                value="<?php echo isset($transactionData['cliente_email']) ? htmlspecialchars($transactionData['cliente_email']) : ''; ?>">
                                            <small>O cliente deve estar cadastrado no Klube Cash</small>
                                        </div>
                                        <button type="button" id="searchClientBtn" class="search-client-btn">
                                            <span class="btn-text">Buscar Cliente</span>
                                            <span class="loading-spinner" style="display: none;"></span>
                                        </button>
                                    </div>
                                    
                                    <!-- Card de informações do cliente -->
                                    <div id="clientInfoCard" class="client-info-card">
                                        <div class="client-info-header">
                                            <svg class="client-info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <h4 class="client-info-title" id="clientInfoTitle">Informações do Cliente</h4>
                                        </div>
                                        <div class="client-info-details" id="clientInfoDetails">
                                            <!-- Será preenchido dinamicamente -->
                                        </div>
                                    </div>
                                </div>
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
                        <!-- NOVA SEÇÃO: USO DE SALDO -->
                        <div id="saldoSection" class="saldo-section" style="display: none;">
                            <h3>💰 Usar Saldo do Cliente</h3>
                            <div class="saldo-info">
                                <div class="saldo-disponivel">
                                    <span>Saldo disponível: </span>
                                    <span id="saldoDisponivel" class="saldo-value">R$ 0,00</span>
                                </div>
                                <div class="usar-saldo-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="usarSaldoCheck" name="usar_saldo_check">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span>Usar saldo nesta venda</span>
                                </div>
                            </div>
                            
                            <div id="saldoControls" class="saldo-controls" style="display: none;">
                                <div class="form-group">
                                    <label for="valorSaldoUsado">Valor do saldo a usar (R$)</label>
                                    <input type="number" id="valorSaldoUsado" name="valor_saldo_usado" 
                                        min="0" step="0.01" value="0">
                                    <small>Máximo: <span id="maxSaldo">R$ 0,00</span></small>
                                </div>
                                
                                <div class="saldo-buttons">
                                    <button type="button" id="usarTodoSaldo" class="btn-saldo">Usar Todo Saldo</button>
                                    <button type="button" id="usar50Saldo" class="btn-saldo">Usar 50%</button>
                                    <button type="button" id="limparSaldo" class="btn-saldo">Limpar</button>
                                </div>
                                
                                <div class="calculo-preview">
                                    <div class="calculo-item">
                                        <span>Valor original:</span>
                                        <span id="valorOriginal">R$ 0,00</span>
                                    </div>
                                    <div class="calculo-item">
                                        <span>Saldo usado:</span>
                                        <span id="valorSaldoUsadoPreview">R$ 0,00</span>
                                    </div>
                                    <div class="calculo-item valor-final">
                                        <span>Valor a pagar:</span>
                                        <span id="valorFinal">R$ 0,00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos ocultos para uso de saldo -->
                        <input type="hidden" id="usar_saldo" name="usar_saldo" value="nao">
                        <input type="hidden" id="valor_saldo_usado_hidden" name="valor_saldo_usado" value="0">
                        
                        <!-- resto dos campos existentes... -->
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
                            <h3>Simulação de Comissão e Cashback</h3>
                            <div class="cashback-details">
                                <div class="cashback-item">
                                    <span class="cashback-label">Valor da Venda:</span>
                                    <span class="cashback-value" id="display-valor-venda">R$ 0,00</span>
                                </div>
                                <div class="cashback-item saldo-row" id="cashback-saldo-row" style="display: none;">
                                    <span class="cashback-label">Saldo Usado pelo Cliente:</span>
                                    <span class="cashback-value" id="display-saldo-usado">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Valor Efetivamente Pago:</span>
                                    <span class="cashback-value" id="display-valor-pago">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Cashback do Cliente (5%):</span>
                                    <span class="cashback-value" id="display-valor-cliente">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Receita Klube Cash (5%):</span>
                                    <span class="cashback-value" id="display-valor-admin">R$ 0,00</span>
                                </div>
                                <div class="cashback-item total">
                                    <span class="cashback-label">Comissão Total a Pagar (10%):</span>
                                    <span class="cashback-value" id="display-valor-total">R$ 0,00</span>
                                </div>
                            </div>
                            <div class="cashback-note">
                                <p>* A comissão é calculada sobre o valor efetivamente pago (após desconto do saldo usado).</p>
                                <p>* O cashback será liberado para o cliente após o pagamento e aprovação da comissão.</p>
                                <p>* Sua loja não recebe cashback - você paga 10% que são distribuídos: 5% cliente + 5% Klube Cash.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Registrar Venda</button>
                            <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Seção de ajuda existente -->
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
                            <span>Posso usar o saldo do cliente na venda?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Sim! Se o cliente possui saldo de cashback em sua loja, você pode usar parte ou todo o saldo na nova venda. Basta buscar o cliente pelo email e escolher o valor a ser usado.</p>
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
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Variáveis globais
        let clientData = null;
        let clientBalance = 0;
        const storeId = <?php echo $storeId; ?>;
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            const valorInput = document.getElementById('valor_total');
            const emailInput = document.getElementById('cliente_email');
            const searchBtn = document.getElementById('searchClientBtn');
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            const valorSaldoUsado = document.getElementById('valorSaldoUsado');
            
            // Event listeners
            valorInput.addEventListener('input', calcularAutomatico);
            valorInput.addEventListener('blur', calcularAutomatico);
            emailInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarCliente();
                }
            });
            searchBtn.addEventListener('click', buscarCliente);
            
            // Event listeners para uso de saldo
            usarSaldoCheck.addEventListener('change', toggleUsarSaldo);
            valorSaldoUsado.addEventListener('input', calcularPreview);
            valorSaldoUsado.addEventListener('blur', atualizarSimulacao);
            
            // Botões de saldo
            document.getElementById('usarTodoSaldo').addEventListener('click', () => {
                document.getElementById('valorSaldoUsado').value = clientBalance.toFixed(2);
                calcularPreview();
                atualizarSimulacao();
            });
            
            document.getElementById('usar50Saldo').addEventListener('click', () => {
                document.getElementById('valorSaldoUsado').value = (clientBalance * 0.5).toFixed(2);
                calcularPreview();
                atualizarSimulacao();
            });
            
            document.getElementById('limparSaldo').addEventListener('click', () => {
                document.getElementById('valorSaldoUsado').value = 0;
                calcularPreview();
                atualizarSimulacao();
            });
            
            // Accordion para ajuda
            setupAccordion();
            
            // Inicializar simulação
            atualizarSimulacao();
        });
        
        // Função para buscar cliente
        async function buscarCliente() {
            const email = document.getElementById('cliente_email').value.trim();
            const searchBtn = document.getElementById('searchClientBtn');
            const clientInfoCard = document.getElementById('clientInfoCard');
            
            if (!email) {
                alert('Por favor, digite um email válido');
                return;
            }
            
            // Mostrar loading
            searchBtn.disabled = true;
            searchBtn.querySelector('.btn-text').textContent = 'Buscando...';
            searchBtn.querySelector('.loading-spinner').style.display = 'inline-block';
            
            try {
                const response = await fetch('../../api/store-client-search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'search_client',
                        email: email,
                        store_id: storeId
                    })
                });
                
                const data = await response.json();
                
                if (data.status) {
                    clientData = data.data;
                    clientBalance = data.data.saldo || 0;
                    mostrarInfoCliente(data.data);
                    mostrarSecaoSaldo();
                } else {
                    mostrarErroCliente(data.message);
                    esconderSecaoSaldo();
                }
            } catch (error) {
                console.error('Erro ao buscar cliente:', error);
                mostrarErroCliente('Erro ao buscar cliente. Tente novamente.');
                esconderSecaoSaldo();
            } finally {
                // Esconder loading
                searchBtn.disabled = false;
                searchBtn.querySelector('.btn-text').textContent = 'Buscar Cliente';
                searchBtn.querySelector('.loading-spinner').style.display = 'none';
            }
        }
        
        // Função para mostrar informações do cliente
        function mostrarInfoCliente(client) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            // Remover classes de erro
            clientInfoCard.className = 'client-info-card success';
            clientInfoCard.style.display = 'block';
            
            clientInfoTitle.textContent = 'Cliente Encontrado';
            
            let detailsHTML = `
                <div class="client-info-item">
                    <span class="client-info-label">Nome:</span>
                    <span class="client-info-value">${client.nome}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Email:</span>
                    <span class="client-info-value">${client.email}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Status:</span>
                    <span class="client-info-value">Cliente ativo</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Saldo disponível:</span>
                    <span class="client-info-value">${client.saldo > 0 ? 'R$ ' + formatCurrency(client.saldo) : 'Nenhum saldo disponível'}</span>
                </div>
            `;
            
            clientInfoDetails.innerHTML = detailsHTML;
        }
        
        // Função para mostrar erro na busca do cliente
        function mostrarErroCliente(message) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            clientInfoCard.className = 'client-info-card error';
            clientInfoCard.style.display = 'block';
            
            clientInfoTitle.textContent = 'Cliente Não Encontrado';
            clientInfoDetails.innerHTML = `
                <div class="client-info-item">
                    <span class="client-info-value">${message}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-value">Verifique se o email está correto e se o cliente está cadastrado no Klube Cash.</span>
                </div>
            `;
            
            // Limpar dados do cliente
            clientData = null;
            clientBalance = 0;
        }
        
        // Função para mostrar seção de saldo
        function mostrarSecaoSaldo() {
            const saldoSection = document.getElementById('saldoSection');
            const saldoDisponivel = document.getElementById('saldoDisponivel');
            const maxSaldo = document.getElementById('maxSaldo');
            const valorSaldoUsado = document.getElementById('valorSaldoUsado');
            
            if (clientBalance > 0) {
                saldoSection.style.display = 'block';
                saldoDisponivel.textContent = 'R$ ' + formatCurrency(clientBalance);
                maxSaldo.textContent = 'R$ ' + formatCurrency(clientBalance);
                valorSaldoUsado.max = clientBalance;
            } else {
                saldoSection.style.display = 'none';
            }
        }
        
        // Função para esconder seção de saldo
        function esconderSecaoSaldo() {
            document.getElementById('saldoSection').style.display = 'none';
            document.getElementById('usarSaldoCheck').checked = false;
            document.getElementById('saldoControls').style.display = 'none';
            document.getElementById('usar_saldo').value = 'nao';
            document.getElementById('valor_saldo_usado_hidden').value = '0';
        }
        
        // Função para alternar uso de saldo
        function toggleUsarSaldo() {
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            const saldoControls = document.getElementById('saldoControls');
            const usarSaldoHidden = document.getElementById('usar_saldo');
            
            console.log('Toggle saldo - checkbox:', usarSaldoCheck.checked);
            
            if (usarSaldoCheck.checked) {
                saldoControls.style.display = 'block';
                usarSaldoHidden.value = 'sim';  // STRING 'sim'
                calcularAutomatico();
            } else {
                saldoControls.style.display = 'none';
                usarSaldoHidden.value = 'nao';  // STRING 'nao'
                document.getElementById('valorSaldoUsado').value = 0;
                document.getElementById('valor_saldo_usado_hidden').value = '0';
                calcularPreview();
                atualizarSimulacao();
            }
            
            console.log('usar_saldo hidden value:', usarSaldoHidden.value);
        }
        
        // Função para calcular automaticamente quando sair do campo de valor total
        function calcularAutomatico() {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            
            if (clientBalance > 0 && usarSaldoCheck.checked && valorTotal > 0) {
                // Calcular o máximo de saldo que pode ser usado
                const maxSaldoUsavel = Math.min(clientBalance, valorTotal);
                document.getElementById('valorSaldoUsado').value = maxSaldoUsavel.toFixed(2);
                calcularPreview();
            }
            
            atualizarSimulacao();
        }
        
        // Função para calcular preview do uso de saldo
        function calcularPreview() {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const valorSaldoUsado = parseFloat(document.getElementById('valorSaldoUsado').value) || 0;
            const valorFinal = Math.max(0, valorTotal - valorSaldoUsado);
            
            // Atualizar preview visual
            document.getElementById('valorOriginal').textContent = 'R$ ' + formatCurrency(valorTotal);
            document.getElementById('valorSaldoUsadoPreview').textContent = 'R$ ' + formatCurrency(valorSaldoUsado);
            document.getElementById('valorFinal').textContent = 'R$ ' + formatCurrency(valorFinal);
            
            // CRÍTICO: Atualizar o campo hidden que será enviado
            document.getElementById('valor_saldo_usado_hidden').value = valorSaldoUsado;
            
            console.log('Preview calculado - Saldo usado:', valorSaldoUsado);
            
            // Validações
            const valorSaldoUsadoInput = document.getElementById('valorSaldoUsado');
            if (valorSaldoUsado > clientBalance) {
                valorSaldoUsadoInput.value = clientBalance.toFixed(2);
                calcularPreview();
                return;
            }
            
            if (valorSaldoUsado > valorTotal) {
                valorSaldoUsadoInput.value = valorTotal.toFixed(2);
                calcularPreview();
                return;
            }
        }
        
        // Função para formatar valores como moeda
        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Função para calcular e atualizar a simulação
      
        function atualizarSimulacao() {
            const valorInput = document.getElementById('valor_total');
            const displayValorVenda = document.getElementById('display-valor-venda');
            const displaySaldoUsado = document.getElementById('display-saldo-usado');
            const displayValorPago = document.getElementById('display-valor-pago');
            const displayValorCliente = document.getElementById('display-valor-cliente');
            const displayValorAdmin = document.getElementById('display-valor-admin');
            const displayValorTotal = document.getElementById('display-valor-total');
            const saldoRow = document.getElementById('cashback-saldo-row');
            const noteSaldo = document.getElementById('cashback-note-saldo');
            
            let valorTotal = parseFloat(valorInput.value) || 0;
            
            // Verificar se está usando saldo
            const usarSaldo = document.getElementById('usar_saldo').value === 'sim';
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;
            
            let valorPago = valorTotal;
            if (usarSaldo && valorSaldoUsado > 0) {
                valorPago = Math.max(0, valorTotal - valorSaldoUsado);
                saldoRow.style.display = 'flex';
            } else {
                saldoRow.style.display = 'none';
            }
            
            // CORREÇÃO: Porcentagens fixas conforme especificação
            const porcentagemCliente = 5.00;  // Cliente sempre recebe 5%
            const porcentagemAdmin = 5.00;    // Admin sempre recebe 5%
            const porcentagemTotal = 10.00;   // Total sempre 10%
            
            // Calcular cashback sobre o valor PAGO (não sobre o valor total)
            const valorCliente = valorPago * porcentagemCliente / 100;
            const valorAdmin = valorPago * porcentagemAdmin / 100;
            const valorTotalComissao = valorPago * porcentagemTotal / 100;
            
            // Atualizar displays
            displayValorVenda.textContent = `R$ ${formatCurrency(valorTotal)}`;
            displaySaldoUsado.textContent = `R$ ${formatCurrency(valorSaldoUsado)}`;
            displayValorPago.textContent = `R$ ${formatCurrency(valorPago)}`;
            displayValorCliente.textContent = `R$ ${formatCurrency(valorCliente)}`;
            displayValorAdmin.textContent = `R$ ${formatCurrency(valorAdmin)}`;
            displayValorTotal.textContent = `R$ ${formatCurrency(valorTotalComissao)}`;
        }
        
        // Função para setup do accordion (mantida do código original)
        function setupAccordion() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                const icon = item.querySelector('.accordion-icon');
                
                header.addEventListener('click', () => {
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
        }
        
        // Validação de formulário
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            console.log('Enviando formulário...');
            console.log('usar_saldo:', document.getElementById('usar_saldo').value);
            console.log('valor_saldo_usado:', document.getElementById('valor_saldo_usado_hidden').value);
            
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;
            
            if (valorTotal <= 0) {
                e.preventDefault();
                alert('Por favor, informe o valor total da venda');
                return;
            }
            
            if (!clientData) {
                e.preventDefault();
                alert('Por favor, busque e selecione um cliente antes de registrar a venda');
                return;
            }
            
            // Debug final antes do envio
            console.log('Formulário validado - enviando...');
        });
    </script>
    
</body>
</html>