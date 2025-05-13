<?php
// views/admin/stores.php
// Definir o menu ativo na sidebar
$activeMenu = 'lojas';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: /views/auth/login.php?error=acesso_restrito");
    exit;
}

// Obter parâmetros de paginação e filtros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Preparar filtros
$filters = [];
if (!empty($search)) {
    $filters['busca'] = $search;
}
if (!empty($status)) {
    $filters['status'] = $status;
}
if (!empty($category)) {
    $filters['categoria'] = $category;
}

try {
    // Obter dados das lojas
    $result = AdminController::manageStores($filters, $page);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $stores = $hasError ? [] : $result['data']['lojas'];
    $statistics = $hasError ? [] : $result['data']['estatisticas'];
    $categories = $hasError ? [] : $result['data']['categorias'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $stores = [];
    $statistics = [];
    $pagination = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Lojas - Klube Cash</title>
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
        .main-content {
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }
        
        /* Wrapper da página */
        .page-wrapper {
            background-color: #FFF9F2;
            min-height: 100vh;
            padding: 30px;
        }
        
        /* Cabeçalho */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 24px;
            color: var(--dark-gray);
            font-weight: 600;
        }
        
        /* Barra de busca e ações */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filters-form {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        
        .search-bar {
            position: relative;
            width: 300px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #FFD9B3;
            border-radius: 30px;
            background-color: var(--white);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .search-bar .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            background: none;
            border: none;
            cursor: pointer;
        }
        
        /* Botões */
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        /* Card principal */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid #FFD9B3;
            margin-bottom: 30px;
        }
        
        /* Tabela de lojas */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 15px 10px;
            text-align: left;
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 2px solid #FFD9B3;
        }
        
        .table td {
            border-bottom: 1px solid #EEEEEE;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: var(--primary-light);
        }
        
        /* Status badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        
        .badge-success {
            background-color: #E6F7E6;
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: #FFF8E6;
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
        }
        
        /* Ações na tabela */
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-btn:hover {
            background-color: #E06E00;
        }
        
        /* Checkbox personalizado */
        .checkbox-wrapper {
            display: inline-block;
            position: relative;
            width: 20px;
            height: 20px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 0;
            height: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #fff;
            border: 2px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark:after {
            display: block;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination-item {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: var(--dark-gray);
            text-decoration: none;
        }
        
        .pagination-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .pagination-item.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .pagination-arrow {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark-gray);
            background-color: var(--white);
            border: 1px solid #EEEEEE;
            text-decoration: none;
        }
        
        .pagination-arrow:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Modal para adicionar/editar loja */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 600px;
            padding: 30px;
            box-shadow: var(--shadow);
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            cursor: pointer;
            color: var(--medium-gray);
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='%23333' d='M8 12l-6-6 1.41-1.41L8 9.17l4.59-4.58L14 6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        .btn-secondary:hover {
            background-color: #E0E0E0;
        }
        
        .form-text {
            font-size: 12px;
            color: var(--medium-gray);
            margin-top: 5px;
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .alert-danger {
            background-color: #FFEAE6;
            color: #F44336;
            border: 1px solid #F44336;
        }
        
        .alert-success {
            background-color: #E8F5E9;
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-content {
                padding-left: 0;
            }
            
            .page-wrapper {
                padding: 75px 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .actions-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .filters-form {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-bar, .filter-controls {
                width: 100%;
            }
            
            .modal-content {
                max-width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <h1>Lojas</h1>
                <button class="btn btn-primary" onclick="showStoreModal()">Adicionar</button>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Barra de Busca e Filtros -->
            <div class="actions-bar">
                <form method="GET" action="" class="filters-form">
                    <div class="search-bar">
                        <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="filter-controls">
                        <select name="status" class="form-select" style="width: auto; display: inline-block; margin-right: 10px;" onchange="this.form.submit()">
                            <option value="">Todos os status</option>
                            <option value="aprovado" <?php echo $status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="rejeitado" <?php echo $status === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                        
                        <?php if (!empty($categories)): ?>
                        <select name="category" class="form-select" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Card Principal com Tabela de Lojas -->
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkmark"></span>
                                    </div>
                                </th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Cadastro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stores)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Nenhuma loja encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stores as $store): ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" class="store-checkbox" value="<?php echo $store['id']; ?>">
                                                <span class="checkmark"></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($store['nome_fantasia']); ?></td>
                                        <td><?php echo htmlspecialchars($store['email']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($store['data_cadastro'])); ?></td>
                                        <td>
                                            <?php if ($store['status'] === 'aprovado'): ?>
                                                <span class="badge badge-success">Aprovado</span>
                                            <?php elseif ($store['status'] === 'pendente'): ?>
                                                <button class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;" 
                                                    onclick="approveStore(<?php echo $store['id']; ?>)">Aprovar</button>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Rejeitado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;" 
                                                onclick="viewStoreDetails(<?php echo $store['id']; ?>)">Ver Detalhes</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($pagination['total_paginas'], $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" 
                               class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para Adicionar/Editar Loja -->
    <div class="modal" id="storeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="storeModalTitle">Adicionar Loja</h3>
                <div class="modal-close" onclick="hideStoreModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>
            
            <form id="storeForm" onsubmit="submitStoreForm(event)">
                <input type="hidden" id="storeId" name="id" value="">
                
                <div class="form-group">
                    <label class="form-label" for="nomeFantasia">Nome Fantasia</label>
                    <input type="text" class="form-control" id="nomeFantasia" name="nome_fantasia" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="razaoSocial">Razão Social</label>
                    <input type="text" class="form-control" id="razaoSocial" name="razao_social" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="cnpj">CNPJ</label>
                    <input type="text" class="form-control" id="cnpj" name="cnpj" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="categoria">Categoria</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="Alimentação">Alimentação</option>
                        <option value="Vestuário">Vestuário</option>
                        <option value="Eletrônicos">Eletrônicos</option>
                        <option value="Beleza">Beleza</option>
                        <option value="Saúde">Saúde</option>
                        <option value="Serviços">Serviços</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="porcentagemCashback">Porcentagem de Cashback (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemCashback" name="porcentagem_cashback">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pendente">Pendente</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="rejeitado">Rejeitado</option>
                    </select>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideStoreModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Detalhes da Loja -->
    <div class="modal" id="storeDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="storeDetailsTitle">Detalhes da Loja</h3>
                <div class="modal-close" onclick="hideStoreDetailsModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>
            
            <div id="storeDetailsContent">
                <div class="alert alert-info">Carregando detalhes...</div>
            </div>
            
            <div class="form-footer">
                <button type="button" class="btn btn-secondary" onclick="hideStoreDetailsModal()">Fechar</button>
                <button type="button" class="btn btn-primary" id="editStoreBtn">Editar</button>
            </div>
        </div>
    </div>

    <!-- Script JavaScript -->
    <script>
        // Variáveis globais
        let currentStoreId = null;
        
        // Função para selecionar/desselecionar todos os checkboxes
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.store-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
        
        // Função para mostrar modal de adicionar loja
        function showStoreModal() {
            document.getElementById('storeModalTitle').textContent = 'Adicionar Loja';
            document.getElementById('storeForm').reset();
            document.getElementById('storeId').value = '';
            currentStoreId = null;
            
            // Mostrar modal
            document.getElementById('storeModal').classList.add('show');
        }
        
        // Função para esconder modal de loja
        function hideStoreModal() {
            document.getElementById('storeModal').classList.remove('show');
        }
        
        // Função para mostrar modal de detalhes da loja
        function viewStoreDetails(storeId) {
            currentStoreId = storeId;
            
            // Mostrar carregamento
            document.getElementById('storeDetailsTitle').textContent = 'Carregando...';
            document.getElementById('storeDetailsContent').innerHTML = '<div class="alert alert-info">Carregando detalhes da loja...</div>';
            document.getElementById('storeDetailsModal').classList.add('show');
            
            // Fazer requisição AJAX para obter dados da loja
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=store_details&store_id=' + storeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const store = data.data.loja;
                    const statistics = data.data.estatisticas;
                    
                    // Atualizar título do modal
                    document.getElementById('storeDetailsTitle').textContent = store.nome_fantasia;
                    
                    // Construir o conteúdo HTML
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <h4 style="margin-bottom: 15px; color: var(--primary-color);">Informações Básicas</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <p><strong>Razão Social:</strong> ${store.razao_social}</p>
                                    <p><strong>CNPJ:</strong> ${store.cnpj}</p>
                                    <p><strong>E-mail:</strong> ${store.email}</p>
                                </div>
                                <div>
                                    <p><strong>Telefone:</strong> ${store.telefone}</p>
                                    <p><strong>Categoria:</strong> ${store.categoria || 'Não definida'}</p>
                                    <p><strong>Cashback:</strong> ${store.porcentagem_cashback}%</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Estatísticas
                    if (statistics) {
                        html += `
                            <div style="margin-bottom: 20px;">
                                <h4 style="margin-bottom: 15px; color: var(--primary-color);">Estatísticas</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                                        <h5 style="margin: 0; color: var(--dark-gray);">Transações</h5>
                                        <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">${statistics.total_transacoes || 0}</p>
                                    </div>
                                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                                        <h5 style="margin: 0; color: var(--dark-gray);">Vendas</h5>
                                        <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">R$ ${parseFloat(statistics.total_vendas || 0).toFixed(2)}</p>
                                    </div>
                                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                                        <h5 style="margin: 0; color: var(--dark-gray);">Cashback</h5>
                                        <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">R$ ${parseFloat(statistics.total_cashback || 0).toFixed(2)}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Status atual
                    let statusClass = '';
                    let statusText = '';
                    
                    switch (store.status) {
                        case 'aprovado':
                            statusClass = 'badge-success';
                            statusText = 'Aprovado';
                            break;
                        case 'pendente':
                            statusClass = 'badge-warning';
                            statusText = 'Pendente';
                            break;
                        case 'rejeitado':
                            statusClass = 'badge-danger';
                            statusText = 'Rejeitado';
                            break;
                    }
                    
                    html += `
                        <div style="margin-bottom: 20px;">
                            <h4 style="margin-bottom: 15px; color: var(--primary-color);">Status</h4>
                            <p>Status atual: <span class="badge ${statusClass}">${statusText}</span></p>
                    `;
                    
                    // Ações de status
                    if (store.status === 'pendente') {
                        html += `
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button class="btn btn-primary" onclick="approveStore(${store.id})">Aprovar</button>
                                <button class="btn btn-secondary" onclick="rejectStore(${store.id})">Rejeitar</button>
                            </div>
                        `;
                    } else if (store.status === 'rejeitado') {
                        html += `
                            <div style="margin-top: 15px;">
                                <button class="btn btn-primary" onclick="approveStore(${store.id})">Aprovar</button>
                            </div>
                        `;
                    }
                    
                    html += `</div>`;
                    
                    // Atualizar conteúdo
                    document.getElementById('storeDetailsContent').innerHTML = html;
                    
                    // Configurar botão de edição
                    document.getElementById('editStoreBtn').onclick = function() {
                        editStore(store.id);
                    };
                } else {
                    document.getElementById('storeDetailsContent').innerHTML = `
                        <div class="alert alert-danger">${data.message || 'Erro ao carregar detalhes da loja.'}</div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('storeDetailsContent').innerHTML = `
                    <div class="alert alert-danger">Erro ao carregar detalhes da loja: ${error.message}</div>
                `;
            });
        }
        
        // Função para esconder modal de detalhes
        function hideStoreDetailsModal() {
            document.getElementById('storeDetailsModal').classList.remove('show');
        }
        
        // Função para aprovar loja
        function approveStore(storeId) {
            if (confirm('Tem certeza que deseja aprovar esta loja?')) {
                fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update_store_status&store_id=' + storeId + '&status=aprovado'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('Loja aprovada com sucesso!');
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao aprovar loja.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar a solicitação: ' + error.message);
                });
            }
        }
        
        // Função para rejeitar loja
        function rejectStore(storeId) {
            const observacao = prompt('Por favor, informe o motivo da rejeição:');
            
            if (observacao !== null) {
                fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update_store_status&store_id=' + storeId + '&status=rejeitado&observacao=' + encodeURIComponent(observacao)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('Loja rejeitada com sucesso!');
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao rejeitar loja.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar a solicitação: ' + error.message);
                });
            }
        }
        
        // Função para editar loja
        function editStore(storeId) {
            // Esconder modal de detalhes
            hideStoreDetailsModal();
            
            // Mostrar carregamento
            document.getElementById('storeModalTitle').textContent = 'Carregando...';
            document.getElementById('storeForm').reset();
            document.getElementById('storeId').value = storeId;
            document.getElementById('storeModal').classList.add('show');
            
            // Fazer requisição AJAX para obter dados da loja
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=store_details&store_id=' + storeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.data && data.data.loja) {
                    const store = data.data.loja;
                    
                    // Preencher o formulário
                    document.getElementById('storeModalTitle').textContent = 'Editar Loja';
                    document.getElementById('storeId').value = store.id;
                    document.getElementById('nomeFantasia').value = store.nome_fantasia;
                    document.getElementById('razaoSocial').value = store.razao_social;
                    document.getElementById('cnpj').value = store.cnpj;
                    document.getElementById('email').value = store.email;
                    document.getElementById('telefone').value = store.telefone;
                    document.getElementById('porcentagemCashback').value = store.porcentagem_cashback;
                    
                    // Campos opcionais
                    if (store.categoria) {
                        document.getElementById('categoria').value = store.categoria;
                    }
                    
                    document.getElementById('status').value = store.status;
                } else {
                    hideStoreModal();
                    alert(data.message || 'Erro ao carregar dados da loja');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                hideStoreModal();
                alert('Erro ao carregar dados da loja: ' + error.message);
            });
        }
        
        // Função para enviar formulário
        function submitStoreForm(event) {
            event.preventDefault();
            
            // Obter dados do formulário
            const form = document.getElementById('storeForm');
            const formData = new FormData(form);
            
            // Verificar se estamos editando ou criando
            const storeId = formData.get('id');
            const isEditing = storeId !== '';
            
            // Convertendo formData para URLSearchParams
            const data = new URLSearchParams();
            
            if (isEditing) {
                data.append('action', 'update_store');
                data.append('store_id', storeId);
            } else {
                data.append('action', 'register');
            }
            
            // Adicionar todos os campos do formulário
            for (const pair of formData.entries()) {
                if (pair[0] !== 'id') { // Não incluir ID novamente
                    data.append(pair[0], pair[1]);
                }
            }
            
            // Mostrar indicador de carregamento
            const saveButton = form.querySelector('button[type="submit"]');
            const originalButtonText = saveButton.textContent;
            saveButton.textContent = 'Salvando...';
            saveButton.disabled = true;
            
            // Enviar requisição
            fetch('../../controllers/StoreController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(isEditing ? 'Loja atualizada com sucesso!' : 'Loja adicionada com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao processar solicitação');
                    
                    // Restaurar botão
                    saveButton.textContent = originalButtonText;
                    saveButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a solicitação: ' + error.message);
                
                // Restaurar botão
                saveButton.textContent = originalButtonText;
                saveButton.disabled = false;
            });
        }
    </script>
</body>
</html>