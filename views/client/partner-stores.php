<?php
// views/client/partner-stores.php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

// Iniciar sessão e verificar autenticação
session_start();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

if (!AuthController::isClient()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a clientes.'));
    exit;
}

$userId = AuthController::getCurrentUserId();

// Processar filtros
$filters = [
    'categoria' => $_GET['categoria'] ?? 'todas',
    'nome' => trim($_GET['nome'] ?? ''),
    'cashback_min' => floatval($_GET['cashback_min'] ?? 0),
    'ordenar' => $_GET['ordenar'] ?? 'nome'
];

// Parâmetros de paginação
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    $db = Database::getConnection();
    
    // Query base para buscar lojas parceiras ativas
    $whereConditions = ["l.status = 'aprovado'"];
    $params = [];
    
    // Aplicar filtros
    if ($filters['categoria'] !== 'todas' && !empty($filters['categoria'])) {
        $whereConditions[] = "l.categoria = ?";
        $params[] = $filters['categoria'];
    }
    
    if (!empty($filters['nome'])) {
        $whereConditions[] = "(l.nome_fantasia LIKE ? OR l.descricao LIKE ?)";
        $params[] = '%' . $filters['nome'] . '%';
        $params[] = '%' . $filters['nome'] . '%';
    }
    
    if ($filters['cashback_min'] > 0) {
        $whereConditions[] = "l.porcentagem_cashback >= ?";
        $params[] = $filters['cashback_min'];
    }
    
    // Ordenação
    $orderBy = match($filters['ordenar']) {
        'cashback_desc' => 'l.porcentagem_cashback DESC',
        'cashback_asc' => 'l.porcentagem_cashback ASC',
        'categoria' => 'l.categoria ASC, l.nome_fantasia ASC',
        'mais_recentes' => 'l.data_criacao DESC',
        default => 'l.nome_fantasia ASC'
    };
    
    // Query principal
    $query = "
        SELECT 
            l.id,
            l.nome_fantasia,
            l.categoria,
            l.porcentagem_cashback,
            l.descricao,
            l.logo,
            l.site,
            l.telefone,
            l.endereco,
            l.cidade,
            l.estado,
            l.data_criacao,
            CASE WHEN f.loja_id IS NOT NULL THEN 1 ELSE 0 END as eh_favorita,
            COUNT(DISTINCT t.id) as total_transacoes
        FROM lojas l
        LEFT JOIN favoritos f ON l.id = f.loja_id AND f.usuario_id = ?
        LEFT JOIN transacoes_cashback t ON l.id = t.loja_id
        WHERE " . implode(' AND ', $whereConditions) . "
        GROUP BY l.id, f.loja_id
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?
    ";
    
    // Adicionar user_id no início dos params
    array_unshift($params, $userId);
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query para contar total de lojas (para paginação)
    $countParams = array_slice($params, 1, -2); // Remove user_id, limit e offset
    $countQuery = "
        SELECT COUNT(DISTINCT l.id) as total
        FROM lojas l
        WHERE " . implode(' AND ', $whereConditions);
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalLojas = $countStmt->fetchColumn();
    
    // Calcular paginação
    $totalPages = ceil($totalLojas / $limit);
    
    // Buscar categorias disponíveis
    $categoriesQuery = "SELECT DISTINCT categoria FROM lojas WHERE status = 'aprovado' ORDER BY categoria";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categorias = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Processar favoritos via AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        if ($_POST['action'] === 'toggle_favorite') {
            $lojaId = intval($_POST['loja_id']);
            
            // Verificar se já é favorito
            $checkQuery = "SELECT id FROM favoritos WHERE usuario_id = ? AND loja_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$userId, $lojaId]);
            
            if ($checkStmt->rowCount() > 0) {
                // Remover dos favoritos
                $deleteQuery = "DELETE FROM favoritos WHERE usuario_id = ? AND loja_id = ?";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute([$userId, $lojaId]);
                echo json_encode(['status' => 'removed', 'message' => 'Removido dos favoritos']);
            } else {
                // Adicionar aos favoritos
                $insertQuery = "INSERT INTO favoritos (usuario_id, loja_id, data_criacao) VALUES (?, ?, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$userId, $lojaId]);
                echo json_encode(['status' => 'added', 'message' => 'Adicionado aos favoritos']);
            }
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("Erro ao carregar lojas parceiras: " . $e->getMessage());
    $lojas = [];
    $totalLojas = 0;
    $totalPages = 0;
    $categorias = [];
}

