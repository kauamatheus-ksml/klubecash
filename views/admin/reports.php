<?php
// views/admin/reports.php
$activeMenu = 'relatorios';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Processar filtros
$filters = [];
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}

// Período padrão (último mês) se nenhum filtro
if (empty($filters)) {
    $filters['data_inicio'] = date('Y-m-d', strtotime('-1 month'));
    $filters['data_fim'] = date('Y-m-d');
}

try {
    // Obter dados dos relatórios - USANDO O CONTROLLER
    $result = AdminController::getFinancialReports($filters);
    
    if (!$result['status']) {
        throw new Exception($result['message']);
    }
    
    $reportData = $result['data'];
    
    // Extrair dados para facilitar o uso na view
    $saldoStats = $reportData['saldo_stats'];
    $movimentacoesStats = $reportData['movimentacoes_stats'];
    $vendasComparacao = $reportData['vendas_comparacao'];
    $lojasSaldo = $reportData['lojas_saldo'];
    $financialData = $reportData['financial_data'];
    $adminComission = $reportData['admin_comission'];
    $monthlyData = $reportData['monthly_data'];
    
    $hasError = false;
    $errorMessage = '';
    
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    
    // Valores padrão em caso de erro
    $saldoStats = [];
    $movimentacoesStats = [];
    $vendasComparacao = [];
    $lojasSaldo = [];
    $financialData = ['total_cashback' => 0, 'total_vendas' => 0];
    $adminComission = 0;
    $monthlyData = [];
}

// Funções auxiliares
function formatCurrency($value) {
    return 'R$ ' . number_format($value ?: 0, 2, ',', '.');
}

function formatPercentage($value) {
    return number_format($value ?: 0, 1, ',', '.') . '%';
}

