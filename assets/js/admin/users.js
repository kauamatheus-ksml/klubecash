// assets/js/admin/users.js

// Variáveis globais
let currentUserId = null;
let selectedUsers = [];
let availableStores = [];
let isStoreUser = false;
let isEditMode = false;

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeUserManagement();
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
    
    // Carregar lojas disponíveis
    loadAvailableStores();
    
    // Configurar máscaras de input
    setupInputMasks();
    
    // Inicializar sistema de mensagens
    createMessageContainer();
}

/**
 * Cria container de mensagens se não existir
 */
function createMessageContainer() {
    if (!document.getElementById('messageContainer')) {
        const container = document.createElement('div');
        container.id = 'messageContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            width: 350px;
        `;
        document.body.appendChild(container);
    }
}

/**
 * Configura os event listeners para filtros
 */
function setupFilterListeners() {
    const tipoFilter = document.getElementById('tipoFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    
    if (tipoFilter) {
        tipoFilter.addEventListener('change', applyFilters);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });
    }
}

/**
 * Configura os event listeners para formulários
 */
function setupFormListeners() {
    const userTypeSelect = document.getElementById('userType');
    const emailSelect = document.getElementById('userEmailSelect');
    
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
}

/**
 * Configura máscaras de input
 */
function setupInputMasks() {
    const phoneInput = document.getElementById('userPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
}

/**
 * Aplica filtros à listagem
 */
function applyFilters() {
    const form = document.getElementById('filtersForm');
    if (form) {
        form.submit();
    }
}

/**
 * Limpa todos os filtros
 */
function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('busca');
    url.searchParams.delete('tipo');
    url.searchParams.delete('status');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

/**
 * Exibe mensagem para o usuário - VERSÃO CORRIGIDA E ROBUSTA
 */
function showMessage(message, type = 'success') {
    // Garantir que o container existe
    createMessageContainer();
    
    const messageContainer = document.getElementById('messageContainer');
    if (!messageContainer) {
        // Fallback para alert se não conseguir criar o container
        alert(message);
        return;
    }
    
    // Mapear tipos para classes do Bootstrap
    const alertClassMap = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const alertClass = alertClassMap[type] || 'alert-info';
    
    // Mapear tipos para ícones
    const iconMap = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    const iconClass = iconMap[type] || 'fa-info-circle';
    
    // Criar elemento de mensagem
    const messageElement = document.createElement('div');
    messageElement.className = `alert ${alertClass} alert-dismissible fade show`;
    messageElement.style.cssText = `
        margin-bottom: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border: none;
        border-radius: 8px;
        animation: slideInRight 0.3s ease-out;
    `;
    
    messageElement.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>
        <strong>${type === 'error' ? 'Erro!' : type === 'success' ? 'Sucesso!' : type === 'warning' ? 'Atenção!' : 'Info:'}</strong>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
    `;
    
    // Adicionar ao container
    messageContainer.appendChild(messageElement);
    
    // Rolar para a mensagem
    messageElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (messageElement.parentElement) {
            messageElement.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => messageElement.remove(), 300);
        }
    }, 5000);
}

/**
 * Função auxiliar para mostrar loading - VERSÃO APRIMORADA
 */
function showLoading(message = 'Processando...') {
    let loadingEl = document.getElementById('globalLoading');
    if (!loadingEl) {
        loadingEl = document.createElement('div');
        loadingEl.id = 'globalLoading';
        loadingEl.innerHTML = `
            <div style="
                position: fixed; 
                top: 0; 
                left: 0; 
                width: 100%; 
                height: 100%; 
                background: rgba(0,0,0,0.6); 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                z-index: 9999;
                backdrop-filter: blur(2px);
            ">
                <div style="
                    background: white; 
                    padding: 30px 40px; 
                    border-radius: 12px; 
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    min-width: 200px;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #FF7A00;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 15px;
                    "></div>
                    <div style="
                        color: #333;
                        font-size: 16px;
                        font-weight: 500;
                    ">${message}</div>
                </div>
            </div>
        `;
        document.body.appendChild(loadingEl);
        
        // Adicionar CSS para animação
        if (!document.getElementById('loadingStyles')) {
            const style = document.createElement('style');
            style.id = 'loadingStyles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    } else {
        loadingEl.style.display = 'flex';
        const messageEl = loadingEl.querySelector('div:last-child');
        if (messageEl) messageEl.textContent = message;
    }
}

/**
 * Função auxiliar para esconder loading
 */
function hideLoading() {
    const loadingEl = document.getElementById('globalLoading');
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
}

/**
 * Função utilitária para fazer requisições AJAX - VERSÃO ROBUSTA
 */
async function makeRequest(url, data, method = 'POST') {
    try {
        console.log('=== DEBUG JAVASCRIPT ===');
        console.log(`Fazendo requisição ${method} para:`, url);
        console.log('Dados enviados:', data instanceof URLSearchParams ? Object.fromEntries(data) : data);
        console.log('========================');
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data instanceof URLSearchParams ? data.toString() : data
        });
        
        console.log('Status da resposta:', response.status);
        console.log('Headers da resposta:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Resposta como texto:', text);
        
        try {
            const jsonData = JSON.parse(text);
            console.log('Dados JSON parseados:', jsonData);
            return jsonData;
        } catch (parseError) {
            console.error('Erro ao fazer parse do JSON:', parseError);
            console.error('Texto recebido:', text);
            throw new Error('Resposta não é JSON válido');
        }
        
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
}

