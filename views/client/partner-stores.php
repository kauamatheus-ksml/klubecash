<?php
// views/client/partner-stores.php
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
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: ../auth/login.php?error=acesso_restrito");
    exit;
}

// Obter dados do usuário
$userId = $_SESSION['user_id'];

// Definir valores padrão para filtros e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [];

// Processar filtros se submetidos
if (isset($_GET['filtrar'])) {
    if (!empty($_GET['categoria']) && $_GET['categoria'] != 'todas') {
        $filters['categoria'] = $_GET['categoria'];
    }
    
    if (!empty($_GET['nome'])) {
        $filters['nome'] = $_GET['nome'];
    }
    
    if (!empty($_GET['cashback_min'])) {
        $filters['cashback_min'] = $_GET['cashback_min'];
    }
}

// Obter dados das lojas parceiras
$result = ClientController::getPartnerStores($userId, $filters, $page);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição
$storesData = $hasError ? [] : $result['data'];

// Processar adição/remoção de favoritos
$favoriteMessage = '';
if (isset($_POST['toggle_favorite'])) {
    $storeId = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
    $isFavorite = isset($_POST['is_favorite']) ? (int)$_POST['is_favorite'] : 0;
    
    $favoriteResult = ClientController::toggleFavoriteStore($userId, $storeId, !$isFavorite);
    $favoriteMessage = $favoriteResult['message'];
    
    // Recarregar dados após alteração de favorito
    $result = ClientController::getPartnerStores($userId, $filters, $page);
    $hasError = !$result['status'];
    $storesData = $hasError ? [] : $result['data'];
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
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Cabeçalho da Página -->
        <div class="page-header">
            <div>
                <h1>Lojas Parceiras</h1>
                <p>Conheça as lojas que oferecem cashback no Klube Cash</p>
            </div>
        </div>
        
        <?php if (!empty($favoriteMessage)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($favoriteMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Filtros -->
        <div class="card filters-section">
            <h3 style="margin-bottom: 15px; font-size: 16px; color: var(--dark-gray);">Filtros</h3>
            <form action="" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label" for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="todas">Todas as Categorias</option>
                        <?php if (!empty($storesData['categorias'])): ?>
                            <?php foreach ($storesData['categorias'] as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo (isset($filters['categoria']) && $filters['categoria'] == $categoria) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="nome">Nome da Loja</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo $filters['nome'] ?? ''; ?>" placeholder="Buscar pelo nome">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="cashback_min">Cashback Mínimo (%)</label>
                    <input type="number" id="cashback_min" name="cashback_min" class="form-control" value="<?php echo $filters['cashback_min'] ?? ''; ?>" min="0" step="0.5" placeholder="Ex: 3.5">
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" name="filtrar" value="1" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
        
        <!-- Estatísticas -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-title">Total de Lojas</div>
                <div class="summary-card-value"><?php echo $storesData['estatisticas']['total_lojas'] ?? 0; ?></div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Média de Cashback</div>
                <div class="summary-card-value"><?php echo number_format($storesData['estatisticas']['media_cashback'] ?? 0, 2); ?>%</div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-title">Maior Cashback</div>
                <div class="summary-card-value"><?php echo number_format($storesData['estatisticas']['maior_cashback'] ?? 0, 2); ?>%</div>
            </div>
        </div>
        
        <!-- Lista de Lojas -->
        <div class="stores-grid">
            <?php if (empty($storesData['lojas'])): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 30px;">
                    <p>Nenhuma loja encontrada com os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($storesData['lojas'] as $loja): ?>
                    <div class="store-card">
                        <div class="store-header">
                            <div class="store-logo">
                                <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                            </div>
                            <h3 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h3>
                            <p class="store-category">
                                <span class="badge badge-primary">
                                    <?php echo htmlspecialchars($loja['categoria']); ?>
                                </span>
                            </p>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="store_id" value="<?php echo $loja['id']; ?>">
                                <input type="hidden" name="is_favorite" value="<?php echo $loja['is_favorite'] ?? 0; ?>">
                                <button type="submit" name="toggle_favorite" class="store-favorite <?php echo (!empty($loja['is_favorite'])) ? 'active' : ''; ?>">
                                    <?php if (!empty($loja['is_favorite'])): ?>
                                        &#10084;
                                    <?php else: ?>
                                        &#9825;
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                        
                        <div class="store-body">
                            <p class="store-cashback">
                                Cashback: <span><?php echo number_format($loja['porcentagem_cashback'], 2); ?>%</span>
                            </p>
                            
                            <?php if (!empty($loja['cashback_recebido']) || !empty($loja['compras_realizadas'])): ?>
                                <div class="store-info">
                                    <span>Compras: <?php echo $loja['compras_realizadas'] ?? 0; ?></span>
                                    <span>Cashback recebido: R$ <?php echo number_format($loja['cashback_recebido'] ?? 0, 2, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($loja['website'])): ?>
                                <p style="font-size: 14px; color: var(--medium-gray);">
                                    Website: <a href="<?php echo htmlspecialchars($loja['website']); ?>" target="_blank" style="color: var(--primary-color);"><?php echo htmlspecialchars($loja['website']); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="store-footer">
                            <a href="#" class="store-button" onclick="verDetalhes(<?php echo $loja['id']; ?>)">Ver Detalhes</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Paginação -->
        <?php if (!empty($storesData['paginacao']) && $storesData['paginacao']['total_paginas'] > 1): ?>
            <ul class="pagination">
                <?php 
                $currentPage = $storesData['paginacao']['pagina_atual'];
                $totalPages = $storesData['paginacao']['total_paginas'];
                
                // Construir parâmetros da URL
                $urlParams = [];
                foreach ($filters as $key => $value) {
                    $urlParams[] = "$key=" . urlencode($value);
                }
                $urlParams[] = "filtrar=1";
                $queryString = !empty($urlParams) ? '&' . implode('&', $urlParams) : '';
                
                // Anterior
                if ($currentPage > 1): 
                ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $currentPage - 1 . $queryString; ?>" class="pagination-link">
                            &laquo;
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php 
                // Páginas
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $start + 4);
                
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $i . $queryString; ?>" class="pagination-link <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php 
                // Próximo
                if ($currentPage < $totalPages): 
                ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $currentPage + 1 . $queryString; ?>" class="pagination-link">
                            &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal de Detalhes da Loja (será implementado via JavaScript) -->
    <div id="storeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background-color: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%; position: relative;">
            <button onclick="closeModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            <h3 style="margin-bottom: 20px; font-size: 22px;">Detalhes da Loja</h3>
            <div id="storeDetails">
                <!-- Será preenchido via JavaScript -->
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
    
    <script>
        // Função para exibir detalhes da loja
        function verDetalhes(storeId) {
            // Em um cenário real, faríamos uma requisição AJAX para buscar os detalhes da loja
            // Aqui apenas simularemos isso exibindo o modal
            
            document.getElementById('storeModal').style.display = 'flex';
            
            // Simular carregamento de dados (em produção, faria uma chamada AJAX)
            document.getElementById('storeDetails').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <p>Carregando detalhes da loja #${storeId}...</p>
                    <p>Esta funcionalidade estará disponível em breve!</p>
                </div>
            `;
        }
        
        // Função para fechar modal
        function closeModal() {
            document.getElementById('storeModal').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('storeModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>