<?php
/**
 * Página de Gerenciamento de Usuários - Admin
 * Klube Cash - Sistema de Cashback
 */

// Definir o menu ativo na sidebar
$activeMenu = 'usuarios';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/UserController.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação e permissões
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Processar parâmetros da URL
$page = max(1, (int)($_GET['page'] ?? 1));
$filters = [
    'tipo' => $_GET['tipo'] ?? '',
    'status' => $_GET['status'] ?? '',
    'busca' => $_GET['busca'] ?? ''
];

// Carregar dados dos usuários
try {
    $result = UserController::listUsers($filters, $page);
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';
    
    if (!$hasError) {
        $users = $result['data']['usuarios'];
        $statistics = $result['data']['estatisticas'];
        $pagination = $result['data']['paginacao'];
    } else {
        $users = [];
        $statistics = [];
        $pagination = [];
    }
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao carregar dados: " . $e->getMessage();
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
    <title>Gerenciar Usuários - Klube Cash Admin</title>
    
    <!-- CSS -->
    <link href="<?= CSS_URL ?>/main.css?v=<?= ASSETS_VERSION ?>" rel="stylesheet">
    <link href="<?= CSS_URL ?>/admin.css?v=<?= ASSETS_VERSION ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS específico para gestão de usuários -->
    <style>
        .users-management {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .users-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .btn-add-user {
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .status-ativo { background: #d4edda; color: #155724; }
        .status-inativo { background: #f8d7da; color: #721c24; }
        .status-bloqueado { background: #d1ecf1; color: #0c5460; }
        
        .tipo-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .tipo-cliente { background: #cce5ff; color: #004085; }
        .tipo-loja { background: #fff3cd; color: #856404; }
        .tipo-admin { background: #d4edda; color: #155724; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.85em;
        }
        
        .btn-view { background: #007bff; color: white; }
        .btn-edit { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        
        .pagination {
            padding: 20px;
            text-align: center;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .users-table {
                font-size: 0.9em;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../../views/components/header.php'; ?>
    
    <div class="admin-layout">
        <?php include '../../views/components/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="users-management">
                <!-- Cabeçalho -->
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                    <p>Gerencie todos os usuários do sistema Klube Cash</p>
                </div>
                
                <!-- Exibir erros se houver -->
                <?php if ($hasError): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <?php if (!$hasError && !empty($statistics)): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number"><?= number_format($statistics['total_clientes']) ?></div>
                        <div class="label">Clientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?= number_format($statistics['total_lojas']) ?></div>
                        <div class="label">Lojas</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?= number_format($statistics['total_admins']) ?></div>
                        <div class="label">Administradores</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?= number_format($statistics['total_ativos']) ?></div>
                        <div class="label">Usuários Ativos</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="filters-section">
                    <h3><i class="fas fa-filter"></i> Filtros</h3>
                    <form method="GET" class="filters-form">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="tipo">Tipo de Usuário:</label>
                                <select name="tipo" id="tipo">
                                    <option value="">Todos os tipos</option>
                                    <option value="cliente" <?= $filters['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                    <option value="loja" <?= $filters['tipo'] === 'loja' ? 'selected' : '' ?>>Loja</option>
                                    <option value="admin" <?= $filters['tipo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status:</label>
                                <select name="status" id="status">
                                    <option value="">Todos os status</option>
                                    <option value="ativo" <?= $filters['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= $filters['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="bloqueado" <?= $filters['status'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="busca">Buscar:</label>
                                <input type="text" name="busca" id="busca" value="<?= htmlspecialchars($filters['busca']) ?>" placeholder="Nome ou email...">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tabela de Usuários -->
                <div class="users-table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Lista de Usuários</h3>
                        <button class="btn-add-user" onclick="openCreateUserModal()">
                            <i class="fas fa-plus"></i> Adicionar Usuário
                        </button>
                    </div>
                    
                    <?php if (!$hasError && !empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Último Login</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['nome']) ?></strong>
                                    <?php if ($user['tipo'] === 'loja' && !empty($user['loja_nome'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($user['loja_nome']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="tipo-badge tipo-<?= $user['tipo'] ?>">
                                        <?= ucfirst($user['tipo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $user['status'] ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($user['data_criacao'])) ?></td>
                                <td>
                                    <?= $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'Nunca' ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewUser(<?= $user['id'] ?>)" title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action btn-edit" onclick="editUserStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')" title="Editar status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['tipo'] !== 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome']) ?>')" title="Remover">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Paginação -->
                    <?php if ($pagination['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_previous']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['pagina_atual'] - 1])) ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_paginas']; $i++): ?>
                            <?php if ($i == $pagination['pagina_atual']): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['pagina_atual'] + 1])) ?>">
                                Próximo <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <i class="fas fa-users" style="font-size: 3em; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>Nenhum usuário encontrado com os filtros aplicados.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal para Criar Usuário -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Adicionar Novo Usuário</h3>
                <span class="close" onclick="closeCreateUserModal()">&times;</span>
            </div>
            
            <form id="createUserForm">
                <div class="form-group">
                    <label for="create_nome">Nome Completo *</label>
                    <input type="text" id="create_nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="create_email">Email *</label>
                    <input type="email" id="create_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="create_telefone">Telefone</label>
                    <input type="text" id="create_telefone" name="telefone">
                </div>
                
                <div class="form-group">
                    <label for="create_senha">Senha *</label>
                    <input type="password" id="create_senha" name="senha" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="create_tipo">Tipo de Usuário *</label>
                    <select id="create_tipo" name="tipo" required>
                        <option value="">Selecione...</option>
                        <option value="cliente">Cliente</option>
                        <option value="loja">Loja</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="create_status">Status *</label>
                    <select id="create_status" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para Editar Status -->
    <div id="editStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Alterar Status do Usuário</h3>
                <span class="close" onclick="closeEditStatusModal()">&times;</span>
            </div>
            
            <form id="editStatusForm">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label for="edit_status">Novo Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditStatusModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alteração
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para Ver Detalhes -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Detalhes do Usuário</h3>
                <span class="close" onclick="closeViewUserModal()">&times;</span>
            </div>
            
            <div id="userDetailsContent">
                <!-- Conteúdo será carregado dinamicamente -->
            </div>
        </div>
    </div>
    
    <!-- Container para mensagens -->
    <div id="messageContainer"></div>
    
    <!-- JavaScript -->
    <script src="<?= JS_URL ?>/main.js?v=<?= ASSETS_VERSION ?>"></script>
    <script>
        // Variáveis globais
        const USER_CONTROLLER_URL = '<?= USER_CONTROLLER_URL ?>';
        
        // Funções para modais
        function openCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'block';
            document.getElementById('createUserForm').reset();
        }
        
        function closeCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }
        
        function editUserStatus(userId, currentStatus) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_status').value = currentStatus;
            document.getElementById('editStatusModal').style.display = 'block';
        }
        
        function closeEditStatusModal() {
            document.getElementById('editStatusModal').style.display = 'none';
        }
        
        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
        }
        
        // Função para ver detalhes do usuário
        function viewUser(userId) {
            fetch(`${USER_CONTROLLER_URL}?action=details&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        const user = data.data;
                        const content = `
                            <div style="line-height: 1.6;">
                                <p><strong>ID:</strong> ${user.id}</p>
                                <p><strong>Nome:</strong> ${user.nome}</p>
                                <p><strong>Email:</strong> ${user.email}</p>
                                <p><strong>Telefone:</strong> ${user.telefone || 'Não informado'}</p>
                                <p><strong>Tipo:</strong> ${user.tipo}</p>
                                <p><strong>Status:</strong> ${user.status}</p>
                                <p><strong>Criado em:</strong> ${new Date(user.data_criacao).toLocaleString('pt-BR')}</p>
                                <p><strong>Último login:</strong> ${user.ultimo_login ? new Date(user.ultimo_login).toLocaleString('pt-BR') : 'Nunca'}</p>
                                
                                ${user.tipo === 'loja' && user.loja_id ? `
                                    <hr>
                                    <h4>Informações da Loja:</h4>
                                    <p><strong>Nome Fantasia:</strong> ${user.nome_fantasia || 'Não informado'}</p>
                                    <p><strong>CNPJ:</strong> ${user.cnpj || 'Não informado'}</p>
                                    <p><strong>Status da Loja:</strong> ${user.loja_status || 'Não informado'}</p>
                                ` : ''}
                                
                                ${user.estatisticas ? `
                                    <hr>
                                    <h4>Estatísticas:</h4>
                                    ${user.tipo === 'cliente' ? `
                                        <p><strong>Total de Transações:</strong> ${user.estatisticas.total_transacoes}</p>
                                        <p><strong>Total de Cashback:</strong> R$ ${parseFloat(user.estatisticas.total_cashback).toFixed(2)}</p>
                                        <p><strong>Cashback Disponível:</strong> R$ ${parseFloat(user.estatisticas.cashback_disponivel).toFixed(2)}</p>
                                    ` : ''}
                                    ${user.tipo === 'loja' ? `
                                        <p><strong>Total de Vendas:</strong> ${user.estatisticas.total_vendas}</p>
                                        <p><strong>Volume de Vendas:</strong> R$ ${parseFloat(user.estatisticas.volume_vendas).toFixed(2)}</p>
                                        <p><strong>Cashback Gerado:</strong> R$ ${parseFloat(user.estatisticas.cashback_gerado).toFixed(2)}</p>
                                    ` : ''}
                                ` : ''}
                            </div>
                        `;
                        document.getElementById('userDetailsContent').innerHTML = content;
                        document.getElementById('viewUserModal').style.display = 'block';
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar detalhes:', error);
                    showMessage('Erro ao carregar detalhes do usuário', 'error');
                });
        }
        
        // Função para deletar usuário
        function deleteUser(userId, userName) {
            if (confirm(`Tem certeza que deseja remover o usuário "${userName}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('user_id', userId);
                
                fetch(USER_CONTROLLER_URL, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        showMessage(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao deletar usuário:', error);
                    showMessage('Erro ao remover usuário', 'error');
                });
            }
        }
        
        // Função para mostrar mensagens
        function showMessage(message, type = 'info') {
            const container = document.getElementById('messageContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.style.cssText = `
                padding: 15px;
                margin: 10px 0;
                border-radius: 4px;
                position: relative;
                animation: slideIn 0.3s ease;
            `;
            
            if (type === 'success') {
                alert.style.backgroundColor = '#d4edda';
                alert.style.color = '#155724';
                alert.style.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                alert.style.backgroundColor = '#f8d7da';
                alert.style.color = '#721c24';
                alert.style.border = '1px solid #f5c6cb';
            }
            
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
                <button onclick="this.parentElement.remove()" style="
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                ">&times;</button>
            `;
            
            container.appendChild(alert);
            
            // Remover automaticamente após 5 segundos
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Event listeners para formulários
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            fetch(USER_CONTROLLER_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    showMessage(data.message, 'success');
                    closeCreateUserModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao criar usuário:', error);
                showMessage('Erro ao criar usuário', 'error');
            });
        });
        
        document.getElementById('editStatusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_status');
            
            fetch(USER_CONTROLLER_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    showMessage(data.message, 'success');
                    closeEditStatusModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar status:', error);
                showMessage('Erro ao atualizar status', 'error');
            });
        });
        
        // Fechar modais ao clicar fora
        window.addEventListener('click', function(event) {
            const modals = ['createUserModal', 'editStatusModal', 'viewUserModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // CSS para animação
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>