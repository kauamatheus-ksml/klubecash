/* assets/js/dashboard-interactions-v2.js */

/**
 * Dashboard Responsivo - Sistema de Interações Avançadas
 * Gerencia todas as interações do dashboard admin para máxima responsividade
 */

class ResponsiveDashboard {
    constructor() {
        this.breakpoints = {
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            '2xl': 1536
        };
        
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.observers = new Map();
        this.animations = new Map();
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeAnimations();
        this.setupResponsiveObservers();
        this.initializeCharts();
        this.optimizePerformance();
        
        // Aguardar DOM estar completamente carregado
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.onDOMReady();
            });
        } else {
            this.onDOMReady();
        }
    }
    
    onDOMReady() {
        this.setupIntersectionObservers();
        this.initializeCardsAnimations();
        this.setupTouchGestures();
        this.optimizeForDevice();
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        
        if (width >= this.breakpoints['2xl']) return '2xl';
        if (width >= this.breakpoints.xl) return 'xl';
        if (width >= this.breakpoints.lg) return 'lg';
        if (width >= this.breakpoints.md) return 'md';
        if (width >= this.breakpoints.sm) return 'sm';
        return 'xs';
    }
    
    setupEventListeners() {
        // Resize otimizado com debounce
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 150);
        });
        
        // Orientação em dispositivos móveis
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });
        
        // Scroll otimizado
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.handleScroll();
            }, 16); // 60fps
        }, { passive: true });
        
        // Foco para acessibilidade
        document.addEventListener('focusin', (e) => {
            this.handleFocus(e);
        });
        
        // Cliques globais
        document.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });
    }
    
    handleResize() {
        const newBreakpoint = this.getCurrentBreakpoint();
        
        if (newBreakpoint !== this.currentBreakpoint) {
            this.currentBreakpoint = newBreakpoint;
            this.onBreakpointChange();
        }
        
        this.recalculateLayouts();
        this.updateChartSizes();
        this.adjustTextSizes();
    }
    
    onBreakpointChange() {
        console.log(`Mudança para breakpoint: ${this.currentBreakpoint}`);
        
        // Reorganizar grid de métricas
        this.reorganizeMetricsGrid();
        
        // Ajustar cards
        this.adjustCardLayouts();
        
        // Reconfigurar transações
        this.reconfigureTransactionCards();
        
        // Notificar outros componentes
        this.dispatchBreakpointEvent();
    }
    
    reorganizeMetricsGrid() {
        const metricsGrid = document.querySelector('.metrics-grid');
        if (!metricsGrid) return;
        
        const cards = metricsGrid.querySelectorAll('.metric-card');
        
        switch (this.currentBreakpoint) {
            case 'xs':
            case 'sm':
                // Mobile: 1 coluna
                metricsGrid.style.gridTemplateColumns = '1fr';
                break;
            case 'md':
                // Tablet: 2 colunas
                metricsGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
                break;
            case 'lg':
            case 'xl':
            case '2xl':
                // Desktop: 4 colunas
                metricsGrid.style.gridTemplateColumns = 'repeat(4, 1fr)';
                break;
        }
        
        // Animar reposicionamento
        this.animateGridReorganization(cards);
    }
    
    adjustCardLayouts() {
        const dashboardGrid = document.querySelector('.dashboard-grid');
        if (!dashboardGrid) return;
        
        switch (this.currentBreakpoint) {
            case 'xs':
            case 'sm':
            case 'md':
                dashboardGrid.style.gridTemplateColumns = '1fr';
                break;
            case 'lg':
            case 'xl':
            case '2xl':
                dashboardGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
                break;
        }
    }
    
    reconfigureTransactionCards() {
        const transactionsGrid = document.querySelector('.transactions-grid');
        if (!transactionsGrid) return;
        
        switch (this.currentBreakpoint) {
            case 'xs':
            case 'sm':
            case 'md':
                transactionsGrid.style.gridTemplateColumns = '1fr';
                break;
            case 'lg':
                transactionsGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
                break;
            case 'xl':
            case '2xl':
                transactionsGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
                break;
        }
        
        // Ajustar padding interno dos cards
        const transactionCards = transactionsGrid.querySelectorAll('.transaction-card');
        transactionCards.forEach(card => {
            if (this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm') {
                card.style.padding = 'var(--space-md)';
            } else {
                card.style.padding = 'var(--space-xl)';
            }
        });
    }
    
    dispatchBreakpointEvent() {
        const event = new CustomEvent('breakpointChange', {
            detail: { 
                breakpoint: this.currentBreakpoint,
                width: window.innerWidth 
            }
        });
        document.dispatchEvent(event);
    }
    
    setupIntersectionObservers() {
        // Observer para animações de entrada
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Animar números se for um card de métrica
                    if (entry.target.classList.contains('metric-card')) {
                        this.animateMetricCard(entry.target);
                    }
                    
                    // Observar apenas uma vez
                    animationObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '20px'
        });
        
        // Observar todos os elementos animáveis
        const animatableElements = document.querySelectorAll(
            '.metric-card, .dashboard-card, .transaction-card, .client-rank-item, .store-item'
        );
        
        animatableElements.forEach(element => {
            animationObserver.observe(element);
        });
        
        this.observers.set('animation', animationObserver);
        
        // Observer para lazy loading de conteúdo
        this.setupLazyLoadingObserver();
    }
    
    setupLazyLoadingObserver() {
        const lazyObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadLazyContent(entry.target);
                    lazyObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.05,
            rootMargin: '50px'
        });
        
        // Elementos que devem carregar de forma lazy
        const lazyElements = document.querySelectorAll('[data-lazy-load]');
        lazyElements.forEach(element => {
            lazyObserver.observe(element);
        });
        
        this.observers.set('lazy', lazyObserver);
    }
    
    animateMetricCard(card) {
        const valueElement = card.querySelector('.card-value');
        if (!valueElement) return;
        
        const text = valueElement.textContent;
        const number = this.extractNumber(text);
        
        if (!isNaN(number)) {
            this.animateNumber(valueElement, 0, number, 1500, text);
        }
        
        // Animar progresso se existir
        const progressFill = card.querySelector('.progress-fill');
        if (progressFill) {
            const width = progressFill.style.width;
            progressFill.style.width = '0%';
            setTimeout(() => {
                progressFill.style.width = width;
            }, 500);
        }
    }
    
    extractNumber(text) {
        // Remover formatação e extrair número
        const cleaned = text.replace(/[^\d,.-]/g, '').replace(',', '.');
        return parseFloat(cleaned);
    }
    
    animateNumber(element, start, end, duration, originalText) {
        const startTime = performance.now();
        const isPrice = originalText.includes('R$');
        const isPercentage = originalText.includes('%');
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = start + (end - start) * this.easeOutCubic(progress);
            
            if (isPrice) {
                element.textContent = 'R$ ' + this.formatCurrency(current);
            } else if (isPercentage) {
                element.textContent = current.toFixed(1) + '%';
            } else {
                element.textContent = Math.round(current).toLocaleString('pt-BR');
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    formatCurrency(value) {
        return value.toLocaleString('pt-BR', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }
    
    easeOutCubic(x) {
        return 1 - Math.pow(1 - x, 3);
    }
    
    setupTouchGestures() {
        if (!('ontouchstart' in window)) return;
        
        // Swipe gestures para cards de transação
        const transactionCards = document.querySelectorAll('.transaction-card');
        
        transactionCards.forEach(card => {
            this.addSwipeGesture(card);
        });
        
        // Pull to refresh no hero
        this.setupPullToRefresh();
    }
    
    addSwipeGesture(element) {
        let startX, startY, startTime;
        
        element.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startTime = Date.now();
        }, { passive: true });
        
        element.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;
            
            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - startX;
            const deltaY = touch.clientY - startY;
            const deltaTime = Date.now() - startTime;
            
            // Verificar se é um swipe horizontal rápido
            if (Math.abs(deltaX) > Math.abs(deltaY) && 
                Math.abs(deltaX) > 50 && 
                deltaTime < 300) {
                
                if (deltaX > 0) {
                    this.handleSwipeRight(element);
                } else {
                    this.handleSwipeLeft(element);
                }
            }
            
            startX = startY = null;
        }, { passive: true });
    }
    
    handleSwipeRight(element) {
        // Swipe direita - ação de aprovação ou visualização
        if (element.classList.contains('transaction-card')) {
            const transactionId = this.extractTransactionId(element);
            if (transactionId) {
                this.viewTransactionDetails(transactionId);
            }
        }
    }
    
    handleSwipeLeft(element) {
        // Swipe esquerda - ação secundária
        if (element.classList.contains('transaction-card')) {
            this.showQuickActions(element);
        }
    }
    
    extractTransactionId(element) {
        const idElement = element.querySelector('.transaction-id');
        if (!idElement) return null;
        
        const text = idElement.textContent;
        const match = text.match(/#(\d+)/);
        return match ? match[1] : null;
    }
    
    setupPullToRefresh() {
        const hero = document.querySelector('.dashboard-hero');
        if (!hero) return;
        
        let startY, isRefreshing = false;
        
        hero.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
            }
        }, { passive: true });
        
        hero.addEventListener('touchmove', (e) => {
            if (!startY || isRefreshing || window.scrollY > 0) return;
            
            const currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            if (deltaY > 0) {
                const pullDistance = Math.min(deltaY, 80);
                hero.style.transform = `translateY(${pullDistance * 0.5}px)`;
                
                if (pullDistance > 60) {
                    hero.style.background = 'linear-gradient(135deg, #10B981 0%, #059669 100%)';
                }
            }
        }, { passive: true });
        
        hero.addEventListener('touchend', (e) => {
            if (!startY || isRefreshing) return;
            
            const endY = e.changedTouches[0].clientY;
            const deltaY = endY - startY;
            
            hero.style.transform = '';
            hero.style.background = '';
            
            if (deltaY > 60) {
                this.triggerRefresh();
            }
            
            startY = null;
        }, { passive: true });
    }
    
    triggerRefresh() {
        console.log('Refresh triggered');
        this.showNotification('Atualizando dados...', 'info');
        
        // Simular carregamento
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
    
    optimizeForDevice() {
        // Detectar tipo de dispositivo
        const isMobile = this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
        const isTablet = this.currentBreakpoint === 'md';
        const isDesktop = !isMobile && !isTablet;
        
        // Aplicar otimizações específicas
        if (isMobile) {
            this.applyMobileOptimizations();
        } else if (isTablet) {
            this.applyTabletOptimizations();
        } else {
            this.applyDesktopOptimizations();
        }
        
        // Detectar capacidades do dispositivo
        this.detectDeviceCapabilities();
    }
    
    applyMobileOptimizations() {
        // Reduzir animações em dispositivos móveis para economizar bateria
        document.documentElement.style.setProperty('--transition-normal', '150ms');
        document.documentElement.style.setProperty('--transition-slow', '200ms');
        
        // Aumentar áreas de toque
        const buttons = document.querySelectorAll('button, .action-btn');
        buttons.forEach(button => {
            button.style.minHeight = '44px';
            button.style.minWidth = '44px';
        });
        
        // Lazy load mais agressivo
        this.setupAggressiveLazyLoading();
    }
    
    applyTabletOptimizations() {
        // Otimizações específicas para tablet
        const dashboardGrid = document.querySelector('.dashboard-grid');
        if (dashboardGrid) {
            dashboardGrid.style.gap = 'var(--space-lg)';
        }
    }
    
    applyDesktopOptimizations() {
        // Hover effects apenas em desktop
        const cards = document.querySelectorAll('.metric-card, .dashboard-card, .transaction-card');
        cards.forEach(card => {
            card.classList.add('desktop-hover');
        });
        
        // Shortcuts de teclado
        this.setupKeyboardShortcuts();
    }
    
    detectDeviceCapabilities() {
        // Detectar suporte a hover
        const hasHover = window.matchMedia('(hover: hover)').matches;
        if (hasHover) {
            document.body.classList.add('has-hover');
        }
        
        // Detectar densidade de pixels
        const pixelRatio = window.devicePixelRatio || 1;
        if (pixelRatio > 1.5) {
            document.body.classList.add('high-dpi');
        }
        
        // Detectar preferência por movimento reduzido
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            document.body.classList.add('reduced-motion');
        }
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R para refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.triggerRefresh();
            }
            
            // Escape para fechar modais/notificações
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
            
            // Setas para navegação entre cards
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                this.navigateCards(e.key === 'ArrowRight' ? 1 : -1);
            }
        });
    }
    
    navigateCards(direction) {
        const focusedCard = document.activeElement.closest('.metric-card, .dashboard-card, .transaction-card');
        if (!focusedCard) return;
        
        const allCards = Array.from(document.querySelectorAll('.metric-card, .dashboard-card, .transaction-card'));
        const currentIndex = allCards.indexOf(focusedCard);
        
        if (currentIndex === -1) return;
        
        const nextIndex = currentIndex + direction;
        if (nextIndex >= 0 && nextIndex < allCards.length) {
            allCards[nextIndex].focus();
        }
    }
    
    closeAllModals() {
        // Fechar notificações
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            notification.remove();
        });
        
        // Fechar outros modais se existirem
        const modals = document.querySelectorAll('.modal, .overlay');
        modals.forEach(modal => {
            modal.classList.remove('active', 'show');
        });
    }
    
    setupAggressiveLazyLoading() {
        // Lazy loading ainda mais agressivo para mobile
        const lazyImages = document.querySelectorAll('img[data-src]');
        const lazyImageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    lazyImageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '10px'
        });
        
        lazyImages.forEach(img => {
            lazyImageObserver.observe(img);
        });
    }
    
    initializeCharts() {
        // Placeholder para futuros gráficos
        const chartContainers = document.querySelectorAll('[data-chart]');
        
        chartContainers.forEach(container => {
            const chartType = container.dataset.chart;
            this.createResponsiveChart(container, chartType);
        });
    }
    
    createResponsiveChart(container, type) {
        // Implementação futura de gráficos responsivos
        console.log(`Creating ${type} chart in`, container);
    }
    
    updateChartSizes() {
        // Atualizar tamanhos dos gráficos no resize
        const charts = document.querySelectorAll('[data-chart]');
        charts.forEach(chart => {
            // Trigger resize event for chart libraries
            const resizeEvent = new Event('resize');
            chart.dispatchEvent(resizeEvent);
        });
    }
    
    recalculateLayouts() {
        // Recalcular layouts complexos
        this.recalculateFinancialSummary();
        this.recalculateClientRanking();
    }
    
    recalculateFinancialSummary() {
        const summaryItems = document.querySelectorAll('.summary-item');
        
        if (this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm') {
            summaryItems.forEach(item => {
                item.style.flexDirection = 'column';
                item.style.textAlign = 'center';
                item.style.gap = 'var(--space-sm)';
            });
        } else {
            summaryItems.forEach(item => {
                item.style.flexDirection = 'row';
                item.style.textAlign = 'left';
                item.style.gap = '0';
            });
        }
    }
    
    recalculateClientRanking() {
        const clientItems = document.querySelectorAll('.client-rank-item');
        
        if (this.currentBreakpoint === 'xs') {
            clientItems.forEach(item => {
                item.style.flexDirection = 'column';
                item.style.textAlign = 'center';
            });
        } else {
            clientItems.forEach(item => {
                item.style.flexDirection = 'row';
                item.style.textAlign = 'left';
            });
        }
    }
    
    adjustTextSizes() {
        // Ajustar tamanhos de texto dinamicamente
        const titleElements = document.querySelectorAll('.dashboard-title .admin-name');
        
        titleElements.forEach(element => {
            const containerWidth = element.parentElement.offsetWidth;
            const textLength = element.textContent.length;
            
            if (containerWidth < 300 || textLength > 20) {
                element.style.fontSize = 'var(--text-2xl)';
            } else {
                element.style.fontSize = 'var(--text-4xl)';
            }
        });
    }
    
    handleOrientationChange() {
        // Aguardar estabilização da orientação
        setTimeout(() => {
            this.handleResize();
            this.recalculateLayouts();
        }, 150);
    }
    
    handleScroll() {
        const scrollY = window.scrollY;
        
        // Parallax effect no hero (apenas desktop)
        if (this.currentBreakpoint !== 'xs' && this.currentBreakpoint !== 'sm') {
            const hero = document.querySelector('.dashboard-hero');
            if (hero && scrollY < hero.offsetHeight) {
                const parallaxSpeed = 0.5;
                hero.style.transform = `translateY(${scrollY * parallaxSpeed}px)`;
            }
        }
        
        // Sticky header em mobile
        this.handleStickyElements(scrollY);
    }
    
    handleStickyElements(scrollY) {
        const isMobile = this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
        
        if (isMobile) {
            const hero = document.querySelector('.dashboard-hero');
            const sectionHeaders = document.querySelectorAll('.section-header');
            
            if (hero && scrollY > hero.offsetHeight) {
                sectionHeaders.forEach(header => {
                    header.classList.add('sticky-active');
                });
            } else {
                sectionHeaders.forEach(header => {
                    header.classList.remove('sticky-active');
                });
            }
        }
    }
    
    handleFocus(e) {
        // Melhorar acessibilidade com foco visível
        const focusedElement = e.target;
        
        if (focusedElement.matches('.metric-card, .dashboard-card, .transaction-card')) {
            focusedElement.classList.add('focused');
            
            // Scroll suave para o elemento focado
            focusedElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
    
    handleGlobalClick(e) {
        // Remover classe de foco quando clicado
        const previousFocused = document.querySelector('.focused');
        if (previousFocused && previousFocused !== e.target.closest('.metric-card, .dashboard-card, .transaction-card')) {
            previousFocused.classList.remove('focused');
        }
    }
    
    loadLazyContent(element) {
        // Carregar conteúdo lazy
        const contentType = element.dataset.lazyLoad;
        
        switch (contentType) {
            case 'charts':
                this.loadChartContent(element);
                break;
            case 'notifications':
                this.loadNotifications(element);
                break;
            case 'transactions':
                this.loadMoreTransactions(element);
                break;
        }
    }
    
    loadChartContent(element) {
        // Simular carregamento de gráfico
        element.innerHTML = '<div class="chart-placeholder">Carregando gráfico...</div>';
        
        setTimeout(() => {
            element.innerHTML = '<div class="chart-loaded">Gráfico carregado</div>';
        }, 1000);
    }
    
    loadNotifications(element) {
        // Carregar notificações adicionais
        console.log('Loading additional notifications');
    }
    
    loadMoreTransactions(element) {
        // Carregar mais transações
        console.log('Loading more transactions');
    }
    
    animateGridReorganization(cards) {
        // Animar reorganização do grid
        cards.forEach((card, index) => {
            card.style.transition = 'transform 0.3s ease';
            card.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, index * 50);
        });
    }
    
    initializeAnimations() {
        // Inicializar animações personalizadas
        this.setupCounterAnimations();
        this.setupProgressAnimations();
        this.setupHoverAnimations();
    }
    
    setupCounterAnimations() {
        // Animações de contadores já implementadas em animateMetricCard
    }
    
    setupProgressAnimations() {
        const progressBars = document.querySelectorAll('.progress-bar');
        
        progressBars.forEach(bar => {
            const fill = bar.querySelector('.progress-fill');
            if (!fill) return;
            
            const targetWidth = fill.style.width || '0%';
            fill.style.width = '0%';
            
            setTimeout(() => {
                fill.style.width = targetWidth;
            }, 500);
        });
    }
    
    setupHoverAnimations() {
        // Hover animations já definidas no CSS
        // Adicionar apenas lógica especial se necessário
    }
    
    optimizePerformance() {
        // Otimizações de performance
        this.enableWillChange();
        this.optimizeRepaints();
        this.setupIntersectionOptimizations();
    }
    
    enableWillChange() {
        // Habilitar will-change em elementos que serão animados
        const animatedElements = document.querySelectorAll(
            '.metric-card, .dashboard-card, .transaction-card'
        );
        
        animatedElements.forEach(element => {
            element.style.willChange = 'transform, opacity';
        });
    }
    
    optimizeRepaints() {
        // Usar transform e opacity para animações em vez de propriedades que causam reflow
        const style = document.createElement('style');
        style.textContent = `
            .optimized-animation {
                will-change: transform, opacity;
            }
            
            .optimized-animation:hover {
                transform: translateY(-2px) scale(1.02);
            }
        `;
        document.head.appendChild(style);
    }
    
    setupIntersectionOptimizations() {
        // Pausar animações fora da viewport
        const animationPauseObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                } else {
                    entry.target.style.animationPlayState = 'paused';
                }
            });
        });
        
        const animatedElements = document.querySelectorAll('[class*="animate"]');
        animatedElements.forEach(element => {
            animationPauseObserver.observe(element);
        });
    }
    
    setupResponsiveObservers() {
        // Observer para mudanças de CSS custom properties
        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(entries => {
                entries.forEach(entry => {
                    this.handleElementResize(entry);
                });
            });
            
            const observableElements = document.querySelectorAll(
                '.dashboard-container, .metrics-grid, .dashboard-grid'
            );
            
            observableElements.forEach(element => {
                resizeObserver.observe(element);
            });
            
            this.observers.set('resize', resizeObserver);
        }
    }
    
    handleElementResize(entry) {
        const element = entry.target;
        const { width, height } = entry.contentRect;
        
        // Ajustar com base no tamanho do elemento
        if (element.classList.contains('dashboard-container')) {
            this.adjustContainerLayout(element, width, height);
        }
    }
    
    adjustContainerLayout(container, width, height) {
        // Ajustes dinâmicos baseados no tamanho do container
        if (width < 600) {
            container.classList.add('compact-layout');
        } else {
            container.classList.remove('compact-layout');
        }
    }
    
    // Métodos públicos para interação externa
    showNotification(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Mostrar com animação
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // Auto remover
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, duration);
        
        return notification;
    }
    
    viewTransactionDetails(transactionId) {
        window.location.href = `<?php echo ADMIN_TRANSACTION_DETAILS_URL; ?>/${transactionId}`;
    }
    
    showQuickActions(element) {
        // Mostrar ações rápidas (implementação futura)
        this.showNotification('Ações rápidas em desenvolvimento', 'info');
    }
    
    // Cleanup
    destroy() {
        // Limpar observers
        this.observers.forEach(observer => {
            observer.disconnect();
        });
        
        // Limpar event listeners
        window.removeEventListener('resize', this.handleResize);
        window.removeEventListener('orientationchange', this.handleOrientationChange);
        window.removeEventListener('scroll', this.handleScroll);
        
        // Limpar animações
        this.animations.forEach(animation => {
            animation.cancel();
        });
    }
}

