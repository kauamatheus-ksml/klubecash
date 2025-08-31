// Dashboard JavaScript for Store
document.addEventListener('DOMContentLoaded', function() {
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