<?php
// views/client/partner-stores.php
require_once '../../config/constants.php';
require_once '../../config/database.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header('Location: ' . LOGIN_URL);
    exit;
}

$userId = $_SESSION['user_id'];

// Definir valores padrão para filtros e paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$filters = [];

// Processar filtros
if (isset($_GET['filtrar'])) {
    if (!empty($_GET['categoria']) && $_GET['categoria'] != 'todas') {
        $filters['categoria'] = $_GET['categoria'];
    }
    if (!empty($_GET['nome'])) {
        $filters['nome'] = trim($_GET['nome']);
    }
    if (!empty($_GET['ordenar'])) {
        $filters['ordenar'] = $_GET['ordenar'];
    }
}

try {
    $db = Database::getConnection();
    
    // Query principal para buscar lojas (sem duplicatas)
    $whereConditions = ["l.status = 'aprovado'"];
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['categoria'])) {
        $whereConditions[] = "l.categoria = :categoria";
        $params[':categoria'] = $filters['categoria'];
    }
    
    if (!empty($filters['nome'])) {
        $whereConditions[] = "l.nome_fantasia LIKE :nome";
        $params[':nome'] = '%' . $filters['nome'] . '%';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Definir ordenação
    $orderBy = "l.nome_fantasia ASC";
    if (!empty($filters['ordenar'])) {
        switch ($filters['ordenar']) {
            case 'cashback_desc':
                $orderBy = "l.porcentagem_cashback DESC, l.nome_fantasia ASC";
                break;
            case 'cashback_asc':
                $orderBy = "l.porcentagem_cashback ASC, l.nome_fantasia ASC";
                break;
            case 'recente':
                $orderBy = "l.data_criacao DESC";
                break;
            default:
                $orderBy = "l.nome_fantasia ASC";
        }
    }
    
    // Contar total de lojas
    $countQuery = "
        SELECT COUNT(DISTINCT l.id) as total
        FROM lojas l  
        WHERE $whereClause
    ";
    
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    
    // Buscar lojas com paginação
    $offset = ($page - 1) * $perPage;
    
    $query = "
        SELECT DISTINCT
            l.id,
            l.nome_fantasia,
            l.categoria,
            l.porcentagem_cashback,
            l.logo,
            l.descricao,
            l.data_criacao,
            CASE WHEN lf.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
        FROM lojas l
        LEFT JOIN lojas_favoritas lf ON lf.loja_id = l.id AND lf.usuario_id = :userId
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular paginação
    $totalPages = ceil($totalCount / $perPage);
    
    // Buscar categorias disponíveis
    $categoriesQuery = "
        SELECT DISTINCT categoria 
        FROM lojas 
        WHERE status = 'aprovado' AND categoria IS NOT NULL AND categoria != '' 
        ORDER BY categoria
    ";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasError = false;
    $errorMessage = '';
    
} catch (PDOException $e) {
    error_log('Erro ao carregar lojas parceiras: ' . $e->getMessage());
    $hasError = true;
    $errorMessage = 'Erro ao carregar lojas parceiras. Tente novamente.';
    $stores = [];
    $categories = [];
    $totalCount = 0;
    $totalPages = 0;
}

