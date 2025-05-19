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
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: ../auth/login.php?error=acesso_restrito");
    exit;
}

// Obter dados do usuário PRIMEIRO
$userId = $_SESSION['user_id'];

// DEBUG TEMPORÁRIO - REMOVER DEPOIS
$debug = true; // Mude para false depois de corrigir

if ($debug) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>DEBUG - Informações do Sistema</h3>";
    
    try {
        $db = Database::getConnection();
        echo "✓ Conexão com banco OK<br>";
        
        // Verificar tabelas
        $tables = ['lojas', 'usuarios', 'transacoes_cashback', 'cashback_saldos', 'cashback_movimentacoes', 'favorites'];
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() > 0) {
                echo "✓ Tabela '$table' existe<br>";
            } else {
                echo "✗ Tabela '$table' NÃO existe<br>";
            }
        }
        
        // Verificar se há lojas
        $result = $db->query("SELECT COUNT(*) as total FROM lojas");
        $count = $result->fetch();
        echo "📊 Total de lojas: " . $count['total'] . "<br>";
        
        if ($count['total'] > 0) {
            $result = $db->query("SELECT COUNT(*) as aprovadas FROM lojas WHERE status = 'aprovado'");
            $aprovadas = $result->fetch();
            echo "📊 Lojas aprovadas: " . $aprovadas['aprovadas'] . "<br>";
        }
        
        // Verificar usuário atual (AGORA com $userId definido)
        echo "👤 User ID: " . $userId . "<br>";
        echo "👤 User Name: " . ($_SESSION['user_name'] ?? 'Não definido') . "<br>";
        
        // Verificar se o usuário existe no banco
        $checkUserQuery = "SELECT id, nome, email, tipo FROM usuarios WHERE id = :user_id";
        $checkUserStmt = $db->prepare($checkUserQuery);
        $checkUserStmt->bindParam(':user_id', $userId);
        $checkUserStmt->execute();
        $userData = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "✅ Usuário encontrado no banco: " . $userData['nome'] . " (Tipo: " . $userData['tipo'] . ")<br>";
        } else {
            echo "❌ Usuário não encontrado no banco<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ ERRO: " . $e->getMessage() . "<br>";
        echo "❌ Stack trace: " . $e->getTraceAsString() . "<br>";
    }
    
    echo "</div>";
    
    // Debug adicional da sessão
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px; border: 1px solid #ffeaa7;'>";
    echo "<h4>🔍 DEBUG - Dados da Sessão</h4>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
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
        $filters['nome'] = $_GET['nome'];
    }
    
    if (!empty($_GET['cashback_min'])) {
        $filters['cashback_min'] = $_GET['cashback_min'];
    }
    
    if (!empty($_GET['tem_saldo']) && $_GET['tem_saldo'] != 'todas') {
        $filters['tem_saldo'] = $_GET['tem_saldo'];
    }
    
    if (!empty($_GET['ordenar']) && $_GET['ordenar'] != 'nome') {
        $filters['ordenar'] = $_GET['ordenar'];
    }
}

// Debug dos filtros se necessário
if ($debug) {
    echo "<div style='background: #e7f3ff; padding: 10px; margin: 10px; border: 1px solid #bee5eb;'>";
    echo "<h4>🔍 DEBUG - Filtros Aplicados</h4>";
    echo "<pre>";
    print_r($filters);
    echo "</pre>";
    echo "📄 Página: " . $page . "<br>";
    echo "</div>";
}

