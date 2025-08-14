/**
 * JavaScript para funcionalidades da área da loja
 * Klube Cash
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar qualquer funcionalidade específica da loja
    initModals();
    initTransactionForms();
    initBatchUpload();
    initPaymentForms();
});

/**
 * Inicializa os modais utilizados na área da loja
 */
function initModals() {
    // Processa os modais na página
    const modals = document.querySelectorAll('.modal');
    const closeBtns = document.querySelectorAll('.modal .close');
    
    // Fechar modais ao clicar no botão fechar
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Fechar modais ao clicar fora deles
    window.addEventListener('click', function(event) {
        modals.forEach(modal => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    });
}

/**
 * Inicializa os formulários de registro de transação
 */
function initTransactionForms() {
    const transactionForm = document.getElementById('transactionForm');
    if (!transactionForm) return;
    
    // Campos do formulário
    const valorTotal = document.getElementById('valor_total');
    const emailCliente = document.getElementById('email_cliente');
    const calcCashback = document.getElementById('calc_cashback');
    const calcComissao = document.getElementById('calc_comissao');
    const calcTotal = document.getElementById('calc_total');
    
    // Calcular valores de cashback e comissão ao digitar
    valorTotal?.addEventListener('input', function() {
        calcularValores(this.value);
    });
    
    // Função para calcular valores
    function calcularValores(valor) {
        if (!valor || isNaN(valor) || valor <= 0) {
            // Limpar calculadora
            if (calcCashback) calcCashback.textContent = '0,00';
            if (calcComissao) calcComissao.textContent = '0,00';
            if (calcTotal) calcTotal.textContent = '0,00';
            return;
        }
        
        // Converter para número
        const valorNumerico = parseFloat(valor);
        
        // Calcular valores (10% total, 5% cliente, 5% admin)
        const valorTotal = valorNumerico * 0.1;
        const valorCashback = valorNumerico * 0.05;
        const valorComissao = valorNumerico * 0.05;
        
        // Atualizar exibição
        if (calcCashback) calcCashback.textContent = valorCashback.toFixed(2).replace('.', ',');
        if (calcComissao) calcComissao.textContent = valorComissao.toFixed(2).replace('.', ',');
        if (calcTotal) calcTotal.textContent = valorTotal.toFixed(2).replace('.', ',');
    }
    
    // Validação do formulário
    transactionForm.addEventListener('submit', function(e) {
        let valid = true;
        let message = '';
        
        // Validar valor
        if (!valorTotal.value || isNaN(valorTotal.value) || parseFloat(valorTotal.value) <= 0) {
            valid = false;
            message = 'Por favor, informe um valor válido para a transação.';
        }
        
        // Validar email
        if (!emailCliente.value || !validateEmail(emailCliente.value)) {
            valid = false;
            message = 'Por favor, informe um email válido do cliente.';
        }
        
        if (!valid) {
            e.preventDefault();
            alert(message);
        }
    });
    
    // Função para validar email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
}

/**
 * Inicializa a funcionalidade de upload em lote
 */
function initBatchUpload() {
    const batchForm = document.getElementById('batchUploadForm');
    const fileInput = document.getElementById('batchFile');
    const preview = document.getElementById('filePreview');
    
    if (!batchForm || !fileInput) return;
    
    // Exibir preview quando arquivo for selecionado
    fileInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) {
            if (preview) preview.innerHTML = '<p>Nenhum arquivo selecionado</p>';
            return;
        }
        
        const file = this.files[0];
        
        // Verificar tipo de arquivo
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            if (preview) preview.innerHTML = '<p class="error">Apenas arquivos CSV são permitidos</p>';
            this.value = '';
            return;
        }
        
        // Exibir informações do arquivo
        if (preview) {
            preview.innerHTML = `
                <div class="file-info">
                    <p><strong>Arquivo:</strong> ${file.name}</p>
                    <p><strong>Tamanho:</strong> ${formatFileSize(file.size)}</p>
                </div>
            `;
        }
    });
    
    // Função para formatar tamanho do arquivo
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' bytes';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
        else return (bytes / 1048576).toFixed(2) + ' MB';
    }
}

/**
 * Inicializa os formulários de pagamento
 */
function initPaymentForms() {
    const paymentForm = document.getElementById('paymentForm');
    const selectAllCheckbox = document.getElementById('selectAll');
    const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
    const totalElement = document.getElementById('totalAmount');
    
    if (!paymentForm) return;
    
    // Selecionar/deselecionar todas as transações
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            transactionCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            calculateTotal();
        });
    }
    
    // Calcular total ao selecionar/deselecionar
    transactionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', calculateTotal);
    });
    
    // Calcular e exibir total selecionado
    function calculateTotal() {
        let total = 0;
        
        transactionCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const value = parseFloat(checkbox.getAttribute('data-value') || 0);
                total += value;
            }
        });
        
        if (totalElement) {
            totalElement.textContent = total.toFixed(2).replace('.', ',');
        }
    }
    
    // Validação do formulário de pagamento
    paymentForm.addEventListener('submit', function(e) {
        // Verificar se pelo menos uma transação foi selecionada
        let anySelected = false;
        transactionCheckboxes.forEach(checkbox => {
            if (checkbox.checked) anySelected = true;
        });
        
        if (!anySelected) {
            e.preventDefault();
            alert('Por favor, selecione pelo menos uma transação para pagar.');
            return;
        }
        
        // Verificar método de pagamento
        const metodoPagamento = document.querySelector('input[name="metodo_pagamento"]:checked');
        if (!metodoPagamento) {
            e.preventDefault();
            alert('Por favor, selecione um método de pagamento.');
            return;
        }
    });
}