// Recuperar mensagem da sessão
$favoriteMessage = '';
$favoriteMessageType = '';
if (isset($_SESSION['favorite_message'])) {
    $favoriteMessage = $_SESSION['favorite_message'];
    $favoriteMessageType = $_SESSION['favorite_message_type'];
    unset($_SESSION['favorite_message']);
    unset($_SESSION['favorite_message_type']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojas Parceiras - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/client/partner-stores.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../components/navbar.php'; ?>

    <div class="page-wrapper" style="margin-top: 80px;">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1><i class="fas fa-store"></i> Lojas Parceiras</h1>
                        <p>Descubra onde você pode ganhar cashback em suas compras</p>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $totalCount; ?></span>
                                <span class="stat-label">Lojas Parceiras</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($categories); ?></span>
                                <span class="stat-label">Categorias</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if (!empty($favoriteMessage)): ?>
                <div class="toast toast-<?php echo htmlspecialchars($favoriteMessageType); ?>">
                    <i class="fas <?php echo ($favoriteMessageType == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($favoriteMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($hasError): ?>
                <div class="error-container">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Ops! Algo deu errado</h3>
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    <button onclick="window.location.reload()" class="retry-btn">
                        <i class="fas fa-redo"></i> Tentar Novamente
                    </button>
                </div>
            <?php else: ?>
                <!-- Filtros e Busca -->
                <div class="filters-section">
                    <form method="GET" class="filters-form" id="filtersForm">
                        <div class="filters-row">
                            <!-- Busca por nome -->
                            <div class="filter-group">
                                <label for="nome">Buscar loja:</label>
                                <div class="search-input-group">
                                    <i class="fas fa-search"></i>
                                    <input type="text" 
                                           id="nome" 
                                           name="nome" 
                                           placeholder="Digite o nome da loja..."
                                           value="<?php echo htmlspecialchars($_GET['nome'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Filtro por categoria -->
                            <div class="filter-group">
                                <label for="categoria">Categoria:</label>
                                <select id="categoria" name="categoria">
                                    <option value="todas">Todas as categorias</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>" 
                                                <?php echo (($_GET['categoria'] ?? '') === $category) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Ordenação -->
                            <div class="filter-group">
                                <label for="ordenar">Ordenar por:</label>
                                <select id="ordenar" name="ordenar">
                                    <option value="nome" <?php echo (($_GET['ordenar'] ?? '') === 'nome') ? 'selected' : ''; ?>>
                                        Nome A-Z
                                    </option>
                                    <option value="cashback_desc" <?php echo (($_GET['ordenar'] ?? '') === 'cashback_desc') ? 'selected' : ''; ?>>
                                        Maior Cashback
                                    </option>
                                    <option value="cashback_asc" <?php echo (($_GET['ordenar'] ?? '') === 'cashback_asc') ? 'selected' : ''; ?>>
                                        Menor Cashback
                                    </option>
                                    <option value="recente" <?php echo (($_GET['ordenar'] ?? '') === 'recente') ? 'selected' : ''; ?>>
                                        Mais Recentes
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Botões -->
                            <div class="filter-actions">
                                <button type="submit" name="filtrar" class="filter-btn">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="clear-btn">
                                    <i class="fas fa-times"></i> Limpar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Lista de Lojas -->
                <?php if (empty($stores)): ?>
                    <div class="empty-state">
                        <div class="empty-illustration">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Nenhuma loja encontrada</h3>
                        <p>Tente ajustar os filtros ou buscar por outro termo.</p>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="clear-filters-btn">
                            <i class="fas fa-refresh"></i> Ver todas as lojas
                        </a>
                    </div>
                <?php else: ?>
                    <div class="stores-section">
                        <div class="stores-header">
                            <h2>
                                <?php if (!empty($filters)): ?>
                                    Resultados da busca (<?php echo $totalCount; ?> lojas)
                                <?php else: ?>
                                    Todas as Lojas Parceiras (<?php echo $totalCount; ?>)
                                <?php endif; ?>
                            </h2>
                        </div>
                        
                        <div class="stores-grid">
                            <?php foreach ($stores as $store): ?>
                                <div class="store-card">
                                    <div class="store-header">
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
                                        
                                        <div class="store-actions">
                                            <form method="POST" action="../../controllers/client_actions.php" class="favorite-form">
                                                <input type="hidden" name="action" value="toggle_favorite">
                                                <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                                <input type="hidden" name="is_favorite" value="<?php echo $store['is_favorite'] ? '0' : '1'; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                                <button type="submit" class="favorite-btn <?php echo $store['is_favorite'] ? 'favorited' : ''; ?>"
                                                        title="<?php echo $store['is_favorite'] ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="store-info">
                                        <h3 class="store-name"><?php echo htmlspecialchars($store['nome_fantasia']); ?></h3>
                                        
                                        <div class="store-meta">
                                            <span class="store-category">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($store['categoria'] ?: 'Geral'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="cashback-info">
                                            <div class="cashback-badge">
                                                <span class="cashback-percentage"><?php echo number_format($store['porcentagem_cashback'], 1); ?>%</span>
                                                <span class="cashback-label">de cashback</span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($store['descricao'])): ?>
                                            <p class="store-description">
                                                <?php echo htmlspecialchars(substr($store['descricao'], 0, 100)); ?>
                                                <?php if (strlen($store['descricao']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="store-actions-bottom">
                                        <a href="<?php echo SITE_URL; ?>/lojas/detalhes/<?php echo $store['id']; ?>" class="view-store-btn">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php
                                // Manter filtros na paginação
                                $queryParams = $_GET;
                                unset($queryParams['page']);
                                $baseUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($queryParams);
                                $baseUrl .= empty($queryParams) ? 'page=' : '&page=';
                                ?>
                                
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo $baseUrl . '1'; ?>" class="page-link first">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="<?php echo $baseUrl . ($page - 1); ?>" class="page-link prev">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="<?php echo $baseUrl . $i; ?>" 
                                       class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo $baseUrl . ($page + 1); ?>" class="page-link next">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="<?php echo $baseUrl . $totalPages; ?>" class="page-link last">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/views/client/partner-stores.js"></script>
</body>
</html>