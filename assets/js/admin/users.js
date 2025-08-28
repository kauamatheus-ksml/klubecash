// assets/js/admin/users_new.js - Sistema Profissional de Gerenciamento de Usuários
// Compatível com a nova interface users_new.php

// Variáveis globais
let currentUserId = null;
let selectedUsers = [];
let availableStores = [];
let isStoreUser = false;
let isEditMode = false;
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};
let visibleColumns = new Set(['select', 'id', 'nome', 'email', 'tipo', 'status', 'data_criacao', 'acoes']);

// Toast notification system
let toastContainer = null;

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeUserManagement();
    createToastContainer();
});

/**
 * Inicializa o sistema de gerenciamento de usuários
 */
function initializeUserManagement() {
    // Event listeners para filtros
    setupFilterListeners();
    
    // Event listeners para formulários
    setupFormListeners();
    
    // Event listeners para modais
    setupModalListeners();
    
    // Event listeners para tabela
    setupTableListeners();
    
    // Event listeners para paginação
    setupPaginationListeners();
    
    // Event listeners para ações em massa
    setupBulkActionListeners();
    
    // Configurar controles de coluna
    setupColumnControls();
    
    // Carregar lojas disponíveis
    loadAvailableStores();
    
    // Configurar máscaras de input
    setupInputMasks();
    
    // Configurar validação de senha
    setupPasswordValidation();
    
    // Carregar estatísticas
    loadStatistics();
    
    // Configurar filtros avançados
    setupAdvancedFilters();
}

/**
 * Configura os event listeners para filtros
 */
function setupFilterListeners() {
    const basicSearch = document.getElementById('basicSearch');
    const tipoFilter = document.getElementById('filterTipo');
    const statusFilter = document.getElementById('filterStatus');
    const advancedToggle = document.getElementById('toggleAdvanced');
    
    // Busca básica
    if (basicSearch) {
        let searchTimeout;
        basicSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = this.value.trim();
                applyFilters();
            }, 500);
        });
    }
    
    // Filtros básicos
    if (tipoFilter) {
        tipoFilter.addEventListener('change', function() {
            currentFilters.tipo = this.value;
            applyFilters();
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            currentFilters.status = this.value;
            applyFilters();
        });
    }
    
    // Toggle filtros avançados
    if (advancedToggle) {
        advancedToggle.addEventListener('click', toggleAdvancedFilters);
    }
    
    // Filtros avançados
    setupAdvancedFilterListeners();
}

/**
 * Configura event listeners avançados para filtros
 */
function setupAdvancedFilterListeners() {
    const dataInicioInput = document.getElementById('filterDataInicio');
    const dataFimInput = document.getElementById('filterDataFim');
    const emailInput = document.getElementById('filterEmail');
    const telefoneInput = document.getElementById('filterTelefone');
    const mvpFilter = document.getElementById('filterMvp');
    const clearFiltersBtn = document.getElementById('clearFilters');
    
    [dataInicioInput, dataFimInput, emailInput, telefoneInput, mvpFilter].forEach(input => {
        if (input) {
            input.addEventListener('change', function() {
                const filterName = this.id.replace('filter', '').toLowerCase();
                if (filterName === 'datainicio') {
                    currentFilters.data_inicio = this.value;
                } else if (filterName === 'datafim') {
                    currentFilters.data_fim = this.value;
                } else {
                    currentFilters[filterName] = this.value;
                }
                applyFilters();
            });
        }
    });
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }
}

/**
 * Configura os event listeners para formulários
 */
function setupFormListeners() {
    const userTypeSelect = document.getElementById('userType');
    const emailSelect = document.getElementById('userEmailSelect');
    const userForm = document.getElementById('userForm');
    
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            handleUserTypeChange(this.value);
        });
    }
    
    if (emailSelect) {
        emailSelect.addEventListener('change', function() {
            handleStoreEmailChange(this.value);
        });
    }
    
    if (userForm) {
        userForm.addEventListener('submit', submitUserForm);
    }
}

/**
 * Configura os event listeners para tabela
 */
function setupTableListeners() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', toggleSelectAll);
    }
    
    // Event delegation para checkboxes de usuários
    document.addEventListener('change', function(event) {
        if (event.target.classList.contains('user-checkbox')) {
            const userId = parseInt(event.target.value);
            toggleUserSelection(event.target, userId);
        }
    });
}

/**
 * Configura os event listeners para paginação
 */
function setupPaginationListeners() {
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function() {
            currentPage = 1; // Reset para primeira página
            loadUsers();
        });
    }
    
    const pageJumpInput = document.getElementById('pageJump');
    if (pageJumpInput) {
        pageJumpInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                jumpToPage();
            }
        });
    }
}

/**
 * Configura os event listeners para ações em massa
 */