function formatMonth($yearMonth) {
    $parts = explode('-', $yearMonth);
    $year = $parts[0];
    $month = $parts[1];
    
    $monthNames = [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
        '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
        '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ];
    
    return $monthNames[$month] . '/' . $year;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/admin/reports.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Relatórios Financeiros</h1>
            
            <!-- Filtro de Data -->
            <div class="filter-container">
                <button class="filter-button" onclick="toggleDateFilter()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Filtrar Período</span>
                </button>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Resumo Financeiro Geral -->
            <h2 class="section-title">Resumo Financeiro</h2>
            <div class="cards-row">
                <div class="card">
                    <h3 class="card-title">Cashback Pago</h3>
                    <div class="card-value"><?php echo formatCurrency($financialData['total_cashback']); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Receita (Comissão Admin)</h3>
                    <div class="card-value"><?php echo formatCurrency($adminComission); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Saldo Total Disponível</h3>
                    <div class="card-value"><?php echo formatCurrency($saldoStats['total_saldo_disponivel'] ?? 0); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Saldo Usado no Período</h3>
                    <div class="card-value"><?php echo formatCurrency($movimentacoesStats['usos_periodo'] ?? 0); ?></div>
                </div>
            </div>
            
            <!-- Estatísticas de Saldo -->
            <h2 class="section-title">Estatísticas de Saldo de Cashback</h2>
            <div class="cards-row">
                <div class="card">
                    <h3 class="card-title">Clientes com Saldo</h3>
                    <div class="card-value"><?php echo number_format($saldoStats['total_clientes_com_saldo'] ?? 0, 0, ',', '.'); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Saldo Médio por Cliente</h3>
                    <div class="card-value"><?php echo formatCurrency($saldoStats['media_saldo_por_cliente'] ?? 0); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Total Creditado (Histórico)</h3>
                    <div class="card-value"><?php echo formatCurrency($saldoStats['total_saldo_creditado'] ?? 0); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Total Usado (Histórico)</h3>
                    <div class="card-value"><?php echo formatCurrency($saldoStats['total_saldo_usado'] ?? 0); ?></div>
                </div>
            </div>
            
            <!-- Movimentações do Período -->
            <h2 class="section-title">Movimentações de Saldo no Período</h2>
            <div class="cards-row">
                <div class="card">
                    <h3 class="card-title">Créditos</h3>
                    <div class="card-value">
                        <?php echo formatCurrency($movimentacoesStats['creditos_periodo'] ?? 0); ?>
                        <small>(<?php echo $movimentacoesStats['qtd_creditos'] ?? 0; ?> operações)</small>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Usos</h3>
                    <div class="card-value">
                        <?php echo formatCurrency($movimentacoesStats['usos_periodo'] ?? 0); ?>
                        <small>(<?php echo $movimentacoesStats['qtd_usos'] ?? 0; ?> operações)</small>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Estornos</h3>
                    <div class="card-value">
                        <?php echo formatCurrency($movimentacoesStats['estornos_periodo'] ?? 0); ?>
                        <small>(<?php echo $movimentacoesStats['qtd_estornos'] ?? 0; ?> operações)</small>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Saldo Líquido Movimento</h3>
                    <div class="card-value">
                        <?php 
                        $saldoLiquido = ($movimentacoesStats['creditos_periodo'] ?? 0) - ($movimentacoesStats['usos_periodo'] ?? 0) + ($movimentacoesStats['estornos_periodo'] ?? 0);
                        echo formatCurrency($saldoLiquido);
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Comparação Vendas Originais vs Líquidas -->
            <h2 class="section-title">Vendas Originais X Vendas Líquidas (com desconto de saldo)</h2>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Vendas Originais</th>
                            <th>Saldo Usado</th>
                            <th>Vendas Líquidas</th>
                            <th>% Desconto Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vendasComparacao)): ?>
                            <tr>
                                <td colspan="5" class="no-data">Nenhum dado disponível para o período selecionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vendasComparacao as $data): ?>
                                <?php
                                $vendasOriginais = $data['vendas_originais'] ?? 0;
                                $saldoUsado = $data['total_saldo_usado_mes'] ?? 0;
                                $vendasLiquidas = $data['vendas_liquidas'] ?? 0;
                                $percentualDesconto = $vendasOriginais > 0 ? ($saldoUsado / $vendasOriginais) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo formatMonth($data['mes']); ?></td>
                                    <td><?php echo formatCurrency($vendasOriginais); ?></td>
                                    <td><?php echo formatCurrency($saldoUsado); ?></td>
                                    <td><?php echo formatCurrency($vendasLiquidas); ?></td>
                                    <td><?php echo formatPercentage($percentualDesconto); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Ranking de Lojas por Uso de Saldo -->
            <h2 class="section-title">Top 10 Lojas - Uso de Saldo</h2>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Loja</th>
                            <th>Saldo Atual</th>
                            <th>Total Creditado</th>
                            <th>Total Usado</th>
                            <th>% de Uso</th>
                            <th>Clientes com Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lojasSaldo)): ?>
                            <tr>
                                <td colspan="6" class="no-data">Nenhuma loja com movimentação de saldo</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lojasSaldo as $loja): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loja['nome_fantasia']); ?></td>
                                    <td><?php echo formatCurrency($loja['saldo_atual']); ?></td>
                                    <td><?php echo formatCurrency($loja['total_creditado']); ?></td>
                                    <td><?php echo formatCurrency($loja['total_usado']); ?></td>
                                    <td><?php echo formatPercentage($loja['percentual_uso']); ?></td>
                                    <td><?php echo number_format($loja['clientes_com_saldo'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tabela: Cashback Pago X Lucro -->
            <h2 class="section-title">Cashback Pago X Receita</h2>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Cashback Pago</th>
                            <th>Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyData)): ?>
                            <tr>
                                <td colspan="3" class="no-data">Nenhum dado disponível para o período selecionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyData as $data): ?>
                                <tr>
                                    <td><?php echo formatMonth($data['mes']); ?></td>
                                    <td><?php echo formatCurrency($data['total_cashback']); ?></td>
                                    <td><?php echo formatCurrency($data['comissao_admin']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Filtro de Data -->
    <div id="dateFilterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Filtrar por Período</h2>
                <button class="close-button" onclick="toggleDateFilter()">&times;</button>
            </div>
            
            <form id="dateFilterForm" action="" method="get">
                <div class="form-group">
                    <label class="form-label" for="dataInicio">Data Inicial</label>
                    <input type="date" class="form-control" id="dataInicio" name="data_inicio" value="<?php echo $filters['data_inicio'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="dataFim">Data Final</label>
                    <input type="date" class="form-control" id="dataFim" name="data_fim" value="<?php echo $filters['data_fim'] ?? ''; ?>">
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">Limpar</button>
                    <button type="submit" class="btn btn-primary">Aplicar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleDateFilter() {
            const modal = document.getElementById('dateFilterModal');
            modal.classList.toggle('active');
        }
        
        function clearFilters() {
            document.getElementById('dataInicio').value = '';
            document.getElementById('dataFim').value = '';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('dateFilterModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>