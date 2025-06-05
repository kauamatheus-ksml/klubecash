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
    $clientId = intval($_POST['cliente_id_hidden'] ?? 0); // Usar o ID do cliente do campo hidden
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
    

    if ($clientId <= 0) {
        $error = 'Cliente não selecionado. Por favor, busque e selecione um cliente.';
    } else {
        // Buscar usuário pelo ID
        $userQuery = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = :id AND tipo = :tipo AND status = :status");
        $userQuery->bindParam(':id', $clientId, PDO::PARAM_INT);
        $tipoCliente = USER_TYPE_CLIENT;
        $userQuery->bindParam(':tipo', $tipoCliente);
        $status = USER_ACTIVE;
        $userQuery->bindParam(':status', $status);
        $userQuery->execute();

        if ($userQuery->rowCount() === 0) {
            $error = 'Cliente não encontrado ou não está ativo. Verifique o cliente selecionado.';
        } else {
            $client = $userQuery->fetch(PDO::FETCH_ASSOC);
        }
    }
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
                'usuario_id' => $client['id'], // Já estava correto
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
                                <label for="search_term">Buscar Cliente (Email ou CPF)*</label>
                                <div class="client-search-container">
                                    <div class="email-input-group"> 
                                        <div class="email-input-wrapper"> 
                                            <input type="text" id="search_term" name="search_term"
                                                placeholder="Digite o Email ou CPF do cliente" required
                                                value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : (isset($transactionData['cliente_email']) ? htmlspecialchars($transactionData['cliente_email']) : ''); ?>">
                                            <small>Digite o email ou CPF completo do cliente cadastrado no Klube Cash.</small>
                                        </div>
                                        <button type="button" id="searchClientBtn" class="search-client-btn">
                                            <span class="btn-text">Buscar Cliente</span>
                                            <span class="loading-spinner" style="display: none;"></span>
                                        </button>
                                    </div>

                                    <div id="clientInfoCard" class="client-info-card">
                                        <div class="client-info-header">
                                            <svg class="client-info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <h4 class="client-info-title" id="clientInfoTitle">Informações do Cliente</h4>
                                        </div>
                                        <div class="client-info-details" id="clientInfoDetails">
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
                                <div class="codigo-input-group">
                                    <input type="text" id="codigo_transacao" name="codigo_transacao" required
                                        value="<?php echo isset($transactionData['codigo_transacao']) ? htmlspecialchars($transactionData['codigo_transacao']) : ''; ?>"
                                        placeholder="Código/número da venda no seu sistema">
                                    <button type="button" id="generateCodeBtn" class="generate-code-btn" title="Gerar código automaticamente">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2v4"></path>
                                            <path d="m16.2 7.8 2.9-2.9"></path>
                                            <path d="M18 12h4"></path>
                                            <path d="m16.2 16.2 2.9 2.9"></path>
                                            <path d="M12 18v4"></path>
                                            <path d="m4.9 19.1 2.9-2.9"></path>
                                            <path d="M2 12h4"></path>
                                            <path d="m4.9 4.9 2.9 2.9"></path>
                                        </svg>
                                        <span class="btn-text">Gerar</span>
                                    </button>
                                </div>
                                <small>Identificador único da venda. Use seu código interno ou clique em "Gerar" para criar automaticamente.</small>
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
                        <input type="hidden" id="cliente_id_hidden" name="cliente_id" value=""> {/* NOVO CAMPO */}
                        
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
    
    <<script>
        // ========================================
        // VARIÁVEIS GLOBAIS
        // ========================================

        /**
         * Dados do cliente selecionado
         * @type {Object|null}
         */
        let clientData = null;

        /**
         * Saldo disponível do cliente na loja atual
         * @type {number}
         */
        let clientBalance = 0;

        /**
         * ID da loja atual (vem do PHP)
         * @type {number}
         */
        const storeId = <?php echo $storeId; ?>;

        // ========================================
        // INICIALIZAÇÃO DA PÁGINA
        // ========================================

        /**
         * Inicializa todos os event listeners e configurações quando a página carrega
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Obter referências dos elementos principais
            const valorInput = document.getElementById('valor_total');
            const searchInput = document.getElementById('search_term'); // Modificado de emailInput e cliente_email
            const searchBtn = document.getElementById('searchClientBtn');
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            const valorSaldoUsado = document.getElementById('valorSaldoUsado');
            const generateCodeBtn = document.getElementById('generateCodeBtn');
            
            // Event listeners para valor total (recalcula automaticamente)
            valorInput.addEventListener('input', calcularAutomatico);
            valorInput.addEventListener('blur', calcularAutomatico);
            
            // Event listeners para busca de cliente
            searchInput.addEventListener('keypress', function(e) { // Modificado de emailInput
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
            
            // Event listeners para botões de saldo rápido
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
            
            // Event listener para botão de gerar código
            if (generateCodeBtn) {
                generateCodeBtn.addEventListener('click', gerarCodigoTransacao);
            }
            
            // Inicializar componentes da página
            setupAccordion();
            atualizarSimulacao();
            adicionarNotificationStyles();
        });

        // ========================================
        // FUNÇÕES DE BUSCA DE CLIENTE
        // ========================================

        /**
         * Busca cliente pelo email informado via API
         * Exibe informações do cliente e habilita funcionalidades de saldo
         */
        async function buscarCliente() {
            const searchTerm = document.getElementById('search_term').value.trim(); // Modificado de cliente_email para search_term
            const searchBtn = document.getElementById('searchClientBtn');
            const clientInfoCard = document.getElementById('clientInfoCard');

            // Validar se termo de busca foi informado
            if (!searchTerm) { // Modificado de !email
                alert('Por favor, digite um email ou CPF válido');
                return;
            }

            // Ativar estado de loading no botão
            searchBtn.disabled = true;
            searchBtn.querySelector('.btn-text').textContent = 'Buscando...';
            searchBtn.querySelector('.loading-spinner').style.display = 'inline-block';

            
            try {
                // Fazer requisição para API de busca de cliente
                const response = await fetch('../../api/store-client-search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'search_client',
                        search_term: searchTerm, // Modificado de email: email para search_term: searchTerm
                        store_id: storeId
                    })
                });
                
                const data = await response.json();
                
                // Processar resposta da API
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
                // Restaurar estado normal do botão
                searchBtn.disabled = false;
                searchBtn.querySelector('.btn-text').textContent = 'Buscar Cliente';
                searchBtn.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        /**
         * Exibe as informações do cliente encontrado no card de informações
         * @param {Object} client - Dados do cliente retornados pela API
         */
        function mostrarInfoCliente(client) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            // Configurar card como sucesso e torná-lo visível
            clientInfoCard.className = 'client-info-card success';
            clientInfoCard.style.display = 'block';
            clientInfoTitle.textContent = 'Cliente Encontrado';
            
            // Montar HTML com informações do cliente
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
            document.getElementById('cliente_id_hidden').value = client.id; // ADICIONAR ESTA LINHA

        }

        /**
         * Exibe mensagem de erro quando cliente não é encontrado
         * @param {string} message - Mensagem de erro a ser exibida
         */
        function mostrarErroCliente(message) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            // Configurar card como erro
            clientInfoCard.className = 'client-info-card error';
            clientInfoCard.style.display = 'block';
            clientInfoTitle.textContent = 'Cliente Não Encontrado';
            
            // Exibir mensagem de erro
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
            document.getElementById('cliente_id_hidden').value = ''; // ADICIONAR ESTA LINHA (limpar ID)
        }

        // ========================================
        // FUNÇÕES DE GERENCIAMENTO DE SALDO
        // ========================================

        /**
         * Exibe a seção de uso de saldo quando cliente tem saldo disponível
         */
        function mostrarSecaoSaldo() {
            const saldoSection = document.getElementById('saldoSection');
            const saldoDisponivel = document.getElementById('saldoDisponivel');
            const maxSaldo = document.getElementById('maxSaldo');
            const valorSaldoUsado = document.getElementById('valorSaldoUsado');
            
            // Só mostrar se cliente tem saldo
            if (clientBalance > 0) {
                saldoSection.style.display = 'block';
                saldoDisponivel.textContent = 'R$ ' + formatCurrency(clientBalance);
                maxSaldo.textContent = 'R$ ' + formatCurrency(clientBalance);
                valorSaldoUsado.max = clientBalance;
            } else {
                saldoSection.style.display = 'none';
            }
        }

        /**
         * Esconde a seção de saldo e reseta todos os valores relacionados
         */
        function esconderSecaoSaldo() {
            document.getElementById('saldoSection').style.display = 'none';
            document.getElementById('usarSaldoCheck').checked = false;
            document.getElementById('saldoControls').style.display = 'none';
            document.getElementById('usar_saldo').value = 'nao';
            document.getElementById('valor_saldo_usado_hidden').value = '0';
        }

        /**
         * Alterna entre usar ou não usar saldo do cliente na transação
         */
        function toggleUsarSaldo() {
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            const saldoControls = document.getElementById('saldoControls');
            const usarSaldoHidden = document.getElementById('usar_saldo');
            
            console.log('Toggle saldo - checkbox:', usarSaldoCheck.checked);
            
            if (usarSaldoCheck.checked) {
                // Habilitar uso de saldo
                saldoControls.style.display = 'block';
                usarSaldoHidden.value = 'sim';
                calcularAutomatico();
            } else {
                // Desabilitar uso de saldo
                saldoControls.style.display = 'none';
                usarSaldoHidden.value = 'nao';
                document.getElementById('valorSaldoUsado').value = 0;
                document.getElementById('valor_saldo_usado_hidden').value = '0';
                calcularPreview();
                atualizarSimulacao();
            }
            
            console.log('usar_saldo hidden value:', usarSaldoHidden.value);
        }

        // ========================================
        // FUNÇÕES DE CÁLCULO E SIMULAÇÃO
        // ========================================

        /**
         * Calcula automaticamente o saldo máximo a ser usado quando valor total muda
         */
        function calcularAutomatico() {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            
            // Se tem saldo, está habilitado para usar e tem valor total
            if (clientBalance > 0 && usarSaldoCheck.checked && valorTotal > 0) {
                // Calcular o máximo de saldo que pode ser usado (menor entre saldo disponível e valor da venda)
                const maxSaldoUsavel = Math.min(clientBalance, valorTotal);
                document.getElementById('valorSaldoUsado').value = maxSaldoUsavel.toFixed(2);
                calcularPreview();
            }
            
            atualizarSimulacao();
        }

        /**
         * Calcula e atualiza o preview do uso de saldo em tempo real
         */
        function calcularPreview() {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const valorSaldoUsado = parseFloat(document.getElementById('valorSaldoUsado').value) || 0;
            const valorFinal = Math.max(0, valorTotal - valorSaldoUsado);
            
            // Atualizar preview visual na seção de saldo
            document.getElementById('valorOriginal').textContent = 'R$ ' + formatCurrency(valorTotal);
            document.getElementById('valorSaldoUsadoPreview').textContent = 'R$ ' + formatCurrency(valorSaldoUsado);
            document.getElementById('valorFinal').textContent = 'R$ ' + formatCurrency(valorFinal);
            
            // CRÍTICO: Atualizar o campo hidden que será enviado no formulário
            document.getElementById('valor_saldo_usado_hidden').value = valorSaldoUsado;
            
            console.log('Preview calculado - Saldo usado:', valorSaldoUsado);
            
            // Validações para evitar valores inválidos
            const valorSaldoUsadoInput = document.getElementById('valorSaldoUsado');
            
            // Não pode usar mais saldo que o disponível
            if (valorSaldoUsado > clientBalance) {
                valorSaldoUsadoInput.value = clientBalance.toFixed(2);
                calcularPreview();
                return;
            }
            
            // Não pode usar mais saldo que o valor total da venda
            if (valorSaldoUsado > valorTotal) {
                valorSaldoUsadoInput.value = valorTotal.toFixed(2);
                calcularPreview();
                return;
            }
        }

        /**
         * Atualiza a simulação completa de cashback e comissões
         */
        function atualizarSimulacao() {
            const valorInput = document.getElementById('valor_total');
            const displayValorVenda = document.getElementById('display-valor-venda');
            const displaySaldoUsado = document.getElementById('display-saldo-usado');
            const displayValorPago = document.getElementById('display-valor-pago');
            const displayValorCliente = document.getElementById('display-valor-cliente');
            const displayValorAdmin = document.getElementById('display-valor-admin');
            const displayValorTotal = document.getElementById('display-valor-total');
            const saldoRow = document.getElementById('cashback-saldo-row');
            
            let valorTotal = parseFloat(valorInput.value) || 0;
            
            // Verificar se está usando saldo
            const usarSaldo = document.getElementById('usar_saldo').value === 'sim';
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;
            
            // Calcular valor efetivamente pago
            let valorPago = valorTotal;
            if (usarSaldo && valorSaldoUsado > 0) {
                valorPago = Math.max(0, valorTotal - valorSaldoUsado);
                saldoRow.style.display = 'flex'; // Mostrar linha do saldo usado
            } else {
                saldoRow.style.display = 'none'; // Esconder linha do saldo usado
            }
            
            // Porcentagens fixas do sistema Klube Cash
            const porcentagemCliente = 5.00;  // Cliente sempre recebe 5%
            const porcentagemAdmin = 5.00;    // Admin sempre recebe 5%
            const porcentagemTotal = 10.00;   // Total sempre 10%
            
            // Calcular cashback sobre o valor EFETIVAMENTE PAGO (não sobre o valor total)
            const valorCliente = valorPago * porcentagemCliente / 100;
            const valorAdmin = valorPago * porcentagemAdmin / 100;
            const valorTotalComissao = valorPago * porcentagemTotal / 100;
            
            // Atualizar todos os displays da simulação
            displayValorVenda.textContent = `R$ ${formatCurrency(valorTotal)}`;
            displaySaldoUsado.textContent = `R$ ${formatCurrency(valorSaldoUsado)}`;
            displayValorPago.textContent = `R$ ${formatCurrency(valorPago)}`;
            displayValorCliente.textContent = `R$ ${formatCurrency(valorCliente)}`;
            displayValorAdmin.textContent = `R$ ${formatCurrency(valorAdmin)}`;
            displayValorTotal.textContent = `R$ ${formatCurrency(valorTotalComissao)}`;
        }

        // ========================================
        // FUNÇÕES DE GERAÇÃO DE CÓDIGO
        // ========================================

        /**
         * Gera automaticamente um código único para a transação
         */
        function gerarCodigoTransacao() {
            const generateBtn = document.getElementById('generateCodeBtn');
            const codigoInput = document.getElementById('codigo_transacao');
            
            // Ativar estado de loading
            generateBtn.classList.add('generating');
            generateBtn.disabled = true;
            
            // Simular um pequeno delay para melhor UX
            setTimeout(() => {
                // Obter data e hora atual
                const agora = new Date();
                const ano = agora.getFullYear().toString().slice(-2);
                const mes = String(agora.getMonth() + 1).padStart(2, '0');
                const dia = String(agora.getDate()).padStart(2, '0');
                const hora = String(agora.getHours()).padStart(2, '0');
                const minuto = String(agora.getMinutes()).padStart(2, '0');
                const segundo = String(agora.getSeconds()).padStart(2, '0');
                
                // Gerar números aleatórios para garantir unicidade
                const random1 = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                const random2 = Math.floor(Math.random() * 100).toString().padStart(2, '0');
                
                // Formato final: KC + AAMMDD + HHMMSS + Random
                // Exemplo: KC240327142530001
                const codigo = `KC${ano}${mes}${dia}${hora}${minuto}${segundo}${random1}${random2}`;
                
                // Definir o código no input
                codigoInput.value = codigo;
                
                // Adicionar feedback visual temporário
                codigoInput.classList.add('codigo-gerado');
                setTimeout(() => {
                    codigoInput.classList.remove('codigo-gerado');
                }, 2000);
                
                // Remover estado de loading
                generateBtn.classList.remove('generating');
                generateBtn.disabled = false;
                
                // Focar no próximo campo
                const nextField = document.getElementById('data_transacao');
                if (nextField) {
                    nextField.focus();
                }
                
                // Mostrar notificação de sucesso
                mostrarNotificacao('Código gerado com sucesso!', 'success');
                
            }, 800); // Delay de 800ms para melhor experiência
        }

        // ========================================
        // FUNÇÕES DE UTILIDADE
        // ========================================

        /**
         * Formata número como moeda brasileira
         * @param {number} value - Valor a ser formatado
         * @returns {string} - Valor formatado como moeda
         */
        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        /**
         * Configura o funcionamento do accordion na seção de ajuda
         */
        function setupAccordion() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                const icon = item.querySelector('.accordion-icon');
                
                header.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    
                    // Fechar todos os itens primeiro
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

        /**
         * Mostra notificação temporária na tela
         * @param {string} mensagem - Texto da notificação
         * @param {string} tipo - Tipo da notificação (success, info, warning, error)
         */
        function mostrarNotificacao(mensagem, tipo = 'info') {
            // Criar elemento de notificação
            const notification = document.createElement('div');
            notification.className = `notification ${tipo}`;
            notification.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>${mensagem}</span>
            `;
            
            // Aplicar estilos inline
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${tipo === 'success' ? 'var(--success-color)' : 'var(--info-color)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--border-radius-sm);
                box-shadow: var(--shadow-lg);
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-size: var(--font-size-sm);
                font-weight: 600;
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                max-width: 300px;
            `;
            
            // Adicionar ao body
            document.body.appendChild(notification);
            
            // Remover após 3 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        /**
         * Adiciona estilos CSS para as animações das notificações
         */
        function adicionarNotificationStyles() {
            const notificationStyles = document.createElement('style');
            notificationStyles.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(notificationStyles);
        }

        // ========================================
        // VALIDAÇÃO DO FORMULÁRIO
        // ========================================

        /**
         * Valida o formulário antes do envio
         */
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            console.log('Enviando formulário...');
            console.log('usar_saldo:', document.getElementById('usar_saldo').value);
            console.log('valor_saldo_usado:', document.getElementById('valor_saldo_usado_hidden').value);
            
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;
            
            // Validar valor total
            if (valorTotal <= 0) {
                e.preventDefault();
                alert('Por favor, informe o valor total da venda');
                return;
            }
            
            // Validar se cliente foi selecionado
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