function setupBulkActionListeners() {
    const bulkActivateBtn = document.getElementById('bulkActivate');
    const bulkDeactivateBtn = document.getElementById('bulkDeactivate');
    const bulkBlockBtn = document.getElementById('bulkBlock');
    const cancelBulkBtn = document.getElementById('cancelBulk');
    
    if (bulkActivateBtn) {
        bulkActivateBtn.addEventListener('click', () => bulkAction('ativo'));
    }
    
    if (bulkDeactivateBtn) {
        bulkDeactivateBtn.addEventListener('click', () => bulkAction('inativo'));
    }
    
    if (bulkBlockBtn) {
        bulkBlockBtn.addEventListener('click', () => bulkAction('bloqueado'));
    }
    
    if (cancelBulkBtn) {
        cancelBulkBtn.addEventListener('click', clearSelection);
    }
}

/**
 * Configura controles de visibilidade de colunas
 */
function setupColumnControls() {
    const columnToggles = document.querySelectorAll('.column-toggle');
    columnToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const columnName = this.dataset.column;
            toggleColumnVisibility(columnName, this.checked);
        });
    });
}

/**
 * Configura os event listeners para modais
 */
function setupModalListeners() {
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            hideUserModal();
            hideViewUserModal();
        }
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideUserModal();
            hideViewUserModal();
        }
    });
    
    // Event listeners para abas do modal
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });
}

/**
 * Configura máscaras de input
 */
function setupInputMasks() {
    const phoneInput = document.getElementById('userPhone');
    const cpfInput = document.getElementById('userCpf');
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }
    
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }
}

/**
 * Configura validação de senha
 */
function setupPasswordValidation() {
    const passwordInput = document.getElementById('userPassword');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    if (passwordInput && strengthIndicator) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value, strengthIndicator);
        });
    }
}

/**
 * Atualiza indicador de força da senha
 */
function updatePasswordStrength(password, indicator) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) {
        strength += 1;
    } else {
        feedback.push('Mínimo 8 caracteres');
    }
    
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Uma letra maiúscula');
    }
    
    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Uma letra minúscula');
    }
    
    if (/\d/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Um número');
    }
    
    if (/[^\w\s]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Um caractere especial');
    }
    
    const strengthClasses = ['weak', 'fair', 'good', 'strong', 'very-strong'];
    const strengthTexts = ['Muito fraca', 'Fraca', 'Regular', 'Forte', 'Muito forte'];
    
    indicator.className = 'password-strength ' + strengthClasses[strength - 1];
    indicator.textContent = password ? strengthTexts[strength - 1] : '';
    
    if (feedback.length > 0 && password) {
        indicator.title = 'Faltam: ' + feedback.join(', ');
    } else {
        indicator.title = '';
    }
}

/**
 * Carrega estatísticas do dashboard
 */
function loadStatistics() {
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=getUserStatistics'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status && data.data) {
            updateStatistics(data.data);
        }
    })
    .catch(error => {
        console.error('Erro ao carregar estatísticas:', error);
    });
}

/**
 * Atualiza os cards de estatísticas
 */
function updateStatistics(stats) {
    const elements = {
        totalUsers: document.getElementById('totalUsers'),
        activeUsers: document.getElementById('activeUsers'),
        storeUsers: document.getElementById('storeUsers'),
        newUsers: document.getElementById('newUsers'),
        mvpStores: document.getElementById('mvpStores'),
        blockedUsers: document.getElementById('blockedUsers')
    };
    
    Object.keys(elements).forEach(key => {
        if (elements[key] && stats[key] !== undefined) {
            elements[key].textContent = stats[key];
        }
    });
}

/**
 * Configura filtros avançados
 */
function setupAdvancedFilters() {
    const advancedSection = document.getElementById('advancedFilters');
    if (advancedSection) {
        advancedSection.style.display = 'none';
    }
}

/**
 * Alterna exibição dos filtros avançados
 */
function toggleAdvancedFilters() {
    const advancedSection = document.getElementById('advancedFilters');
    const toggleBtn = document.getElementById('toggleAdvanced');
    
    if (advancedSection && toggleBtn) {
        const isVisible = advancedSection.style.display !== 'none';
        advancedSection.style.display = isVisible ? 'none' : 'block';
        toggleBtn.innerHTML = isVisible ? 
            '<i class="fas fa-chevron-down"></i> Mostrar Filtros Avançados' :
            '<i class="fas fa-chevron-up"></i> Ocultar Filtros Avançados';
    }
}

/**
 * Aplica filtros à listagem
 */
function applyFilters() {
    currentPage = 1; // Reset para primeira página
    loadUsers();
}

/**
 * Limpa todos os filtros
 */
function clearAllFilters() {
    currentFilters = {};
    
    // Limpar inputs
    const inputs = [
        'basicSearch', 'filterTipo', 'filterStatus', 'filterDataInicio',
        'filterDataFim', 'filterEmail', 'filterTelefone', 'filterMvp'
    ];
    
    inputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.value = '';
        }
    });
    
    // Recarregar dados
    applyFilters();
    showToast('Filtros limpos com sucesso!', 'success');
}

