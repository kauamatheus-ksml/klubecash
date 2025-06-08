<?php
// views/admin/users.php

// Definir o menu ativo na sidebar
$activeMenu = 'usuarios';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/UserController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=" . urlencode('Acesso restrito a administradores'));
    exit;
}

// Obter filtros da URL
$filters = [];
if (isset($_GET['tipo'])) $filters['tipo'] = $_GET['tipo'];
if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
if (isset($_GET['busca'])) $filters['busca'] = $_GET['busca'];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Carregar usuários
$result = UserController::listUsers($filters, $page);
$users = $result['data'] ?? [];
$pagination = $result['pagination'] ?? [];

// Carregar estatísticas
$statsResult = UserController::getUserStats();
$stats = $statsResult['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Cash Admin</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos específicos para a página de usuários */
        .users-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .filters-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .users-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-bloqueado {
            background: #fff3cd;
            color: #856404;
        }
        
        .type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .type-cliente {
            background: #cce5ff;
            color: #0066cc;
        }
        
        .type-admin {
            background: #ffe6cc;
            color: #cc6600;
        }
        
        .type-loja {
            background: #e6f7e6;
            color: #009900;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit:hover {
            background: #1976d2;
            color: white;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .btn-delete:hover {
            background: #d32f2f;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .users-table-container {
                overflow-x: auto;
            }
            
            .filters-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Header -->
            <?php include '../components/header.php'; ?>
            
            <!-- Conteúdo da Página -->
            <div class="page-content">
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
                
                <!-- Estatísticas -->
                <?php if (!empty($stats)): ?>
                <div class="users-stats">
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-users text-primary"></i></div>
                        <div class="number"><?= number_format($stats['total_usuarios']) ?></div>
                        <div class="label">Total de Usuários</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-user text-info"></i></div>
                        <div class="number"><?= number_format($stats['total_clientes']) ?></div>
                        <div class="label">Clientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-store text-success"></i></div>
                        <div class="number"><?= number_format($stats['total_lojas']) ?></div>
                        <div class="label">Lojas</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-shield-alt text-warning"></i></div>
                        <div class="number"><?= number_format($stats['total_admins']) ?></div>
                        <div class="label">Administradores</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-user-plus text-success"></i></div>
                        <div class="number"><?= number_format($stats['novos_hoje']) ?></div>
                        <div class="label">Novos Hoje</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="filters-section">
                    <form method="GET" class="filters-row">
                        <div class="form-group">
                            <label for="busca">Buscar</label>
                            <input type="text" id="busca" name="busca" 
                                   value="<?= htmlspecialchars($filters['busca'] ?? '') ?>"
                                   placeholder="Nome, email ou CPF...">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo">
                                <option value="">Todos os tipos</option>
                                <option value="cliente" <?= ($filters['tipo'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                <option value="loja" <?= ($filters['tipo'] ?? '') === 'loja' ? 'selected' : '' ?>>Loja</option>
                                <option value="admin" <?= ($filters['tipo'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Todos os status</option>
                                <option value="ativo" <?= ($filters['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= ($filters['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                <option value="bloqueado" <?= ($filters['status'] ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="?" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Tabela de Usuários -->
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['nome']) ?></strong>
                                                <?php if ($user['cpf']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($user['cpf']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['email']) ?>
                                        <?php if ($user['email_verified']): ?>
                                            <i class="fas fa-check-circle text-success" title="Email verificado"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['telefone'] ?: '-') ?></td>
                                    <td>
                                        <span class="type-badge type-<?= $user['tipo'] ?>">
                                            <?= ucfirst($user['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['status'] ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($user['data_criacao'])) ?>
                                    </td>
                                    <td>
                                        <?= $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'Nunca' ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-edit" 
                                                    onclick="editUser(<?= $user['id'] ?>)"
                                                    title="Editar usuário">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn-icon btn-delete" 
                                                    onclick="confirmDeleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome']) ?>')"
                                                    title="Excluir usuário">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div style="padding: 2rem;">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h3>Nenhum usuário encontrado</h3>
                                            <p class="text-muted">Tente ajustar os filtros ou adicione novos usuários.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if (!empty($pagination) && $pagination['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['currentPage'] > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['currentPage'] - 1])) ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $pagination['currentPage'] - 2);
                    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                           class="<?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['currentPage'] + 1])) ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['totalPages']])) ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Usuário -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Usuário</h2>
                <button class="btn-close" onclick="closeUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="userForm">
                <input type="hidden" id="userId" name="userId">
                
                <div class="form-group">
                    <label for="nome" class="required">Nome</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" 
                           placeholder="(00) 00000-0000">
                </div>
                
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" 
                           placeholder="000.000.000-00">
                </div>
                
                <div class="form-group">
                    <label for="tipoUsuario" class="required">Tipo</label>
                    <select id="tipoUsuario" name="tipo" required>
                        <option value="">Selecione o tipo</option>
                        <option value="cliente">Cliente</option>
                        <option value="loja">Loja</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="statusUsuario">Status</label>
                    <select id="statusUsuario" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label for="senha" class="required">Senha</label>
                    <input type="password" id="senha" name="senha" 
                           placeholder="Mínimo 6 caracteres">
                    <small class="text-muted" id="passwordHelp">
                        Deixe em branco para manter a senha atual (apenas na edição)
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../../assets/js/main.js"></script>
    <script>
        // Variáveis globais
        let isEditing = false;
        
        // Máscaras para campos
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para telefone
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput) {
                telefoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                        if (value.length < 14) {
                            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                        }
                    }
                    e.target.value = value;
                });
            }
            
            // Máscara para CPF
            const cpfInput = document.getElementById('cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    e.target.value = value;
                });
            }
        });
        
        // Abrir modal para novo usuário
        function openUserModal() {
            isEditing = false;
            document.getElementById('modalTitle').textContent = 'Novo Usuário';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('senha').required = true;
            document.getElementById('passwordHelp').style.display = 'none';
            document.getElementById('userModal').style.display = 'block';
        }
        
        // Editar usuário
        async function editUser(userId) {
            try {
                isEditing = true;
                document.getElementById('modalTitle').textContent = 'Editar Usuário';
                document.getElementById('senha').required = false;
                document.getElementById('passwordHelp').style.display = 'block';
                
                // Buscar dados do usuário
                const response = await fetch(`../../api/users.php?id=${userId}`);
                const data = await response.json();
                
                if (data.status) {
                    const user = data.data;
                    
                    // Preencher formulário
                    document.getElementById('userId').value = user.id;
                    document.getElementById('nome').value = user.nome;
                    document.getElementById('email').value = user.email;
                    document.getElementById('telefone').value = user.telefone || '';
                    document.getElementById('cpf').value = user.cpf || '';
                    document.getElementById('tipoUsuario').value = user.tipo;
                    document.getElementById('statusUsuario').value = user.status;
                    
                    document.getElementById('userModal').style.display = 'block';
                } else {
                    alert('Erro ao carregar dados do usuário: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro interno do servidor');
            }
        }
        
        // Fechar modal
        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }
        
        // Confirmar exclusão
        function confirmDeleteUser(userId, userName) {
            if (confirm(`Tem certeza que deseja excluir o usuário "${userName}"?\n\nEsta ação não pode ser desfeita.`)) {
                deleteUser(userId);
            }
        }
        
        // Excluir usuário
        async function deleteUser(userId) {
            try {
                const response = await fetch(`../../api/users.php?id=${userId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.status) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro interno do servidor');
            }
        }
        
        // Submit do formulário
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const url = isEditing ? '../../api/users.php' : '../../api/users.php';
                const method = isEditing ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status) {
                    alert(result.message);
                    closeUserModal();
                    location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro interno do servidor');
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeUserModal();
            }
        }
    </script>
</body>
</html>