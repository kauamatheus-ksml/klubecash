<?php
// No topo do stores.php, adicionar:
error_log("=== Iniciando stores.php ===");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'não definido'));
error_log("Session user_type: " . ($_SESSION['user_type'] ?? 'não definido'));
?>
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

// Debug da sessão
error_log("stores.php - Session status: " . session_status());
error_log("stores.php - User ID: " . ($_SESSION['user_id'] ?? 'não definido'));
error_log("stores.php - User type: " . ($_SESSION['user_type'] ?? 'não definido'));

// Verificar se há dados na sessão
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    error_log("stores.php - ERRO: Dados de sessão ausentes, redirecionando...");
    header("Location: /views/auth/login.php?error=sessao_expirada");
    exit;
}

// Verificar se é admin
if ($_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    error_log("stores.php - ERRO: Usuário não é admin, tipo: " . $_SESSION['user_type']);
    header("Location: /views/auth/login.php?error=acesso_negado");
    exit;
}

error_log("stores.php - Verificações de sessão OK");

set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    return true;
});

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

    <script>
// Variáveis globais
let currentStoreId = null;

// ========== FUNÇÕES DO MODAL DE ADICIONAR/EDITAR LOJA ==========
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

function hideStoreModal() {
    document.getElementById('storeModal').style.display = 'none';
    currentStoreId = null;
}

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
            document.getElementById('categoria').value = store.categoria || 'Outros';
            document.getElementById('porcentagemCashback').value = store.porcentagem_cashback;
            document.getElementById('status').value = store.status;
        } else {
            alert('Erro ao carregar dados da loja: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao carregar loja:', error);
        alert('Erro ao carregar dados da loja.');
    });
}

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
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao salvar loja:', error);
        alert('Erro ao salvar loja.');
    });
}

// ========== FUNÇÕES DO MODAL DE DETALHES ==========
function viewStoreDetails(storeId) {
    console.log('viewStoreDetails chamada para ID:', storeId);
    currentStoreId = storeId;
    
    // Mostrar modal de carregamento
    document.getElementById('storeDetailsTitle').textContent = 'Carregando...';
    document.getElementById('storeDetailsContent').innerHTML = '<div class="alert alert-info">Carregando detalhes da loja...</div>';
    document.getElementById('storeDetailsModal').style.display = 'block';
    
    // Usar diretamente o AdminController
    fetch('../../controllers/AdminController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=store_details&store_id=' + storeId
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data.status && data.data && data.data.loja) {
                renderStoreDetails(data.data.loja);
            } else {
                throw new Error(data.message || 'Dados inválidos');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            document.getElementById('storeDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Erro ao processar resposta:</h5>
                    <p>${e.message}</p>
                    <details>
                        <summary>Ver resposta completa</summary>
                        <pre style="white-space: pre-wrap; max-height: 200px; overflow-y: auto; font-size: 12px;">${text}</pre>
                    </details>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('storeDetailsContent').innerHTML = `
            <div class="alert alert-danger">
                <h5>Erro na requisição:</h5>
                <p>${error.message}</p>
                <p>Verifique se o arquivo AdminController.php existe e as permissões estão corretas.</p>
            </div>
        `;
    });
}

function hideStoreDetailsModal() {
    document.getElementById('storeDetailsModal').style.display = 'none';
}

// ========== FUNÇÃO DE RENDERIZAÇÃO ==========
function renderStoreDetails(store) {
    console.log('Renderizando loja:', store);
    
    document.getElementById('storeDetailsTitle').textContent = store.nome_fantasia;
    
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
        default:
            statusClass = 'badge-secondary';
            statusText = store.status || 'Desconhecido';
    }
    
    const html = `
        <div class="store-details">
            <h4 style="color: #FF7A00; margin-bottom: 20px;">Informações da Loja</h4>
            <table class="details-table">
                <tr><td><strong>Nome Fantasia:</strong></td><td>${store.nome_fantasia}</td></tr>
                <tr><td><strong>Razão Social:</strong></td><td>${store.razao_social}</td></tr>
                <tr><td><strong>CNPJ:</strong></td><td>${store.cnpj}</td></tr>
                <tr><td><strong>Email:</strong></td><td>${store.email}</td></tr>
                <tr><td><strong>Telefone:</strong></td><td>${store.telefone}</td></tr>
                <tr><td><strong>Categoria:</strong></td><td>${store.categoria || 'Não definida'}</td></tr>
                <tr><td><strong>Porcentagem Cashback:</strong></td><td>${store.porcentagem_cashback}%</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge ${statusClass}">${statusText}</span></td></tr>
                <tr><td><strong>Data Cadastro:</strong></td><td>${formatDate(store.data_cadastro)}</td></tr>
                ${store.data_aprovacao ? `<tr><td><strong>Data Aprovação:</strong></td><td>${formatDate(store.data_aprovacao)}</td></tr>` : ''}
                ${store.observacao ? `<tr><td><strong>Observação:</strong></td><td>${store.observacao}</td></tr>` : ''}
            </table>
            
            ${store.status === 'pendente' ? `
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h5>Ações</h5>
                    <button class="btn btn-primary" onclick="updateStoreStatus(${store.id}, 'aprovado')" style="margin-right: 10px;">
                        Aprovar Loja
                    </button>
                    <button class="btn btn-secondary" onclick="rejectStore(${store.id})">
                        Rejeitar Loja
                    </button>
                </div>
            ` : store.status === 'rejeitado' ? `
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h5>Ações</h5>
                    <button class="btn btn-primary" onclick="updateStoreStatus(${store.id}, 'aprovado')">
                        Aprovar Loja
                    </button>
                </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('storeDetailsContent').innerHTML = html;
    
    // Configurar botão de edição
    document.getElementById('editStoreBtn').onclick = function() {
        hideStoreDetailsModal();
        showStoreModal(store.id);
    };
}

// ========== FUNÇÕES DE STATUS ==========
function approveStore(storeId) {
    if (confirm('Tem certeza que deseja aprovar esta loja?')) {
        updateStoreStatus(storeId, 'aprovado');
    }
}

function rejectStore(storeId) {
    const observacao = prompt('Digite o motivo da rejeição (opcional):');
    updateStoreStatus(storeId, 'rejeitado', observacao);
}

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
        console.error('Erro ao atualizar status:', error);
        alert('Erro ao atualizar status da loja.');
    });
}

// ========== FUNÇÕES UTILITÁRIAS ==========
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.store-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// ========== EVENT LISTENERS ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página carregada');
    
    // Configurar formulário
    const storeForm = document.getElementById('storeForm');
    if (storeForm) {
        storeForm.addEventListener('submit', submitStoreForm);
    }
});

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

// Tecla ESC para fechar modais
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        hideStoreModal();
        hideStoreDetailsModal();
    }
});
</script>

    
</body>
</html>