/**
 * Carrega lojas disponíveis para vinculação - VERSÃO CORRIGIDA
 */
function loadAvailableStores() {
    const formData = new URLSearchParams();
    formData.append('action', 'get_available_stores');
    
    makeRequest('../../controllers/AdminController.php', formData)
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
        showMessage('Erro ao carregar lojas disponíveis: ' + error.message, 'error');
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
    
    if (isStore && !isEditMode) {
        // Mostrar seleção de loja
        if (emailContainer) emailContainer.style.display = 'block';
        if (emailInput) emailInput.style.display = 'none';
        if (storeFields) storeFields.style.display = 'block';
        
        if (emailInput) emailInput.required = false;
        
        if (availableStores.length === 0) {
            loadAvailableStores();
        }
    } else {
        // Mostrar input normal
        resetStoreFields();
    }
}

/**
 * Manipula mudança na seleção de loja - VERSÃO CORRIGIDA
 */
function handleStoreEmailChange(email) {
    if (!email) {
        clearStoreFields();
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('action', 'get_store_by_email');
    formData.append('email', email);
    
    makeRequest('../../controllers/AdminController.php', formData)
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
    
    if (emailInput) emailInput.value = '';
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';
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
    
    if (emailContainer) emailContainer.style.display = 'none';
    if (emailInput) {
        emailInput.style.display = 'block';
        emailInput.required = true;
        emailInput.readOnly = false;
    }
    if (storeFields) storeFields.style.display = 'none';
    
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
 * Preenche o formulário com dados do usuário
 */
function fillUserForm(userData) {
    const userIdField = document.getElementById('userId');
    const userNameField = document.getElementById('userName');
    const userEmailField = document.getElementById('userEmail');
    const userTypeField = document.getElementById('userType');
    const userStatusField = document.getElementById('userStatus');
    const userPhoneField = document.getElementById('userPhone');
    const userPasswordField = document.getElementById('userPassword');
    
    if (userIdField) userIdField.value = userData.id;
    if (userNameField) userNameField.value = userData.nome;
    if (userEmailField) userEmailField.value = userData.email;
    if (userTypeField) userTypeField.value = userData.tipo;
    if (userStatusField) userStatusField.value = userData.status;
    if (userPhoneField && userData.telefone) userPhoneField.value = userData.telefone;
    if (userPasswordField) userPasswordField.value = ''; // Sempre limpar senha
}

/**
 * Visualiza detalhes do usuário - VERSÃO CORRIGIDA
 */
function viewUser(userId) {
    if (!userId) {
        showMessage('ID do usuário não fornecido', 'error');
        return;
    }
    
    const modal = document.getElementById('viewUserModal');
    const content = document.getElementById('userViewContent');
    
    if (!modal || !content) {
        showMessage('Modal de visualização não encontrado', 'error');
        return;
    }
    
    modal.classList.add('show');
    content.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Carregando...</div>';
    
    const formData = new URLSearchParams();
    formData.append('action', 'getUserDetails');
    formData.append('user_id', userId);
    
    makeRequest('../../controllers/AdminController.php', formData)
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
 * Altera status do usuário - VERSÃO COMPLETAMENTE CORRIGIDA E ROBUSTA
 */
function changeUserStatus(userId, newStatus, userName) {
    // Validação rigorosa de parâmetros
    if (!userId || !newStatus || !userName) {
        console.error('Parâmetros inválidos:', {userId, newStatus, userName});
        showMessage('Erro: Parâmetros inválidos para alteração de status', 'error');
        return;
    }
    
    // Converter userId para número se necessário
    const userIdNum = parseInt(userId);
    if (isNaN(userIdNum) || userIdNum <= 0) {
        console.error('ID do usuário inválido:', userId);
        showMessage('Erro: ID do usuário inválido', 'error');
        return;
    }
    
    // Validar status
    const validStatuses = ['ativo', 'inativo', 'bloqueado'];
    if (!validStatuses.includes(newStatus)) {
        console.error('Status inválido:', newStatus);
        showMessage('Erro: Status inválido', 'error');
        return;
    }
    
    // Definir texto da ação baseado no status
    const actionText = newStatus === 'ativo' ? 'ativar' : 
                      newStatus === 'inativo' ? 'desativar' : 'bloquear';
    
    // Confirmar ação com o usuário
    if (!confirm(`Tem certeza que deseja ${actionText} o usuário "${userName}"?`)) {
        return;
    }
    
    // Mostrar indicador de carregamento
    showLoading(`${actionText.charAt(0).toUpperCase() + actionText.slice(1)}ando usuário...`);
    
    // Log detalhado para debug
    console.log('=== ALTERAÇÃO DE STATUS DO USUÁRIO ===');
    console.log('User ID:', userIdNum);
    console.log('Novo Status:', newStatus);
    console.log('Nome do Usuário:', userName);
    console.log('Ação:', actionText);
    console.log('Timestamp:', new Date().toISOString());
    
    // Preparar dados para envio
    const formData = new URLSearchParams();
    formData.append('action', 'update_user_status');
    formData.append('user_id', userIdNum.toString());
    formData.append('status', newStatus);
    
    // Log dos dados que serão enviados
    console.log('Dados a serem enviados:', formData.toString());
    
    // Fazer requisição AJAX usando a função utilitária robusta
    makeRequest('../../controllers/AdminController.php', formData)
    .then(data => {
        hideLoading();
        
        console.log('=== RESPOSTA DO SERVIDOR ===');
        console.log('Dados recebidos:', data);
        
        // Verificar se a operação foi bem-sucedida
        if (data && data.status === true) {
            console.log('✅ Operação bem-sucedida');
            showMessage(`Usuário ${actionText} com sucesso!`, 'success');
            
            // Recarregar a página após um pequeno delay
            setTimeout(() => {
                console.log('Recarregando página...');
                window.location.reload();
            }, 1500);
        } else {
            console.log('❌ Operação falhou');
            // Mostrar mensagem de erro específica
            const errorMessage = data && data.message ? data.message : `Erro ao ${actionText} usuário`;
            showMessage(errorMessage, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('=== ERRO NA REQUISIÇÃO ===');
        console.error('Erro completo:', error);
        console.error('Stack trace:', error.stack);
        
        // Mostrar erro detalhado ao usuário
        showMessage(`Erro ao processar a solicitação: ${error.message}`, 'error');
    });
}

/**
 * Submete formulário de usuário - VERSÃO CORRIGIDA
 */
function submitUserForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!form) {
        showMessage('Formulário não encontrado', 'error');
        return;
    }
    
    if (!validateForm(form)) {
        return;
    }
    
    const formData = new FormData(form);
    const isEditing = false;    
    
    // Validação específica para senha
    const senha = formData.get('senha');
    if (!isEditing && (!senha || senha.trim() === '')) {
        showMessage('Senha é obrigatória para criar um novo usuário', 'error');
        const passwordField = document.getElementById('userPassword');
        if (passwordField) passwordField.focus();
        return;
    }
    
    if (senha && senha.trim() !== '' && senha.length < 8) {
        showMessage('A senha deve ter no mínimo 8 caracteres', 'error');
        const passwordField = document.getElementById('userPassword');
        if (passwordField) passwordField.focus();
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
    
    // Desabilitar botão de envio
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
    }
    
    // Preparar dados para envio
    const submitData = new URLSearchParams();
    submitData.append('action', 'register');
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'id' || (key === 'id' && value !== '')) {
            submitData.append(key === 'id' ? 'user_id' : key, value);
        }
    }
    
    showLoading(isEditing ? 'Atualizando usuário...' : 'Criando usuário...');
    
    makeRequest('../../controllers/AdminController.php', submitData)
    .then(data => {
        hideLoading();
        
        if (data.status) {
            showMessage(data.message || (isEditing ? 'Usuário atualizado com sucesso!' : 'Usuário criado com sucesso!'), 'success');
            hideUserModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showMessage(data.message || 'Erro ao processar dados do usuário', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('Erro ao processar a solicitação: ' + error.message, 'error');
    })
    .finally(() => {
        // Reabilitar botão
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = isEditing ? 
                '<i class="fas fa-save"></i> Salvar Alterações' : 
                '<i class="fas fa-user-plus"></i> Criar Usuário';
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
    } else if (emailField) {
        emailField.classList.remove('error');
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
    if (!field) return;
    
    const toggleBtn = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        field.type = 'password';
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

/**
 * Seleciona/deseleciona todos os usuários
 */
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    if (!selectAll) return;
    
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
    if (!checkbox) return;
    
    const userIdNum = parseInt(userId);
    
    if (checkbox.checked) {
        if (!selectedUsers.includes(userIdNum)) {
            selectedUsers.push(userIdNum);
        }
    } else {
        selectedUsers = selectedUsers.filter(id => id !== userIdNum);
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
 * Executa ação em massa - VERSÃO CORRIGIDA
 */
function bulkAction(status) {
    if (selectedUsers.length === 0) {
        showMessage('Nenhum usuário selecionado', 'warning');
        return;
    }
    
    const actionText = status === 'ativo' ? 'ativar' : 
                      status === 'inativo' ? 'desativar' : 'bloquear';
    
    if (!confirm(`Tem certeza que deseja ${actionText} ${selectedUsers.length} usuários selecionados?`)) {
        return;
    }
    
    const bulkActionBar = document.getElementById('bulkActionBar');
    if (bulkActionBar) {
        bulkActionBar.innerHTML = `<div class="bulk-info">Processando ${selectedUsers.length} usuários...</div>`;
    }
    
    showLoading(`Processando ${selectedUsers.length} usuários...`);
    
    let processed = 0;
    let successful = 0;
    
    const processUser = (userId) => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_user_status');
        formData.append('user_id', userId.toString());
        formData.append('status', status);
        
        return makeRequest('../../controllers/AdminController.php', formData)
        .then(data => {
            processed++;
            if (data.status) successful++;
            
            if (processed === selectedUsers.length) {
                hideLoading();
                const successMessage = `${successful} usuários foram ${actionText === 'ativar' ? 'ativados' : actionText === 'desativar' ? 'desativados' : 'bloqueados'} com sucesso!`;
                showMessage(successMessage, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        })
        .catch(() => {
            processed++;
            if (processed === selectedUsers.length) {
                hideLoading();
                const successMessage = `${successful} usuários foram processados com sucesso!`;
                showMessage(successMessage, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        });
    };
    
    // Processar todos os usuários selecionados
    selectedUsers.forEach(processUser);
}

/**
 * Exporta usuários
 */
function exportUsers() {
    showMessage('Função de exportação será implementada em breve', 'info');
}

// Adicionar estilos CSS necessários
const style = document.createElement('style');
style.textContent = `
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
        background: var(--primary-light, #FFF3E6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color, #FF7A00);
        font-size: 1.5rem;
    }
    
    .user-basic-info h4 {
        margin: 0 0 0.25rem 0;
        color: var(--dark-gray, #333);
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .user-basic-info p {
        margin: 0;
        color: var(--medium-gray, #666);
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
        color: var(--dark-gray, #333);
        margin: 0;
    }
    
    .detail-item span {
        color: var(--medium-gray, #666);
        text-align: right;
    }
    
    .badge {
        padding: 0.25em 0.5em;
        font-size: 0.75em;
        border-radius: 0.25rem;
    }
    
    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .badge-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .type-badge {
        padding: 0.25em 0.5em;
        border-radius: 0.25rem;
        font-size: 0.75em;
        font-weight: 500;
    }
    
    .type-cliente {
        background-color: #e7f3ff;
        color: #0056b3;
    }
    
    .type-loja {
        background-color: #e6f7e6;
        color: #0d5e0d;
    }
    
    .type-admin {
        background-color: #fff0e6;
        color: #b8540d;
    }
`;
document.head.appendChild(style);