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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../assets/css/views/admin/users.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
</head>
<body>
    <?php 
    // Simulating the include for context, ensure this path is correct
    // include_once '../components/sidebar.php'; 

    // Simulating variables for the template to run without actual PHP execution
    $hasError = false;
    $errorMessage = '';
    $statistics = [
        'total_usuarios' => 1500,
        'total_clientes' => 1200,
        'total_lojas' => 250,
        'total_admins' => 50
    ];
    $_GET['busca'] = $_GET['busca'] ?? '';
    $_GET['tipo'] = $_GET['tipo'] ?? '';
    $_GET['status'] = $_GET['status'] ?? '';
    $users = [
        [
            'id' => 1, 
            'nome' => 'João Silva', 
            'email' => 'joao.silva@example.com', 
            'tipo' => 'cliente', 
            'status' => 'ativo', 
            'data_criacao' => '2023-01-15 10:00:00', 
            'ultimo_login' => '2023-06-01 14:30:00'
        ],
        [
            'id' => 2, 
            'nome' => 'Loja Exemplo Ltda', 
            'email' => 'contato@lojaexemplo.com', 
            'tipo' => 'loja', 
            'status' => 'inativo', 
            'data_criacao' => '2023-02-20 11:00:00', 
            'ultimo_login' => null
        ],
        [
            'id' => 3, 
            'nome' => 'Admin Master', 
            'email' => 'admin@klubecash.com', 
            'tipo' => 'admin', 
            'status' => 'bloqueado', 
            'data_criacao' => '2023-01-01 08:00:00', 
            'ultimo_login' => '2023-05-25 09:15:00'
        ]
    ];
    $page = 1;
    $pagination = [
        'total_paginas' => 5,
        'por_pagina' => 10,
        'total' => 50
    ];

    // Ensure http_build_query is available or provide a polyfill if running in a non-server environment for testing
    if (!function_exists('http_build_query')) {
        function http_build_query($query_data, $numeric_prefix = '', $arg_separator = '&', $enc_type = PHP_QUERY_RFC1738) {
            $result = [];
            foreach ($query_data as $key => $value) {
                if ($enc_type === PHP_QUERY_RFC3986) {
                    $result[] = rawurlencode($key) . '=' . rawurlencode($value);
                } else {
                    $result[] = urlencode($key) . '=' . urlencode($value);
                }
            }
            return implode($arg_separator, $result);
        }
    }
    ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
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
            </div>
            <?php endif; ?>
            
            <div id="messageContainer" class="alert-container"></div>
            
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
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th class="actions-column">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
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
                                                    $tipos = [
                                                        'cliente' => 'Cliente',
                                                        'loja' => 'Loja',
                                                        'admin' => 'Admin'
                                                    ];
                                                    echo $tipos[$user['tipo']] ?? ucfirst($user['tipo']);
                                                ?>
                                            </span>
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
                                                <div class="date-primary">
                                                    <?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?>
                                                </div>
                                                <div class="date-secondary">
                                                    <?php echo date('H:i', strtotime($user['data_criacao'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php if ($user['ultimo_login']): ?>
                                                    <div class="date-primary">
                                                        <?php echo date('d/m/Y', strtotime($user['ultimo_login'])); ?>
                                                    </div>
                                                    <div class="date-secondary">
                                                        <?php echo date('H:i', strtotime($user['ultimo_login'])); ?>
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
                
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Mostrando <?php echo (($page - 1) * $pagination['por_pagina']) + 1; ?>-<?php echo min($page * $pagination['por_pagina'], $pagination['total']); ?> 
                            de <?php echo $pagination['total']; ?> usuários
                        </div>
                        <div class="pagination">
                            <?php
                                $queryParams = $_GET; // Get current query parameters
                                unset($queryParams['page']); // Remove page from it, as we'll add it
                                $queryString = http_build_query($queryParams);
                                if (!empty($queryString)) {
                                    $queryString = '&amp;' . $queryString;
                                }
                            ?>
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo $queryString; ?>" class="pagination-arrow" title="Primeira página">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo max(1, $page - 1); ?><?php echo $queryString; ?>" class="pagination-arrow" title="Página anterior">
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
                                <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" 
                                   class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pagination['total_paginas']): ?>
                                <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?><?php echo $queryString; ?>" class="pagination-arrow" title="Próxima página">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $pagination['total_paginas']; ?><?php echo $queryString; ?>" class="pagination-arrow" title="Última página">
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
                    </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideViewUserModal()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Carregando...</p>
        </div>
    </div>
    
    <script>
        function showUserModal() { console.log('showUserModal called'); document.getElementById('userModal').style.display = 'block'; }
        function hideUserModal() { console.log('hideUserModal called'); document.getElementById('userModal').style.display = 'none'; }
        function showViewUserModal() { console.log('showViewUserModal called'); document.getElementById('viewUserModal').style.display = 'block'; }
        function hideViewUserModal() { console.log('hideViewUserModal called'); document.getElementById('viewUserModal').style.display = 'none'; }
        function submitUserForm(event) { event.preventDefault(); console.log('submitUserForm called'); hideUserModal(); }
        function clearFilters() { console.log('clearFilters called'); document.getElementById('filtersForm').reset(); }
        function bulkAction(action) { console.log('bulkAction called with:', action); }
        function exportUsers() { console.log('exportUsers called'); }
        function toggleSelectAll() { console.log('toggleSelectAll called'); }
        function toggleUserSelection(el, id) { console.log('toggleUserSelection called for id:', id, 'checked:', el.checked); }
        function editUser(id) { console.log('editUser called for id:', id); showUserModal(); /* populate form */ }
        function viewUser(id) { console.log('viewUser called for id:', id); showViewUserModal(); /* load content */ }
        function changeUserStatus(id, status, name) { console.log('changeUserStatus called for id:', id, 'to status:', status, 'for user:', name); }
        function togglePassword(fieldId) { 
            console.log('togglePassword called for field:', fieldId);
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        // Make sure pagination query string building works correctly for demonstration
        // This part would typically be handled by PHP, adjusted for JS if needed for dynamic updates.
    </script>
    
</body>
</html>