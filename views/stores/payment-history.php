<?php
// views/stores/payment-history.php
// Definir o menu ativo na sidebar
$activeMenu = 'payment-history';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';

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

// Definir parâmetros de paginação e filtros
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

// Aplicar filtros se fornecidos
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}
if (isset($_GET['metodo_pagamento']) && !empty($_GET['metodo_pagamento'])) {
    $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
}

// Obter histórico de pagamentos
$result = TransactionController::getPaymentHistory($storeId, $filters, $page);

// Calcular estatísticas
$totalPagamentos = 0;
$totalAprovados = 0;
$totalPendentes = 0;
$totalRejeitados = 0;
$valorTotalPagamentos = 0;

if ($result['status'] && isset($result['data']['pagamentos'])) {
    foreach ($result['data']['pagamentos'] as $payment) {
        $totalPagamentos++;
        $valorTotalPagamentos += $payment['valor_total'];
        
        if ($payment['status'] === 'aprovado') {
            $totalAprovados++;
        } elseif ($payment['status'] === 'pendente') {
            $totalPendentes++;
        } elseif ($payment['status'] === 'rejeitado') {
            $totalRejeitados++;
        }
    }
}

// Método de pagamento para exibição
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
    <title>Histórico de Pagamentos - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/payment-history.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Histórico de Pagamentos</h1>
                <p class="subtitle">Acompanhe todos os pagamentos de comissões realizados</p>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total de Pagamentos</div>
                    <div class="stat-card-value"><?php echo number_format($totalPagamentos); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Pagamentos Aprovados</div>
                    <div class="stat-card-value"><?php echo number_format($totalAprovados); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Pagamentos Pendentes</div>
                    <div class="stat-card-value"><?php echo number_format($totalPendentes); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total Pago</div>
                    <div class="stat-card-value">R$ <?php echo number_format($valorTotalPagamentos, 2, ',', '.'); ?></div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card filter-container">
                <div class="card-header">
                    <div class="card-title">Filtros</div>
                </div>
                <div class="filter-form">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                    <option value="rejeitado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="metodo_pagamento">Método de Pagamento</label>
                                <select id="metodo_pagamento" name="metodo_pagamento">
                                    <option value="">Todos</option>
                                    <?php foreach ($metodosPagamento as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Listagem de Pagamentos -->
            <div class="card payments-container">
                <div class="card-header">
                    <div class="card-title">Histórico de Pagamentos</div>
                </div>
                
                <?php if ($result['status'] && count($result['data']['pagamentos']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Transações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data']['pagamentos'] as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></td>
                                        <td>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></td>
                                        <td><?php echo isset($metodosPagamento[$payment['metodo_pagamento']]) ? $metodosPagamento[$payment['metodo_pagamento']] : $payment['metodo_pagamento']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php 
                                                    switch($payment['status']) {
                                                        case 'aprovado':
                                                            echo 'Aprovado';
                                                            break;
                                                        case 'pendente':
                                                            echo 'Pendente';
                                                            break;
                                                        case 'rejeitado':
                                                            echo 'Rejeitado';
                                                            break;
                                                        default:
                                                            echo ucfirst($payment['status']);
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $payment['qtd_transacoes']; ?></td>
                                        <td>
                                            <button class="btn btn-action" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">Detalhes</button>
                                            <?php if (!empty($payment['comprovante'])): ?>
                                                <button class="btn btn-action" onclick="viewReceipt('<?php echo htmlspecialchars($payment['comprovante']); ?>')">Comprovante</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($result['data']['paginacao']['total_paginas'] > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $result['data']['paginacao']['pagina_atual']; ?> de <?php echo $result['data']['paginacao']['total_paginas']; ?>
                            </div>
                            <div class="pagination-links">
                                <?php if ($result['data']['paginacao']['pagina_atual'] > 1): ?>
                                    <a href="?page=1<?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Primeira</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Anterior</a>
                                <?php endif; ?>
                                
                                <?php if ($result['data']['paginacao']['pagina_atual'] < $result['data']['paginacao']['total_paginas']): ?>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Próxima</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['total_paginas']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Última</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                <line x1="2" y1="9" x2="22" y2="9"></line>
                            </svg>
                        </div>
                        <h3>Nenhum pagamento encontrado</h3>
                        <p>Não foram encontrados pagamentos com os filtros selecionados.</p>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-primary">Ver Comissões Pendentes</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Informações sobre Status -->
            <div class="card info-card">
                <div class="card-header">
                    <div class="card-title">Sobre os Status de Pagamento</div>
                </div>
                <div class="status-info">
                    <div class="status-item">
                        <span class="status-badge status-pendente">Pendente</span>
                        <p>O pagamento foi registrado e está aguardando a análise do administrador.</p>
                    </div>
                    <div class="status-item">
                        <span class="status-badge status-aprovado">Aprovado</span>
                        <p>O pagamento foi confirmado e o cashback já foi liberado para os clientes.</p>
                    </div>
                    <div class="status-item">
                        <span class="status-badge status-rejeitado">Rejeitado</span>
                        <p>O pagamento foi rejeitado pelo administrador. Verifique o motivo nos detalhes e faça um novo pagamento.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes de Pagamento -->
    <div id="paymentDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Comprovante -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprovante de Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body receipt-container" id="receiptContent">
                <div class="receipt-image-container">
                    <img id="receiptImage" src="" alt="Comprovante de Pagamento">
                </div>
            </div>
        </div>
    </div>
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos dos modais - obtendo referências dos elementos DOM
    const paymentDetailsModal = document.getElementById('paymentDetailsModal');
    const receiptModal = document.getElementById('receiptModal');
    const paymentDetailsContent = document.getElementById('paymentDetailsContent');
    const receiptImage = document.getElementById('receiptImage');
    
    // Configuração dos botões de fechar modais
    // Este código adiciona event listeners para todos os elementos com classe 'close'
    const closeButtons = document.getElementsByClassName('close');
    for (let i = 0; i < closeButtons.length; i++) {
        closeButtons[i].addEventListener('click', function() {
            paymentDetailsModal.style.display = 'none';
            receiptModal.style.display = 'none';
        });
    }
    
    // Fechar modal quando clicar fora dela (no backdrop)
    // Esta funcionalidade melhora a experiência do usuário
    window.addEventListener('click', function(event) {
        if (event.target === paymentDetailsModal) {
            paymentDetailsModal.style.display = 'none';
        }
        if (event.target === receiptModal) {
            receiptModal.style.display = 'none';
        }
    });
    
    // Função principal para visualizar detalhes do pagamento
    // CORREÇÃO PRINCIPAL: Mudança da API para o TransactionController
    window.viewPaymentDetails = function(paymentId) {
        // Validação básica do ID do pagamento
        if (!paymentId || paymentId <= 0) {
            alert('ID do pagamento inválido');
            return;
        }
        
        // Abrir modal e mostrar loading
        paymentDetailsModal.style.display = 'block';
        paymentDetailsContent.innerHTML = '<p>Carregando detalhes...</p>';
        
        // CORREÇÃO: Usar TransactionController ao invés da API separada
        // Mudando de GET para POST conforme esperado pelo controller
        fetch('../../controllers/TransactionController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            // Enviando dados no formato correto para o controller
            body: 'action=payment_details&payment_id=' + encodeURIComponent(paymentId)
        })
        .then(response => {
            // Verificar se a resposta HTTP foi bem-sucedida
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Processar resposta do servidor
            if (data && data.status) {
                renderPaymentDetails(data.data);
            } else {
                // Mostrar mensagem de erro específica retornada pelo servidor
                const errorMessage = data && data.message ? data.message : 'Erro desconhecido ao carregar detalhes';
                paymentDetailsContent.innerHTML = `<p class="error">Erro: ${errorMessage}</p>`;
            }
        })
        .catch(error => {
            // Tratamento de erros de conexão ou processamento
            console.error('Erro na requisição:', error);
            paymentDetailsContent.innerHTML = `
                <p class="error">
                    Erro de conexão. Verifique sua internet e tente novamente.
                    <br><small>Detalhes técnicos: ${error.message}</small>
                </p>
            `;
        });
    };
    
    // Função para visualizar comprovante de pagamento
    window.viewReceipt = function(receiptUrl) {
        // Validação da URL do comprovante
        if (!receiptUrl) {
            alert('Comprovante não disponível');
            return;
        }
        
        // Configurar a URL do comprovante e abrir modal
        receiptImage.src = '../../uploads/comprovantes/' + encodeURIComponent(receiptUrl);
        receiptModal.style.display = 'block';
        
        // Ajustar tamanho da imagem quando carregada
        receiptImage.onload = function() {
            if (receiptImage.height > 600) {
                receiptImage.style.height = '600px';
                receiptImage.style.width = 'auto';
            }
        };
        
        // Tratamento de erro no carregamento da imagem
        receiptImage.onerror = function() {
            alert('Erro ao carregar o comprovante. Arquivo pode estar corrompido ou não encontrado.');
            receiptModal.style.display = 'none';
        };
    };
    
    // Função para renderizar os detalhes do pagamento no modal
    // MELHORIAS: Adicionadas verificações de segurança e tratamento de dados nulos
    function renderPaymentDetails(data) {
        // Verificação de segurança - garantir que os dados existem
        if (!data || !data.pagamento) {
            paymentDetailsContent.innerHTML = '<p class="error">Dados do pagamento não encontrados.</p>';
            return;
        }
        
        const payment = data.pagamento;
        const transactions = data.transacoes || [];
        
        // Construção do HTML de forma segura com verificações
        let html = `
            <div class="payment-summary">
                <div class="summary-row">
                    <span class="summary-label">ID do Pagamento:</span>
                    <span class="summary-value">#${escapeHtml(payment.id || 'N/A')}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Data do Registro:</span>
                    <span class="summary-value">${formatDate(payment.data_registro)}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Valor Total:</span>
                    <span class="summary-value">R$ ${formatCurrency(payment.valor_total)}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Método de Pagamento:</span>
                    <span class="summary-value">${getPaymentMethodName(payment.metodo_pagamento)}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Status:</span>
                    <span class="summary-value status-badge status-${payment.status}">${getStatusName(payment.status)}</span>
                </div>
                ${payment.numero_referencia ? `
                <div class="summary-row">
                    <span class="summary-label">Número de Referência:</span>
                    <span class="summary-value">${escapeHtml(payment.numero_referencia)}</span>
                </div>
                ` : ''}
            </div>
        `;
        
        // Seção de informações de aprovação/rejeição
        if (payment.status && payment.status !== 'pendente') {
            html += `
                <div class="approval-info">
                    <h3>${payment.status === 'aprovado' ? 'Informações de Aprovação' : 'Motivo da Rejeição'}</h3>
                    <div class="approval-details">
                        ${payment.data_aprovacao ? `
                        <div class="approval-row">
                            <span class="approval-label">Data:</span>
                            <span class="approval-value">${formatDate(payment.data_aprovacao)}</span>
                        </div>
                        ` : ''}
                        ${payment.observacao_admin ? `
                        <div class="approval-row">
                            <span class="approval-label">Observação:</span>
                            <span class="approval-value">${escapeHtml(payment.observacao_admin)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Lista de transações incluídas no pagamento
        html += `
            <div class="transactions-list">
                <h3>Transações Incluídas (${transactions.length})</h3>
                ${transactions.length > 0 ? `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Cashback</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactions.map(transaction => `
                                <tr>
                                    <td>${escapeHtml(transaction.codigo_transacao || 'N/A')}</td>
                                    <td>${escapeHtml(transaction.cliente_nome || 'N/A')}</td>
                                    <td>${formatDate(transaction.data_transacao)}</td>
                                    <td>R$ ${formatCurrency(transaction.valor_total)}</td>
                                    <td>R$ ${formatCurrency(transaction.valor_cliente)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : '<p>Nenhuma transação associada a este pagamento.</p>'}
            </div>
        `;
        
        // Observações da loja sobre o pagamento
        if (payment.observacao) {
            html += `
                <div class="payment-notes">
                    <h3>Suas Observações</h3>
                    <p>${escapeHtml(payment.observacao)}</p>
                </div>
            `;
        }
        
        // Ações disponíveis para pagamentos rejeitados
        if (payment.status === 'rejeitado') {
            html += `
                <div class="payment-actions">
                    <a href="../../store/transacoes-pendentes" class="btn btn-primary">Realizar Novo Pagamento</a>
                </div>
            `;
        }
        
        // Inserir o HTML construído no modal
        paymentDetailsContent.innerHTML = html;
    }
    
    // Funções auxiliares para formatação e segurança
    
    // Formatar datas de forma segura
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            // Verificar se a data é válida
            if (isNaN(date.getTime())) return 'Data inválida';
            
            return date.toLocaleDateString('pt-BR') + ' ' + 
                   date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return 'Erro na data';
        }
    }
    
    // Formatar valores monetários de forma segura
    function formatCurrency(value) {
        // Converter para número e tratar valores inválidos
        const numValue = parseFloat(value) || 0;
        
        return numValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Obter nome do método de pagamento de forma legível
    function getPaymentMethodName(method) {
        const methods = {
            'pix': 'PIX',
            'transferencia': 'Transferência Bancária',
            'ted': 'TED',
            'boleto': 'Boleto',
            'cartao': 'Cartão de Crédito',
            'outro': 'Outro'
        };
        return methods[method] || 'Método não especificado';
    }
    
    // Obter nome do status de forma legível
    function getStatusName(status) {
        switch(status) {
            case 'aprovado': return 'Aprovado';
            case 'pendente': return 'Pendente';
            case 'rejeitado': return 'Rejeitado';
            default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Status desconhecido';
        }
    }
    
    // Função de segurança para escapar HTML e prevenir XSS
    function escapeHtml(text) {
        if (!text) return '';
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
</body>
</html>