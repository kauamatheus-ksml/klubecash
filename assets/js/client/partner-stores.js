// assets/js/views/client/partner-stores.js

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide toast messages
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    });

    // Auto-submit form when filters change
    const filtersForm = document.getElementById('filtersForm');
    const categoria = document.getElementById('categoria');
    const ordenar = document.getElementById('ordenar');
    
    if (categoria && ordenar) {
        categoria.addEventListener('change', function() {
            filtersForm.submit();
        });
        
        ordenar.addEventListener('change', function() {
            filtersForm.submit();
        });
    }

    // Handle favorite buttons
    const favoriteForms = document.querySelectorAll('.favorite-form');
    favoriteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('.favorite-btn');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });
    });

    // Search input enhancement
    const searchInput = document.getElementById('nome');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    filtersForm.submit();
                }
            }, 1000);
        });
    }

    // Smooth scroll to stores when filtering
    if (window.location.search.includes('filtrar')) {
        const storesSection = document.querySelector('.stores-section');
        if (storesSection) {
            storesSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Loading states for store cards
    const storeCards = document.querySelectorAll('.store-card');
    storeCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.view-store-btn')) {
                const btn = e.target.closest('.view-store-btn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
            }
        });
    });
});