// assets/js/admin.js
// JavaScript para o painel administrativo - focado em usuários

/**
 * Classe para gerenciar usuários no painel administrativo
 */
class AdminUserManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.filters = {
            tipo: '',
            status: '',
            busca: ''
        };
        
        this.init();
    }
    
    /**
     * Inicializa os event listeners e carrega dados iniciais
     */
    init() {
        console.log('Inicializando AdminUserManager...');
        this.setupEventListeners();
        this.loadUsers();
    }
    
    /**
     * Configura todos os event listeners
     */
    setupEventListeners() {
        // Filtros
        const filterForm = document.getElementById('user-filters');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
        
        // Botão de limpar filtros
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
        
        // Paginação
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('pagination-btn')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (page) {
                    this.loadUsers(page);
                }
            }
        });
        
        // Botões de ação (ativar/desativar)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('toggle-user-status')) {
                e.preventDefault();
                this.toggleUserStatus(e.target);
            }
            
            if (e.target.classList.contains('edit-user-btn')) {
                e.preventDefault();
                this.openEditModal(e.target.dataset.userId);
            }
        });
    }
    
    /**
     * Carrega lista de usuários
     */
    async loadUsers(page = 1) {
        try {
            this.showLoading();
            this.currentPage = page;
            
            // Construir URL com filtros e paginação
            const params = new URLSearchParams({
                action: 'list_users',
                page: page,
                ...this.filters
            });
            
            const response = await fetch(`/admin/ajax/users?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.status) {
                this.renderUsers(data.data.usuarios);
                this.renderPagination(data.data.pagination);
                console.log('Usuários carregados com sucesso:', data.data.usuarios.length);
            } else {
                this.showError('Erro ao carregar usuários: ' + data.message);
            }
            
        } catch (error) {
            console.error('Erro ao carregar usuários:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Renderiza a lista de usuários na tabela
     */
    renderUsers(users) {
        const tbody = document.getElementById('users-table-body');
        if (!tbody) {
            console.error('Elemento users-table-body não encontrado');
            return;
        }
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">Nenhum usuário encontrado</div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = users.map(user => this.createUserRow(user)).join('');
    }
    
    /**
     * Cria uma linha da tabela para um usuário
     */
    createUserRow(user) {
        const statusBadge = this.getStatusBadge(user.status);
        const typeBadge = this.getTypeBadge(user.tipo);
        const actionButtons = this.createActionButtons(user);
        
        return `
            <tr id="user-row-${user.id}">
                <td>
                    <div class="fw-bold">${this.escapeHtml(user.nome)}</div>
                    <div class="text-muted small">${this.escapeHtml(user.email)}</div>
                </td>
                <td>${typeBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="small">${user.data_criacao_formatada}</div>
                </td>
                <td>
                    <div class="small">${user.ultimo_login_formatado}</div>
                </td>
                <td>${actionButtons}</td>
            </tr>
        `;
    }
    
    /**
     * Retorna badge HTML para status do usuário
     */
    getStatusBadge(status) {
        const badges = {
            'ativo': '<span class="badge bg-success">Ativo</span>',
            'inativo': '<span class="badge bg-secondary">Inativo</span>',
            'bloqueado': '<span class="badge bg-danger">Bloqueado</span>'
        };
        
        return badges[status] || '<span class="badge bg-secondary">Desconhecido</span>';
    }
    
    /**
     * Retorna badge HTML para tipo do usuário
     */
    getTypeBadge(tipo) {
        const badges = {
            'admin': '<span class="badge bg-primary">Administrador</span>',
            'cliente': '<span class="badge bg-info">Cliente</span>',
            'loja': '<span class="badge bg-warning">Loja</span>'
        };
        
        return badges[tipo] || '<span class="badge bg-secondary">Desconhecido</span>';
    }
    
    /**
     * Cria botões de ação para cada usuário
     */
    createActionButtons(user) {
        const currentUserId = window.currentUserId || null; // ID do usuário logado
        
        // Não permitir ações no próprio usuário
        if (user.id == currentUserId) {
            return '<span class="text-muted small">Você</span>';
        }
        
        const toggleButton = user.status === 'ativo' ? 
            `<button class="btn btn-sm btn-outline-warning toggle-user-status" 
                     data-user-id="${user.id}" 
                     data-current-status="${user.status}"
                     data-user-name="${this.escapeHtml(user.nome)}"
                     title="Desativar usuário">
                <i class="fas fa-user-slash"></i>
             </button>` :
            `<button class="btn btn-sm btn-outline-success toggle-user-status" 
                     data-user-id="${user.id}" 
                     data-current-status="${user.status}"
                     data-user-name="${this.escapeHtml(user.nome)}"
                     title="Ativar usuário">
                <i class="fas fa-user-check"></i>
             </button>`;
        
        return `
            <div class="btn-group" role="group">
                ${toggleButton}
                <button class="btn btn-sm btn-outline-primary edit-user-btn" 
                        data-user-id="${user.id}"
                        title="Editar usuário">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;
    }
    
    /**
     * Alterna status de um usuário (ativar/desativar)
     */
    async toggleUserStatus(button) {
        const userId = parseInt(button.dataset.userId);
        const currentStatus = button.dataset.currentStatus;
        const userName = button.dataset.userName;
        const newStatus = currentStatus === 'ativo' ? 'inativo' : 'ativo';
        const action = newStatus === 'ativo' ? 'ativar' : 'desativar';
        
        // Confirmar ação
        if (!confirm(`Tem certeza que deseja ${action} o usuário "${userName}"?`)) {
            return;
        }
        
        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const response = await fetch('/admin/ajax/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_status',
                    user_id: userId,
                    status: newStatus
                })
            });
            
            const data = await response.json();
            
            if (data.status) {
                this.showSuccess(data.message);
                // Recarregar a linha do usuário específico
                await this.loadUsers(this.currentPage);
            } else {
                this.showError('Erro: ' + data.message);
                button.disabled = false;
                // Restaurar botão
                this.restoreButtonState(button, currentStatus);
            }
            
        } catch (error) {
            console.error('Erro ao alterar status do usuário:', error);
            this.showError('Erro de conexão. Tente novamente.');
            button.disabled = false;
            this.restoreButtonState(button, currentStatus);
        }
    }
    
    /**
     * Restaura estado original do botão em caso de erro
     */
    restoreButtonState(button, status) {
        if (status === 'ativo') {
            button.innerHTML = '<i class="fas fa-user-slash"></i>';
            button.className = 'btn btn-sm btn-outline-warning toggle-user-status';
        } else {
            button.innerHTML = '<i class="fas fa-user-check"></i>';
            button.className = 'btn btn-sm btn-outline-success toggle-user-status';
        }
    }
    
    /**
     * Aplica filtros da busca
     */
    applyFilters() {
        const form = document.getElementById('user-filters');
        if (!form) return;
        
        const formData = new FormData(form);
        this.filters = {
            tipo: formData.get('tipo') || '',
            status: formData.get('status') || '',
            busca: formData.get('busca') || ''
        };
        
        console.log('Aplicando filtros:', this.filters);
        this.loadUsers(1); // Voltar para primeira página
    }
    
    /**
     * Limpa todos os filtros
     */
    clearFilters() {
        this.filters = { tipo: '', status: '', busca: '' };
        
        // Limpar campos do formulário
        const form = document.getElementById('user-filters');
        if (form) {
            form.reset();
        }
        
        this.loadUsers(1);
    }
    
    /**
     * Renderiza controles de paginação
     */
    renderPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (!container) return;
        
        this.totalPages = pagination.total_pages;
        
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let paginationHtml = '<nav><ul class="pagination justify-content-center">';
        
        // Botão anterior
        const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
        paginationHtml += `
            <li class="page-item ${prevDisabled}">
                <a class="page-link pagination-btn" data-page="${pagination.current_page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Páginas numeradas
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            paginationHtml += `
                <li class="page-item ${activeClass}">
                    <a class="page-link pagination-btn" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Botão próxima
        const nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
        paginationHtml += `
            <li class="page-item ${nextDisabled}">
                <a class="page-link pagination-btn" data-page="${pagination.current_page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationHtml += '</ul></nav>';
        
        // Adicionar informações de registros
        paginationHtml += `
            <div class="text-center mt-2 text-muted small">
                Página ${pagination.current_page} de ${pagination.total_pages} 
                (${pagination.total_records} registros no total)
            </div>
        `;
        
        container.innerHTML = paginationHtml;
    }
    
    /**
     * Mostra indicador de carregamento
     */
    showLoading() {
        const tbody = document.getElementById('users-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <div class="mt-2">Carregando usuários...</div>
                    </td>
                </tr>
            `;
        }
    }
    
    /**
     * Esconde indicador de carregamento
     */
    hideLoading() {
        // O carregamento é escondido quando os dados são renderizados
    }
    
    /**
     * Exibe mensagem de sucesso
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    /**
     * Exibe mensagem de erro
     */
    showError(message) {
        this.showToast(message, 'error');
    }
    
    /**
     * Exibe toast notification
     */
    showToast(message, type = 'info') {
        // Criar container de toasts se não existir
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toastClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';
        
        const toastHtml = `
            <div id="${toastId}" class="toast ${toastClass} text-white" role="alert">
                <div class="toast-header ${toastClass} text-white border-0">
                    <strong class="me-auto">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'error' ? 'exclamation-circle' : 
                                          'info-circle'}"></i>
                        ${type === 'success' ? 'Sucesso' : 
                          type === 'error' ? 'Erro' : 'Informação'}
                    </strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${this.escapeHtml(message)}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('afterbegin', toastHtml);
        
        // Mostrar toast e auto-remover
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Remover do DOM após ser escondido
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    /**
     * Escapa caracteres HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se estamos na página de usuários
    if (document.getElementById('users-table-body')) {
        console.log('Inicializando gerenciador de usuários...');
        window.adminUserManager = new AdminUserManager();
    }
});