// Função helper para gerar URL com filtros
function buildFilterUrl($newFilters = []) {
    global $filters;
    $currentFilters = array_merge($filters, $newFilters);
    $queryString = http_build_query(array_filter($currentFilters, function($value) {
        return $value !== '' && $value !== 'todas' && $value !== 'nome';
    }));
    return '?' . $queryString;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojas Parceiras - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/client.css">
    <link rel="stylesheet" href="../../assets/css/partner-stores.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php include '../components/client-header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <h1>🏪 Lojas Parceiras</h1>
                <p class="header-subtitle">
                    Descubra onde você pode ganhar cashback e economizar dinheiro
                </p>
            </div>
            
            <!-- Estatísticas rápidas -->
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $totalLojas; ?></span>
                    <span class="stat-label">Lojas Parceiras</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($categorias); ?></span>
                    <span class="stat-label">Categorias</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">até 15%</span>
                    <span class="stat-label">de Cashback</span>
                </div>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="search-box">
                    <input type="text" 
                           name="nome" 
                           placeholder="Buscar lojas..." 
                           value="<?php echo htmlspecialchars($filters['nome']); ?>"
                           class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="material-icons">search</i>
                    </button>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Categoria:</label>
                        <select name="categoria" onchange="this.form.submit()">
                            <option value="todas">Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria); ?>" 
                                        <?php echo $filters['categoria'] === $categoria ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($categoria)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Cashback mínimo:</label>
                        <select name="cashback_min" onchange="this.form.submit()">
                            <option value="0">Qualquer valor</option>
                            <option value="2" <?php echo $filters['cashback_min'] == 2 ? 'selected' : ''; ?>>2% ou mais</option>
                            <option value="5" <?php echo $filters['cashback_min'] == 5 ? 'selected' : ''; ?>>5% ou mais</option>
                            <option value="10" <?php echo $filters['cashback_min'] == 10 ? 'selected' : ''; ?>>10% ou mais</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Ordenar por:</label>
                        <select name="ordenar" onchange="this.form.submit()">
                            <option value="nome" <?php echo $filters['ordenar'] === 'nome' ? 'selected' : ''; ?>>Nome A-Z</option>
                            <option value="cashback_desc" <?php echo $filters['ordenar'] === 'cashback_desc' ? 'selected' : ''; ?>>Maior Cashback</option>
                            <option value="categoria" <?php echo $filters['ordenar'] === 'categoria' ? 'selected' : ''; ?>>Categoria</option>
                            <option value="mais_recentes" <?php echo $filters['ordenar'] === 'mais_recentes' ? 'selected' : ''; ?>>Mais Recentes</option>
                        </select>
                    </div>
                    
                    <?php if (array_filter($filters, function($v, $k) { return $v !== '' && $v !== 'todas' && $k !== 'ordenar'; }, ARRAY_FILTER_USE_BOTH)): ?>
                        <a href="?" class="clear-filters-btn">
                            <i class="material-icons">clear</i>
                            Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Grade de Lojas -->
        <div class="stores-section">
            <?php if (empty($lojas)): ?>
                <div class="empty-state">
                    <div class="empty-illustration">
                        <i class="material-icons">store</i>
                    </div>
                    <h3>Nenhuma loja encontrada</h3>
                    <p>
                        <?php if (array_filter($filters)): ?>
                            Tente ajustar os filtros para encontrar mais lojas.
                        <?php else: ?>
                            Ainda não temos lojas parceiras cadastradas. Estamos trabalhando para trazer as melhores opções para você!
                        <?php endif; ?>
                    </p>
                    <?php if (array_filter($filters)): ?>
                        <a href="?" class="btn btn-primary">Ver Todas as Lojas</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="stores-grid">
                    <?php foreach ($lojas as $loja): ?>
                        <div class="store-card" data-store-id="<?php echo $loja['id']; ?>">
                            <div class="store-card-header">
                                <div class="store-logo">
                                    <?php if (!empty($loja['logo']) && file_exists('../../uploads/store_logos/' . $loja['logo'])): ?>
                                        <img src="../../uploads/store_logos/<?php echo htmlspecialchars($loja['logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($loja['nome_fantasia']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    <div class="store-initial" <?php echo !empty($loja['logo']) ? 'style="display: none;"' : ''; ?>>
                                        <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                                    </div>
                                </div>
                                
                                <button class="favorite-btn <?php echo $loja['eh_favorita'] ? 'active' : ''; ?>" 
                                        onclick="toggleFavorite(<?php echo $loja['id']; ?>)">
                                    <i class="material-icons"><?php echo $loja['eh_favorita'] ? 'favorite' : 'favorite_border'; ?></i>
                                </button>
                            </div>
                            
                            <div class="store-info">
                                <h3 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h3>
                                
                                <div class="store-category">
                                    <i class="material-icons">category</i>
                                    <?php echo ucfirst(htmlspecialchars($loja['categoria'])); ?>
                                </div>
                                
                                <div class="cashback-info">
                                    <span class="cashback-percentage">
                                        <?php echo number_format($loja['porcentagem_cashback'], 1); ?>%
                                    </span>
                                    <span class="cashback-label">de cashback</span>
                                </div>
                                
                                <?php if (!empty($loja['descricao'])): ?>
                                    <p class="store-description">
                                        <?php echo htmlspecialchars(substr($loja['descricao'], 0, 100)); ?>
                                        <?php echo strlen($loja['descricao']) > 100 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="store-stats">
                                    <span class="stat">
                                        <i class="material-icons">receipt</i>
                                        <?php echo $loja['total_transacoes']; ?> transações
                                    </span>
                                </div>
                            </div>
                            
                            <div class="store-actions">
                                <?php if (!empty($loja['site'])): ?>
                                    <a href="<?php echo htmlspecialchars($loja['site']); ?>" 
                                       target="_blank" 
                                       class="btn btn-primary btn-sm">
                                        <i class="material-icons">launch</i>
                                        Visitar Loja
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-secondary btn-sm" onclick="showStoreDetails(<?php echo $loja['id']; ?>)">
                                    <i class="material-icons">info</i>
                                    Detalhes
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo buildFilterUrl(['page' => $page - 1]); ?>" class="pagination-btn">
                                <i class="material-icons">chevron_left</i>
                                Anterior
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-info">
                            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                        </div>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo buildFilterUrl(['page' => $page + 1]); ?>" class="pagination-btn">
                                Próxima
                                <i class="material-icons">chevron_right</i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Detalhes da Loja -->
    <div id="storeDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Loja</h3>
                <button class="modal-close" onclick="closeStoreDetails()">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body" id="storeDetailsContent">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Função para alternar favoritos
        async function toggleFavorite(lojaId) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_favorite&loja_id=${lojaId}`
                });
                
                const result = await response.json();
                
                if (result.status === 'added' || result.status === 'removed') {
                    const btn = document.querySelector(`[data-store-id="${lojaId}"] .favorite-btn`);
                    const icon = btn.querySelector('i');
                    
                    if (result.status === 'added') {
                        btn.classList.add('active');
                        icon.textContent = 'favorite';
                    } else {
                        btn.classList.remove('active');
                        icon.textContent = 'favorite_border';
                    }
                    
                    showToast(result.message, 'success');
                }
            } catch (error) {
                console.error('Erro ao alterar favorito:', error);
                showToast('Erro ao alterar favorito. Tente novamente.', 'error');
            }
        }
        
        // Função para mostrar detalhes da loja
        function showStoreDetails(lojaId) {
            // Implementar carregamento de detalhes via AJAX
            const modal = document.getElementById('storeDetailsModal');
            modal.style.display = 'block';
            
            // Aqui você pode fazer uma requisição AJAX para carregar os detalhes completos da loja
            document.getElementById('storeDetailsContent').innerHTML = '<div class="loading">Carregando...</div>';
        }
        
        // Função para fechar modal
        function closeStoreDetails() {
            document.getElementById('storeDetailsModal').style.display = 'none';
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            const modal = document.getElementById('storeDetailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Função para mostrar toast
        function showToast(message, type) {
            // Implementar sistema de toast/notificação
            console.log(`${type}: ${message}`);
        }
    </script>
</body>
</html>