<?php
// views/client/partner-stores.php
// VERSÃO COMPLETAMENTE CORRIGIDA - HUB DE LOJAS PARCEIRAS

// Definir o menu ativo
$activeMenu = 'lojas';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ClientController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: ../auth/login.php?error=acesso_restrito");
    exit;
}

// Obter dados do usuário
$userId = $_SESSION['user_id'];

// Processar adição/remoção de favoritos com Pattern PRG (Post-Redirect-Get)
if (isset($_POST['toggle_favorite'])) {
    $storeId = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
    $isFavorite = isset($_POST['is_favorite']) ? (int)$_POST['is_favorite'] : 0;

    $favoriteResult = ClientController::toggleFavoriteStore($userId, $storeId, !$isFavorite);

    // Armazenar mensagem na sessão
    $_SESSION['favorite_message'] = $favoriteResult['message'];
    $_SESSION['favorite_message_type'] = $favoriteResult['status'] ? 'success' : 'error';

    // Redirecionar para evitar reenvio
    $currentQueryString = '';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $params = [];
        parse_str($_SERVER['QUERY_STRING'], $params);
        $currentQueryString = http_build_query($params);
    }
    
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . (!empty($currentQueryString) ? '?' . $currentQueryString : ''));
    exit;
}

// Recuperar e limpar mensagens da sessão
$favoriteMessage = '';
$favoriteMessageType = '';
if (isset($_SESSION['favorite_message'])) {
    $favoriteMessage = $_SESSION['favorite_message'];
    $favoriteMessageType = $_SESSION['favorite_message_type'];
    unset($_SESSION['favorite_message']);
    unset($_SESSION['favorite_message_type']);
}

// Definir valores padrão para filtros e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [];

// Processar filtros se submetidos
if (isset($_GET['filtrar'])) {
    if (!empty($_GET['categoria']) && $_GET['categoria'] != 'todas') {
        $filters['categoria'] = $_GET['categoria'];
    }

    if (!empty($_GET['nome'])) {
        $filters['nome'] = trim($_GET['nome']);
    }

    if (!empty($_GET['cashback_min'])) {
        $filters['cashback_min'] = (float)$_GET['cashback_min'];
    }

    if (!empty($_GET['ordenar']) && $_GET['ordenar'] != 'nome') {
        $filters['ordenar'] = $_GET['ordenar'];
    }

    if (!empty($_GET['favoritas']) && $_GET['favoritas'] == '1') {
        $filters['favoritas'] = true;
    }
}

