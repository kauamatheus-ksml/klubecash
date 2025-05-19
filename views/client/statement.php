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
// Obter estatísticas de saldo para o período filtrado
$saldoStatQuery = "
    SELECT 
        SUM(CASE WHEN cm.tipo_operacao = 'credito' THEN cm.valor ELSE 0 END) as total_creditado,
        SUM(CASE WHEN cm.tipo_operacao = 'uso' THEN cm.valor ELSE 0 END) as total_usado,
        SUM(CASE WHEN cm.tipo_operacao = 'estorno' THEN cm.valor ELSE 0 END) as total_estornado,
        COUNT(CASE WHEN cm.tipo_operacao = 'uso' THEN 1 END) as qtd_usos
    FROM cashback_movimentacoes cm
    WHERE cm.usuario_id = :user_id
";

// Aplicar filtros de data se existirem
$saldoParams = [':user_id' => $userId];
if (!empty($filters['data_inicio'])) {
    $saldoStatQuery .= " AND cm.data_operacao >= :data_inicio";
    $saldoParams[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
}
if (!empty($filters['data_fim'])) {
    $saldoStatQuery .= " AND cm.data_operacao <= :data_fim";
    $saldoParams[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
}

$saldoStatStmt = $db->prepare($saldoStatQuery);
foreach ($saldoParams as $param => $value) {
    $saldoStatStmt->bindValue($param, $value);
}
$saldoStatStmt->execute();
$saldoEstatisticas = $saldoStatStmt->fetch(PDO::FETCH_ASSOC);

// Obter transações com informações de saldo usado
if (!$hasError && !empty($statementData['transacoes'])) {
    foreach ($statementData['transacoes'] as &$transacao) {
        // Buscar saldo usado nesta transação
        $saldoUsadoQuery = "
            SELECT SUM(valor) as saldo_usado 
            FROM cashback_movimentacoes 
            WHERE transacao_uso_id = :transacao_id AND tipo_operacao = 'uso'
        ";
        $saldoUsadoStmt = $db->prepare($saldoUsadoQuery);
        $saldoUsadoStmt->bindParam(':transacao_id', $transacao['id']);
        $saldoUsadoStmt->execute();
        $saldoUsado = $saldoUsadoStmt->fetch(PDO::FETCH_ASSOC);
        
        $transacao['saldo_usado'] = $saldoUsado['saldo_usado'] ?? 0;
        $transacao['valor_pago'] = $transacao['valor_total'] - $transacao['saldo_usado'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extrato de Cashback - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/client/statement.css">
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
                
                <div class="form-group">
                    <label class="form-label" for="tipo_transacao">Tipo</label>
                    <select id="tipo_transacao" name="tipo_transacao" class="form-control">
                        <option value="todos">Todos</option>
                        <option value="com_saldo" <?php echo (isset($filters['tipo_transacao']) && $filters['tipo_transacao'] == 'com_saldo') ? 'selected' : ''; ?>>Com uso de saldo</option>
                        <option value="sem_saldo" <?php echo (isset($filters['tipo_transacao']) && $filters['tipo_transacao'] == 'sem_saldo') ? 'selected' : ''; ?>>Sem uso de saldo</option>
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
                <div class="summary-card-title">Saldo Usado</div>
                <div class="summary-card-value">R$ <?php echo number_format($saldoEstatisticas['total_usado'] ?? 0, 2, ',', '.'); ?></div>
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
                            <th>Valor Original</th>
                            <th>Saldo Usado</th>
                            <th>Valor Pago</th>
                            <th>Cashback</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statementData['transacoes'])): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">Nenhuma transação encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statementData['transacoes'] as $transacao): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?></td>
                                    <td><?php echo htmlspecialchars($transacao['loja_nome']); ?></td>
                                    <td>R$ <?php echo number_format($transacao['valor_total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($transacao['saldo_usado'] > 0): ?>
                                            <span style="color: #4CAF50; font-weight: 600;">
                                                R$ <?php echo number_format($transacao['saldo_usado'], 2, ',', '.'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #666;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>R$ <?php echo number_format($transacao['valor_pago'], 2, ',', '.'); ?></td>
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
                    <!-- Movimentações de Saldo -->
                    <?php if (!empty($saldoEstatisticas['qtd_usos']) && $saldoEstatisticas['qtd_usos'] > 0): ?>
                    <div class="card" style="margin-top: 20px;">
                        <h3 class="card-title">Resumo de Uso de Saldo no Período</h3>
                        
                        <div class="saldo-summary-cards">
                            <div class="saldo-summary-card">
                                <div class="saldo-summary-title">Saldo Creditado</div>
                                <div class="saldo-summary-value">R$ <?php echo number_format($saldoEstatisticas['total_creditado'] ?? 0, 2, ',', '.'); ?></div>
                            </div>
                            
                            <div class="saldo-summary-card">
                                <div class="saldo-summary-title">Saldo Usado</div>
                                <div class="saldo-summary-value">R$ <?php echo number_format($saldoEstatisticas['total_usado'] ?? 0, 2, ',', '.'); ?></div>
                            </div>
                            
                            <div class="saldo-summary-card">
                                <div class="saldo-summary-title">Estornos</div>
                                <div class="saldo-summary-value">R$ <?php echo number_format($saldoEstatisticas['total_estornado'] ?? 0, 2, ',', '.'); ?></div>
                            </div>
                            
                            <div class="saldo-summary-card">
                                <div class="saldo-summary-title">Quantidade de Usos</div>
                                <div class="saldo-summary-value"><?php echo $saldoEstatisticas['qtd_usos'] ?? 0; ?></div>
                            </div>
                        </div>
                        
                        <div class="saldo-info">
                            <p><strong>Explicação:</strong> O saldo usado refere-se ao cashback de compras anteriores que você utilizou como desconto em novas compras. O valor pago é o que você efetivamente pagou após o desconto do saldo.</p>
                        </div>
                    </div>
                    <?php endif; ?>
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
                <div class="transaction-detail-row">
                    <div class="detail-label">Valor Original:</div>
                    <div class="detail-value" id="transacao-valor-original"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Saldo Usado:</div>
                    <div class="detail-value" id="transacao-saldo-usado"></div>
                </div>
                <div class="transaction-detail-row">
                    <div class="detail-label">Valor Pago:</div>
                    <div class="detail-value" id="transacao-valor-pago"></div>
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
                    
                    // Valores originais e calculados
                    const valorOriginal = parseFloat(transacao.valor_total);
                    const saldoUsado = parseFloat(transacao.saldo_usado || 0);
                    const valorPago = valorOriginal - saldoUsado;
                    
                    document.getElementById('transacao-valor-original').textContent = 'R$ ' + formatarValor(valorOriginal);
                    document.getElementById('transacao-saldo-usado').textContent = saldoUsado > 0 ? 'R$ ' + formatarValor(saldoUsado) : 'Não usado';
                    document.getElementById('transacao-valor-pago').textContent = 'R$ ' + formatarValor(valorPago);
                    
                    // Valores tradicionais
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