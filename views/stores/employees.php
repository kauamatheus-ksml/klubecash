<?php
// views/stores/employees.php
$activeMenu = 'funcionarios';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';

session_start();

// Verificar se é loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_STORE) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Inicializar variáveis
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

// Processar filtros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    if (!empty($_GET['subtipo']) && $_GET['subtipo'] !== 'todos') {
        $filters['subtipo'] = $_GET['subtipo'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'todos') {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['busca'])) {
        $filters['busca'] = trim($_GET['busca']);
    }
}

try {
    // Obter dados dos funcionários
    $result = StoreController::getEmployees($filters, $page);
    
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';
    
    $employees = $hasError ? [] : $result['data']['funcionarios'];
    $statistics = $hasError ? [] : $result['data']['estatisticas'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $employees = [];
    $statistics = [];
    $pagination = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/users.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-user-tie"></i> Gerenciar Funcionários</h1>
                    <p>Gerencie os funcionários da sua loja</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="showEmployeeModal()">
                        <i class="fas fa-plus"></i> Novo Funcionário
                    </button>
                </div>
            </div>

            <!-- Estatísticas -->
            <?php if (!$hasError && !empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_funcionarios']); ?></h3>
                        <p>Total de Funcionários</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon financial">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_financeiro']); ?></h3>
                        <p>Financeiro</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon manager">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_gerente']); ?></h3>
                        <p>Gerentes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon seller">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_vendedor']); ?></h3>
                        <p>Vendedores</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Container de mensagens -->
            <div id="messageContainer" class="alert-container"></div>
            
            <!-- Filtros -->
            <div class="filters-section">
                <form method="GET" class="filters-form" id="filtersForm">
                    <div class="filter-group">
                        <div class="search-bar">
                            <input type="text" 
                                   name="busca" 
                                   id="searchInput"
                                   placeholder="Buscar por nome ou email..." 
                                   value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <select name="subtipo" id="subtipoFilter">
                            <option value="todos">Todos os tipos</option>
                            <option value="financeiro" <?php echo (($_GET['subtipo'] ?? '') === 'financeiro') ? 'selected' : ''; ?>>Financeiro</option>
                            <option value="gerente" <?php echo (($_GET['subtipo'] ?? '') === 'gerente') ? 'selected' : ''; ?>>Gerente</option>
                            <option value="vendedor" <?php echo (($_GET['subtipo'] ?? '') === 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status" id="statusFilter">
                            <option value="todos">Todos os status</option>
                            <option value="ativo" <?php echo (($_GET['status'] ?? '') === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo (($_GET['status'] ?? '') === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpar
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Tabela de Funcionários -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Funcionários</h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Funcionário</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Data de Cadastro</th>
                                <th>Último Login</th>
                                <th class="actions-column">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <div class="no-data-content">
                                            <i class="fas fa-user-tie"></i>
                                            <h4>Nenhum funcionário encontrado</h4>
                                            <p>Você ainda não cadastrou funcionários.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div class="user-details">
                                                    <div class="user-name"><?php echo htmlspecialchars($employee['nome']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($employee['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-badge type-<?php echo $employee['subtipo_funcionario']; ?>">
                                                <?php echo ucfirst($employee['subtipo_funcionario']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = $employee['status'] === 'ativo' ? 'badge-success' : 'badge-warning';
                                                $statusIcon = $employee['status'] === 'ativo' ? 'fas fa-check' : 'fas fa-pause';
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <i class="<?php echo $statusIcon; ?>"></i>
                                                <?php echo ucfirst($employee['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <div class="date-primary">
                                                    <?php echo date('d/m/Y', strtotime($employee['data_criacao'])); ?>
                                                </div>
                                                <div class="date-secondary">
                                                    <?php echo date('H:i', strtotime($employee['data_criacao'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php if ($employee['ultimo_login']): ?>
                                                    <div class="date-primary">
                                                        <?php echo date('d/m/Y', strtotime($employee['ultimo_login'])); ?>
                                                    </div>
                                                    <div class="date-secondary">
                                                        <?php echo date('H:i', strtotime($employee['ultimo_login'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" 
                                                        onclick="editEmployee(<?php echo $employee['id']; ?>)"
                                                        title="Editar funcionário">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn delete" 
                                                        onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo addslashes($employee['nome']); ?>')"
                                                        title="Desativar funcionário">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Mostrando <?php echo (($page - 1) * $pagination['por_pagina']) + 1; ?>-<?php echo min($page * $pagination['por_pagina'], $pagination['total']); ?> 
                            de <?php echo $pagination['total']; ?> funcionários
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo isset($_GET) ? '&' . http_build_query($_GET) : ''; ?>" class="pagination-arrow">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET) ? '&' . http_build_query($_GET) : ''; ?>" class="pagination-arrow">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($pagination['total_paginas'], $startPage + 4);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo isset($_GET) ? '&' . http_build_query($_GET) : ''; ?>" 
                                   class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pagination['total_paginas']): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET) ? '&' . http_build_query($_GET) : ''; ?>" class="pagination-arrow">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $pagination['total_paginas']; ?><?php echo isset($_GET) ? '&' . http_build_query($_GET) : ''; ?>" class="pagination-arrow">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Funcionário -->
    <div class="modal" id="employeeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="employeeModalTitle">
                    <i class="fas fa-user-plus"></i> Adicionar Funcionário
                </h3>
                <button class="modal-close" onclick="hideEmployeeModal()" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="employeeForm" onsubmit="submitEmployeeForm(event)">
                    <input type="hidden" id="employeeId" name="id" value="">
                    
                    <div class="form-group">
                        <label class="form-label required" for="employeeName">Nome Completo</label>
                        <input type="text" 
                               class="form-control" 
                               id="employeeName" 
                               name="nome" 
                               required 
                               placeholder="Digite o nome completo">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="employeeEmail">E-mail</label>
                        <input type="email" 
                               class="form-control" 
                               id="employeeEmail" 
                               name="email" 
                               required 
                               placeholder="Digite o e-mail">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="employeePhone">Telefone</label>
                        <input type="tel" 
                               class="form-control" 
                               id="employeePhone" 
                               name="telefone" 
                               placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="employeeType">Tipo de Funcionário</label>
                        <select class="form-select" id="employeeType" name="subtipo_funcionario" required>
                            <option value="">Selecione o tipo...</option>
                            <option value="financeiro">Financeiro</option>
                            <option value="gerente">Gerente</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label class="form-label required" for="employeePassword">Senha</label>
                        <div class="password-input">
                            <input type="password" 
                                   class="form-control" 
                                   id="employeePassword" 
                                   name="senha"
                                   placeholder="Digite a senha">
                            <button type="button" class="password-toggle" onclick="togglePassword('employeePassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text">
                            Mínimo de 8 caracteres (deixe em branco para manter a senha atual ao editar)
                        </small>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideEmployeeModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="employeeForm" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Carregando...</p>
        </div>
    </div>
    
    <script src="../../assets/js/stores/employees.js"></script>
</body>
</html>