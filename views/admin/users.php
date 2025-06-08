<?php
// views/admin/users.php
$activeMenu = 'usuarios';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/UserManagementController.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Processar filtros
$filters = [
    'tipo' => $_GET['tipo'] ?? '',
    'status' => $_GET['status'] ?? '',
    'busca' => $_GET['busca'] ?? ''
];
$page = $_GET['page'] ?? 1;

// Buscar dados
$result = UserManagementController::listUsers($filters, $page);
$users = $result['data']['usuarios'] ?? [];
$stats = $result['data']['estatisticas'] ?? [];
$totalPages = $result['data']['total_paginas'] ?? 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Cash</title>
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
                <h1>Gerenciar Usuários</h1>
                <button class="btn btn-primary" onclick="openUserModal()">
                    <i class="fas fa-plus"></i> Novo Usuário
                </button>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div>
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle text-success"></i>
                    <div>
                        <h3><?php echo $stats['ativos'] ?? 0; ?></h3>
                        <p>Usuários Ativos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user"></i>
                    <div>
                        <h3><?php echo $stats['clientes'] ?? 0; ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-store"></i>
                    <div>
                        <h3><?php echo $stats['lojas'] ?? 0; ?></h3>
                        <p>Lojas</p>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-section">
                <form method="get" class="filters-form">
                    <div class="filter-group">
                        <select name="tipo" class="form-control">
                            <option value="">Todos os tipos</option>
                            <option value="cliente" <?php echo $filters['tipo'] === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                            <option value="loja" <?php echo $filters['tipo'] === 'loja' ? 'selected' : ''; ?>>Loja</option>
                            <option value="admin" <?php echo $filters['tipo'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="status" class="form-control">
                            <option value="">Todos os status</option>
                            <option value="ativo" <?php echo $filters['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $filters['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                            <option value="bloqueado" <?php echo $filters['status'] === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" name="busca" placeholder="Buscar por nome ou email" 
                               value="<?php echo htmlspecialchars($filters['busca']); ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </form>
            </div>
            
            <!-- Tabela de usuários -->
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Cashback Total</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['nome']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['tipo']; ?>">
                                    <?php echo ucfirst($user['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>R$ <?php echo number_format($user['total_cashback'] ?? 0, 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="editUser(<?php echo $user['id']; ?>)" 
                                            class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                            class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Usuário -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Usuário</h2>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" class="form-control">
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control">
                    <small>Deixe em branco para manter a senha atual (apenas edição)</small>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo de Usuário</label>
                    <select id="tipo" name="tipo" required class="form-control">
                        <option value="cliente">Cliente</option>
                        <option value="loja">Loja</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required class="form-control">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    const API_URL = '<?php echo USER_MANAGEMENT_URL; ?>';
    
    // Abrir modal
    function openUserModal() {
        document.getElementById('userModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Novo Usuário';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('senha').required = true;
    }
    
    // Fechar modal
    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }
    
    // Editar usuário
    async function editUser(id) {
        try {
            const response = await fetch(`${API_URL}?id=${id}`);
            const result = await response.json();
            
            if (result.status) {
                const user = result.data;
                document.getElementById('modalTitle').textContent = 'Editar Usuário';
                document.getElementById('userId').value = user.id;
                document.getElementById('nome').value = user.nome;
                document.getElementById('email').value = user.email;
                document.getElementById('telefone').value = user.telefone || '';
                document.getElementById('senha').value = '';
                document.getElementById('senha').required = false;
                document.getElementById('tipo').value = user.tipo;
                document.getElementById('status').value = user.status;
                document.getElementById('userModal').style.display = 'block';
            } else {
                alert(result.message || 'Erro ao buscar usuário');
            }
        } catch (error) {
            alert('Erro ao buscar usuário');
        }
    }
    
    // Excluir usuário
    async function deleteUser(id) {
        if (!confirm('Tem certeza que deseja excluir este usuário?')) {
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?id=${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (result.status) {
                alert('Usuário excluído com sucesso');
                location.reload();
            } else {
                alert(result.message || 'Erro ao excluir usuário');
            }
        } catch (error) {
            alert('Erro ao excluir usuário');
        }
    }
    
    // Salvar usuário
    document.getElementById('userForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        const userId = data.id;
        delete data.id;
        
        // Remover senha se estiver vazia (edição)
        if (!data.senha) {
            delete data.senha;
        }
        
        try {
            const response = await fetch(userId ? `${API_URL}?id=${userId}` : API_URL, {
                method: userId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.status) {
                alert(userId ? 'Usuário atualizado com sucesso' : 'Usuário criado com sucesso');
                location.reload();
            } else {
                alert(result.message || 'Erro ao salvar usuário');
            }
        } catch (error) {
            alert('Erro ao salvar usuário');
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