try {
    // Chamar o método para buscar lojas
    $result = ClientController::getPartnerStores($userId, $filters, $page);
    
    // Debug do resultado
    if ($debug) {
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px; border: 1px solid #dee2e6;'>";
        echo "<h4>🔍 DEBUG - Resultado do Controller</h4>";
        echo "Status: " . ($result['status'] ? 'SUCCESS' : 'ERRO') . "<br>";
        if (!$result['status']) {
            echo "Mensagem de erro: " . $result['message'] . "<br>";
        } else {
            echo "Lojas encontradas: " . count($result['data']['lojas']) . "<br>";
            echo "Categorias: " . count($result['data']['categorias']) . "<br>";
        }
        echo "</div>";
    }
    
    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';
    
    // Dados para exibição
    $storesData = $hasError ? [] : $result['data'];
    
    if (!$hasError && !empty($storesData['lojas'])) {
    foreach ($storesData['lojas'] as &$loja) {
        // Buscar saldo disponível do cliente nesta loja
        $saldoQuery = "
            SELECT 
                saldo_disponivel,
                total_creditado,
                total_usado
            FROM cashback_saldos 
            WHERE usuario_id = ? AND loja_id = ?
        ";
        
        $saldoStmt = $db->prepare($saldoQuery);
        $saldoStmt->execute([$userId, $loja['id']]);
        $saldoInfo = $saldoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($saldoInfo) {
            $loja['saldo_disponivel'] = $saldoInfo['saldo_disponivel'];
            $loja['total_creditado'] = $saldoInfo['total_creditado'];
            $loja['total_usado'] = $saldoInfo['total_usado'];
        } else {
            $loja['saldo_disponivel'] = 0;
            $loja['total_creditado'] = 0;
            $loja['total_usado'] = 0;
        }
        
        // Buscar contagem de usos
        $usosQuery = "SELECT COUNT(*) as total_usos FROM cashback_movimentacoes WHERE usuario_id = ? AND loja_id = ? AND tipo_operacao = 'uso'";
        $usosStmt = $db->prepare($usosQuery);
        $usosStmt->execute([$userId, $loja['id']]);
        $usosInfo = $usosStmt->fetch(PDO::FETCH_ASSOC);
        $loja['total_usos'] = $usosInfo['total_usos'] ?? 0;
        
        // Buscar último uso
        $ultimoUsoQuery = "SELECT data_operacao FROM cashback_movimentacoes WHERE usuario_id = ? AND loja_id = ? ORDER BY data_operacao DESC LIMIT 1";
        $ultimoUsoStmt = $db->prepare($ultimoUsoQuery);
        $ultimoUsoStmt->execute([$userId, $loja['id']]);
        $ultimoUsoInfo = $ultimoUsoStmt->fetch(PDO::FETCH_ASSOC);
        $loja['ultimo_uso'] = $ultimoUsoInfo['data_operacao'] ?? null;
        
        // Buscar cashback pendente
        $pendingQuery = "SELECT SUM(valor_cliente) as cashback_pendente FROM transacoes_cashback WHERE usuario_id = ? AND loja_id = ? AND status = 'pendente'";
        $pendingStmt = $db->prepare($pendingQuery);
        $pendingStmt->execute([$userId, $loja['id']]);
        $pendingInfo = $pendingStmt->fetch(PDO::FETCH_ASSOC);
        $loja['cashback_pendente'] = $pendingInfo['cashback_pendente'] ?? 0;
    }
}
    
    // Obter estatísticas gerais do cliente
     $estatisticasQuery = "
        SELECT 
            COUNT(DISTINCT loja_id) as lojas_com_saldo,
            SUM(saldo_disponivel) as total_saldo_disponivel,
            SUM(total_usado) as total_usado_geral,
            COUNT(DISTINCT CASE WHEN saldo_disponivel > 0 THEN loja_id END) as lojas_saldo_disponivel
        FROM cashback_saldos
        WHERE usuario_id = ?
    ";
    $estatisticasStmt = $db->prepare($estatisticasQuery);
    $estatisticasStmt->execute([$userId]);
    $estatisticasGerais = $estatisticasStmt->fetch(PDO::FETCH_ASSOC);
    
    // Se não houver dados, definir valores padrão
    if (!$estatisticasGerais) {
        $estatisticasGerais = [
            'lojas_com_saldo' => 0,
            'total_saldo_disponivel' => 0,
            'total_usado_geral' => 0,
            'lojas_saldo_disponivel' => 0
        ];
    }
} catch (Exception $e) {
    // Em caso de erro, usar valores padrão
    $estatisticasGerais = [
        'lojas_com_saldo' => 0,
        'total_saldo_disponivel' => 0,
        'total_usado_geral' => 0,
        'lojas_saldo_disponivel' => 0
    ];
}

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

