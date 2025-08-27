<?php
// views/admin/users.php
// Definir o menu ativo na sidebar
$activeMenu = 'usuarios';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Inicializar variáveis de paginação e filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

// Processar filtros se enviados
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    if (!empty($_GET['tipo']) && $_GET['tipo'] !== 'todos') {
        $filters['tipo'] = $_GET['tipo'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'todos') {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['busca'])) {
        $filters['busca'] = trim($_GET['busca']);
    }
}

try {
    // Obter dados dos usuários com filtros aplicados
    $result = AdminController::manageUsers($filters, $page);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $users = $hasError ? [] : $result['data']['usuarios'];
    $statistics = $hasError ? [] : $result['data']['estatisticas'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $users = [];
    $statistics = [];
    $pagination = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- CSS Inline para garantir funcionamento -->
    <style>
        /* Reset básico */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; }
        
        /* Formulário */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control, .form-select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,0.25); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-text { font-size: 12px; color: #666; margin-top: 5px; }
        
        /* Botões */
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        
        /* Tabela */
        .table-container { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; background: white; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .table-actions { display: flex; gap: 5px; }
        .action-btn { padding: 8px; border: none; border-radius: 4px; cursor: pointer; }
        .action-btn.edit { background: #ffc107; color: white; }
        .action-btn.view { background: #17a2b8; color: white; }
        
        /* Badges */
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; color: white; }
        
        /* Campo MVP específico */
        #mvpFieldGroup { margin: 20px 0; }
        .text-warning { color: #ffc107; }
        
        /* Loading */
        .loading-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 20000; }
        .loading-spinner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; }
        
        /* Sidebar e layout */
        .main-content { margin-left: 0; padding: 20px; }
        .page-header { margin-bottom: 30px; }
        .page-title h1 { font-size: 28px; margin-bottom: 10px; }
        .page-actions { margin-top: 15px; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .modal-content { margin: 20px; width: calc(100% - 40px); }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                    <p>Visualize e gerencie todos os usuários do sistema</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="showUserModal()">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
            </div>

            <!-- Estatísticas Rápidas -->
            <?php if (!$hasError && !empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_usuarios']); ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon client">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_clientes']); ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon store">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_lojas']); ?></h3>
                        <p>Lojas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_admins']); ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon employee">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_funcionarios'] ?? 0); ?></h3>
                        <p>Funcionários</p>
                        <small>
                            Financeiro: <?php echo $statistics['total_funcionarios_financeiro'] ?? 0; ?> |
                            Gerente: <?php echo $statistics['total_funcionarios_gerente'] ?? 0; ?> |
                            Vendedor: <?php echo $statistics['total_funcionarios_vendedor'] ?? 0; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Container de mensagens -->
            <div id="messageContainer" class="alert-container"></div>
            
            <!-- Filtros e Busca -->
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
                        <select name="tipo" id="tipoFilter">
                            <option value="todos">Todos os tipos</option>
                            <option value="cliente" <?php echo (($_GET['tipo'] ?? '') === 'cliente') ? 'selected' : ''; ?>>Clientes</option>
                            <option value="loja" <?php echo (($_GET['tipo'] ?? '') === 'loja') ? 'selected' : ''; ?>>Lojas</option>
                            <option value="admin" <?php echo (($_GET['tipo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administradores</option>
                            <option value="funcionario" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] === 'funcionario') ? 'selected' : ''; ?>>Funcionário</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status" id="statusFilter">
                            <option value="todos">Todos os status</option>
                            <option value="ativo" <?php echo (($_GET['status'] ?? '') === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo (($_GET['status'] ?? '') === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                            <option value="bloqueado" <?php echo (($_GET['status'] ?? '') === 'bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Barra de Ações em Massa -->
            <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
                <div class="bulk-info">
                    <span id="selectedCount">0</span> usuários selecionados
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('ativo')">
                        <i class="fas fa-check"></i> Ativar
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('inativo')">
                        <i class="fas fa-pause"></i> Desativar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bloqueado')">
                        <i class="fas fa-ban"></i> Bloquear
                    </button>
                </div>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Tabela de Usuários -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Usuários</h3>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-outline" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkmark"></span>
                                    </div>
                                </th>
                                <th>Usuário</th>
                                <th>Tipo/Subtipo</th>
                                <th>MVP</th>
                                <th>Loja Vinculada</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th class="actions-column">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <div class="no-data-content">
                                            <i class="fas fa-users"></i>
                                            <h4>Nenhum usuário encontrado</h4>
                                            <p>Não há usuários que atendam aos critérios de busca.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" 
                                                       class="user-checkbox" 
                                                       value="<?php echo $user['id']; ?>" 
                                                       onchange="toggleUserSelection(this, <?php echo $user['id']; ?>)">
                                                <span class="checkmark"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="user-details">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-badge type-<?php echo $user['tipo']; ?>">
                                                <?php 
                                                switch($user['tipo']) {
                                                    case 'cliente':
                                                        echo 'Cliente';
                                                        break;
                                                    case 'admin':
                                                        echo 'Administrador';
                                                        break;
                                                    case 'loja':
                                                        echo 'Loja';
                                                        break;
                                                    case 'funcionario':
                                                        echo 'Funcionário';
                                                        if (!empty($user['subtipo_funcionario'])) {
                                                            echo '<br><small class="text-muted">' . ucfirst($user['subtipo_funcionario']) . '</small>';
                                                        }
                                                        break;
                                                    default:
                                                        echo ucfirst($user['tipo']);
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['tipo'] === 'loja'): ?>
                                                <!-- Debug: verificar valor MVP -->
                                                <!-- MVP: <?php echo isset($user['mvp']) ? $user['mvp'] : 'não definido'; ?> -->
                                                <?php if (isset($user['mvp']) && $user['mvp'] === 'sim'): ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-star"></i> MVP
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['tipo'] === 'funcionario' && !empty($user['nome_loja_vinculada'])): ?>
                                                <span class="text-sm">
                                                    <i class="fas fa-store text-muted"></i>
                                                    <?php echo htmlspecialchars($user['nome_loja_vinculada']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                $statusIcon = '';
                                                switch ($user['status']) {
                                                    case 'ativo':
                                                        $statusClass = 'badge-success';
                                                        $statusIcon = 'fas fa-check';
                                                        break;
                                                    case 'inativo':
                                                        $statusClass = 'badge-warning';
                                                        $statusIcon = 'fas fa-pause';
                                                        break;
                                                    case 'bloqueado':
                                                        $statusClass = 'badge-danger';
                                                        $statusIcon = 'fas fa-ban';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <i class="<?php echo $statusIcon; ?>"></i>
                                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php
                                                    // Calcula o timestamp com 3 horas a menos para a data de criação
                                                    $timestamp_criacao = strtotime($user['data_criacao']) - (3 * 60 * 60);
                                                ?>
                                                <div class="date-primary">
                                                    <?php echo date('d/m/Y', $timestamp_criacao); ?>
                                                </div>
                                                <div class="date-secondary">
                                                    <?php echo date('H:i', $timestamp_criacao); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php if ($user['ultimo_login']): ?>
                                                    <?php
                                                        // Calcula o timestamp com 3 horas a menos para o último login
                                                        $timestamp_login = strtotime($user['ultimo_login']) - (3 * 60 * 60);
                                                    ?>
                                                    <div class="date-primary">
                                                        <?php echo date('d/m/Y', $timestamp_login); ?>
                                                    </div>
                                                    <div class="date-secondary">
                                                        <?php echo date('H:i', $timestamp_login); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" 
                                                        onclick="editUser(<?php echo $user['id']; ?>)"
                                                        title="Editar usuário">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn view" 
                                                        onclick="viewUser(<?php echo $user['id']; ?>)"
                                                        title="Visualizar usuário">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['status'] === 'ativo'): ?>
                                                    <button class="action-btn deactivate" 
                                                            onclick="changeUserStatus(<?php echo $user['id']; ?>, 'inativo', '<?php echo addslashes($user['nome']); ?>')"
                                                            title="Desativar usuário">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn activate" 
                                                            onclick="changeUserStatus(<?php echo $user['id']; ?>, 'ativo', '<?php echo addslashes($user['nome']); ?>')"
                                                            title="Ativar usuário">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
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
                            de <?php echo $pagination['total']; ?> usuários
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Primeira página">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo max(1, $page - 1); ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Página anterior">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($pagination['total_paginas'], $startPage + 4);
                                if ($endPage - $startPage < 4) {
                                    $startPage = max(1, $endPage - 4);
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" 
                                   class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pagination['total_paginas']): ?>
                                <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Próxima página">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $pagination['total_paginas']; ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Última página">
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
    
    <!-- Modal de Adicionar/Editar Usuário -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="userModalTitle">
                    <i class="fas fa-user-plus"></i> Adicionar Usuário
                </h3>
                <button class="modal-close" onclick="hideUserModal()" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="userForm" onsubmit="submitUserForm(event)">
                    <input type="hidden" id="userId" name="id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="userType">Tipo de Usuário</label>
                            <select class="form-select" id="userType" name="tipo" required>
                                <option value="">Selecione o tipo...</option>
                                <option value="cliente">Cliente</option>
                                <option value="loja">Loja</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="userStatus">Status</label>
                            <select class="form-select" id="userStatus" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campo MVP - só aparece para usuários tipo Loja -->
                    <div class="form-row" id="mvpFieldGroup" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="userMvp">
                                <i class="fas fa-star text-warning"></i> Status MVP
                            </label>
                            <select class="form-select" id="userMvp" name="mvp">
                                <option value="nao">Não</option>
                                <option value="sim">Sim</option>
                            </select>
                            <small class="form-text text-muted">
                                Lojas MVP têm privilégios especiais na plataforma
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="userName">Nome Completo</label>
                        <input type="text" 
                               class="form-control" 
                               id="userName" 
                               name="nome" 
                               required 
                               placeholder="Digite o nome completo">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="userEmail">E-mail</label>
                        <div id="emailSelectContainer" style="display: none;">
                            <select class="form-select" id="userEmailSelect" name="email_select">
                                <option value="">Selecione uma loja...</option>
                            </select>
                        </div>
                        <input type="email" 
                               class="form-control" 
                               id="userEmail" 
                               name="email" 
                               required 
                               placeholder="Digite o e-mail">
                    </div>
                    
                    <!-- Campos que serão preenchidos automaticamente quando for loja -->
                    <div id="storeDataFields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="storeName">Nome da Loja</label>
                            <input type="text" class="form-control" id="storeName" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="storeDocument">CNPJ</label>
                            <input type="text" class="form-control" id="storeDocument" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="storeCategory">Categoria</label>
                            <input type="text" class="form-control" id="storeCategory" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="userPhone">Telefone</label>
                        <input type="tel" 
                               class="form-control" 
                               id="userPhone" 
                               name="telefone" 
                               placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label class="form-label required" for="userPassword">Senha</label>
                        <div class="password-input">
                            <input type="password" 
                                   class="form-control" 
                                   id="userPassword" 
                                   name="senha"
                                   autocomplete="new-password"
                                   placeholder="Digite a senha">
                            <button type="button" class="password-toggle" onclick="togglePassword('userPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="passwordHelp" class="form-text">
                            Mínimo de 8 caracteres (deixe em branco para manter a senha atual ao editar)
                        </small>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideUserModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="userForm" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização de Usuário -->
    <div class="modal" id="viewUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-eye"></i> Detalhes do Usuário
                </h3>
                <button class="modal-close" onclick="hideViewUserModal()" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="userViewContent">
                    <!-- Conteúdo será carregado dinamicamente -->
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideViewUserModal()">
                    <i class="fas fa-times"></i> Fechar
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
    
    <!-- JavaScript Inline Robusto -->
    <script>
    // Variáveis globais
    let currentUserId = null;
    
    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Página carregada - Inicializando funcionalidades...');
        
        // Verificar se elementos existem
        const mvpField = document.getElementById('mvpFieldGroup');
        console.log('Campo MVP encontrado:', mvpField ? 'SIM' : 'NÃO');
    });
    
    // Função para mostrar modal de edição
    function editUser(userId) {
        if (!userId) return;
        
        console.log('Editando usuário:', userId);
        currentUserId = userId;
        
        const modal = document.getElementById('userModal');
        const title = document.getElementById('userModalTitle');
        const form = document.getElementById('userForm');
        
        if (!modal || !form) {
            console.error('Modal ou formulário não encontrado!');
            return;
        }
        
        // Configurar modal
        if (title) title.innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuário';
        form.reset();
        
        // Mostrar modal
        modal.classList.add('show');
        
        // Carregar dados do usuário
        fetch('/controllers/AdminController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=getUserDetails&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resposta do servidor:', data);
            
            if (data.status && data.data && data.data.usuario) {
                fillUserForm(data.data.usuario);
            } else {
                alert('Erro ao carregar dados do usuário: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados do usuário');
        });
    }
    
    // Função para preencher formulário
    function fillUserForm(userData) {
        console.log('Preenchendo formulário com:', userData);
        
        // Preencher campos básicos
        document.getElementById('userId').value = userData.id || '';
        document.getElementById('userName').value = userData.nome || '';
        document.getElementById('userEmail').value = userData.email || '';
        document.getElementById('userType').value = userData.tipo || '';
        document.getElementById('userStatus').value = userData.status || '';
        document.getElementById('userPhone').value = userData.telefone || '';
        document.getElementById('userPassword').value = '';
        
        // Campo MVP - FORÇAR exibição para lojas
        const mvpFieldGroup = document.getElementById('mvpFieldGroup');
        const mvpSelect = document.getElementById('userMvp');
        
        if (userData.tipo === 'loja' && mvpFieldGroup && mvpSelect) {
            console.log('Exibindo campo MVP para loja');
            
            // Forçar exibição
            mvpFieldGroup.style.display = 'block';
            mvpFieldGroup.style.visibility = 'visible';
            
            // Definir valor
            mvpSelect.value = userData.mvp || 'nao';
            
            console.log('Campo MVP configurado:', userData.mvp || 'nao');
        } else if (mvpFieldGroup) {
            mvpFieldGroup.style.display = 'none';
        }
    }
    
    // Função para esconder modal
    function hideUserModal() {
        const modal = document.getElementById('userModal');
        if (modal) {
            modal.classList.remove('show');
        }
        currentUserId = null;
    }
    
    // Função para submeter formulário
    function submitUserForm(event) {
        event.preventDefault();
        
        console.log('Submetendo formulário...');
        
        const form = document.getElementById('userForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form) return;
        
        // Desabilitar botão
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        }
        
        // Coletar dados
        const formData = new FormData(form);
        const userId = formData.get('id');
        const isEditing = userId !== '';
        
        // Preparar dados para envio
        const data = new URLSearchParams();
        data.append('action', 'update_user');
        data.append('user_id', userId);
        
        // Adicionar todos os campos do formulário
        for (let [key, value] of formData.entries()) {
            if (key !== 'id') {
                data.append(key, value);
                if (key === 'mvp') {
                    console.log('Campo MVP sendo enviado:', value);
                }
            }
        }
        
        // Enviar dados
        fetch('/controllers/AdminController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        })
        .then(response => response.json())
        .then(result => {
            console.log('Resultado:', result);
            
            if (result.status) {
                alert('Usuário atualizado com sucesso!');
                hideUserModal();
                location.reload(); // Recarregar página para mostrar mudanças
            } else {
                alert('Erro: ' + (result.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar usuário');
        })
        .finally(() => {
            // Reabilitar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar';
            }
        });
    }
    
    // Event listeners
    document.addEventListener('click', function(event) {
        // Fechar modal ao clicar fora
        if (event.target.classList.contains('modal')) {
            hideUserModal();
        }
    });
    
    // Submissão do formulário
    document.addEventListener('submit', function(event) {
        if (event.target.id === 'userForm') {
            submitUserForm(event);
        }
    });
    
    // Funções vazias para evitar erros de funções não encontradas
    function viewUser(userId) { console.log('Ver usuário:', userId); }
    function changeUserStatus(userId, status) { console.log('Mudar status:', userId, status); }
    function showUserModal() { console.log('Mostrar modal de novo usuário'); }
    function toggleSelectAll() { console.log('Selecionar todos'); }
    function toggleUserSelection() { console.log('Selecionar usuário'); }
    function clearFilters() { location.href = location.pathname; }
    function exportUsers() { console.log('Exportar usuários'); }
    function bulkAction() { console.log('Ação em massa'); }
    
    </script>
    
</body>
</html>