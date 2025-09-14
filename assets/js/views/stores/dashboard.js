// Dashboard JavaScript for Store
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    // Create overlay element if it doesn't exist
    if (!sidebarOverlay && window.innerWidth <= 991.98) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Toggle sidebar function
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('open');
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.classList.toggle('active');
            }
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
    }
    
    // Close sidebar function
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('open');
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    }
    
    // Sidebar toggle event
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991.98 && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991.98) {
            closeSidebar();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Get chart labels and data from PHP (passed via data attributes)
    const chartContainer = document.getElementById('salesChart');
    if (chartContainer) {
        const chartLabels = JSON.parse(chartContainer.dataset.labels || '[]');
        const chartData = JSON.parse(chartContainer.dataset.data || '[]');
        
        // Configuração do gráfico de vendas mensais
        const salesCtx = chartContainer.getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: chartData,
                    backgroundColor: 'rgba(255, 122, 0, 0.7)',
                    borderColor: 'rgba(255, 122, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }

    // Value visibility toggle functionality
    loadValueVisibilityState();
});

// Function to toggle value visibility
function toggleValueVisibility(targetId) {
    const hiddenValues = getHiddenValues();
    const valueElement = document.querySelector(`[data-id="${targetId}"]`);
    const button = document.querySelector(`[data-target="${targetId}"]`);

    if (!valueElement || !button) return;

    const isHidden = hiddenValues.includes(targetId);

    if (isHidden) {
        // Show value
        valueElement.textContent = valueElement.dataset.original;
        button.querySelector('.eye-open').style.display = '';
        button.querySelector('.eye-closed').style.display = 'none';
        removeFromHiddenValues(targetId);
    } else {
        // Hide value
        valueElement.textContent = '••••••';
        button.querySelector('.eye-open').style.display = 'none';
        button.querySelector('.eye-closed').style.display = '';
        addToHiddenValues(targetId);
    }
}

// Function to toggle table column visibility
function toggleTableColumnVisibility(targetId) {
    const hiddenValues = getHiddenValues();
    const columns = document.querySelectorAll(`[data-column="${targetId}"]`);
    const button = document.querySelector(`[data-target="${targetId}"]`);

    if (!button) return;

    const isHidden = hiddenValues.includes(targetId);

    if (isHidden) {
        // Show values
        columns.forEach(column => {
            const valueElement = column.querySelector('.hideable-value');
            if (valueElement) {
                valueElement.textContent = valueElement.dataset.original;
            }
        });
        button.querySelector('.eye-open').style.display = '';
        button.querySelector('.eye-closed').style.display = 'none';
        removeFromHiddenValues(targetId);
    } else {
        // Hide values
        columns.forEach(column => {
            const valueElement = column.querySelector('.hideable-value');
            if (valueElement) {
                valueElement.textContent = '••••••';
            }
        });
        button.querySelector('.eye-open').style.display = 'none';
        button.querySelector('.eye-closed').style.display = '';
        addToHiddenValues(targetId);
    }
}

// LocalStorage management functions
function getHiddenValues() {
    const stored = localStorage.getItem('klubecash_hidden_values');
    return stored ? JSON.parse(stored) : [];
}

function addToHiddenValues(valueId) {
    const hiddenValues = getHiddenValues();
    if (!hiddenValues.includes(valueId)) {
        hiddenValues.push(valueId);
        localStorage.setItem('klubecash_hidden_values', JSON.stringify(hiddenValues));
    }
}

function removeFromHiddenValues(valueId) {
    const hiddenValues = getHiddenValues();
    const filteredValues = hiddenValues.filter(id => id !== valueId);
    localStorage.setItem('klubecash_hidden_values', JSON.stringify(filteredValues));
}

// Load initial visibility state from localStorage
function loadValueVisibilityState() {
    const hiddenValues = getHiddenValues();

    hiddenValues.forEach(valueId => {
        if (valueId.startsWith('table-')) {
            // Handle table columns
            const columns = document.querySelectorAll(`[data-column="${valueId}"]`);
            const button = document.querySelector(`[data-target="${valueId}"]`);

            if (button) {
                button.querySelector('.eye-open').style.display = 'none';
                button.querySelector('.eye-closed').style.display = '';

                columns.forEach(column => {
                    const valueElement = column.querySelector('.hideable-value');
                    if (valueElement) {
                        valueElement.textContent = '••••••';
                    }
                });
            }
        } else {
            // Handle individual values
            const valueElement = document.querySelector(`[data-id="${valueId}"]`);
            const button = document.querySelector(`[data-target="${valueId}"]`);

            if (valueElement && button) {
                valueElement.textContent = '••••••';
                button.querySelector('.eye-open').style.display = 'none';
                button.querySelector('.eye-closed').style.display = '';
            }
        }
    });
}