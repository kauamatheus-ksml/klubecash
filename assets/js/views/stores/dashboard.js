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
});