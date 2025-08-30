/**
 * Modern Admin Purchases Management System
 * Enterprise-level JavaScript functionality for purchase management
 */

class PurchaseManager {
    constructor() {
        this.selectedPurchases = new Set();
        this.currentFilters = {};
        this.currentSort = { column: 'data_transacao', direction: 'desc' };
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeFilters();
        this.updateKPIs();
        this.loadPurchases();
    }

    bindEvents() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 300);
            });
        }

        // Filter controls
        document.querySelectorAll('.form-select, .form-input').forEach(input => {
            input.addEventListener('change', () => this.applyFilters());
        });

        // Bulk actions
        document.getElementById('selectAll')?.addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        document.getElementById('bulkApprove')?.addEventListener('click', () => {
            this.bulkAction('approve');
        });

        document.getElementById('bulkCancel')?.addEventListener('click', () => {
            this.bulkAction('cancel');
        });

        document.getElementById('bulkExport')?.addEventListener('click', () => {
            this.exportPurchases();
        });

        // Table sorting
        document.querySelectorAll('.data-table th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                this.handleSort(th.dataset.sort);
            });
        });

        // Individual checkboxes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('purchase-checkbox')) {
                this.togglePurchaseSelection(e.target.value, e.target.checked);
            }
        });

        // Action buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('action-btn')) {
                e.preventDefault();
                const action = e.target.dataset.action;
                const purchaseId = e.target.dataset.id;
                this.handlePurchaseAction(action, purchaseId);
            }
        });

        // Modal controls
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal')) {
                this.closeModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    handleSearch(query) {
        this.currentFilters.search = query;
        this.currentPage = 1;
        this.loadPurchases();
    }

    applyFilters() {
        const filters = {};
        
        // Date range
        const dateFrom = document.getElementById('dateFrom')?.value;
        const dateTo = document.getElementById('dateTo')?.value;
        if (dateFrom) filters.dateFrom = dateFrom;
        if (dateTo) filters.dateTo = dateTo;

        // Store filter
        const storeId = document.getElementById('storeFilter')?.value;
        if (storeId && storeId !== '') filters.storeId = storeId;

        // Status filter
        const status = document.getElementById('statusFilter')?.value;
        if (status && status !== '') filters.status = status;

        // Payment type filter
        const paymentType = document.getElementById('paymentFilter')?.value;
        if (paymentType && paymentType !== '') filters.paymentType = paymentType;

        // Amount range
        const amountMin = document.getElementById('amountMin')?.value;
        const amountMax = document.getElementById('amountMax')?.value;
        if (amountMin) filters.amountMin = parseFloat(amountMin);
        if (amountMax) filters.amountMax = parseFloat(amountMax);

        this.currentFilters = { ...this.currentFilters, ...filters };
        this.currentPage = 1;
        this.loadPurchases();
    }

    handleSort(column) {
        if (this.currentSort.column === column) {
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.column = column;
            this.currentSort.direction = 'asc';
        }

        this.updateSortIcons();
        this.loadPurchases();
    }

    updateSortIcons() {
        document.querySelectorAll('.data-table th[data-sort] i').forEach(icon => {
            icon.className = 'fas fa-sort';
            icon.style.opacity = '0.5';
        });

        const currentHeader = document.querySelector(`[data-sort="${this.currentSort.column}"] i`);
        if (currentHeader) {
            currentHeader.className = `fas fa-sort-${this.currentSort.direction === 'asc' ? 'up' : 'down'}`;
            currentHeader.style.opacity = '1';
        }
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.purchase-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            if (checked) {
                this.selectedPurchases.add(checkbox.value);
            } else {
                this.selectedPurchases.delete(checkbox.value);
            }
        });
        this.updateBulkActions();
    }

    togglePurchaseSelection(purchaseId, checked) {
        if (checked) {
            this.selectedPurchases.add(purchaseId);
        } else {
            this.selectedPurchases.delete(purchaseId);
        }
        this.updateBulkActions();
        this.updateSelectAll();
    }

    updateSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.purchase-checkbox');
        const checkedBoxes = document.querySelectorAll('.purchase-checkbox:checked');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }
    }

    updateBulkActions() {
        const count = this.selectedPurchases.size;
        const bulkButtons = document.querySelectorAll('.bulk-actions .btn');
        
        bulkButtons.forEach(btn => {
            btn.disabled = count === 0;
            const countSpan = btn.querySelector('.count');
            if (countSpan) {
                countSpan.textContent = `(${count})`;
            }
        });
    }

    async bulkAction(action) {
        if (this.selectedPurchases.size === 0) {
            this.showNotification('Selecione pelo menos uma compra', 'warning');
            return;
        }

        const actionText = {
            approve: 'aprovar',
            cancel: 'cancelar'
        }[action];

        if (!confirm(`Tem certeza que deseja ${actionText} ${this.selectedPurchases.size} compra(s)?`)) {
            return;
        }

        try {
            this.showLoading(true);
            
            const response = await fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: `bulk_${action}`,
                    purchases: Array.from(this.selectedPurchases)
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`${this.selectedPurchases.size} compra(s) ${actionText}da(s) com sucesso!`, 'success');
                this.selectedPurchases.clear();
                this.loadPurchases();
                this.updateKPIs();
            } else {
                throw new Error(result.message || 'Erro ao processar ação em lote');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async handlePurchaseAction(action, purchaseId) {
        const actions = {
            view: () => this.viewPurchase(purchaseId),
            edit: () => this.editPurchase(purchaseId),
            approve: () => this.approvePurchase(purchaseId),
            cancel: () => this.cancelPurchase(purchaseId),
            delete: () => this.deletePurchase(purchaseId)
        };

        if (actions[action]) {
            await actions[action]();
        }
    }

    async viewPurchase(purchaseId) {
        try {
            this.showLoading(true);
            
            const response = await fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_purchase',
                    id: purchaseId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showPurchaseModal(result.data);
            } else {
                throw new Error(result.message || 'Erro ao carregar compra');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async approvePurchase(purchaseId) {
        if (!confirm('Tem certeza que deseja aprovar esta compra?')) {
            return;
        }

        try {
            this.showLoading(true);
            
            const response = await fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'approve',
                    purchase_id: purchaseId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Compra aprovada com sucesso!', 'success');
                this.loadPurchases();
                this.updateKPIs();
            } else {
                throw new Error(result.message || 'Erro ao aprovar compra');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async cancelPurchase(purchaseId) {
        if (!confirm('Tem certeza que deseja cancelar esta compra?')) {
            return;
        }

        try {
            this.showLoading(true);
            
            const response = await fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'cancel',
                    purchase_id: purchaseId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Compra cancelada com sucesso!', 'success');
                this.loadPurchases();
                this.updateKPIs();
            } else {
                throw new Error(result.message || 'Erro ao cancelar compra');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async exportPurchases() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'export',
                format: 'excel',
                ...this.currentFilters
            });

            if (this.selectedPurchases.size > 0) {
                params.set('selected', Array.from(this.selectedPurchases).join(','));
            }

            const response = await fetch(`purchases.php?${params}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `compras_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showNotification('Exportação realizada com sucesso!', 'success');
            } else {
                throw new Error('Erro ao exportar compras');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadPurchases() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'list',
                page: this.currentPage,
                per_page: this.itemsPerPage,
                sort_column: this.currentSort.column,
                sort_direction: this.currentSort.direction,
                ...this.currentFilters
            });

            const response = await fetch(`purchases.php?${params}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'list',
                    page: this.currentPage,
                    per_page: this.itemsPerPage,
                    sort_column: this.currentSort.column,
                    sort_direction: this.currentSort.direction,
                    ...this.currentFilters
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.renderPurchases(result.data);
                this.renderPagination(result.pagination);
            } else {
                throw new Error(result.message || 'Erro ao carregar compras');
            }
        } catch (error) {
            this.showNotification(`Erro: ${error.message}`, 'error');
            this.renderEmptyState();
        } finally {
            this.showLoading(false);
        }
    }

    renderPurchases(purchases) {
        const tbody = document.getElementById('purchasesTableBody');
        if (!tbody) return;

        if (purchases.length === 0) {
            this.renderEmptyState();
            return;
        }

        tbody.innerHTML = purchases.map(purchase => `
            <tr>
                <td>
                    <div class="custom-checkbox">
                        <input type="checkbox" class="purchase-checkbox" value="${purchase.id}">
                        <span class="checkmark"></span>
                    </div>
                </td>
                <td>#${purchase.id}</td>
                <td>
                    <div class="user-info">
                        <strong>${purchase.cliente_nome}</strong>
                        ${purchase.cliente_email ? `<small>${purchase.cliente_email}</small>` : ''}
                    </div>
                </td>
                <td>${purchase.loja_nome}</td>
                <td>R$ ${parseFloat(purchase.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                <td>R$ ${parseFloat(purchase.cashback_valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                <td><span class="status-badge ${purchase.status.toLowerCase()}">${this.getStatusText(purchase.status)}</span></td>
                <td>${this.formatDate(purchase.data_transacao)}</td>
                <td>
                    <div class="transaction-actions">
                        <button class="action-btn view" data-action="view" data-id="${purchase.id}" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${purchase.status === 'pendente' ? `
                            <button class="action-btn edit" data-action="approve" data-id="${purchase.id}" title="Aprovar">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="action-btn delete" data-action="cancel" data-id="${purchase.id}" title="Cancelar">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');

        this.updateSelectAll();
        this.updateBulkActions();
    }

    renderEmptyState() {
        const tbody = document.getElementById('purchasesTableBody');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 3rem;">
                    <div style="color: #666;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Nenhuma compra encontrada</h3>
                        <p>Não há compras que correspondam aos filtros aplicados.</p>
                        <button class="btn btn-primary" onclick="window.purchaseManager.clearFilters()" style="margin-top: 1rem;">
                            <i class="fas fa-eraser"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    async updateKPIs() {
        try {
            const response = await fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_kpis'
                })
            });

            const result = await response.json();
            
            if (result.success) {
                const kpis = result.data;
                
                // Update KPI values
                document.getElementById('totalPurchases').textContent = kpis.total_purchases.toLocaleString('pt-BR');
                document.getElementById('totalVolume').textContent = `R$ ${kpis.total_volume.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                document.getElementById('totalCashback').textContent = `R$ ${kpis.total_cashback.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                document.getElementById('pendingPurchases').textContent = kpis.pending_count.toLocaleString('pt-BR');
                document.getElementById('avgTicket').textContent = `R$ ${kpis.avg_ticket.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                document.getElementById('approvalRate').textContent = `${kpis.approval_rate.toFixed(1)}%`;
                
                // Update trends
                this.updateTrends(kpis.trends);
            }
        } catch (error) {
            console.error('Erro ao atualizar KPIs:', error);
        }
    }

    updateTrends(trends) {
        Object.keys(trends).forEach(kpi => {
            const element = document.getElementById(`${kpi}Trend`);
            if (element) {
                const trend = trends[kpi];
                element.className = `kpi-change ${trend.direction}`;
                element.innerHTML = `
                    <i class="fas fa-arrow-${trend.direction === 'positive' ? 'up' : 'down'}"></i>
                    ${Math.abs(trend.percentage).toFixed(1)}%
                `;
            }
        });
    }

    showPurchaseModal(purchase) {
        const modal = document.getElementById('purchaseModal');
        if (!modal) return;

        const modalContent = modal.querySelector('#modalContent');
        modalContent.innerHTML = `
            <div class="purchase-details">
                <div class="detail-grid">
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Informações da Compra</h3>
                        <div class="detail-item">
                            <span class="detail-label">ID:</span>
                            <span class="detail-value">#${purchase.id}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Data:</span>
                            <span class="detail-value">${this.formatDate(purchase.data_transacao)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge ${purchase.status.toLowerCase()}">${this.getStatusText(purchase.status)}</span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> Cliente</h3>
                        <div class="detail-item">
                            <span class="detail-label">Nome:</span>
                            <span class="detail-value">${purchase.cliente_nome}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${purchase.cliente_email || 'Não informado'}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-store"></i> Loja</h3>
                        <div class="detail-item">
                            <span class="detail-label">Nome:</span>
                            <span class="detail-value">${purchase.loja_nome}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Categoria:</span>
                            <span class="detail-value">${purchase.loja_categoria || 'Não informado'}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-dollar-sign"></i> Informações Financeiras</h3>
                        <div class="detail-item">
                            <span class="detail-label">Valor Total:</span>
                            <span class="detail-value">R$ ${parseFloat(purchase.valor_total).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Cashback Cliente:</span>
                            <span class="detail-value">R$ ${parseFloat(purchase.valor_cliente).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Cashback Admin:</span>
                            <span class="detail-value">R$ ${parseFloat(purchase.valor_admin).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                        ${purchase.saldo_usado && parseFloat(purchase.saldo_usado) > 0 ? `
                            <div class="detail-item">
                                <span class="detail-label">Saldo Usado:</span>
                                <span class="detail-value">R$ ${parseFloat(purchase.saldo_usado).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        modal.style.display = 'flex';
        modal.classList.add('active');
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('active');
        });
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'Pendente',
            'pendente': 'Pendente',
            'processing': 'Processando',
            'completed': 'Concluída',
            'approved': 'Aprovada',
            'aprovado': 'Aprovada',
            'cancelled': 'Cancelada',
            'cancelado': 'Cancelada'
        };
        return statusTexts[status.toLowerCase()] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    initializeFilters() {
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        
        if (dateFromInput) dateFromInput.value = thirtyDaysAgo.toISOString().split('T')[0];
        if (dateToInput) dateToInput.value = today.toISOString().split('T')[0];
    }

    clearFilters() {
        // Reset all filter inputs
        document.querySelectorAll('.form-input, .form-select').forEach(input => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });
        
        this.currentFilters = {};
        this.currentPage = 1;
        this.loadPurchases();
    }

    showLoading(show) {
        const loader = document.getElementById('loadingIndicator');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
        
        // Disable/enable interactive elements
        const interactiveElements = document.querySelectorAll('button, input, select');
        interactiveElements.forEach(element => {
            element.disabled = show;
        });
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Position and show
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    renderPagination(pagination) {
        const paginationContainer = document.getElementById('pagination');
        if (!paginationContainer || !pagination) return;

        const { currentPage, totalPages, hasNext, hasPrev } = pagination;
        
        let paginationHTML = `
            <button class="pagination-btn" ${!hasPrev ? 'disabled' : ''} onclick="window.purchaseManager.goToPage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
        `;

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button class="pagination-btn" onclick="window.purchaseManager.goToPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-dots">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                        onclick="window.purchaseManager.goToPage(${i})">${i}</button>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="pagination-dots">...</span>`;
            }
            paginationHTML += `<button class="pagination-btn" onclick="window.purchaseManager.goToPage(${totalPages})">${totalPages}</button>`;
        }

        paginationHTML += `
            <button class="pagination-btn" ${!hasNext ? 'disabled' : ''} onclick="window.purchaseManager.goToPage(${currentPage + 1})">
                Próximo <i class="fas fa-chevron-right"></i>
            </button>
        `;

        paginationContainer.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadPurchases();
    }
}

// Global functions for backward compatibility and external access
function exportData() {
    if (window.purchaseManager) {
        window.purchaseManager.exportPurchases();
    }
}

function refreshData() {
    if (window.purchaseManager) {
        window.purchaseManager.updateKPIs();
        window.purchaseManager.loadPurchases();
    }
}

function clearFilters() {
    if (window.purchaseManager) {
        window.purchaseManager.clearFilters();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.purchaseManager = new PurchaseManager();
});

// Add notification styles dynamically if not present
if (!document.querySelector('#notification-styles')) {
    const notificationStyles = document.createElement('style');
    notificationStyles.id = 'notification-styles';
    notificationStyles.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 10000;
            border-left: 4px solid;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification-success { border-left-color: #10b981; }
        .notification-error { border-left-color: #ef4444; }
        .notification-warning { border-left-color: #f59e0b; }
        .notification-info { border-left-color: #3b82f6; }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-content i {
            font-size: 18px;
        }

        .notification-success i { color: #10b981; }
        .notification-error i { color: #ef4444; }
        .notification-warning i { color: #f59e0b; }
        .notification-info i { color: #3b82f6; }

        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            margin-left: 16px;
        }

        .notification-close:hover {
            color: #64748b;
        }

        .pagination-dots {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem;
            color: #94a3b8;
        }

        #loadingIndicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .purchase-details .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .purchase-details .detail-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .purchase-details .detail-section h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .purchase-details .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .purchase-details .detail-item:last-child {
            border-bottom: none;
        }

        .purchase-details .detail-label {
            font-weight: 600;
            color: #64748b;
        }

        .purchase-details .detail-value {
            color: #1e293b;
        }
    `;
    document.head.appendChild(notificationStyles);
}