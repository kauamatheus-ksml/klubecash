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
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");

    exit;
}

// Inicializar variáveis de paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

// Verificar valores dos filtros para debug
error_log('Filtros preparados: ' . print_r($filters, true));
error_log('Página: ' . $page);
try {
    // Obter dados dos usuários
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
    <link rel="stylesheet" href="../../assets/css/views/admin/users.css">
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <h1>Usuários</h1>
                <button class="btn btn-primary" onclick="showUserModal()">Adicionar Usuário</button>
            </div>

            <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
                <div class="selected-count">
                    <span id="selectedCount">0</span> usuários selecionados
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-warning btn-sm" onclick="bulkAction('inativo')">Desativar</button>
                    <button class="btn btn-danger btn-sm" onclick="bulkAction('bloqueado')">Bloquear</button>
                </div>
            </div>
            <!-- Container de mensagens -->
            <div id="messageContainer" class="alert-container"></div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Card Principal -->
            <div class="card">
                <!-- Tabela de Usuários -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" onchange="toggleUserSelection(this, <?php echo $user['id']; ?>)">
                                    <span class="checkmark"></span>
                                </div>
                                </th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Tipo</th>
                                <th>Cadastro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Nenhum usuário encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                                <span class="checkmark"></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($user['tipo'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                switch ($user['status']) {
                                                    case 'ativo':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'inativo':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    case 'bloqueado':
                                                        $statusClass = 'badge-danger';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="action-btn deactivate" onclick="deactivateUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nome']); ?>')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação corrigida -->
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <a href="?page=<?php echo max(1, $page - 1); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($pagination['total_paginas'], $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
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
                <h3 class="modal-title" id="userModalTitle">Adicionar Usuário</h3>
                <div class="modal-close" onclick="hideUserModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>
            
            <form id="userForm" onsubmit="submitUserForm(event)">
                <input type="hidden" id="userId" name="id" value="">
                
                <!-- Campos do formulário -->
                 <div class="form-group">
                    <label class="form-label" for="userType">Tipo</label>
                    <select class="form-select" id="userType" name="tipo">
                        <option value="cliente">Cliente</option>
                        <option value="admin">Administrador</option>
                        <option value="loja">Loja</option>
                    </select>
                </div>
                
                 <div class="form-group">
                    <label class="form-label" for="userEmail">E-mail</label>
                    <div id="emailSelectContainer" style="display: none;">
                        <select class="form-select" id="userEmailSelect" name="email_select">
                            <option value="">Selecione uma loja...</option>
                        </select>
                    </div>
                    <input type="email" class="form-control" id="userEmail" name="email" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="userName">Nome</label>
                    <input type="text" class="form-control" id="userName" name="nome" required>
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
                    <input type="text" class="form-control" id="userPhone" name="telefone">
                </div>
                
                
                
                <div class="form-group">
                    <label class="form-label" for="userStatus">Status</label>
                    <select class="form-select" id="userStatus" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label class="form-label" for="userPassword">Senha</label>
                    <input type="password" class="form-control" id="userPassword" name="senha" required>
                    <small id="passwordHelp" class="form-text">Mínimo de 8 caracteres</small>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
// Variáveis globais
let currentUserId = null;
let selectedUsers = [];
let availableStores = []; // Array com lojas disponíveis para vinculação
let isStoreUser = false; // Flag para controlar se é usuário do tipo loja

// Função para exibir mensagens
function showMessage(message, type = 'success') {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${type}">
            ${message}
        </div>
    `;
    
    // Rolar para a mensagem
    messageContainer.scrollIntoView({ behavior: 'smooth' });
    
    // Remover a mensagem após 5 segundos
    setTimeout(() => {
        messageContainer.innerHTML = '';
    }, 5000);
}

// Função para carregar lojas disponíveis (aprovadas e sem usuário vinculado)
function loadAvailableStores() {
    fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_available_stores'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status && data.data) {
            availableStores = data.data;
            populateStoreSelect();
        } else {
            console.warn('Nenhuma loja disponível encontrada');
            availableStores = [];
        }
    })
    .catch(error => {
        console.error('Erro ao carregar lojas:', error);
        availableStores = [];
    });
}

// Função para popular o select de lojas
function populateStoreSelect() {
    const select = document.getElementById('userEmailSelect');
    
    // Limpar opções existentes
    select.innerHTML = '<option value="">Selecione uma loja...</option>';
    
    // Adicionar lojas disponíveis
    availableStores.forEach(store => {
        const option = document.createElement('option');
        option.value = store.email;
        option.textContent = `${store.nome_fantasia} (${store.email})`;
        option.dataset.storeData = JSON.stringify(store);
        select.appendChild(option);
    });
}

// Função para resetar campos relacionados a loja
function resetStoreFields() {
    // Ocultar container do select de lojas e mostrar input normal de email
    document.getElementById('emailSelectContainer').style.display = 'none';
    document.getElementById('userEmail').style.display = 'block';
    document.getElementById('storeDataFields').style.display = 'none';
    
    // Limpar valores dos campos
    document.getElementById('userEmailSelect').value = '';
    document.getElementById('storeName').value = '';
    document.getElementById('storeDocument').value = '';
    document.getElementById('storeCategory').value = '';
    
    // Habilitar edição dos campos principais
    document.getElementById('userEmail').readOnly = false;
    document.getElementById('userName').readOnly = false;
    document.getElementById('userPhone').readOnly = false;
    document.getElementById('userEmail').required = true;
    
    // Resetar flag
    isStoreUser = false;
}

// Função para mostrar modal de adicionar usuário
function showUserModal() {
    document.getElementById('userModalTitle').textContent = 'Adicionar Usuário';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('passwordHelp').textContent = 'Mínimo de 8 caracteres';
    currentUserId = null;
    
    // Resetar campos de loja
    resetStoreFields();
    
    // Mostrar modal
    document.getElementById('userModal').classList.add('show');
    
    // Carregar lojas disponíveis para o select
    loadAvailableStores();
}

// Função para esconder modal
function hideUserModal() {
    document.getElementById('userModal').classList.remove('show');
}

// Função para editar usuário
function editUser(userId) {
    // Armazenar ID do usuário atual
    currentUserId = userId;
    
    // Mostrar carregamento
    document.getElementById('userModalTitle').textContent = 'Carregando...';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = userId;
    document.getElementById('userModal').classList.add('show');
    
    // Resetar campos de loja (no modo edição, não mostramos a seleção de loja)
    resetStoreFields();
    
    // Fazer requisição AJAX para obter dados do usuário
    fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=getUserDetails&user_id=' + userId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status && data.data && data.data.usuario) {
            const userData = data.data.usuario;
            
            // Preencher o formulário
            document.getElementById('userModalTitle').textContent = 'Editar Usuário';
            document.getElementById('userId').value = userData.id;
            document.getElementById('userName').value = userData.nome;
            document.getElementById('userEmail').value = userData.email;
            
            // Campo telefone
            if (userData.telefone) {
                document.getElementById('userPhone').value = userData.telefone;
            }
            
            document.getElementById('userType').value = userData.tipo;
            document.getElementById('userStatus').value = userData.status;
            
            // Ocultar campo de senha na edição
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('userPassword').required = false;
            document.getElementById('userPassword').value = '';
        } else {
            hideUserModal();
            showMessage(data.message || 'Erro ao carregar dados do usuário', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        hideUserModal();
        showMessage('Erro ao carregar dados do usuário: ' + error.message, 'danger');
    });
}

// Função para desativar usuário
function deactivateUser(userId, userName) {
    if (confirm(`Tem certeza que deseja desativar o usuário "${userName}"?`)) {
        fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_user_status&user_id=' + userId + '&status=inativo'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                showMessage('Usuário desativado com sucesso!');
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                showMessage(data.message || 'Erro ao desativar usuário', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showMessage('Erro ao processar a solicitação: ' + error.message, 'danger');
        });
    }
}

// Função para enviar formulário (modificada para lidar com lojas)
function submitUserForm(event) {
    event.preventDefault();
    
    // Obter dados do formulário
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    // Se for usuário do tipo loja, usar o email selecionado no select
    if (isStoreUser) {
        const selectedEmail = document.getElementById('userEmailSelect').value;
        if (selectedEmail) {
            formData.set('email', selectedEmail);
        } else {
            showMessage('Por favor, selecione uma loja antes de continuar.', 'danger');
            return;
        }
    }
    
    // Verificar se estamos editando ou criando
    const userId = formData.get('id');
    const isEditing = userId !== '';
    
    // Mostrar indicador de carregamento
    const saveButton = form.querySelector('button[type="submit"]');
    const originalButtonText = saveButton.textContent;
    saveButton.textContent = 'Salvando...';
    saveButton.disabled = true;
    
    // Converter FormData para URLSearchParams para melhor compatibilidade
    const data = new URLSearchParams();
    
    if (isEditing) {
        // Para edição, usamos AdminController.php com action=update_user
        data.append('action', 'update_user');
        data.append('user_id', userId);
        data.append('nome', formData.get('nome'));
        data.append('email', formData.get('email'));
        data.append('telefone', formData.get('telefone') || '');
        data.append('tipo', formData.get('tipo'));
        data.append('status', formData.get('status'));
        
        // Senha opcional
        if (formData.get('senha') && formData.get('senha').trim() !== '') {
            data.append('senha', formData.get('senha'));
        }
        
        var url = '<?php echo SITE_URL; ?>/controllers/AdminController.php';
    } else {
        // Para criação, usamos AuthController.php
        data.append('action', 'register');
        data.append('nome', formData.get('nome'));
        data.append('email', formData.get('email'));
        data.append('telefone', formData.get('telefone') || '');
        data.append('senha', formData.get('senha'));
        data.append('tipo', formData.get('tipo'));
        data.append('ajax', '1'); // Indicar que é uma chamada AJAX
        
        var url = '<?php echo SITE_URL; ?>/controllers/AuthController.php';
    }
    
    // Enviar requisição
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    })
    .then(response => {
        return response.text();
    })
    .then(text => {
        console.log('Resposta completa do servidor:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Falha ao analisar JSON:", text);
            throw new Error("Resposta do servidor não é JSON válido");
        }
        return data;
    })
    .then(data => {
        if (data.status) {
            hideUserModal();
            let message = isEditing ? 'Usuário atualizado com sucesso!' : 'Usuário adicionado com sucesso!';
            
            // Adicionar informação sobre vinculação da loja se aplicável
            if (data.store_linked) {
                message += ' A loja foi vinculada automaticamente ao usuário.';
            }
            
            showMessage(message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showMessage(data.message || 'Erro ao processar solicitação', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showMessage('Erro ao processar a solicitação: ' + error.message, 'danger');
    })
    .finally(() => {
        saveButton.textContent = originalButtonText;
        saveButton.disabled = false;
    });
}

// Função para atualizar a barra de ações em massa
function updateBulkActionBar() {
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = selectedUsers.length;
    
    if (selectedUsers.length > 0) {
        bulkActionBar.style.display = 'flex';
    } else {
        bulkActionBar.style.display = 'none';
    }
}

// Função para processar checkbox individual
function toggleUserSelection(checkbox, userId) {
    if (checkbox.checked) {
        if (!selectedUsers.includes(userId)) {
            selectedUsers.push(userId);
        }
    } else {
        selectedUsers = selectedUsers.filter(id => id !== userId);
        // Desmarcar o checkbox "selecionar todos" se nem todos estão selecionados
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
    }
    
    updateBulkActionBar();
}

// Função para selecionar/desselecionar todos
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    selectedUsers = [];
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            const userId = parseInt(checkbox.value);
            if (!selectedUsers.includes(userId)) {
                selectedUsers.push(userId);
            }
        }
    });
    
    updateBulkActionBar();
}

// Função para executar ação em massa
function bulkAction(status) {
    if (selectedUsers.length === 0) return;
    
    const actionText = status === 'inativo' ? 'desativar' : 'bloquear';
    
    if (confirm(`Tem certeza que deseja ${actionText} ${selectedUsers.length} usuários selecionados?`)) {
        const bulkActionBar = document.getElementById('bulkActionBar');
        bulkActionBar.innerHTML = `<div class="selected-count">Processando ${selectedUsers.length} usuários...</div>`;
        
        let processed = 0;
        let successful = 0;
        
        selectedUsers.forEach(userId => {
            fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_user_status&user_id=${userId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                processed++;
                if (data.status) successful++;
                
                if (processed === selectedUsers.length) {
                    showMessage(`${successful} usuários foram ${actionText === 'desativar' ? 'desativados' : 'bloqueados'} com sucesso!`);
                    setTimeout(() => { location.reload(); }, 1500);
                }
            })
            .catch(() => {
                processed++;
                if (processed === selectedUsers.length) {
                    showMessage(`${successful} usuários foram ${actionText === 'desativar' ? 'desativados' : 'bloqueados'} com sucesso!`);
                    setTimeout(() => { location.reload(); }, 1500);
                }
            });
        });
    }
}

// Event Listeners - executados quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para mudança no tipo de usuário
    const userTypeSelect = document.getElementById('userType');
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            const isStore = this.value === 'loja';
            isStoreUser = isStore;
            
            if (isStore) {
                // Mostrar interface específica para lojas
                document.getElementById('emailSelectContainer').style.display = 'block';
                document.getElementById('userEmail').style.display = 'none';
                document.getElementById('storeDataFields').style.display = 'block';
                
                // Tornar campo de email não obrigatório quando usando select
                document.getElementById('userEmail').required = false;
                
                // Carregar lojas se ainda não foram carregadas
                if (availableStores.length === 0) {
                    loadAvailableStores();
                }
            } else {
                // Mostrar interface normal para outros tipos de usuário
                resetStoreFields();
            }
        });
    }
    
    // Event listener para mudança na seleção de loja
    const emailSelect = document.getElementById('userEmailSelect');
    if (emailSelect) {
        emailSelect.addEventListener('change', function() {
            const selectedEmail = this.value;
            
            if (selectedEmail) {
                // Buscar dados detalhados da loja selecionada
                fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_store_by_email&email=' + encodeURIComponent(selectedEmail)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.data) {
                        const store = data.data;
                        
                        // Preencher campos automaticamente com dados da loja
                        document.getElementById('userEmail').value = store.email;
                        document.getElementById('userName').value = store.nome_fantasia;
                        document.getElementById('userPhone').value = store.telefone || '';
                        
                        // Preencher campos informativos (somente leitura)
                        document.getElementById('storeName').value = store.nome_fantasia;
                        document.getElementById('storeDocument').value = store.cnpj;
                        document.getElementById('storeCategory').value = store.categoria || 'Não informado';
                        
                        // Tornar campos principais somente leitura para evitar alterações acidentais
                        document.getElementById('userEmail').readOnly = true;
                        document.getElementById('userName').readOnly = true;
                        document.getElementById('userPhone').readOnly = true;
                    } else {
                        showMessage(data.message || 'Erro ao carregar dados da loja', 'danger');
                        // Resetar campos em caso de erro
                        document.getElementById('userEmail').value = '';
                        document.getElementById('userName').value = '';
                        document.getElementById('userPhone').value = '';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showMessage('Erro ao carregar dados da loja: ' + error.message, 'danger');
                });
            } else {
                // Limpar campos se nenhuma loja for selecionada
                document.getElementById('userEmail').value = '';
                document.getElementById('userName').value = '';
                document.getElementById('userPhone').value = '';
                document.getElementById('storeName').value = '';
                document.getElementById('storeDocument').value = '';
                document.getElementById('storeCategory').value = '';
                
                // Reabilitar edição dos campos
                document.getElementById('userEmail').readOnly = false;
                document.getElementById('userName').readOnly = false;
                document.getElementById('userPhone').readOnly = false;
            }
        });
    }
});
</script>
</body>
</html>