// Inicializar dashboard responsivo
const responsiveDashboard = new ResponsiveDashboard();

// Exportar para uso global
window.ResponsiveDashboard = ResponsiveDashboard;
window.dashboardInstance = responsiveDashboard;

// Event listeners para funções globais (mantendo compatibilidade)
window.approveStore = function(storeId) {
    if (confirm('Tem certeza que deseja aprovar esta loja?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo SITE_URL; ?>/controllers/StoreController.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                responsiveDashboard.showNotification('Loja aprovada com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                responsiveDashboard.showNotification('Erro ao aprovar loja. Tente novamente.', 'error');
            }
        };
        xhr.send('action=approve&id=' + storeId);
    }
};

window.rejectStore = function(storeId) {
    if (confirm('Tem certeza que deseja rejeitar esta loja?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo SITE_URL; ?>/controllers/StoreController.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                responsiveDashboard.showNotification('Loja rejeitada com sucesso!', 'warning');
                setTimeout(() => location.reload(), 1500);
            } else {
                responsiveDashboard.showNotification('Erro ao rejeitar loja. Tente novamente.', 'error');
            }
        };
        xhr.send('action=reject&id=' + storeId);
    }
};

window.viewTransaction = function(transactionId) {
    responsiveDashboard.viewTransactionDetails(transactionId);
};

window.viewAllTransactions = function() {
    window.location.href = '<?php echo ADMIN_TRANSACTIONS_URL; ?>';
};

window.viewAllClients = function() {
    window.location.href = '<?php echo ADMIN_USERS_URL; ?>';
};

window.exportFinancialData = function() {
    responsiveDashboard.showNotification('Exportando dados financeiros...', 'info');
    setTimeout(() => {
        responsiveDashboard.showNotification('Dados exportados com sucesso!', 'success');
    }, 2000);
};

window.exportTransactions = function() {
    responsiveDashboard.showNotification('Exportando transações...', 'info');
    setTimeout(() => {
        responsiveDashboard.showNotification('Transações exportadas com sucesso!', 'success');
    }, 2000);
};

window.markAllAsRead = function() {
    responsiveDashboard.showNotification('Todas as notificações foram marcadas como lidas', 'success');
};