try {
    // Obter todas as lojas parceiras (SEM DADOS DE SALDO)
    $result = ClientController::getPartnerStoresHub($userId, $filters, $page);

    // Verificar resultado
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição
    if (!$hasError) {
        $stores = $result['data']['lojas'] ?? [];
        $pagination = $result['data']['paginacao'] ?? [];
        $categorias = $result['data']['categorias'] ?? [];
        $estatisticas = $result['data']['estatisticas'] ?? [];
    } else {
        $stores = [];
        $pagination = [];
        $categorias = [];
        $estatisticas = [];
    }

} catch (Exception $e) {
    $hasError = true;
    $errorMessage = 'Erro ao carregar lojas parceiras. Tente novamente.';
    $stores = [];
    $pagination = [];
    $categorias = [];
    $estatisticas = [];
    error_log('Erro na página de lojas parceiras: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojas Parceiras - Klube Cash</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/client.css">
    <link rel="stylesheet" href="../../assets/css/partner-stores.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Meta Tags -->
    <meta name="description" content="Descubra todas as lojas parceiras do Klube Cash e ganhe cashback em suas compras.">
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <!-- Header/Navbar -->
    <?php include '../components/header.php'; ?>

    <div class="main-container">
        <!-- Sidebar -->
        <?php include '../components/sidebar.php'; ?>

        <!-- Conteúdo Principal -->
        <div class="content-area">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?php echo CLIENT_DASHBOARD_URL; ?>">
                    <i class="fas fa-home"></i> Início
                </a>
                <span class="separator">/</span>
                <span class="current">Lojas Parceiras</span>
            </div>

            <!-- Mensagem de Feedback -->
            <?php if (!empty($favoriteMessage)): ?>
                <div class="alert alert-<?php echo $favoriteMessageType; ?> alert-dismissible">
                    <i class="fas fa-<?php echo $favoriteMessageType == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($favoriteMessage); ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Header da Página -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1 class="page-title">
                        <i class="fas fa-store"></i>
                        Lojas Parceiras
                    </h1>
                    <p class="page-subtitle">
                        Descubra todas as lojas onde você pode ganhar cashback com suas compras
                    </p>
                </div>

                <!-- Estatísticas Rápidas -->
                <?php if (!empty($estatisticas) && !$hasError): ?>
                <div class="stats-overview">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($estatisticas['total_lojas'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Lojas Parceiras</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($estatisticas['total_categorias'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Categorias</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($estatisticas['cashback_medio'], 1, ',', '.'); ?>%</div>
                        <div class="stat-label">Cashback Médio</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($estatisticas['minhas_favoritas'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Minhas Favoritas</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Filtros e Busca -->
            <div class="filters-section">
                <form method="GET" class="filters-form" id="filtrosForm">
                    <div class="filters-row">
                        <!-- Busca por Nome -->
                        <div class="filter-group">
                            <label for="nome">Buscar Loja:</label>
                            <div class="search-input-group">
                                <input type="text" 
                                       id="nome" 
                                       name="nome" 
                                       placeholder="Digite o nome da loja..." 
                                       value="<?php echo htmlspecialchars($_GET['nome'] ?? ''); ?>">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>

                        <!-- Filtro por Categoria -->
                        <div class="filter-group">
                            <label for="categoria">Categoria:</label>
                            <select id="categoria" name="categoria">
                                <option value="todas">Todas as Categorias</option>
                                <?php if (!empty($categorias)): ?>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['categoria']); ?>" 
                                                <?php echo (($_GET['categoria'] ?? '') == $categoria['categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['categoria']); ?>
                                            (<?php echo $categoria['total']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Filtro por Cashback Mínimo -->
                        <div class="filter-group">
                            <label for="cashback_min">Cashback Mínimo:</label>
                            <select id="cashback_min" name="cashback_min">
                                <option value="">Qualquer %</option>
                                <option value="1" <?php echo (($_GET['cashback_min'] ?? '') == '1') ? 'selected' : ''; ?>>1% ou mais</option>
                                <option value="2" <?php echo (($_GET['cashback_min'] ?? '') == '2') ? 'selected' : ''; ?>>2% ou mais</option>
                                <option value="3" <?php echo (($_GET['cashback_min'] ?? '') == '3') ? 'selected' : ''; ?>>3% ou mais</option>
                                <option value="5" <?php echo (($_GET['cashback_min'] ?? '') == '5') ? 'selected' : ''; ?>>5% ou mais</option>
                            </select>
                        </div>

                        <!-- Ordenação -->
                        <div class="filter-group">
                            <label for="ordenar">Ordenar por:</label>
                            <select id="ordenar" name="ordenar">
                                <option value="nome" <?php echo (($_GET['ordenar'] ?? '') == 'nome') ? 'selected' : ''; ?>>Nome A-Z</option>
                                <option value="cashback_desc" <?php echo (($_GET['ordenar'] ?? '') == 'cashback_desc') ? 'selected' : ''; ?>>Maior Cashback</option>
                                <option value="categoria" <?php echo (($_GET['ordenar'] ?? '') == 'categoria') ? 'selected' : ''; ?>>Categoria</option>
                                <option value="recentes" <?php echo (($_GET['ordenar'] ?? '') == 'recentes') ? 'selected' : ''; ?>>Mais Recentes</option>
                            </select>
                        </div>
                    </div>

                    <div class="filters-actions">
                        <div class="filter-toggles">
                            <label class="toggle-filter">
                                <input type="checkbox" 
                                       name="favoritas" 
                                       value="1" 
                                       <?php echo (($_GET['favoritas'] ?? '') == '1') ? 'checked' : ''; ?>>
                                <span class="toggle-text">
                                    <i class="fas fa-heart"></i> Apenas Favoritas
                                </span>
                            </label>
                        </div>

                        <div class="filter-buttons">
                            <button type="submit" name="filtrar" value="1" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="<?php echo CLIENT_STORES_URL; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Resultados -->
            <div class="results-section">
                <?php if ($hasError): ?>
                    <!-- Estado de Erro -->
                    <div class="error-state">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Ops! Algo deu errado</h3>
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                        <button onclick="window.location.reload()" class="btn btn-primary">
                            <i class="fas fa-refresh"></i> Tentar Novamente
                        </button>
                    </div>

                <?php elseif (empty($stores)): ?>
                    <!-- Estado Vazio -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-store-slash"></i>
                        </div>
                        <h3>Nenhuma loja encontrada</h3>
                        <p>
                            <?php if (!empty($filters)): ?>
                                Não encontramos lojas que correspondam aos seus filtros. 
                                Que tal ajustar os critérios de busca?
                            <?php else: ?>
                                Parece que ainda não temos lojas parceiras cadastradas. 
                                Estamos trabalhando para trazer mais opções!
                            <?php endif; ?>
                        </p>
                        <div class="empty-actions">
                            <?php if (!empty($filters)): ?>
                                <a href="<?php echo CLIENT_STORES_URL; ?>" class="btn btn-primary">
                                    <i class="fas fa-times"></i> Limpar Filtros
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo CLIENT_DASHBOARD_URL; ?>" class="btn btn-outline">
                                <i class="fas fa-home"></i> Voltar ao Início
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Lista de Lojas -->
                    <div class="results-header">
                        <div class="results-info">
                            <span class="results-count">
                                <?php 
                                if (!empty($pagination)) {
                                    $inicio = (($pagination['pagina_atual'] - 1) * $pagination['por_pagina']) + 1;
                                    $fim = min($pagination['pagina_atual'] * $pagination['por_pagina'], $pagination['total']);
                                    echo "Mostrando {$inicio}-{$fim} de {$pagination['total']} lojas";
                                } else {
                                    echo count($stores) . " loja(s) encontrada(s)";
                                }
                                ?>
                            </span>
                        </div>

                        <div class="view-toggles">
                            <button type="button" class="view-toggle active" data-view="grid" title="Visualização em Grade">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button" class="view-toggle" data-view="list" title="Visualização em Lista">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Grid de Lojas -->
                    <div class="stores-grid" id="storesContainer">
                        <?php foreach ($stores as $store): ?>
                            <div class="store-card" data-store-id="<?php echo $store['id']; ?>">
                                <!-- Header do Card -->
                                <div class="store-card-header">
                                    <!-- Logo da Loja -->
                                    <div class="store-logo">
                                        <?php if (!empty($store['logo']) && file_exists('../../uploads/store_logos/' . $store['logo'])): ?>
                                            <img src="../../uploads/store_logos/<?php echo htmlspecialchars($store['logo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($store['nome_fantasia']); ?>"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="store-initial" style="display: none;">
                                                <?php echo strtoupper(substr($store['nome_fantasia'], 0, 1)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="store-initial">
                                                <?php echo strtoupper(substr($store['nome_fantasia'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Botão Favorito -->
                                    <form method="POST" class="favorite-form" style="display: inline;">
                                        <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                        <input type="hidden" name="is_favorite" value="<?php echo $store['is_favorite'] ? 1 : 0; ?>">
                                        <button type="submit" 
                                                name="toggle_favorite" 
                                                class="favorite-btn <?php echo $store['is_favorite'] ? 'active' : ''; ?>"
                                                title="<?php echo $store['is_favorite'] ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Informações da Loja -->
                                <div class="store-info">
                                    <h3 class="store-name"><?php echo htmlspecialchars($store['nome_fantasia']); ?></h3>
                                    
                                    <div class="store-category">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($store['categoria'] ?? 'Categoria não informada'); ?>
                                    </div>

                                    <div class="store-cashback">
                                        <span class="cashback-label">Cashback:</span>
                                        <span class="cashback-value">
                                            <?php echo number_format($store['porcentagem_cashback'], 1, ',', '.'); ?>%
                                        </span>
                                    </div>

                                    <?php if (!empty($store['descricao'])): ?>
                                        <div class="store-description">
                                            <?php 
                                            $descricao = htmlspecialchars($store['descricao']);
                                            echo strlen($descricao) > 100 ? substr($descricao, 0, 100) . '...' : $descricao;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Ações do Card -->
                                <div class="store-actions">
                                    <a href="../../views/stores/details.php?id=<?php echo $store['id']; ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-info-circle"></i> Ver Detalhes
                                    </a>
                                    
                                    <?php if (!empty($store['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($store['website']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Visitar Loja
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Badges -->
                                <div class="store-badges">
                                    <?php if ($store['is_favorite']): ?>
                                        <span class="badge badge-favorite">
                                            <i class="fas fa-heart"></i> Favorita
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($store['is_new']) && $store['is_new']): ?>
                                        <span class="badge badge-new">
                                            <i class="fas fa-star"></i> Nova
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($store['porcentagem_cashback'] >= 5): ?>
                                        <span class="badge badge-high-cashback">
                                            <i class="fas fa-fire"></i> Alto Cashback
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginação -->
                    <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                        <div class="pagination-wrapper">
                            <nav class="pagination">
                                <!-- Primeira Página -->
                                <?php if ($pagination['pagina_atual'] > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                                       class="pagination-link pagination-first">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- Página Anterior -->
                                <?php if ($pagination['pagina_atual'] > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['pagina_atual'] - 1])); ?>" 
                                       class="pagination-link pagination-prev">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- Páginas -->
                                <?php
                                $startPage = max(1, $pagination['pagina_atual'] - 2);
                                $endPage = min($pagination['total_paginas'], $pagination['pagina_atual'] + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="pagination-link <?php echo ($i == $pagination['pagina_atual']) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Próxima Página -->
                                <?php if ($pagination['pagina_atual'] < $pagination['total_paginas']): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['pagina_atual'] + 1])); ?>" 
                                       class="pagination-link pagination-next">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- Última Página -->
                                <?php if ($pagination['pagina_atual'] < $pagination['total_paginas']): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['total_paginas']])); ?>" 
                                       class="pagination-link pagination-last">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>

                            <div class="pagination-info">
                                Página <?php echo $pagination['pagina_atual']; ?> de <?php echo $pagination['total_paginas']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/main.js"></script>
    <script>
        // Script para alternar visualização
        document.addEventListener('DOMContentLoaded', function() {
            const viewToggles = document.querySelectorAll('.view-toggle');
            const storesContainer = document.getElementById('storesContainer');

            viewToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const view = this.getAttribute('data-view');
                    
                    // Atualizar botões ativos
                    viewToggles.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Atualizar visualização
                    if (view === 'list') {
                        storesContainer.classList.add('list-view');
                    } else {
                        storesContainer.classList.remove('list-view');
                    }
                });
            });

            // Busca em tempo real
            const searchInput = document.getElementById('nome');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        document.getElementById('filtrosForm').submit();
                    }
                }, 500);
            });
        });
    </script>
</body>
</html>