/**
 * Carrega lista de usuários com filtros e paginação
 */
function loadUsers() {
    showLoadingOverlay();
    
    const params = new URLSearchParams({
        action: 'getUsers',
        page: currentPage,
        limit: document.getElementById('itemsPerPage')?.value || 25,
        ...currentFilters
    });
    
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingOverlay();
        
        if (data.status) {
            updateUsersTable(data.data.users);
            updatePagination(data.data.pagination);
        } else {
            showToast(data.message || 'Erro ao carregar usuários', 'error');
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Erro:', error);
        showToast('Erro ao carregar usuários: ' + error.message, 'error');
    });
}

/**
 * Atualiza tabela de usuários
 */
function updateUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i><br>
                    <span class="text-muted">Nenhum usuário encontrado com os filtros aplicados</span>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map(user => createUserRow(user)).join('');
}

/**
 * Cria linha da tabela para um usuário
 */
function createUserRow(user) {
    const statusClass = {
        'ativo': 'success',
        'inativo': 'warning',
        'bloqueado': 'danger'
    };
    
    const typeClass = {
        'admin': 'primary',
        'loja': 'info',
        'cliente': 'secondary',
        'funcionario': 'dark'
    };
    
    return `
        <tr data-user-id="${user.id}">
            <td class="column-select">
                <input type="checkbox" class="user-checkbox" value="${user.id}">
            </td>
            <td class="column-id">#${user.id}</td>
            <td class="column-nome">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <strong>${user.nome}</strong>
                        ${user.tipo === 'loja' && user.mvp === 'sim' ? '<span class="badge badge-gold ms-1">MVP</span>' : ''}
                    </div>
                </div>
            </td>
            <td class="column-email">${user.email}</td>
            <td class="column-tipo">
                <span class="badge badge-${typeClass[user.tipo] || 'secondary'}">
                    ${user.tipo.charAt(0).toUpperCase() + user.tipo.slice(1)}
                </span>
            </td>
            <td class="column-status">
                <span class="badge badge-${statusClass[user.status] || 'secondary'}">
                    ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                </span>
            </td>
            <td class="column-data_criacao">
                ${new Date(user.data_criacao).toLocaleDateString('pt-BR')}
            </td>
            <td class="column-acoes">
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewUser(${user.id})" title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="editUser(${user.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" title="Status">
                            <i class="fas fa-cog"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><button class="dropdown-item" onclick="changeUserStatus(${user.id}, 'ativo', '${user.nome}')">
                                <i class="fas fa-check-circle text-success me-2"></i>Ativar
                            </button></li>
                            <li><button class="dropdown-item" onclick="changeUserStatus(${user.id}, 'inativo', '${user.nome}')">
                                <i class="fas fa-pause-circle text-warning me-2"></i>Desativar
                            </button></li>
                            <li><button class="dropdown-item" onclick="changeUserStatus(${user.id}, 'bloqueado', '${user.nome}')">
                                <i class="fas fa-ban text-danger me-2"></i>Bloquear
                            </button></li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Atualiza controles de paginação
 */
function updatePagination(pagination) {
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;
    
    const paginationContainer = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    const pageJumpInput = document.getElementById('pageJump');
    
    if (pageInfo) {
        pageInfo.textContent = `Página ${currentPage} de ${totalPages} (${pagination.total_records} registros)`;
    }
    
    if (pageJumpInput) {
        pageJumpInput.max = totalPages;
        pageJumpInput.value = currentPage;
    }
    
    if (paginationContainer) {
        paginationContainer.innerHTML = createPaginationHTML(pagination);
    }
}

/**
 * Cria HTML da paginação
 */
function createPaginationHTML(pagination) {
    const { current_page, total_pages, has_prev, has_next } = pagination;
    let html = '';
    
    // Botão anterior
    html += `
        <button class="btn btn-outline-primary" ${!has_prev ? 'disabled' : ''} 
                onclick="changePage(${current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>
    `;
    
    // Números das páginas
    const start = Math.max(1, current_page - 2);
    const end = Math.min(total_pages, current_page + 2);
    
    if (start > 1) {
        html += `<button class="btn btn-outline-primary" onclick="changePage(1)">1</button>`;
        if (start > 2) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    for (let i = start; i <= end; i++) {
        const isActive = i === current_page;
        html += `
            <button class="btn ${isActive ? 'btn-primary' : 'btn-outline-primary'}" 
                    onclick="changePage(${i})" ${isActive ? 'disabled' : ''}>
                ${i}
            </button>
        `;
    }
    
    if (end < total_pages) {
        if (end < total_pages - 1) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        html += `<button class="btn btn-outline-primary" onclick="changePage(${total_pages})">${total_pages}</button>`;
    }
    
    // Botão próximo
    html += `
        <button class="btn btn-outline-primary" ${!has_next ? 'disabled' : ''} 
                onclick="changePage(${current_page + 1})">
            Próximo <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    return html;
}

