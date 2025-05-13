<?php
// views/client/statement.php
// Definir o menu ativo para a navbar
$activeMenu = 'extrato';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ClientController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do usuário
$userId = $_SESSION['user_id'];

// Definir valores padrão para filtros e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [];

// Processar filtros se submetidos
if (isset($_GET['filtrar'])) {
    if (!empty($_GET['data_inicio'])) {
        $filters['data_inicio'] = $_GET['data_inicio'];
    }
    
    if (!empty($_GET['data_fim'])) {
        $filters['data_fim'] = $_GET['data_fim'];
    }
    
    if (!empty($_GET['loja_id']) && $_GET['loja_id'] != 'todas') {
        $filters['loja_id'] = $_GET['loja_id'];
    }
    
    if (!empty($_GET['status']) && $_GET['status'] != 'todos') {
        $filters['status'] = $_GET['status'];
    }
}

// Obter dados do extrato
$result = ClientController::getStatement($userId, $filters, $page);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição
$statementData = $hasError ? [] : $result['data'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extrato de Cashback - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --danger-color: #F44336;
            --warning-color: #FFC107;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
        }
        
        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Cabeçalho da página */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: var(--medium-gray);
            font-size: 16px;
        }
        
        /* Card para o conteúdo */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        /* Seção de filtros */
        .filters-section {
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        /* Resumo financeiro */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .summary-card {
            background-color: var(--primary-light);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-card-title {
            font-size: 14px;
            color: var(--medium-gray);
            margin-bottom: 5px;
        }
        
        .summary-card-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Tabela de extrato */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }
        
        .table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 2px solid #eee;
        }
        
        .table td {
            border-bottom: 1px solid #eee;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: var(--primary-light);
        }
        
        /* Badges de status */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #E6F7E6;
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: #FFF8E6;
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        
        .pagination-item {
            margin: 0 5px;
        }
        
        .pagination-link {
            display: block;
            padding: 8px 12px;
            background-color: var(--white);
            border-radius: 5px;
            color: var(--dark-gray);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid #eee;
        }
        
        .pagination-link:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .pagination-link.active {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        /* Estilos de botões */
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .btn-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Ícones nas ações */
        .btn-icon {
            display: flex;
            align-items: center;
        }
        
        .btn-icon svg {
            margin-right: 5px;
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        /* Modal para detalhes da transação */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 600px;
            padding: 30px;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--medium-gray);
        }
        
        .transaction-detail-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--dark-gray);
        }
        
        .transaction-detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 40%;
            font-weight: 600;
            color: var(--medium-gray);
        }
        
        .detail-value {
            width: 60%;
            color: var(--dark-gray);
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .btn-actions {
                flex-direction: column;
                width: 100%;
                margin-top: 15px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Cabeçalho da Página -->
        <div class="page-header">
            <div>
                <h1>Extrato de Cashback</h1>
                <p>Visualize e filtre suas transações de cashback</p>
            </div>
            <div class="btn-actions">
                <button class="btn btn-outline btn-icon" onclick="exportarExtrato()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Exportar PDF
                </button>
            </div>
        </div>
        
        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Filtros -->
        <div class="card filters-section">
            <h3 style="margin-bottom: 15px; font-size: 16px; color: var(--dark-gray);">Filtros</h3>
            <form action="" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label" for="data_inicio">Data Inicial</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo $filters['data_inicio'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="data_fim">Data Final</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo $filters['data_fim'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="loja_id">Loja</label>
                    <select id="loja_id" name="loja_id" class="form-control">
                        <option value="todas">Todas as Lojas</option>
                        <!-- Opções de lojas seriam inseridas aqui de forma dinâmica -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="todos">Todos</option>
                        <option value="aprovado" <?php echo (isset($filters['status']) && $filters['status'] == 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="pendente" <?php echo (isset($filters['status']) && $filters['status'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="cancelado" <?php echo (isset($filters['status']) && $filters['status'] == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" name="filtrar" value="1" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
        
        <!-- Resumo Financeiro -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Total de Compras</div>
                <div class="summary-card-value">R$ <?php echo number_format($statementData['estatisticas']['total_compras'] ?? 0, 2, ',', '.'); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Total de Cashback</div>
                <div class="summary-card-value">R$ <?php echo number_format($statementData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Transações</div>
                <div class="summary-card-value"><?php echo $statementData['estatisticas']['total_transacoes'] ?? 0; ?></div>
            </div>
        </div>
        
        <!-- Tabela de Extrato -->
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Loja</th>
                            <th>Valor da Compra</th>
                            <th>Cashback</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statementData['transacoes'])): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">Nenhuma transação encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statementData['transacoes'] as $transacao): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?></td>
                                    <td><?php echo htmlspecialchars($transacao['loja_nome']); ?></td>
                                    <td>R$ <?php echo number_format($transacao['valor_total'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($transacao['valor_cashback'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch ($transacao['status']) {
                                            case 'aprovado':
                                                $statusClass = 'badge-success';
                                                break;
                                            case 'pendente':
                                                $statusClass = 'badge-warning';
                                                break;
                                            case 'cancelado':
                                                $statusClass = 'badge-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($transacao['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 5px 10px;" onclick="verDetalhes(<?php echo $transacao['id']; ?>)">
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if (!empty($statementData['paginacao']) && $statementData['paginacao']['total_paginas'] > 1): ?>
                <ul class="pagination">
                    <?php 
                    $currentPage = $statementData['paginacao']['pagina_atual'];
                    $totalPages = $statementData['paginacao']['total_paginas'];
                    
                    // Construir parâmetros da URL
                    $urlParams = [];
                    foreach ($filters as $key => $value) {
                        $urlParams[] = "$key=" . urlencode($value);
                    }
                    $urlParams[] = "filtrar=1";
                    $queryString = !empty($urlParams) ? '&' . implode('&', $urlParams) : '';
                    
                    // Anterior
                    if ($currentPage > 1): 
                    ?>
                        <li class="pagination-item">
                            <a href="?page=<?php echo $currentPage - 1 . $queryString; ?>" class="pagination-link">
                                &laquo;
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Páginas
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $start + 4);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <li class="pagination-item">
                            <a href="?page=<?php echo $i . $queryString; ?>" class="pagination-link <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php 
                    // Próximo
                    if ($currentPage < $totalPages): 
                    ?>
                        <li class="pagination-item">
                            <a href="?page=<?php echo $currentPage + 1 . $queryString; ?>" class="pagination-link">
                                &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de Detalhes -->
    <div class="modal" id="detalheModal">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharModal()">&times;</button>
            <h2 class="transaction-detail-title">Detalhes da Transação</h2>
            <div id="detalheConteudo">
                <!-- Conteúdo será preenchido via JavaScript -->
                <div class="transaction-detail-row">
                    <div class="detail-label">ID da Transação:</div>
                    <div class="detail-value" id="transacao-id"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Data e Hora:</div>
                    <div class="detail-value" id="transacao-data"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Loja:</div>
                    <div class="detail-value" id="transacao-loja"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Valor da Compra:</div>
                    <div class="detail-value" id="transacao-valor"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Valor do Cashback:</div>
                    <div class="detail-value" id="transacao-cashback"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Percentual:</div>
                    <div class="detail-value" id="transacao-percentual"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value" id="transacao-status"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Descrição:</div>
                    <div class="detail-value" id="transacao-descricao"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Função para exportar extrato em PDF
        function exportarExtrato() {
            alert('Funcionalidade de exportação será implementada.');
            // Implementação real usaria uma biblioteca como jsPDF ou uma chamada ao servidor
        }
        
        // Função para exibir detalhes da transação
        function verDetalhes(transacaoId) {
            // Em um cenário real, seria feita uma requisição AJAX para buscar os detalhes da transação
            fetch(`<?php echo SITE_URL; ?>/controllers/ClientController.php?action=transaction&transaction_id=${transacaoId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const transacao = data.data.transacao;
                    
                    // Preencher os campos do modal
                    document.getElementById('transacao-id').textContent = transacao.id;
                    document.getElementById('transacao-data').textContent = formatarData(transacao.data_transacao);
                    document.getElementById('transacao-loja').textContent = transacao.loja_nome;
                    document.getElementById('transacao-valor').textContent = 'R$ ' + formatarValor(transacao.valor_total);
                    document.getElementById('transacao-cashback').textContent = 'R$ ' + formatarValor(transacao.valor_cashback);
                    
                    // Calcular percentual
                    const percentual = (transacao.valor_cashback / transacao.valor_total) * 100;
                    document.getElementById('transacao-percentual').textContent = formatarValor(percentual) + '%';
                    
                    // Status com formatação adequada
                    const statusElement = document.getElementById('transacao-status');
                    statusElement.textContent = capitalizarPrimeiraLetra(transacao.status);
                    statusElement.className = '';
                    
                    let statusClass = '';
                    switch (transacao.status) {
                        case 'aprovado':
                            statusClass = 'badge-success';
                            break;
                        case 'pendente':
                            statusClass = 'badge-warning';
                            break;
                        case 'cancelado':
                            statusClass = 'badge-danger';
                            break;
                    }
                    statusElement.classList.add('badge', statusClass);
                    
                    // Descrição (opcional)
                    document.getElementById('transacao-descricao').textContent = transacao.descricao || 'Não disponível';
                    
                    // Exibir modal
                    document.getElementById('detalheModal').classList.add('show');
                } else {
                    alert('Erro ao buscar detalhes da transação: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao buscar detalhes da transação');
            });
        }
        
        // Função para fechar o modal
        function fecharModal() {
            document.getElementById('detalheModal').classList.remove('show');
        }
        
        // Utilitários
        function formatarData(dataString) {
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR');
        }
        
        function formatarValor(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function capitalizarPrimeiraLetra(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('detalheModal');
            if (event.target === modal) {
                fecharModal();
            }
        };
    </script>
</body>
</html>