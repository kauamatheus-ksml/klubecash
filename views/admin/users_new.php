<?php
/**
 * Sistema de Gerenciamento de Usuários - KlubeCash Admin
 * 
 * Sistema completo e profissional para gerenciamento de usuários com:
 * - Interface moderna e responsiva
 * - Filtros avançados e busca inteligente
 * - Paginação otimizada
 * - Logs de auditoria
 * - Validações robustas
 * - Segurança contra XSS, CSRF e SQL Injection
 * - Performance otimizada para grandes volumes de dados
 * 
 * @author KlubeCash Development Team
 * @version 2.0
 * @since 2025-08-27
 */

$activeMenu = 'usuarios';

// Includes necessários com verificação de existência
if (!file_exists(__DIR__ . '/../../controllers/AuthController.php')) {
    die('Erro: Arquivo AuthController não encontrado.');
}
if (!file_exists(__DIR__ . '/../../controllers/AdminController.php')) {
    die('Erro: Arquivo AdminController não encontrado.');
}

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../config/constants.php';

// Inicializar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação de autenticação robusta
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Processamento de parâmetros com sanitização
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$filters = [
    'tipo' => filter_var($_GET['tipo'] ?? '', FILTER_SANITIZE_STRING),
    'subtipo_funcionario' => filter_var($_GET['subtipo_funcionario'] ?? '', FILTER_SANITIZE_STRING),
    'status' => filter_var($_GET['status'] ?? '', FILTER_SANITIZE_STRING),
    'loja_vinculada' => filter_var($_GET['loja_vinculada'] ?? '', FILTER_VALIDATE_INT),
    'busca' => trim(filter_var($_GET['busca'] ?? '', FILTER_SANITIZE_STRING)),
    'data_inicio' => filter_var($_GET['data_inicio'] ?? '', FILTER_SANITIZE_STRING),
    'data_fim' => filter_var($_GET['data_fim'] ?? '', FILTER_SANITIZE_STRING)
];

// Obter dados com tratamento de erros
try {
    $result = AdminController::manageUsers($filters, $page);
    $success = $result['status'] ?? false;
    
    if ($success) {
        $users = $result['data']['usuarios'] ?? [];
        $statistics = $result['data']['estatisticas'] ?? [];
        $pagination = $result['data']['paginacao'] ?? [];
    } else {
        $error = $result['message'] ?? 'Erro ao carregar usuários';
        $users = [];
        $statistics = [];
        $pagination = [];
    }
} catch (Exception $e) {
    error_log('Erro na view users.php: ' . $e->getMessage());
    $error = 'Erro interno do sistema';
    $users = [];
    $statistics = [];
    $pagination = [];
}

