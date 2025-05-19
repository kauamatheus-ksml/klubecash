<?php
// views/admin/stores.php
// Definir o menu ativo na sidebar
$activeMenu = 'lojas';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';
require_once '../../models/CashbackBalance.php';

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
$status = isset($_GET['status']) ? strtolower($_GET['status']) : '';
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

// Adicionando log para debug
error_log("Filtros: " . print_r($filters, true));

try {
    // Testar conexão com banco
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Erro na conexão com o banco de dados");
    }
    
    // Verificar se tabela 'lojas' existe
    $testQuery = $db->query("SHOW TABLES LIKE 'lojas'");
    if ($testQuery->rowCount() == 0) {
        throw new Exception("Tabela 'lojas' não encontrada no banco de dados");
    }
    
    // Obter dados das lojas com informações de saldo
    $result = AdminController::manageStoresWithBalance($filters, $page);
    
    // Log para debug
    error_log("Resultado da consulta: " . print_r($result, true));

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $stores = $hasError ? [] : $result['data']['lojas'];
    $statistics = $hasError ? [] : (isset($result['data']['estatisticas']) ? $result['data']['estatisticas'] : []);
    $categories = $hasError ? [] : (isset($result['data']['categorias']) ? $result['data']['categorias'] : []);
    $pagination = $hasError ? [] : (isset($result['data']['paginacao']) ? $result['data']['paginacao'] : []);
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    error_log("Erro em stores.php: " . $e->getMessage());
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
    <link rel="stylesheet" href="../../assets/css/views/admin/stores.css">
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
            
            <!-- Cards de Estatísticas com Informações de Saldo -->
            <?php if (!empty($statistics)): ?>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total de Lojas</div>
                    <div class="stat-card-value"><?php echo number_format($statistics['total_lojas'] ?? 0); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Lojas com Saldo Ativo</div>
                    <div class="stat-card-value"><?php echo number_format($statistics['lojas_com_saldo'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">Clientes com saldo disponível</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Saldo Acumulado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($statistics['total_saldo_acumulado'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Saldo disponível dos clientes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Saldo Usado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($statistics['total_saldo_usado'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Economia gerada aos clientes</div>
                </div>
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
                        <select name="status" class="form-select" style="width: auto; display: inline-block; margin-right: 10px;">
                            <option value="">Todos os status</option>
                            <option value="aprovado" <?php echo $status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="rejeitado" <?php echo $status === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                        
                        <?php if (!empty($categories)): ?>
                        <select name="category" class="form-select" style="width: auto; display: inline-block;">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-secondary">Filtrar</button>
                        <!-- Botão para limpar filtros -->
                        <a href="?" class="btn btn-outline-secondary">Limpar Filtros</a>
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
                                <th>Saldo Clientes</th>
                                <th>% Uso Saldo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stores)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Nenhuma loja encontrada</td>
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
                                        <td>
                                            <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                                            <?php if ($store['total_saldo_clientes'] > 0): ?>
                                                <span class="balance-indicator" title="Clientes com saldo">💰</span>
                                            <?php endif; ?>
                                        </td>
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
                                            <?php if ($store['total_saldo_clientes'] > 0): ?>
                                                <span class="saldo-amount">R$ <?php echo number_format($store['total_saldo_clientes'], 2, ',', '.'); ?></span>
                                                <small class="saldo-count">(<?php echo $store['clientes_com_saldo']; ?> clientes)</small>
                                            <?php else: ?>
                                                <span class="sem-saldo">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($store['total_transacoes'] > 0): ?>
                                                <?php 
                                                $percentualUso = ($store['transacoes_com_saldo'] / $store['total_transacoes']) * 100;
                                                ?>
                                                <span class="percentage-badge">
                                                    <?php echo number_format($percentualUso, 1); ?>%
                                                </span>
                                                <small class="usage-detail">
                                                    (<?php echo $store['transacoes_com_saldo']; ?>/<?php echo $store['total_transacoes']; ?>)
                                                </small>
                                            <?php else: ?>
                                                <span class="sem-transacoes">0%</span>
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
    
    <!-- Modal para adicionar/editar loja -->
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

    <!-- Script JavaScript existente -->
    <script>
    // Variáveis globais
    let currentStoreId = null;

    // Função para mostrar o modal de adicionar loja
    function showStoreModal(storeId = null) {
        currentStoreId = storeId;
        
        // Resetar formulário
        document.getElementById('storeForm').reset();
        document.getElementById('storeId').value = '';
        
        if (storeId) {
            // Modo edição - carregar dados da loja
            document.getElementById('storeModalTitle').textContent = 'Editar Loja';
            loadStoreData(storeId);
        } else {
            // Modo criação
            document.getElementById('storeModalTitle').textContent = 'Adicionar Loja';
        }
        
        // Mostrar modal
        document.getElementById('storeModal').style.display = 'block';
    }

    // Função para esconder o modal de loja
    function hideStoreModal() {
        document.getElementById('storeModal').style.display = 'none';
        currentStoreId = null;
    }

    // Função para carregar dados da loja (para edição)
    function loadStoreData(storeId) {
        fetch('../../controllers/AdminController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=store_details&store_id=' + storeId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status && data.data.loja) {
                const store = data.data.loja;
                
                // Preencher formulário
                document.getElementById('storeId').value = store.id;
                document.getElementById('nomeFantasia').value = store.nome_fantasia;
                document.getElementById('razaoSocial').value = store.razao_social;
                document.getElementById('cnpj').value = store.cnpj;
                document.getElementById('email').value = store.email;
                document.getElementById('telefone').value = store.telefone;
                document.getElementById('categoria').value = store.categoria;
                document.getElementById('porcentagemCashback').value = store.porcentagem_cashback;
                document.getElementById('status').value = store.status;
            } else {
                alert('Erro ao carregar dados da loja: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados da loja.');
        });
    }

    // Função para submeter o formulário da loja
    function submitStoreForm(event) {
        event.preventDefault();
        
        const formData = new FormData(document.getElementById('storeForm'));
        const isEditing = document.getElementById('storeId').value !== '';
        
        // Adicionar action
        formData.append('action', isEditing ? 'update_store' : 'create_store');
        if (isEditing) {
            formData.append('store_id', document.getElementById('storeId').value);
        }
        
        // Enviar dados
        fetch('../../controllers/AdminController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                alert(data.message || (isEditing ? 'Loja atualizada com sucesso!' : 'Loja criada com sucesso!'));
                hideStoreModal();
                location.reload(); // Recarregar página para mostrar mudanças
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar loja.');
        });
    }

    // Função para ver detalhes da loja com saldo
    function viewStoreDetails(storeId) {
        currentStoreId = storeId;
        
        // Mostrar carregamento
        document.getElementById('storeDetailsTitle').textContent = 'Carregando...';
        document.getElementById('storeDetailsContent').innerHTML = '<div class="alert alert-info">Carregando detalhes da loja...</div>';
        document.getElementById('storeDetailsModal').style.display = 'block';
        
        // Fazer requisição AJAX para obter dados da loja com informações de saldo
        fetch('../../controllers/AdminController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=store_details_with_balance&store_id=' + storeId
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status) {
                renderStoreDetailsWithBalance(data.data);
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

    // Função para renderizar detalhes da loja com saldo
    function renderStoreDetailsWithBalance(data) {
        const store = data.loja;
        const statistics = data.estatisticas;
        const balanceStats = data.estatisticas_saldo;
        
        // Atualizar título do modal
        document.getElementById('storeDetailsTitle').textContent = store.nome_fantasia;
        
        // Construir o conteúdo HTML com informações de saldo
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
        
        // Estatísticas gerais
        if (statistics) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px; color: var(--primary-color);">Estatísticas de Transações</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                            <h5 style="margin: 0; color: var(--dark-gray);">Transações</h5>
                            <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">${statistics.total_transacoes || 0}</p>
                        </div>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                            <h5 style="margin: 0; color: var(--dark-gray);">Vendas</h5>
                            <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">R$ ${parseFloat(statistics.total_vendas || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        </div>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                            <h5 style="margin: 0; color: var(--dark-gray);">Cashback</h5>
                            <p style="font-size: 18px; font-weight: bold; margin: 10px 0;">R$ ${parseFloat(statistics.total_cashback || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Estatísticas de saldo
        if (balanceStats) {
            const totalTransacoes = parseInt(balanceStats.total_transacoes) || 0;
            const transacoesComSaldo = parseInt(balanceStats.transacoes_com_saldo) || 0;
            const percentualUso = totalTransacoes > 0 ? (transacoesComSaldo / totalTransacoes) * 100 : 0;
            
            html += `
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px; color: #28a745;">💰 Estatísticas de Saldo</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="background: #f8fff8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                            <p style="margin: 0; font-size: 14px; color: #666;">Saldo Total dos Clientes</p>
                            <p style="font-size: 20px; font-weight: bold; margin: 5px 0; color: #28a745;">R$ ${parseFloat(balanceStats.total_saldo_clientes || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                            <p style="margin: 0; font-size: 12px; color: #666;">${balanceStats.clientes_com_saldo || 0} clientes com saldo</p>
                        </div>
                        <div style="background: #fff8f0; padding: 15px; border-radius: 8px; border-left: 4px solid #FF7A00;">
                            <p style="margin: 0; font-size: 14px; color: #666;">Total Saldo Usado</p>
                            <p style="font-size: 20px; font-weight: bold; margin: 5px 0; color: #FF7A00;">R$ ${parseFloat(balanceStats.total_saldo_usado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                            <p style="margin: 0; font-size: 12px; color: #666;">${transacoesComSaldo} transações com uso de saldo</p>
                        </div>
                    </div>
                    
                    ${totalTransacoes > 0 ? `
                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p style="margin: 0; font-size: 14px; color: #666;">Taxa de Uso de Saldo</p>
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                            <div style="flex: 1; background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="width: ${percentualUso}%; height: 100%; background: #28a745;"></div>
                            </div>
                            <span style="font-weight: bold; color: #28a745;">${percentualUso.toFixed(1)}%</span>
                        </div>
                    </div>
                    ` : ''}
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
                    <button class="btn btn-primary" onclick="updateStoreStatus(${store.id}, 'aprovado')">Aprovar</button>
                    <button class="btn btn-secondary" onclick="updateStoreStatus(${store.id}, 'rejeitado')">Rejeitar</button>
                </div>
            `;
        } else if (store.status === 'rejeitado') {
            html += `
                <div style="margin-top: 15px;">
                    <button class="btn btn-primary" onclick="updateStoreStatus(${store.id}, 'aprovado')">Aprovar</button>
                </div>
            `;
        }
        
        html += `</div>`;
        
        // Atualizar conteúdo
        document.getElementById('storeDetailsContent').innerHTML = html;
        
        // Configurar botão de edição
        document.getElementById('editStoreBtn').onclick = function() {
            hideStoreDetailsModal();
            showStoreModal(store.id);
        };
    }

    // Função para esconder modal de detalhes
    function hideStoreDetailsModal() {
        document.getElementById('storeDetailsModal').style.display = 'none';
    }

    // Função para aprovar loja
    function approveStore(storeId) {
        if (confirm('Tem certeza que deseja aprovar esta loja?')) {
            updateStoreStatus(storeId, 'aprovado');
        }
    }

    // Função para rejeitar loja
    function rejectStore(storeId) {
        const observacao = prompt('Digite o motivo da rejeição (opcional):');
        updateStoreStatus(storeId, 'rejeitado', observacao);
    }

    // Função para atualizar status da loja
    function updateStoreStatus(storeId, status, observacao = '') {
        fetch('../../controllers/AdminController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_store_status&store_id=${storeId}&status=${status}&observacao=${encodeURIComponent(observacao)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                alert(data.message || 'Status atualizado com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar status da loja.');
        });
    }

    // Função para selecionar/deselecionar todos
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.store-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }

    // Fechar modais ao clicar fora
    window.onclick = function(event) {
        const storeModal = document.getElementById('storeModal');
        const detailsModal = document.getElementById('storeDetailsModal');
        
        if (event.target === storeModal) {
            hideStoreModal();
        }
        
        if (event.target === detailsModal) {
            hideStoreDetailsModal();
        }
    }

    // Evento para formulário
    document.addEventListener('DOMContentLoaded', function() {
        const storeForm = document.getElementById('storeForm');
        if (storeForm) {
            storeForm.addEventListener('submit', submitStoreForm);
        }
    });
    </script>
    
    <style>
    /* Estilos adicionais para informações de saldo */
    .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
}

.modal-content {
    background-color: white;
    position: relative;
    margin: 5% auto;
    padding: 0;
    width: 90%;
    max-width: 800px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.modal-title {
    margin: 0;
    color: #2c3e50;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background-color: #e9ecef;
}

.modal-close svg {
    color: #6c757d;
}

/* Formulário dentro do modal */
.modal-content form {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.form-control,
.form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #FF7A00;
    box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
}

.form-footer {
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background-color: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

/* Botões */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background-color: #FF7A00;
    color: white;
}

.btn-primary:hover {
    background-color: #e56500;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6169;
}

/* Badge styles */
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
}

/* Alert styles */
.alert {
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-size: 0.9rem;
}

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* Responsividade */
@media (max-width: 768px) {
    .modal-content {
        margin: 2% auto;
        width: 95%;
        max-height: 95vh;
    }
    
    .modal-header {
        padding: 16px 20px;
    }
    
    .modal-content form {
        padding: 20px;
    }
    
    .form-footer {
        padding: 12px 20px;
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
    .balance-indicator {
        margin-left: 5px;
        font-size: 0.8rem;
    }
    
    .saldo-amount {
        color: #28a745;
        font-weight: 600;
    }
    
    .saldo-count {
        color: #6c757d;
        font-size: 0.8rem;
        display: block;
    }
    
    .sem-saldo,
    .sem-transacoes {
        color: #6c757d;
        font-style: italic;
    }
    
    .percentage-badge {
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .usage-detail {
        color: #6c757d;
        font-size: 0.75rem;
        display: block;
        margin-top: 2px;
    }
    
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-card-title {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .stat-card-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 5px;
    }
    
    .stat-card-subtitle {
        font-size: 0.8rem;
        color: #6c757d;
        font-style: italic;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table th:nth-child(6),
        .table th:nth-child(7),
        .table td:nth-child(6),
        .table td:nth-child(7) {
            display: none;
        }
    }
    </style>
</body>
</html>