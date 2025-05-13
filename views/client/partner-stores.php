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
    
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --danger-color: #F44336;
            --warning-color: #FFC107;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
        }
        
        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Cabeçalho da página */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: var(--medium-gray);
            font-size: 16px;
        }
        
        /* Card para o conteúdo */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        /* Seção de filtros */
        .filters-section {
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        /* Estatísticas */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .summary-card {
            background-color: var(--primary-light);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-card-title {
            font-size: 14px;
            color: var(--medium-gray);
            margin-bottom: 5px;
        }
        
        .summary-card-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Grade de lojas */
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .store-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
            position: relative;
        }
        
        .store-card:hover {
            transform: translateY(-5px);
        }
        
        .store-header {
            padding: 15px;
            background-color: var(--primary-light);
            position: relative;
        }
        
        .store-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .store-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }
        
        .store-category {
            font-size: 14px;
            color: var(--medium-gray);
        }
        
        .store-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }
        
        .store-favorite.active {
            color: #FF4D4D;
        }
        
        .store-body {
            padding: 15px;
        }
        
        .store-cashback {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .store-cashback span {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .store-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--medium-gray);
            margin-bottom: 10px;
        }
        
        .store-footer {
            padding: 15px;
            background-color: #F9F9F9;
            text-align: center;
        }
        
        .store-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .store-button:hover {
            background-color: #E06E00;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        
        .pagination-item {
            margin: 0 5px;
        }
        
        .pagination-link {
            display: block;
            padding: 8px 12px;
            background-color: var(--white);
            border-radius: 5px;
            color: var(--dark-gray);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid #eee;
        }
        
        .pagination-link:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .pagination-link.active {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        /* Botões */
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #E6F7E6;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .stores-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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