// Obter lojas para o select
$storesResult = AdminController::getAvailableStores();
$stores = $storesResult['status'] ? $storesResult['data'] : [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Sistema de Gerenciamento de Usuários - KlubeCash Admin">
    
    <title>Gerenciar Usuários - Admin KlubeCash</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style">
    
    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    
    <!-- CSS Personalizado -->
    <link href="/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>" rel="stylesheet">
    <link href="/assets/css/views/admin/users_new.css?v=<?= filemtime(__DIR__ . '/../../assets/css/views/admin/users_new.css') ?>" rel="stylesheet">
    
    <!-- Meta tags para performance -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#FF7A00">
    
    <style>
    /* Critical CSS inline para performance */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(2px);
    }
    .loading-content {
        text-align: center;
        padding: 2rem;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .user-avatar-large {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin: 0 auto 1rem;
    }
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .status-ativo .status-indicator { background: #28a745; }
    .status-inativo .status-indicator { background: #ffc107; }
    .status-bloqueado .status-indicator { background: #dc3545; }
    
    /* Melhorias na tabela */
    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF7A00, #E06E00);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        flex-shrink: 0;
    }
    .user-details {
        min-width: 0;
        flex: 1;
    }
    .user-details strong {
        display: block;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .user-details small {
        color: #6c757d;
        font-size: 0.875rem;
    }
    .badge-sm {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        margin-left: 4px;
    }
    
    /* Toast notifications */
    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
    }
    </style>
</head>
<body>
    <!-- Includes dos componentes -->
    <?php include __DIR__ . '/../components/sidebar-admin.php'; ?>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página Aprimorado -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="page-title">
                            <h1 class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                Gerenciar Usuários
                            </h1>
                            <p class="text-muted mb-0">
                                Sistema completo de gerenciamento com controle avançado
                                <span class="badge bg-info ms-2">
                                    <?= number_format($statistics['total_usuarios'] ?? 0) ?> usuários
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="page-actions">
                            <div class="btn-group me-2">
                                <button class="btn btn-outline-secondary" id="btnAtualizarDados" title="Atualizar dados">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-outline-info" id="btnExportarCSV" title="Exportar CSV">
                                    <i class="fas fa-file-csv"></i>
                                </button>
                                <button class="btn btn-outline-primary" id="btnRelatorios" title="Relatórios">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            </div>
                            <button class="btn btn-success" id="btnNovoUsuario">
                                <i class="fas fa-plus me-1"></i>
                                Novo Usuário
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alertas de Erro/Sucesso -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro:</strong> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Estatísticas Avançadas -->
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-primary">
                                <?= number_format($statistics['total_usuarios'] ?? 0) ?>
                            </div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-success">
                                <?= number_format($statistics['total_ativos'] ?? 0) ?>
                            </div>
                            <div class="stat-label">Ativos</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-info">
                                <?= number_format($statistics['total_lojas'] ?? 0) ?>
                            </div>
                            <div class="stat-label">Lojas</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-warning">
                                <?= number_format($statistics['total_funcionarios'] ?? 0) ?>
                            </div>
                            <div class="stat-label">Funcionários</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-secondary">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-secondary">
                                <?= number_format($statistics['total_2fa_ativo'] ?? 0) ?>
                            </div>
                            <div class="stat-label">2FA Ativo</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon bg-dark">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-dark">
                                <?= number_format($statistics['total_ativos_30_dias'] ?? 0) ?>
                            </div>
                            <div class="stat-label">Ativos 30d</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistema de Filtros Avançado -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-filter text-primary me-2"></i>
                            Filtros Avançados
                            <span class="badge bg-primary ms-2" id="activeFiltersCount">0</span>
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" id="btnExpandirFiltros" title="Expandir filtros">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <button class="btn btn-outline-danger" id="btnLimparFiltros">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="filtersContainer">
                    <form id="filtersForm">
                        <div class="row g-3">
                            <!-- Filtros Básicos (sempre visíveis) -->
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <label class="form-label fw-bold">Busca Rápida</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="busca" id="filterBusca" 
                                           placeholder="Nome, email, telefone, CPF..." 
                                           value="<?= htmlspecialchars($filters['busca'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-lg-2 col-md-3 col-sm-6">
                                <label class="form-label fw-bold">Tipo</label>
                                <select class="form-select" name="tipo" id="filterTipo">
                                    <option value="">Todos os tipos</option>
                                    <option value="cliente" <?= ($filters['tipo'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                    <option value="admin" <?= ($filters['tipo'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="loja" <?= ($filters['tipo'] ?? '') === 'loja' ? 'selected' : '' ?>>Loja</option>
                                    <option value="funcionario" <?= ($filters['tipo'] ?? '') === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-3 col-sm-6">
                                <label class="form-label fw-bold">Status</label>
                                <select class="form-select" name="status" id="filterStatus">
                                    <option value="">Todos os status</option>
                                    <option value="ativo" <?= ($filters['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($filters['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="bloqueado" <?= ($filters['status'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                </select>
                            </div>
                            
                            <!-- Filtros Avançados (expansíveis) -->
                            <div class="col-12">
                                <div class="collapse" id="advancedFilters">
                                    <hr class="my-3">
                                    <div class="row g-3">
                                        <div class="col-lg-3 col-md-4 col-sm-6">
                                            <label class="form-label">Subtipo Funcionário</label>
                                            <select class="form-select" name="subtipo_funcionario" id="filterSubtipo">
                                                <option value="">Todos os subtipos</option>
                                                <option value="financeiro" <?= ($filters['subtipo_funcionario'] ?? '') === 'financeiro' ? 'selected' : '' ?>>Financeiro</option>
                                                <option value="gerente" <?= ($filters['subtipo_funcionario'] ?? '') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                                <option value="vendedor" <?= ($filters['subtipo_funcionario'] ?? '') === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-4 col-sm-6">
                                            <label class="form-label">Loja Vinculada</label>
                                            <select class="form-select" name="loja_vinculada" id="filterLojaVinculada">
                                                <option value="">Todas as lojas</option>
                                                <?php foreach ($stores as $store): ?>
                                                    <option value="<?= $store['id'] ?>" <?= ($filters['loja_vinculada'] ?? '') == $store['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($store['nome_fantasia']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 col-sm-6">
                                            <label class="form-label">Data Início</label>
                                            <input type="date" class="form-control" name="data_inicio" id="filterDataInicio" 
                                                   value="<?= $filters['data_inicio'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 col-sm-6">
                                            <label class="form-label">Data Fim</label>
                                            <input type="date" class="form-control" name="data_fim" id="filterDataFim" 
                                                   value="<?= $filters['data_fim'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 col-sm-6">
                                            <label class="form-label">Ordenação</label>
                                            <select class="form-select" name="ordem" id="filterOrdem">
                                                <option value="data_criacao_desc">Mais recentes</option>
                                                <option value="data_criacao_asc">Mais antigos</option>
                                                <option value="nome_asc">Nome A-Z</option>
                                                <option value="nome_desc">Nome Z-A</option>
                                                <option value="ultimo_login_desc">Último login</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Usuários Avançada -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 me-3">
                                <i class="fas fa-table text-primary me-2"></i>
                                Lista de Usuários
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark">
                                    <?= number_format($pagination['total'] ?? 0) ?> registros
                                </span>
                                <?php if (!empty($filters['busca'])): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-search me-1"></i>
                                        Filtrado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2">
                            <!-- Controles de Seleção -->
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="checkbox" class="btn-check" id="selectAll">
                                <label class="btn btn-outline-secondary" for="selectAll" title="Selecionar todos">
                                    <i class="fas fa-check-square"></i>
                                </label>
                            </div>
                            
                            <!-- Ações em Lote -->
                            <div class="btn-group btn-group-sm" id="bulkActions" style="display: none;">
                                <button class="btn btn-outline-success" id="btnAtivarSelecionados">
                                    <i class="fas fa-check"></i> Ativar
                                </button>
                                <button class="btn btn-outline-warning" id="btnInativarSelecionados">
                                    <i class="fas fa-pause"></i> Inativar
                                </button>
                                <button class="btn btn-outline-danger" id="btnBloquearSelecionados">
                                    <i class="fas fa-ban"></i> Bloquear
                                </button>
                            </div>
                            
                            <!-- Configurações da Tabela -->
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header">Colunas Visíveis</h6></li>
                                    <li><div class="dropdown-item-text">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="colTelefone" checked>
                                            <label class="form-check-label" for="colTelefone">Telefone</label>
                                        </div>
                                    </div></li>
                                    <li><div class="dropdown-item-text">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="colDataCriacao" checked>
                                            <label class="form-check-label" for="colDataCriacao">Data Criação</label>
                                        </div>
                                    </div></li>
                                    <li><div class="dropdown-item-text">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="colLojaVinculada">
                                            <label class="form-check-label" for="colLojaVinculada">Loja Vinculada</label>
                                        </div>
                                    </div></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" id="resetColumns">Resetar Colunas</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </th>
                                    <th>Usuário</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th class="col-telefone">Telefone</th>
                                    <th class="col-data-criacao">Data Criação</th>
                                    <th>Último Login</th>
                                    <th class="col-loja-vinculada" style="display: none;">Loja Vinculada</th>
                                    <th width="120" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                    <tr id="emptyState">
                                        <td colspan="9" class="text-center py-5">
                                            <div class="empty-state">
                                                <div class="empty-icon mb-3">
                                                    <i class="fas fa-users fa-3x text-muted"></i>
                                                </div>
                                                <h5 class="text-muted mb-2">Nenhum usuário encontrado</h5>
                                                <p class="text-muted mb-3">
                                                    <?= !empty($filters['busca']) ? 'Nenhum resultado para sua busca. Tente ajustar os filtros.' : 'Nenhum usuário cadastrado ainda.' ?>
                                                </p>
                                                <?php if (empty($filters['busca'])): ?>
                                                    <button class="btn btn-primary" id="btnNovoUsuarioEmpty">
                                                        <i class="fas fa-plus me-1"></i>
                                                        Criar Primeiro Usuário
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user['id'] ?>" class="user-row">
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input user-select" value="<?= $user['id'] ?>">
                                            </td>
                                            
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <i class="fas <?= match($user['tipo']) {
                                                            'admin' => 'fa-user-shield',
                                                            'loja' => 'fa-store',
                                                            'funcionario' => 'fa-user-tie',
                                                            default => 'fa-user'
                                                        } ?>"></i>
                                                    </div>
                                                    <div class="user-details">
                                                        <strong class="user-name"><?= htmlspecialchars($user['nome']) ?></strong>
                                                        <small class="d-block text-muted user-email">
                                                            <?= htmlspecialchars($user['email']) ?>
                                                        </small>
                                                        <div class="mt-1">
                                                            <?php if ($user['subtipo_funcionario']): ?>
                                                                <span class="badge badge-sm bg-info me-1">
                                                                    <?= ucfirst($user['subtipo_funcionario']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($user['mvp'] === 'sim'): ?>
                                                                <span class="badge badge-sm bg-warning text-dark me-1">
                                                                    <i class="fas fa-star me-1"></i>MVP
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($user['email_verified']): ?>
                                                                <span class="badge badge-sm bg-success">
                                                                    <i class="fas fa-check-circle me-1"></i>Verificado
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($user['two_factor_enabled']): ?>
                                                                <span class="badge badge-sm bg-dark">
                                                                    <i class="fas fa-shield-alt me-1"></i>2FA
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <span class="badge bg-<?= match($user['tipo']) {
                                                    'admin' => 'danger',
                                                    'loja' => 'primary',
                                                    'funcionario' => 'info',
                                                    default => 'secondary'
                                                } ?> text-uppercase">
                                                    <?= $user['tipo'] ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <span class="status-badge status-<?= $user['status'] ?> d-inline-flex align-items-center">
                                                    <span class="status-indicator"></span>
                                                    <?= ucfirst($user['status']) ?>
                                                </span>
                                            </td>
                                            
                                            <td class="col-telefone">
                                                <?php if ($user['telefone']): ?>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?= htmlspecialchars($user['telefone']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td class="col-data-criacao">
                                                <span class="text-muted small" title="<?= date('d/m/Y H:i:s', strtotime($user['data_criacao'])) ?>">
                                                    <?= date('d/m/Y', strtotime($user['data_criacao'])) ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <?php if ($user['ultimo_login']): ?>
                                                    <span class="text-muted small" title="<?= date('d/m/Y H:i:s', strtotime($user['ultimo_login'])) ?>">
                                                        <?= date('d/m/Y H:i', strtotime($user['ultimo_login'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Nunca</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td class="col-loja-vinculada" style="display: none;">
                                                <?php if ($user['nome_loja_vinculada']): ?>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-store me-1"></i>
                                                        <?= htmlspecialchars($user['nome_loja_vinculada']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewUser(<?= $user['id'] ?>)" 
                                                            title="Visualizar detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="editUser(<?= $user['id'] ?>)" 
                                                            title="Editar usuário">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                data-bs-toggle="dropdown" 
                                                                title="Mais ações">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><h6 class="dropdown-header">Status</h6></li>
                                                            <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?= $user['id'] ?>, 'ativo')">
                                                                <i class="fas fa-check-circle text-success me-2"></i>Ativar
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?= $user['id'] ?>, 'inativo')">
                                                                <i class="fas fa-pause-circle text-warning me-2"></i>Inativar
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?= $user['id'] ?>, 'bloqueado')">
                                                                <i class="fas fa-ban text-danger me-2"></i>Bloquear
                                                            </a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><h6 class="dropdown-header">Logs</h6></li>
                                                            <li><a class="dropdown-item" href="#" onclick="viewUserLogs(<?= $user['id'] ?>)">
                                                                <i class="fas fa-history text-info me-2"></i>Ver Histórico
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="viewUserSessions(<?= $user['id'] ?>)">
                                                                <i class="fas fa-desktop text-primary me-2"></i>Sessões Ativas
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Paginação Avançada -->
            <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="pagination-info text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Mostrando 
                                <strong><?= number_format(($pagination['pagina_atual'] - 1) * $pagination['por_pagina'] + 1) ?></strong>
                                a 
                                <strong><?= number_format(min($pagination['pagina_atual'] * $pagination['por_pagina'], $pagination['total'])) ?></strong>
                                de 
                                <strong><?= number_format($pagination['total']) ?></strong>
                                usuários
                                
                                <!-- Links Rápidos -->
                                <div class="mt-2 d-block d-md-inline-block ms-md-3">
                                    <div class="btn-group btn-group-sm">
                                        <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto;">
                                            <option value="15" <?= ($pagination['por_pagina'] ?? 15) == 15 ? 'selected' : '' ?>>15 por página</option>
                                            <option value="25" <?= ($pagination['por_pagina'] ?? 15) == 25 ? 'selected' : '' ?>>25 por página</option>
                                            <option value="50" <?= ($pagination['por_pagina'] ?? 15) == 50 ? 'selected' : '' ?>>50 por página</option>
                                            <option value="100" <?= ($pagination['por_pagina'] ?? 15) == 100 ? 'selected' : '' ?>>100 por página</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <nav aria-label="Navegação da paginação">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Primeira página -->
                                    <li class="page-item <?= $pagination['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=1&<?= http_build_query(array_filter($filters)) ?>" title="Primeira página">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    
                                    <!-- Página anterior -->
                                    <li class="page-item <?= $pagination['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $pagination['pagina_anterior'] ?>&<?= http_build_query(array_filter($filters)) ?>" title="Página anterior">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <!-- Páginas numeradas -->
                                    <?php 
                                    $startPage = max(1, $pagination['pagina_atual'] - 2);
                                    $endPage = min($pagination['total_paginas'], $pagination['pagina_atual'] + 2);
                                    
                                    // Se estamos muito no início, mostrar mais páginas à frente
                                    if ($startPage <= 2) {
                                        $endPage = min($pagination['total_paginas'], 5);
                                    }
                                    
                                    // Se estamos muito no final, mostrar mais páginas atrás
                                    if ($endPage >= $pagination['total_paginas'] - 1) {
                                        $startPage = max(1, $pagination['total_paginas'] - 4);
                                    }
                                    
                                    // Adicionar primeira página se não estiver no range
                                    if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&<?= http_build_query(array_filter($filters)) ?>">
                                                1
                                            </a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Páginas do range -->
                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $pagination['pagina_atual'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>">
                                                <?= $i ?>
                                                <?php if ($i === $pagination['pagina_atual']): ?>
                                                    <span class="visually-hidden">(atual)</span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Adicionar última página se não estiver no range -->
                                    <?php if ($endPage < $pagination['total_paginas']): ?>
                                        <?php if ($endPage < $pagination['total_paginas'] - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $pagination['total_paginas'] ?>&<?= http_build_query(array_filter($filters)) ?>">
                                                <?= $pagination['total_paginas'] ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Próxima página -->
                                    <li class="page-item <?= $pagination['pagina_atual'] >= $pagination['total_paginas'] ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $pagination['proxima_pagina'] ?>&<?= http_build_query(array_filter($filters)) ?>" title="Próxima página">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    
                                    <!-- Última página -->
                                    <li class="page-item <?= $pagination['pagina_atual'] >= $pagination['total_paginas'] ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $pagination['total_paginas'] ?>&<?= http_build_query(array_filter($filters)) ?>" title="Última página">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            
                            <!-- Ir para página específica -->
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Ir para:</small>
                                <div class="input-group input-group-sm" style="width: 100px;">
                                    <input type="number" class="form-control" id="gotoPage" 
                                           min="1" max="<?= $pagination['total_paginas'] ?>" 
                                           placeholder="Página" value="<?= $pagination['pagina_atual'] ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="btnGotoPage">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Container para Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
    
    <!-- Modal Novo/Editar Usuário Avançado -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        <span id="modalTitle">Novo Usuário</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Progress Bar -->
                    <div class="progress mb-4" style="height: 4px;">
                        <div class="progress-bar bg-success" id="formProgress" role="progressbar" 
                             style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <form id="formUsuario" novalidate>
                        <input type="hidden" id="userId" name="user_id">
                        
                        <!-- Abas de Navegação -->
                        <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" 
                                        data-bs-target="#basic-info" type="button" role="tab">
                                    <i class="fas fa-user me-1"></i>Informações Básicas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="access-tab" data-bs-toggle="tab" 
                                        data-bs-target="#access-info" type="button" role="tab">
                                    <i class="fas fa-key me-1"></i>Acesso e Permissões
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="additional-tab" data-bs-toggle="tab" 
                                        data-bs-target="#additional-info" type="button" role="tab">
                                    <i class="fas fa-cogs me-1"></i>Configurações Avançadas
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="userTabsContent">
                            <!-- Aba 1: Informações Básicas -->
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nome Completo *</label>
                                        <input type="text" class="form-control" id="userName" name="nome" 
                                               required maxlength="100" autocomplete="name">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Email *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="userEmail" name="email" 
                                                   required maxlength="255" autocomplete="email">
                                        </div>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">O email será usado para login e notificações.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Telefone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="userTelefone" name="telefone" 
                                                   placeholder="(11) 99999-9999" autocomplete="tel">
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">CPF</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" id="userCpf" name="cpf" 
                                                   placeholder="000.000.000-00" maxlength="14">
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba 2: Acesso e Permissões -->
                            <div class="tab-pane fade" id="access-info" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tipo de Usuário *</label>
                                        <select class="form-select" id="userTipo" name="tipo" required>
                                            <option value="">Selecione o tipo de usuário</option>
                                            <option value="cliente">Cliente</option>
                                            <option value="admin">Administrador</option>
                                            <option value="loja">Loja</option>
                                            <option value="funcionario">Funcionário</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="col-md-6" id="subtipoFuncionarioField" style="display: none;">
                                        <label class="form-label fw-bold">Subtipo de Funcionário *</label>
                                        <select class="form-select" id="userSubtipo" name="subtipo_funcionario">
                                            <option value="">Selecione o subtipo</option>
                                            <option value="financeiro">Financeiro</option>
                                            <option value="gerente">Gerente</option>
                                            <option value="vendedor">Vendedor</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">Define as permissões específicas do funcionário.</div>
                                    </div>
                                    
                                    <div class="col-md-12" id="lojaVinculadaField" style="display: none;">
                                        <label class="form-label">Loja Vinculada</label>
                                        <select class="form-select" id="userLojaVinculada" name="loja_vinculada_id">
                                            <option value="">Selecione uma loja (opcional)</option>
                                            <?php foreach ($stores as $store): ?>
                                                <option value="<?= $store['id'] ?>">
                                                    <?= htmlspecialchars($store['nome_fantasia']) ?>
                                                    <?php if ($store['categoria']): ?>
                                                        - <?= htmlspecialchars($store['categoria']) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Para funcionários, vincula o usuário a uma loja específica.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Status do Usuário</label>
                                        <select class="form-select" id="userStatus" name="status">
                                            <option value="ativo">Ativo</option>
                                            <option value="inativo">Inativo</option>
                                            <option value="bloqueado">Bloqueado</option>
                                        </select>
                                        <div class="form-text">Controla se o usuário pode acessar o sistema.</div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Senha <span id="senhaObrigatoria">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="userSenha" name="senha" 
                                                   autocomplete="new-password" minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">
                                            <span id="senhaTexto">Mínimo 8 caracteres com letras e números.</span>
                                            <span id="senhaTextoEdit" style="display: none;">Deixe em branco para manter a senha atual.</span>
                                        </div>
                                        
                                        <!-- Indicador de força da senha -->
                                        <div class="password-strength mt-2" id="passwordStrength" style="display: none;">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar" id="strengthBar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="text-muted" id="strengthText">Digite uma senha</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba 3: Configurações Avançadas -->
                            <div class="tab-pane fade" id="additional-info" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Programa MVP</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="userMvpSwitch" name="mvp_switch">
                                            <label class="form-check-label" for="userMvpSwitch">
                                                Participante do programa MVP
                                            </label>
                                        </div>
                                        <input type="hidden" id="userMvp" name="mvp" value="nao">
                                        <div class="form-text">Usuários MVP têm acesso a recursos exclusivos.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Configurações de Segurança</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="emailVerified" name="email_verified">
                                            <label class="form-check-label" for="emailVerified">
                                                Email verificado
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="twoFactorEnabled" name="two_factor_enabled">
                                            <label class="form-check-label" for="twoFactorEnabled">
                                                Autenticação em dois fatores
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Observações Internas</label>
                                        <textarea class="form-control" id="userObservacoes" name="observacoes" 
                                                  rows="3" maxlength="500" placeholder="Anotações internas sobre o usuário..."></textarea>
                                        <div class="form-text">Visível apenas para administradores (máximo 500 caracteres).</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" id="btnPreviousTab" style="display: none;">
                                <i class="fas fa-chevron-left me-1"></i>Anterior
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" id="btnNextTab">
                                Próximo<i class="fas fa-chevron-right ms-1"></i>
                            </button>
                            <button type="button" class="btn btn-success" id="btnSalvarUsuario" style="display: none;">
                                <i class="fas fa-save me-1"></i>
                                <span id="btnSalvarText">Salvar Usuário</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visualizar Usuário -->
    <div class="modal fade" id="modalViewUsuario" tabindex="-1" aria-labelledby="modalViewUsuarioLabel">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalViewUsuarioLabel">
                        <i class="fas fa-user-circle me-2"></i>
                        Detalhes do Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="modalViewContent">
                    <!-- Loading state -->
                    <div class="text-center py-5" id="userDetailsLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2 text-muted">Carregando detalhes do usuário...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fechar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEditFromView">
                        <i class="fas fa-edit me-1"></i>Editar Usuário
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <h5 class="text-primary">Processando...</h5>
            <p class="text-muted mb-0" id="loadingMessage">Aguarde um momento</p>
        </div>
    </div>

    <!-- Scripts Core -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Scripts Personalizados -->
    <script>
    // Configurações globais
    window.KLUBECASH_CONFIG = {
        apiUrl: '/controllers/AdminController.php',
        currentUserId: <?= $_SESSION['user_id'] ?? 'null' ?>,
        csrfToken: '<?= hash('sha256', session_id() . 'users_management') ?>',
        pagination: {
            currentPage: <?= $pagination['pagina_atual'] ?? 1 ?>,
            totalPages: <?= $pagination['total_paginas'] ?? 1 ?>,
            perPage: <?= $pagination['por_pagina'] ?? 15 ?>
        }
    };
    </script>
    <script src="/assets/js/admin/users_new.js?v=<?= filemtime(__DIR__ . '/../../assets/js/admin/users_new.js') ?>"></script>
</body>
</html>