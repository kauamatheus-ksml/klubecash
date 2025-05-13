<?php
// views/admin/reports.php
// Definir o menu ativo na sidebar
$activeMenu = 'relatorios';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Inicializar variáveis de filtro
$filters = [];

// Processar filtros se enviados
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}

// Período padrão (último mês) se nenhum filtro for aplicado
if (empty($filters)) {
    $filters['data_inicio'] = date('Y-m-d', strtotime('-1 month'));
    $filters['data_fim'] = date('Y-m-d');
}

try {
    // Obter dados do relatório financeiro
    $result = AdminController::generateReport('financeiro', $filters);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    if (!$hasError) {
        $reportData = $result['data']['resultados'];
        
        // Valores totais
        $totalCashback = $reportData['financeiro']['total_cashback'] ?? 0;
        $totalComissao = $reportData['comissao_admin'] ?? 0;
        $lucro = $totalComissao;
        
        // Dados mensais
        $monthlyData = $reportData['por_mes'] ?? [];
    }
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $reportData = [];
    $totalCashback = 0;
    $totalComissao = 0;
    $lucro = 0;
    $monthlyData = [];
}

// Função para formatar valor
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Função para formatar mês
function formatMonth($yearMonth) {
    $parts = explode('-', $yearMonth);
    $year = $parts[0];
    $month = $parts[1];
    
    $monthNames = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Março',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro'
    ];
    
    return $monthNames[$month] . '/' . $year;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Klube Cash</title>
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
            --border-radius: 8px;
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
        .main-content {
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }
        
        /* Wrapper da página */
        .page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Título da página */
        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 25px;
        }
        
        /* Filtro de data */
        .filter-container {
            margin-bottom: 25px;
        }
        
        .filter-button {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--white);
            border: 1px solid #FFD9B3;
            border-radius: var(--border-radius);
            padding: 10px 15px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-gray);
            transition: all 0.3s;
        }
        
        .filter-button:hover {
            border-color: var(--primary-color);
        }
        
        .filter-button svg {
            color: var(--primary-color);
        }
        
        /* Cartões de informações */
        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .cards-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid #FFD9B3;
            padding: 20px;
            flex: 1;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .card.full-width {
            width: 100%;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-gray);
        }
        
        /* Tabela de comparação */
        .table-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid #FFD9B3;
            margin-bottom: 30px;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #F0F0F0;
        }
        
        .comparison-table th {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .comparison-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Modal de filtro de data */
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
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            width: 400px;
            max-width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .close-button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--medium-gray);
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #DDD;
            border-radius: var(--border-radius);
        }
        
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .page-wrapper {
                padding: 75px 20px;
            }
            .main-content {
                padding-left: 0;
            }
            
            .cards-row {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Relatórios</h1>
            
            <!-- Filtro de Data -->
            <div class="filter-container">
                <button class="filter-button" onclick="toggleDateFilter()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Data</span>
                </button>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Total de Cashback Pago -->
            <h2 class="section-title">Total de Cashback Pago</h2>
            <div class="cards-row">
                <div class="card full-width">
                    <div class="card-value"><?php echo formatCurrency($totalCashback); ?></div>
                </div>
            </div>
            
            <!-- Cashback Pago e Lucro -->
            <div class="cards-row">
                <div class="card">
                    <h3 class="card-title">Cashback Pago</h3>
                    <div class="card-value"><?php echo formatCurrency($totalCashback); ?></div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Lucro</h3>
                    <div class="card-value"><?php echo formatCurrency($lucro); ?></div>
                </div>
            </div>
            
            <!-- Tabela de Comparação -->
            <h2 class="section-title">Cashback Pago X Lucro</h2>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Cashback Pago</th>
                            <th>Lucro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyData)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Nenhum dado disponível para o período selecionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyData as $data): ?>
                                <?php
                                $monthCashback = $data['total_cashback'] ?? 0;
                                // Considerando o lucro como um percentual do cashback (ajuste conforme sua lógica de negócio)
                                $monthLucro = isset($data['comissao_admin']) ? $data['comissao_admin'] : ($monthCashback * 0.2);
                                ?>
                                <tr>
                                    <td><?php echo formatMonth($data['mes']); ?></td>
                                    <td><?php echo formatCurrency($monthCashback); ?></td>
                                    <td><?php echo formatCurrency($monthLucro); ?></td>
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
                <h2 class="modal-title">Filtrar por Data</h2>
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
        // Abrir/fechar modal de filtro de data
        function toggleDateFilter() {
            const modal = document.getElementById('dateFilterModal');
            modal.classList.toggle('active');
        }
        
        // Limpar filtros
        function clearFilters() {
            document.getElementById('dataInicio').value = '';
            document.getElementById('dataFim').value = '';
        }
    </script>
</body>
</html>