/**
 * Muda para uma página específica
 */
function changePage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadUsers();
    }
}

/**
 * Pula para página digitada
 */
function jumpToPage() {
    const input = document.getElementById('pageJump');
    if (input) {
        const page = parseInt(input.value);
        if (page >= 1 && page <= totalPages) {
            changePage(page);
        } else {
            input.value = currentPage;
            showToast('Página inválida', 'error');
        }
    }
}

/**
 * Sistema de Toast Notifications
 */
function createToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(toastContainer);
    }
}

/**
 * Exibe toast notification
 */
function showToast(message, type = 'success', duration = 5000) {
    const toast = document.createElement('div');
    const iconMap = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.className = `toast toast-${type} show`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas ${iconMap[type]} me-2"></i>
            <strong class="me-auto">
                ${type === 'success' ? 'Sucesso' : 
                  type === 'error' ? 'Erro' : 
                  type === 'warning' ? 'Atenção' : 'Informação'}
            </strong>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, duration);
}

/**
 * Exibe mensagem para o usuário (mantido para compatibilidade)
 */
function showMessage(message, type = 'success') {
    showToast(message, type);
}

/**
 * Exibe loading overlay
 */
function showLoading() {
    showLoadingOverlay();
}

/**
 * Esconde loading overlay
 */
function hideLoading() {
    hideLoadingOverlay();
}

/**
 * Exibe loading overlay (nova implementação)
 */
function showLoadingOverlay() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <div class="mt-2">Carregando...</div>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

/**
 * Esconde loading overlay (nova implementação)
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Carrega lojas disponíveis para vinculação
 */