// Funções auxiliares
function formatCurrency($value) {
    return 'R$ ' . number_format($value ?: 0, 2, ',', '.');
}

// Função para formatar data
function formatDate($date) {
    if (!$date) return 'Nunca';
    return date('d/m/Y', strtotime($date));
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
            <div class="header-actions">
                <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="btn btn-secondary">Meus Saldos</a>
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
        
        <!-- Resumo de Saldos -->
        <div class="balance-summary-section">
            <div class="balance-summary-cards">
                <div class="balance-summary-card">
                    <div class="balance-summary-title">Saldo Total Disponível</div>
                    <div class="balance-summary-value"><?php echo formatCurrency($estatisticasGerais['total_saldo_disponivel']); ?></div>
                </div>
                
                <div class="balance-summary-card">
                    <div class="balance-summary-title">Lojas com Saldo</div>
                    <div class="balance-summary-value"><?php echo $estatisticasGerais['lojas_saldo_disponivel']; ?></div>
                </div>
                
                <div class="balance-summary-card">
                    <div class="balance-summary-title">Total Usado</div>
                    <div class="balance-summary-value"><?php echo formatCurrency($estatisticasGerais['total_usado_geral']); ?></div>
                </div>
                
                <div class="balance-summary-card">
                    <div class="balance-summary-title">Lojas Utilizadas</div>
                    <div class="balance-summary-value"><?php echo $estatisticasGerais['lojas_com_saldo']; ?></div>
                </div>
            </div>
        </div>
        
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
                
                <div class="form-group">
                    <label class="form-label" for="tem_saldo">Saldo</label>
                    <select id="tem_saldo" name="tem_saldo" class="form-control">
                        <option value="todas">Todas as Lojas</option>
                        <option value="com_saldo" <?php echo (isset($filters['tem_saldo']) && $filters['tem_saldo'] == 'com_saldo') ? 'selected' : ''; ?>>Com saldo disponível</option>
                        <option value="sem_saldo" <?php echo (isset($filters['tem_saldo']) && $filters['tem_saldo'] == 'sem_saldo') ? 'selected' : ''; ?>>Sem saldo</option>
                        <option value="ja_usei" <?php echo (isset($filters['tem_saldo']) && $filters['tem_saldo'] == 'ja_usei') ? 'selected' : ''; ?>>Já usei saldo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="ordenar">Ordenar por</label>
                    <select id="ordenar" name="ordenar" class="form-control">
                        <option value="nome" <?php echo (!isset($filters['ordenar']) || $filters['ordenar'] == 'nome') ? 'selected' : ''; ?>>Nome</option>
                        <option value="cashback" <?php echo (isset($filters['ordenar']) && $filters['ordenar'] == 'cashback') ? 'selected' : ''; ?>>% Cashback</option>
                        <option value="saldo" <?php echo (isset($filters['ordenar']) && $filters['ordenar'] == 'saldo') ? 'selected' : ''; ?>>Saldo disponível</option>
                        <option value="uso" <?php echo (isset($filters['ordenar']) && $filters['ordenar'] == 'uso') ? 'selected' : ''; ?>>Mais usadas</option>
                    </select>
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
            
            <div class="summary-card">
                <div class="summary-card-title">Menor Cashback</div>
                <div class="summary-card-value"><?php echo number_format($storesData['estatisticas']['menor_cashback'] ?? 0, 2); ?>%</div>
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
                    <div class="store-card <?php echo $loja['saldo_disponivel'] > 0 ? 'has-balance' : ''; ?>">
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
                            
                            <!-- Indicador de saldo -->
                            <?php if ($loja['saldo_disponivel'] > 0): ?>
                                <div class="balance-indicator">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                    Saldo: <?php echo formatCurrency($loja['saldo_disponivel']); ?>
                                </div>
                            <?php endif; ?>
                            
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
                            
                            <!-- Informações de saldo e uso -->
                            <div class="balance-info">
                                <?php if ($loja['cashback_pendente'] > 0): ?>
                                    <div class="balance-pending">
                                        <span class="balance-label">Pendente:</span>
                                        <span class="balance-value"><?php echo formatCurrency($loja['cashback_pendente']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($loja['total_usado'] > 0): ?>
                                    <div class="balance-used">
                                        <span class="balance-label">Já usado:</span>
                                        <span class="balance-value"><?php echo formatCurrency($loja['total_usado']); ?></span>
                                        <small class="usage-count">(<?php echo $loja['total_usos']; ?> vezes)</small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($loja['ultimo_uso']): ?>
                                    <div class="last-usage">
                                        <span class="balance-label">Último uso:</span>
                                        <span class="balance-value"><?php echo formatDate($loja['ultimo_uso']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Informações tradicionais -->
                            <?php if (!empty($loja['website'])): ?>
                                <p style="font-size: 14px; color: var(--medium-gray); margin-top: 10px;">
                                    Website: <a href="<?php echo htmlspecialchars($loja['website']); ?>" target="_blank" style="color: var(--primary-color);"><?php echo htmlspecialchars($loja['website']); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="store-footer">
                            <div class="store-actions">
                                <a href="#" class="store-button" onclick="verDetalhes(<?php echo $loja['id']; ?>)">Ver Detalhes</a>
                                <?php if ($loja['saldo_disponivel'] > 0): ?>
                                    <button class="btn-use-balance" onclick="usarSaldo(<?php echo $loja['id']; ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                        </svg>
                                        Usar Saldo
                                    </button>
                                <?php endif; ?>
                            </div>
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
    
    <!-- Modal de Detalhes da Loja -->
    <div id="storeModal" class="modal">
        <div class="modal-content">
            <button onclick="closeModal()" class="modal-close">&times;</button>
            <h3 class="modal-title">Detalhes da Loja</h3>
            <div id="storeDetails">
                <!-- Será preenchido via JavaScript -->
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Uso de Saldo -->
    <div id="useBalanceModal" class="modal">
        <div class="modal-content">
            <button onclick="closeUseBalanceModal()" class="modal-close">&times;</button>
            <h3 class="modal-title">Usar Saldo</h3>
            <div id="useBalanceContent">
                <p>Funcionalidade de uso de saldo será implementada em breve!</p>
                <p>Esta opção permitirá que você use seu saldo de cashback diretamente na loja.</p>
            </div>
        </div>
    </div>
    
    <script>
        // Função para exibir detalhes da loja
        function verDetalhes(storeId) {
            document.getElementById('storeModal').style.display = 'flex';
            
            // Simular carregamento de dados (em produção, faria uma chamada AJAX)
            document.getElementById('storeDetails').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <p>Carregando detalhes da loja #${storeId}...</p>
                    <p>Esta funcionalidade incluirá:</p>
                    <ul style="text-align: left; max-width: 300px; margin: 20px auto;">
                        <li>Histórico completo de transações</li>
                        <li>Detalhamento do saldo disponível</li>
                        <li>Gráfico de movimentações</li>
                        <li>Informações detalhadas da loja</li>
                    </ul>
                </div>
            `;
        }
        
        // Função para usar saldo
        function usarSaldo(storeId) {
            document.getElementById('useBalanceModal').style.display = 'flex';
        }
        
        // Função para fechar modal principal
        function closeModal() {
            document.getElementById('storeModal').style.display = 'none';
        }
        
        // Função para fechar modal de uso de saldo
        function closeUseBalanceModal() {
            document.getElementById('useBalanceModal').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const storeModal = document.getElementById('storeModal');
            const useBalanceModal = document.getElementById('useBalanceModal');
            
            if (event.target === storeModal) {
                closeModal();
            }
            if (event.target === useBalanceModal) {
                closeUseBalanceModal();
            }
        };
    </script>
</body>
</html>