function loadAvailableStores() {
    fetch('/controllers/AdminController.php', {
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

/**
 * Popula o select de lojas
 */
function populateStoreSelect() {
    const select = document.getElementById('userEmailSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="">Selecione uma loja...</option>';
    
    availableStores.forEach(store => {
        const option = document.createElement('option');
        option.value = store.email;
        option.textContent = `${store.nome_fantasia} (${store.email})`;
        option.dataset.storeData = JSON.stringify(store);
        select.appendChild(option);
    });
}

/**
 * Manipula mudança no tipo de usuário
 */
function handleUserTypeChange(type) {
    const isStore = type === 'loja';
    isStoreUser = isStore;
    
    const emailContainer = document.getElementById('emailSelectContainer');
    const emailInput = document.getElementById('userEmail');
    const storeFields = document.getElementById('storeDataFields');
    const mvpFieldGroup = document.getElementById('mvpFieldGroup');
    
    // Mostrar/ocultar campo MVP apenas para lojas
    if (mvpFieldGroup) {
        mvpFieldGroup.style.display = isStore ? 'block' : 'none';
    }
    
    if (isStore && !isEditMode) {
        // Mostrar seleção de loja (novo usuário)
        if (emailContainer) emailContainer.style.display = 'block';
        if (emailInput) emailInput.style.display = 'none';
        if (storeFields) storeFields.style.display = 'block';
        
        if (emailInput) emailInput.required = false;
        
        if (availableStores.length === 0) {
            loadAvailableStores();
        }
    } else if (isStore && isEditMode) {
        // Para edição de loja, manter campos normais mas mostrar MVP
        if (emailContainer) emailContainer.style.display = 'none';
        if (emailInput) {
            emailInput.style.display = 'block';
            emailInput.required = true;
            emailInput.readOnly = false;
        }
        if (storeFields) storeFields.style.display = 'none';
    } else {
        // Mostrar input normal para outros tipos
        resetStoreFields();
    }
}

/**
 * Manipula mudança na seleção de loja
 */
function handleStoreEmailChange(email) {
    if (!email) {
        clearStoreFields();
        return;
    }
    
    // Buscar dados da loja selecionada
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_store_by_email&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status && data.data) {
            fillStoreFields(data.data);
        } else {
            showMessage(data.message || 'Erro ao carregar dados da loja', 'error');
            clearStoreFields();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showMessage('Erro ao carregar dados da loja: ' + error.message, 'error');
    });
}

/**
 * Preenche os campos com dados da loja
 */
function fillStoreFields(store) {
    const emailInput = document.getElementById('userEmail');
    const nameInput = document.getElementById('userName');
    const phoneInput = document.getElementById('userPhone');
    const storeNameInput = document.getElementById('storeName');
    const storeDocumentInput = document.getElementById('storeDocument');
    const storeCategoryInput = document.getElementById('storeCategory');
    
    if (emailInput) emailInput.value = store.email;
    if (nameInput) nameInput.value = store.nome_fantasia;
    if (phoneInput) phoneInput.value = store.telefone || '';
    if (storeNameInput) storeNameInput.value = store.nome_fantasia;
    if (storeDocumentInput) storeDocumentInput.value = store.cnpj;
    if (storeCategoryInput) storeCategoryInput.value = store.categoria || 'Não informado';
    
    // Tornar campos principais read-only
    if (emailInput) emailInput.readOnly = true;
    if (nameInput) nameInput.readOnly = true;
    if (phoneInput) phoneInput.readOnly = true;
}

/**
 * Limpa os campos de loja
 */
function clearStoreFields() {
    const emailInput = document.getElementById('userEmail');
    const nameInput = document.getElementById('userName');
    const phoneInput = document.getElementById('userPhone');
    const storeNameInput = document.getElementById('storeName');
    const storeDocumentInput = document.getElementById('storeDocument');
    const storeCategoryInput = document.getElementById('storeCategory');
    const mvpInput = document.getElementById('userMvp');
    
    if (emailInput) emailInput.value = '';
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (mvpInput) mvpInput.value = 'nao';
    if (storeNameInput) storeNameInput.value = '';
    if (storeDocumentInput) storeDocumentInput.value = '';
    if (storeCategoryInput) storeCategoryInput.value = '';
    
    // Reabilitar edição
    if (emailInput) emailInput.readOnly = false;
    if (nameInput) nameInput.readOnly = false;
    if (phoneInput) phoneInput.readOnly = false;
}

/**
 * Reseta campos relacionados a loja
 */
function resetStoreFields() {
    const emailContainer = document.getElementById('emailSelectContainer');
    const emailInput = document.getElementById('userEmail');
    const storeFields = document.getElementById('storeDataFields');
    const mvpFieldGroup = document.getElementById('mvpFieldGroup');
    
    if (emailContainer) emailContainer.style.display = 'none';
    if (emailInput) {
        emailInput.style.display = 'block';
        emailInput.required = true;
        emailInput.readOnly = false;
    }
    if (storeFields) storeFields.style.display = 'none';
    if (mvpFieldGroup) mvpFieldGroup.style.display = 'none';
    
    clearStoreFields();
    isStoreUser = false;
}

/**
 * Exibe modal de adicionar usuário
 */
function showUserModal() {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('userModalTitle');
    const form = document.getElementById('userForm');
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordField = document.getElementById('userPassword');
    const passwordHelp = document.getElementById('passwordHelp');
    
    if (!modal) return;
    
    // Configurar modal para criação
    if (title) title.innerHTML = '<i class="fas fa-user-plus"></i> Adicionar Usuário';
    if (form) form.reset();
    document.getElementById('userId').value = '';
    currentUserId = null;
    isEditMode = false;
    
    // Configurar campo de senha
    if (passwordGroup) passwordGroup.style.display = 'block';
    if (passwordField) passwordField.required = true;
    if (passwordHelp) passwordHelp.textContent = 'Mínimo de 8 caracteres';
    
    // Resetar campos de loja
    resetStoreFields();
    
    // Mostrar modal
    modal.classList.add('show');
    
    // Focar no primeiro campo
    const firstInput = modal.querySelector('input, select');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
}

/**
 * Esconde modal de usuário
 */
function hideUserModal() {
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Edita usuário
 */
function editUser(userId) {
    if (!userId) return;
    
    // IMPORTANTE: Definir isEditMode logo no início
    isEditMode = true;
    currentUserId = userId;
    
    const modal = document.getElementById('userModal');
    const title = document.getElementById('userModalTitle');
    const form = document.getElementById('userForm');
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordField = document.getElementById('userPassword');
    const passwordHelp = document.getElementById('passwordHelp');
    
    if (!modal) return;
    
    // Configurar modal para edição
    if (title) title.innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuário';
    if (form) form.reset();
    
    // Configurar campo de senha (opcional na edição)
    if (passwordGroup) passwordGroup.style.display = 'block';
    if (passwordField) passwordField.required = false;
    if (passwordHelp) passwordHelp.textContent = 'Mínimo de 8 caracteres (deixe em branco para manter a senha atual)';
    
    // Mostrar modal
    modal.classList.add('show');
    
    // Carregar dados do usuário
    showLoading();
    
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=getUserDetails&user_id=${userId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.status && data.data && data.data.usuario) {
            fillUserForm(data.data.usuario);
        } else {
            hideUserModal();
            showMessage(data.message || 'Erro ao carregar dados do usuário', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erro:', error);
        hideUserModal();
        showMessage('Erro ao carregar dados do usuário: ' + error.message, 'error');
    });
}

/**
 * Preenche o formulário com dados do usuário
 */
function fillUserForm(userData) {
    document.getElementById('userId').value = userData.id;
    document.getElementById('userName').value = userData.nome;
    document.getElementById('userEmail').value = userData.email;
    document.getElementById('userType').value = userData.tipo;
    document.getElementById('userStatus').value = userData.status;
    
    if (userData.telefone) {
        document.getElementById('userPhone').value = userData.telefone;
    }
    
    // Campo MVP (apenas para lojas)
    const mvpSelect = document.getElementById('userMvp');
    if (mvpSelect && userData.tipo === 'loja') {
        mvpSelect.value = userData.mvp || 'nao';
    }
    
    // Importante: definir isEditMode antes de chamar handleUserTypeChange
    isEditMode = true;
    
    // Mostrar/ocultar campo MVP baseado no tipo
    handleUserTypeChange(userData.tipo);
    
    // Limpar campo de senha
    document.getElementById('userPassword').value = '';
}

/**
 * Visualiza detalhes do usuário
 */
function viewUser(userId) {
    if (!userId) return;
    
    const modal = document.getElementById('viewUserModal');
    const content = document.getElementById('userViewContent');
    
    if (!modal || !content) return;
    
    modal.classList.add('show');
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
    
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=getUserDetails&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status && data.data && data.data.usuario) {
            displayUserDetails(data.data.usuario);
        } else {
            content.innerHTML = '<div class="alert alert-danger">Erro ao carregar dados do usuário</div>';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        content.innerHTML = '<div class="alert alert-danger">Erro ao carregar dados do usuário</div>';
    });
}

/**
 * Exibe detalhes do usuário
 */
function displayUserDetails(userData) {
    const content = document.getElementById('userViewContent');
    if (!content) return;
    
    const tipoLabels = {
        'cliente': 'Cliente',
        'loja': 'Loja',
        'admin': 'Administrador'
    };
    
    const statusLabels = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'bloqueado': 'Bloqueado'
    };
    
    const statusClass = {
        'ativo': 'badge-success',
        'inativo': 'badge-warning',
        'bloqueado': 'badge-danger'
    };
    
    content.innerHTML = `
        <div class="user-details">
            <div class="user-detail-header">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-basic-info">
                    <h4>${userData.nome}</h4>
                    <p>${userData.email}</p>
                </div>
            </div>
            
            <div class="user-detail-grid">
                <div class="detail-item">
                    <label>Tipo:</label>
                    <span class="type-badge type-${userData.tipo}">
                        ${tipoLabels[userData.tipo] || userData.tipo}
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Status:</label>
                    <span class="badge ${statusClass[userData.status] || 'badge-secondary'}">
                        ${statusLabels[userData.status] || userData.status}
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Telefone:</label>
                    <span>${userData.telefone || 'Não informado'}</span>
                </div>
                
                <div class="detail-item">
                    <label>Data de Cadastro:</label>
                    <span>${new Date(userData.data_criacao).toLocaleString('pt-BR')}</span>
                </div>
                
                <div class="detail-item">
                    <label>Último Login:</label>
                    <span>${userData.ultimo_login ? new Date(userData.ultimo_login).toLocaleString('pt-BR') : 'Nunca'}</span>
                </div>
            </div>
        </div>
    `;
}

/**
 * Esconde modal de visualização
 */
function hideViewUserModal() {
    const modal = document.getElementById('viewUserModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Altera status do usuário
 */
function changeUserStatus(userId, newStatus, userName) {
    if (!userId || !newStatus) return;
    
    const actionText = newStatus === 'ativo' ? 'ativar' : 
                      newStatus === 'inativo' ? 'desativar' : 'bloquear';
    
    if (!confirm(`Tem certeza que deseja ${actionText} o usuário "${userName}"?`)) {
        return;
    }
    
    showLoading();
    
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_user_status&user_id=${userId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.status) {
            showMessage(`Usuário ${actionText} com sucesso!`);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showMessage(data.message || `Erro ao ${actionText} usuário`, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erro:', error);
        showMessage(`Erro ao processar a solicitação: ${error.message}`, 'error');
    });
}

/**
 * Submete formulário de usuário
 */
function submitUserForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!form || !validateForm(form)) {
        return;
    }
    
    const formData = new FormData(form);
    const userId = formData.get('id');
    const isEditing = userId !== '';
    
    // Validação específica para senha
    const senha = formData.get('senha');
    if (!isEditing && (!senha || senha.trim() === '')) {
        showMessage('Senha é obrigatória para criar um novo usuário', 'error');
        document.getElementById('userPassword').focus();
        return;
    }
    
    if (senha && senha.trim() !== '' && senha.length < 8) {
        showMessage('A senha deve ter no mínimo 8 caracteres', 'error');
        document.getElementById('userPassword').focus();
        return;
    }
    
    // Se for usuário do tipo loja, usar email selecionado
    if (isStoreUser && !isEditing) {
        const selectedEmail = document.getElementById('userEmailSelect').value;
        if (selectedEmail) {
            formData.set('email', selectedEmail);
        } else {
            showMessage('Por favor, selecione uma loja antes de continuar.', 'error');
            return;
        }
    }
    
    // Desabilitar botão e mostrar loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    }
    
    // Converter FormData para URLSearchParams
    const data = new URLSearchParams();
    
    if (isEditing) {
        data.append('action', 'update_user');
        data.append('user_id', userId);
    } else {
        data.append('action', 'register');
        data.append('ajax', '1');
    }
    
    // Adicionar dados do formulário
    for (let [key, value] of formData.entries()) {
        if (key !== 'id') {
            data.append(key, value);
        }
    }
    
    const url = isEditing ? '/controllers/AdminController.php' : '/controllers/AuthController.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Resposta inválida:", text);
            throw new Error("Resposta do servidor não é JSON válido");
        }
        return data;
    })
    .then(data => {
        if (data.status) {
            hideUserModal();
            let message = isEditing ? 'Usuário atualizado com sucesso!' : 'Usuário adicionado com sucesso!';
            
            if (data.store_linked) {
                message += ' A loja foi vinculada automaticamente ao usuário.';
            }
            
            showMessage(message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showMessage(data.message || 'Erro ao processar solicitação', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showMessage('Erro ao processar a solicitação: ' + error.message, 'error');
    })
    .finally(() => {
        // Reabilitar botão
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar';
        }
    });
}

/**
 * Valida formulário
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    // Validação específica de email
    const emailField = document.getElementById('userEmail');
    if (emailField && emailField.value && !isValidEmail(emailField.value)) {
        emailField.classList.add('error');
        showMessage('Por favor, insira um email válido', 'error');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Valida email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Alterna visibilidade da senha
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleBtn = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        field.type = 'password';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

/**
 * Seleciona/deseleciona todos os usuários
 */
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

/**
 * Alterna seleção de usuário individual
 */
function toggleUserSelection(checkbox, userId) {
    if (checkbox.checked) {
        if (!selectedUsers.includes(userId)) {
            selectedUsers.push(userId);
        }
    } else {
        selectedUsers = selectedUsers.filter(id => id !== userId);
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
    }
    
    updateBulkActionBar();
}

/**
 * Atualiza barra de ações em massa
 */
function updateBulkActionBar() {
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (!bulkActionBar || !selectedCount) return;
    
    selectedCount.textContent = selectedUsers.length;
    
    if (selectedUsers.length > 0) {
        bulkActionBar.style.display = 'flex';
    } else {
        bulkActionBar.style.display = 'none';
    }
}

/**
 * Executa ação em massa
 */
function bulkAction(status) {
    if (selectedUsers.length === 0) return;
    
    const actionText = status === 'ativo' ? 'ativar' : 
                      status === 'inativo' ? 'desativar' : 'bloquear';
    
    if (!confirm(`Tem certeza que deseja ${actionText} ${selectedUsers.length} usuários selecionados?`)) {
        return;
    }
    
    const bulkActionBar = document.getElementById('bulkActionBar');
    if (bulkActionBar) {
        bulkActionBar.innerHTML = `<div class="bulk-info">Processando ${selectedUsers.length} usuários...</div>`;
    }
    
    let processed = 0;
    let successful = 0;
    
    const processUser = (userId) => {
        return fetch('/controllers/AdminController.php', {
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
                const successMessage = `${successful} usuários foram ${actionText === 'ativar' ? 'ativados' : actionText === 'desativar' ? 'desativados' : 'bloqueados'} com sucesso!`;
                showMessage(successMessage);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        })
        .catch(() => {
            processed++;
            if (processed === selectedUsers.length) {
                const successMessage = `${successful} usuários foram processados com sucesso!`;
                showMessage(successMessage);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        });
    };
    
    // Processar todos os usuários selecionados
    selectedUsers.forEach(processUser);
}

/**
 * Alterna visibilidade de colunas
 */
function toggleColumnVisibility(columnName, visible) {
    const columns = document.querySelectorAll(`.column-${columnName}`);
    columns.forEach(column => {
        column.style.display = visible ? '' : 'none';
    });
    
    if (visible) {
        visibleColumns.add(columnName);
    } else {
        visibleColumns.delete(columnName);
    }
    
    // Salvar preferências no localStorage
    localStorage.setItem('userTableColumns', JSON.stringify(Array.from(visibleColumns)));
}

/**
 * Carrega preferências de colunas
 */
function loadColumnPreferences() {
    const saved = localStorage.getItem('userTableColumns');
    if (saved) {
        visibleColumns = new Set(JSON.parse(saved));
        
        // Aplicar preferências
        const allColumns = ['select', 'id', 'nome', 'email', 'tipo', 'status', 'data_criacao', 'acoes'];
        allColumns.forEach(column => {
            const toggle = document.querySelector(`[data-column="${column}"]`);
            if (toggle) {
                toggle.checked = visibleColumns.has(column);
                toggleColumnVisibility(column, visibleColumns.has(column));
            }
        });
    }
}

/**
 * Troca de aba no modal
 */
function switchTab(tabName) {
    // Remover classe ativa de todas as abas
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(btn => btn.classList.remove('active'));
    tabPanes.forEach(pane => pane.classList.remove('active'));
    
    // Ativar aba selecionada
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    const activePane = document.getElementById(tabName);
    
    if (activeButton) activeButton.classList.add('active');
    if (activePane) activePane.classList.add('active');
}

/**
 * Limpa seleção de usuários
 */
function clearSelection() {
    selectedUsers = [];
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (selectAll) {
        selectAll.checked = false;
    }
    
    updateBulkActionBar();
}

/**
 * Exporta usuários para CSV
 */
function exportUsersToCSV() {
    showLoadingOverlay();
    
    const params = new URLSearchParams({
        action: 'exportUsers',
        format: 'csv',
        ...currentFilters
    });
    
    fetch('/controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.blob())
    .then(blob => {
        hideLoadingOverlay();
        
        // Criar link para download
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `usuarios_${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showToast('Usuários exportados com sucesso!', 'success');
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Erro:', error);
        showToast('Erro ao exportar usuários: ' + error.message, 'error');
    });
}

/**
 * Exporta usuários (mantido para compatibilidade)
 */
function exportUsers() {
    exportUsersToCSV();
}

// Event listeners adicionais para inicialização tardia
document.addEventListener('DOMContentLoaded', function() {
    // Carregar preferências de colunas
    loadColumnPreferences();
    
    // Carregar dados iniciais
    if (typeof loadUsers === 'function') {
        loadUsers();
    }
    
    // Configurar atualização automática das estatísticas
    setInterval(loadStatistics, 300000); // A cada 5 minutos
});

// Adicionar estilos CSS avançados
const advancedStyles = document.createElement('style');
advancedStyles.textContent = `
    /* Estilos para Toast Notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
    }
    
    .toast {
        min-width: 300px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
    }
    
    .toast-success {
        background: #d4edda;
        border-left: 4px solid #28a745;
    }
    
    .toast-error {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
    }
    
    .toast-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    
    .toast-info {
        background: #d1ecf1;
        border-left: 4px solid #17a2b8;
    }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9998;
        backdrop-filter: blur(2px);
    }
    
    .loading-spinner {
        text-align: center;
        color: var(--primary-color);
    }
    
    /* Password Strength Indicator */
    .password-strength {
        height: 4px;
        border-radius: 2px;
        margin-top: 5px;
        transition: all 0.3s ease;
    }
    
    .password-strength.weak {
        background: #dc3545;
        width: 20%;
    }
    
    .password-strength.fair {
        background: #fd7e14;
        width: 40%;
    }
    
    .password-strength.good {
        background: #ffc107;
        width: 60%;
    }
    
    .password-strength.strong {
        background: #198754;
        width: 80%;
    }
    
    .password-strength.very-strong {
        background: #0d6efd;
        width: 100%;
    }
    
    /* User Info in Table */
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-light, #e3f2fd);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color, #1976d2);
        font-size: 14px;
    }
    
    /* Badge MVP */
    .badge-gold {
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        color: #8b4513;
        font-weight: bold;
        text-shadow: 0 1px 1px rgba(0,0,0,0.2);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    
    .action-buttons .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    /* Pagination */
    .pagination-ellipsis {
        padding: 6px 12px;
        color: #6c757d;
    }
    
    /* Advanced Filters */
    #advancedFilters {
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
        margin-top: 1rem;
    }
    
    /* Bulk Action Bar */
    .bulk-action-bar {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 15px;
        z-index: 100;
        display: none;
        align-items: center;
        gap: 15px;
    }
    
    .bulk-info {
        font-weight: 500;
        color: #495057;
    }
    
    /* Column Visibility Controls */
    .column-controls {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 15px;
    }
    
    .column-toggle {
        margin-right: 15px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 2px;
        }
        
        .user-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .toast-container {
            left: 10px;
            right: 10px;
            top: 10px;
            max-width: none;
        }
    }
`;
document.head.appendChild(advancedStyles);

// Adicionar estilos CSS para campos com erro
const errorStyles = document.createElement('style');
errorStyles.textContent = `
    .form-control.error,
    .form-select.error {
        border-color: var(--danger-color, #dc3545) !important;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
    }
    
    .user-details {
        max-width: 500px;
    }
    
    .user-detail-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #E9ECEF;
    }
    
    .user-detail-header .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--primary-light, #e3f2fd);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color, #1976d2);
        font-size: 1.5rem;
    }
    
    .user-basic-info h4 {
        margin: 0 0 0.25rem 0;
        color: var(--dark-gray, #343a40);
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .user-basic-info p {
        margin: 0;
        color: var(--medium-gray, #6c757d);
        font-size: 0.875rem;
    }
    
    .user-detail-grid {
        display: grid;
        gap: 1rem;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #F8F9FA;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-item label {
        font-weight: 600;
        color: var(--dark-gray, #343a40);
        margin: 0;
    }
    
    .detail-item span {
        color: var(--medium-gray, #6c757d);
        text-align: right;
    }
`;
document.head.appendChild(errorStyles);