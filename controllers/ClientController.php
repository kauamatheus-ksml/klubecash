<?php
// controllers/ClientController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/AuthController.php';

/**
 * Controlador do Cliente
 * Gerencia operações relacionadas a clientes como obtenção de extrato,
 * visualização de cashback, perfil e interação com lojas parceiras
 */
class ClientController {
    
    /**
    * Obtém detalhes específicos de saldo de uma loja para o cliente (CORRIGIDO)
    */
    public static function getStoreBalanceDetails($userId, $lojaId) {
        try {
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe
            $storeStmt = $db->prepare("
                SELECT id, nome_fantasia, categoria, porcentagem_cashback, website, descricao, logo
                FROM lojas 
                WHERE id = :loja_id AND status = :status
            ");
            $storeStmt->bindParam(':loja_id', $lojaId);
            $status = STORE_APPROVED;
            $storeStmt->bindParam(':status', $status);
            $storeStmt->execute();
            $loja = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$loja) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            // Obter saldo do cliente nesta loja
            $saldoStmt = $db->prepare("
                SELECT 
                    saldo_disponivel,
                    total_creditado,
                    total_usado,
                    data_criacao,
                    ultima_atualizacao
                FROM cashback_saldos 
                WHERE usuario_id = :user_id AND loja_id = :loja_id
            ");
            $saldoStmt->bindParam(':user_id', $userId);
            $saldoStmt->bindParam(':loja_id', $lojaId);
            $saldoStmt->execute();
            $saldo = $saldoStmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não existe saldo, criar array padrão
            if (!$saldo) {
                $saldo = [
                    'saldo_disponivel' => 0,
                    'total_creditado' => 0,
                    'total_usado' => 0,
                    'data_criacao' => null,
                    'ultima_atualizacao' => null
                ];
            }
            
            // Obter movimentações recentes (últimas 10)
            $movimentacoesStmt = $db->prepare("
                SELECT 
                    id,
                    tipo_operacao,
                    valor,
                    saldo_anterior,
                    saldo_atual,
                    descricao,
                    data_operacao,
                    transacao_origem_id,
                    transacao_uso_id
                FROM cashback_movimentacoes 
                WHERE usuario_id = :user_id AND loja_id = :loja_id
                ORDER BY data_operacao DESC
                LIMIT 10
            ");
            $movimentacoesStmt->bindParam(':user_id', $userId);
            $movimentacoesStmt->bindParam(':loja_id', $lojaId);
            $movimentacoesStmt->execute();
            $movimentacoes = $movimentacoesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter estatísticas gerais
            $estatisticasStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_movimentacoes,
                    COUNT(CASE WHEN tipo_operacao = 'credito' THEN 1 END) as total_creditos,
                    COUNT(CASE WHEN tipo_operacao = 'uso' THEN 1 END) as total_usos,
                    COUNT(CASE WHEN tipo_operacao = 'estorno' THEN 1 END) as total_estornos,
                    MIN(data_operacao) as primeira_movimentacao,
                    MAX(data_operacao) as ultima_movimentacao
                FROM cashback_movimentacoes 
                WHERE usuario_id = :user_id AND loja_id = :loja_id
            ");
            $estatisticasStmt->bindParam(':user_id', $userId);
            $estatisticasStmt->bindParam(':loja_id', $lojaId);
            $estatisticasStmt->execute();
            $estatisticas = $estatisticasStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter dados mensais para gráfico (últimos 6 meses)
            $dadosMensaisStmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(data_operacao, '%Y-%m') as mes,
                    SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as creditos,
                    SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as usos,
                    SUM(CASE WHEN tipo_operacao = 'estorno' THEN valor ELSE 0 END) as estornos
                FROM cashback_movimentacoes
                WHERE usuario_id = :user_id AND loja_id = :loja_id
                AND data_operacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(data_operacao, '%Y-%m')
                ORDER BY mes ASC
            ");
            $dadosMensaisStmt->bindParam(':user_id', $userId);
            $dadosMensaisStmt->bindParam(':loja_id', $lojaId);
            $dadosMensaisStmt->execute();
            $dadosMensais = $dadosMensaisStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'loja' => $loja,
                    'saldo' => $saldo,
                    'movimentacoes' => $movimentacoes,
                    'estatisticas' => $estatisticas,
                    'dados_mensais' => $dadosMensais
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes do saldo da loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar detalhes da loja.'];
        }
    }

    /**
    * Simula o uso de saldo de uma loja específica
    * 
    * @param int $userId ID do cliente
    * @param int $lojaId ID da loja
    * @param float $valor Valor a ser usado
    * @return array Resultado da simulação
    */
    public static function simulateBalanceUse($userId, $lojaId, $valor) {
        try {
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter saldo atual
            $saldoStmt = $db->prepare("
                SELECT saldo_disponivel 
                FROM cashback_saldos 
                WHERE usuario_id = :user_id AND loja_id = :loja_id
            ");
            $saldoStmt->bindParam(':user_id', $userId);
            $saldoStmt->bindParam(':loja_id', $lojaId);
            $saldoStmt->execute();
            $saldo = $saldoStmt->fetch(PDO::FETCH_ASSOC);
            
            $saldoAtual = $saldo ? floatval($saldo['saldo_disponivel']) : 0;
            $valorSolicitado = floatval($valor);
            
            $podeUsar = $saldoAtual >= $valorSolicitado && $valorSolicitado > 0;
            $saldoRestante = $podeUsar ? ($saldoAtual - $valorSolicitado) : $saldoAtual;
            
            return [
                'status' => true,
                'data' => [
                    'pode_usar' => $podeUsar,
                    'saldo_atual' => $saldoAtual,
                    'valor_solicitado' => $valorSolicitado,
                    'saldo_restante' => $saldoRestante,
                    'mensagem' => $podeUsar ? 
                        'Valor disponível para uso' : 
                        'Saldo insuficiente. Disponível: R$ ' . number_format($saldoAtual, 2, ',', '.')
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao simular uso de saldo: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar simulação.'];
        }
    }
    /**
    * Obtém os dados do dashboard do cliente
    * 
    * @param int $userId ID do cliente
    * @return array Dados do dashboard
    */
    public static function getDashboardData($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();

            // Verificar e criar tabelas necessárias
            self::createFavoritesTableIfNotExists($db);
            self::createNotificationsTableIfNotExists($db);

            // Verificar se o perfil está incompleto e não há notificação recente
            if (self::isProfileIncomplete($userId) && !self::hasRecentProfileNotification($userId)) {
                // Enviar notificação para completar perfil
                self::notifyClient(
                    $userId, 
                    'Complete seu perfil', 
                    'Complete seus dados cadastrais para aproveitar melhor sua experiência no Klube Cash. Clique aqui para atualizar seu perfil agora.',
                    'warning',
                    CLIENT_PROFILE_URL
                );
            }

            // Obter saldo total de cashback
            $balanceStmt = $db->prepare("
                SELECT SUM(valor_cashback) as saldo_total
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $balanceStmt->bindParam(':user_id', $userId);
            $status = TRANSACTION_APPROVED;
            $balanceStmt->bindParam(':status', $status);
            $balanceStmt->execute();
            $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
            $totalBalance = $balanceData['saldo_total'] ?? 0;
            
            // Obter transações recentes
            $transactionsStmt = $db->prepare("
                SELECT t.*, l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
                ORDER BY t.data_transacao DESC
                LIMIT 5
            ");
            $transactionsStmt->bindParam(':user_id', $userId);
            $transactionsStmt->execute();
            $recentTransactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter estatísticas de cashback
            $statisticsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    MAX(data_transacao) as ultima_transacao
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $statisticsStmt->bindParam(':user_id', $userId);
            $statisticsStmt->bindParam(':status', $status);
            $statisticsStmt->execute();
            $statistics = $statisticsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter lojas favoritas/mais utilizadas
            $favoritesStmt = $db->prepare("
                SELECT 
                    l.id, l.nome_fantasia, 
                    COUNT(t.id) as total_compras,
                    SUM(t.valor_cashback) as total_cashback,
                    l.porcentagem_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id AND t.status = :status
                GROUP BY l.id
                ORDER BY total_compras DESC
                LIMIT 3
            ");
            $favoritesStmt->bindParam(':user_id', $userId);
            $favoritesStmt->bindParam(':status', $status);
            $favoritesStmt->execute();
            $favoriteStores = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter notificações do cliente
            $notifications = self::getClientNotifications($userId);
            
            // Consolidar dados
            return [
                'status' => true,
                'data' => [
                    'saldo_total' => $totalBalance,
                    'transacoes_recentes' => $recentTransactions,
                    'estatisticas' => $statistics,
                    'lojas_favoritas' => $favoriteStores,
                    'notificacoes' => $notifications
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter dados do dashboard: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar dados do dashboard: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtém o extrato de transações do cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para o extrato (período, loja, etc)
     * @param int $page Página atual
     * @return array Extrato de transações
     */
    public static function getStatement($userId, $filters = [], $page = 1) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar consulta base
            $query = "
                SELECT t.*, l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
            ";
            
            $params = [':user_id' => $userId];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por data inicial
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $query .= " AND t.data_transacao >= :data_inicio";
                    $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
                }
                
                // Filtro por data final
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $query .= " AND t.data_transacao <= :data_fim";
                    $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
                }
                
                // Filtro por loja
                if (isset($filters['loja_id']) && !empty($filters['loja_id'])) {
                    $query .= " AND t.loja_id = :loja_id";
                    $params[':loja_id'] = $filters['loja_id'];
                }
                
                // Filtro por status
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $query .= " AND t.status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            
            // Adicionar ordenação
            $query .= " ORDER BY t.data_transacao DESC";
            
            // Calcular total de registros para paginação
            $countStmt = $db->prepare(str_replace('t.*, l.nome_fantasia as loja_nome', 'COUNT(*) as total', $query));
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Adicionar paginação
            $perPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estatísticas
            $statisticsQuery = "
                SELECT 
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    COUNT(*) as total_transacoes
                FROM transacoes_cashback
                WHERE usuario_id = :user_id
            ";
            
            // Aplicar os mesmos filtros nas estatísticas
            if (!empty($filters)) {
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $statisticsQuery .= " AND data_transacao >= :data_inicio";
                }
                
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $statisticsQuery .= " AND data_transacao <= :data_fim";
                }
                
                if (isset($filters['loja_id']) && !empty($filters['loja_id'])) {
                    $statisticsQuery .= " AND loja_id = :loja_id";
                }
                
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $statisticsQuery .= " AND status = :status";
                }
            }
            
            $statsStmt = $db->prepare($statisticsQuery);
            foreach ($params as $param => $value) {
                $statsStmt->bindValue($param, $value);
            }
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'transacoes' => $transactions,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter extrato: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar extrato. Tente novamente.'];
        }
    }
    
    /**
     * Obtém lista de lojas parceiras para o cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para as lojas
     * @param int $page Página atual
     * @return array Lista de lojas parceiras
     */
    public static function getPartnerStores($userId, $filters = [], $page = 1) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar consulta base
            $query = "
                SELECT l.*, 
                       IFNULL(
                           (SELECT SUM(t.valor_cashback) 
                            FROM transacoes_cashback t 
                            WHERE t.loja_id = l.id AND t.usuario_id = :user_id AND t.status = 'aprovado'), 
                           0
                       ) as cashback_recebido,
                       (SELECT COUNT(*) 
                        FROM transacoes_cashback t 
                        WHERE t.loja_id = l.id AND t.usuario_id = :user_id_count) as compras_realizadas
                FROM lojas l
                WHERE l.status = :status
            ";
            
            $params = [
                ':user_id' => $userId,
                ':user_id_count' => $userId,
                ':status' => STORE_APPROVED
            ];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por categoria
                if (isset($filters['categoria']) && !empty($filters['categoria'])) {
                    $query .= " AND l.categoria = :categoria";
                    $params[':categoria'] = $filters['categoria'];
                }
                
                // Filtro por nome
                if (isset($filters['nome']) && !empty($filters['nome'])) {
                    $query .= " AND (l.nome_fantasia LIKE :nome OR l.razao_social LIKE :nome)";
                    $params[':nome'] = '%' . $filters['nome'] . '%';
                }
                
                // Filtro por porcentagem de cashback
                if (isset($filters['cashback_min']) && !empty($filters['cashback_min'])) {
                    $query .= " AND l.porcentagem_cashback >= :cashback_min";
                    $params[':cashback_min'] = $filters['cashback_min'];
                }
            }
            
            // Adicionar ordenação (padrão: porcentagem de cashback decrescente)
            $orderBy = isset($filters['order_by']) ? $filters['order_by'] : 'porcentagem_cashback';
            $orderDir = isset($filters['order_dir']) && strtolower($filters['order_dir']) == 'asc' ? 'ASC' : 'DESC';
            $query .= " ORDER BY l.$orderBy $orderDir";
            
            // Calcular total de registros para paginação
            $countStmt = $db->prepare(str_replace('l.*, IFNULL', 'COUNT(*) as total, IFNULL', $query));
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Adicionar paginação
            $perPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter categorias disponíveis para filtro
            $categoriesStmt = $db->prepare("SELECT DISTINCT categoria FROM lojas WHERE status = :status ORDER BY categoria");
            $categoriesStmt->bindValue(':status', STORE_APPROVED);
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Obter estatísticas
            $statsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_lojas,
                    AVG(porcentagem_cashback) as media_cashback,
                    MAX(porcentagem_cashback) as maior_cashback
                FROM lojas
                WHERE status = :status
            ");
            $statsStmt->bindValue(':status', STORE_APPROVED);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'lojas' => $stores,
                    'categorias' => $categories,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter lojas parceiras: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar lojas parceiras. Tente novamente.'];
        }
    }
    
    /**
     * Obtém detalhes do perfil do cliente
     * 
     * @param int $userId ID do cliente
     * @return array Dados do perfil
     */
    public static function getProfileData($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter dados do perfil
            $stmt = $db->prepare("
                SELECT id, nome, email, data_criacao, ultimo_login, status
                FROM usuarios
                WHERE id = :user_id AND tipo = :tipo
            ");
            $stmt->bindParam(':user_id', $userId);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $stmt->execute();
            $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profileData) {
                return ['status' => false, 'message' => 'Perfil não encontrado.'];
            }
            
            // Obter estatísticas do cliente
            $statsStmt = $db->prepare("
                SELECT 
                    SUM(valor_cashback) as total_cashback,
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    COUNT(DISTINCT loja_id) as total_lojas_utilizadas
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $statsStmt->bindParam(':user_id', $userId);
            $status = TRANSACTION_APPROVED;
            $statsStmt->bindParam(':status', $status);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter informações adicionais do cliente
            $addressStmt = $db->prepare("
                SELECT *
                FROM usuarios_endereco
                WHERE usuario_id = :user_id
                ORDER BY principal DESC
                LIMIT 1
            ");
            $addressStmt->bindParam(':user_id', $userId);
            $addressStmt->execute();
            $address = $addressStmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar se existe tabela de informações adicionais
            $contactStmt = $db->prepare("
                SELECT *
                FROM usuarios_contato
                WHERE usuario_id = :user_id
                LIMIT 1
            ");
            $contactStmt->bindParam(':user_id', $userId);
            $contactStmt->execute();
            $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
            
            // Consolidar dados
            return [
                'status' => true,
                'data' => [
                    'perfil' => $profileData,
                    'estatisticas' => $statistics,
                    'endereco' => $address ?: null,
                    'contato' => $contact ?: null
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter dados do perfil: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar dados do perfil. Tente novamente.'];
        }
    }
    
    /**
     * Atualiza os dados do perfil do cliente
     * 
     * @param int $userId ID do cliente
     * @param array $data Dados a serem atualizados
     * @return array Resultado da operação
     */
    public static function updateProfile($userId, $data) {
        try {
            // Registrar os dados recebidos para diagnóstico
            error_log('Tentando atualizar perfil para usuário ID: ' . $userId);
            error_log('Dados recebidos: ' . print_r($data, true));
            
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se as tabelas necessárias existem
            self::ensureTablesExist($db);
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Atualizar dados básicos
            if (isset($data['nome']) && !empty($data['nome'])) {
                error_log('Atualizando nome do usuário: ' . $data['nome']);
                $updateStmt = $db->prepare("UPDATE usuarios SET nome = :nome WHERE id = :user_id");
                $updateStmt->bindParam(':nome', $data['nome']);
                $updateStmt->bindParam(':user_id', $userId);
                $updateStmt->execute();
            }
            
            // Atualizar senha se fornecida
            if (isset($data['senha_atual']) && isset($data['nova_senha']) && !empty($data['senha_atual']) && !empty($data['nova_senha'])) {
                error_log('Tentando atualizar senha');
                // Verificar senha atual
                $checkStmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = :user_id");
                $checkStmt->bindParam(':user_id', $userId);
                $checkStmt->execute();
                $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($data['senha_atual'], $user['senha_hash'])) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Senha atual incorreta.'];
                }
                
                // Validar nova senha
                if (strlen($data['nova_senha']) < PASSWORD_MIN_LENGTH) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'A nova senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
                }
                
                // Atualizar senha
                $senha_hash = password_hash($data['nova_senha'], PASSWORD_DEFAULT);
                $updatePassStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :user_id");
                $updatePassStmt->bindParam(':senha_hash', $senha_hash);
                $updatePassStmt->bindParam(':user_id', $userId);
                $updatePassStmt->execute();
                error_log('Senha atualizada com sucesso');
            }
            
            // Atualizar/inserir endereço se fornecido
            if (isset($data['endereco']) && !empty($data['endereco'])) {
                error_log('Processando dados de endereço');
                
                // Verificar se todos os campos necessários estão presentes
                $requiredFields = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (!isset($data['endereco'][$field]) || empty($data['endereco'][$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    error_log('Campos de endereço faltando: ' . implode(', ', $missingFields));
                    // Continuar mesmo com campos faltantes, preenchendo com vazios
                    foreach ($missingFields as $field) {
                        $data['endereco'][$field] = '';
                    }
                }
                
                // Verificar se já existe endereço
                $checkAddrStmt = $db->prepare("SELECT id FROM usuarios_endereco WHERE usuario_id = :user_id LIMIT 1");
                $checkAddrStmt->bindParam(':user_id', $userId);
                $checkAddrStmt->execute();
                
                if ($checkAddrStmt->rowCount() > 0) {
                    // Atualizar endereço existente
                    error_log('Atualizando endereço existente');
                    $addrId = $checkAddrStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    $updateAddrStmt = $db->prepare("
                        UPDATE usuarios_endereco 
                        SET 
                            cep = :cep,
                            logradouro = :logradouro,
                            numero = :numero,
                            complemento = :complemento,
                            bairro = :bairro,
                            cidade = :cidade,
                            estado = :estado,
                            principal = :principal
                        WHERE id = :id
                    ");
                    $updateAddrStmt->bindParam(':id', $addrId);
                } else {
                    // Inserir novo endereço
                    error_log('Inserindo novo endereço');
                    $updateAddrStmt = $db->prepare("
                        INSERT INTO usuarios_endereco 
                        (usuario_id, cep, logradouro, numero, complemento, bairro, cidade, estado, principal)
                        VALUES
                        (:user_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :principal)
                    ");
                    $updateAddrStmt->bindParam(':user_id', $userId);
                }
                
                // Garantir que os valores existam
                $cep = isset($data['endereco']['cep']) ? $data['endereco']['cep'] : '';
                $logradouro = isset($data['endereco']['logradouro']) ? $data['endereco']['logradouro'] : '';
                $numero = isset($data['endereco']['numero']) ? $data['endereco']['numero'] : '';
                $complemento = isset($data['endereco']['complemento']) ? $data['endereco']['complemento'] : '';
                $bairro = isset($data['endereco']['bairro']) ? $data['endereco']['bairro'] : '';
                $cidade = isset($data['endereco']['cidade']) ? $data['endereco']['cidade'] : '';
                $estado = isset($data['endereco']['estado']) ? $data['endereco']['estado'] : '';
                $principal = isset($data['endereco']['principal']) ? $data['endereco']['principal'] : 1;
                
                // Bind dos parâmetros comuns
                $updateAddrStmt->bindParam(':cep', $cep);
                $updateAddrStmt->bindParam(':logradouro', $logradouro);
                $updateAddrStmt->bindParam(':numero', $numero);
                $updateAddrStmt->bindParam(':complemento', $complemento);
                $updateAddrStmt->bindParam(':bairro', $bairro);
                $updateAddrStmt->bindParam(':cidade', $cidade);
                $updateAddrStmt->bindParam(':estado', $estado);
                $updateAddrStmt->bindParam(':principal', $principal);
                
                try {
                    $updateAddrStmt->execute();
                    error_log('Endereço salvo com sucesso');
                } catch (PDOException $e) {
                    error_log('Erro ao salvar endereço: ' . $e->getMessage());
                    throw $e; // Relançar para ser capturado pelo catch externo
                }
            }
            
            // Atualizar/inserir contato se fornecido
            if (isset($data['contato']) && !empty($data['contato'])) {
                error_log('Processando dados de contato');
                
                // Verificar se já existe contato
                $checkContactStmt = $db->prepare("SELECT id FROM usuarios_contato WHERE usuario_id = :user_id LIMIT 1");
                $checkContactStmt->bindParam(':user_id', $userId);
                $checkContactStmt->execute();
                
                if ($checkContactStmt->rowCount() > 0) {
                    // Atualizar contato existente
                    error_log('Atualizando contato existente');
                    $contactId = $checkContactStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    $updateContactStmt = $db->prepare("
                        UPDATE usuarios_contato 
                        SET 
                            telefone = :telefone,
                            celular = :celular,
                            email_alternativo = :email_alternativo
                        WHERE id = :id
                    ");
                    $updateContactStmt->bindParam(':id', $contactId);
                } else {
                    // Inserir novo contato
                    error_log('Inserindo novo contato');
                    $updateContactStmt = $db->prepare("
                        INSERT INTO usuarios_contato 
                        (usuario_id, telefone, celular, email_alternativo)
                        VALUES
                        (:user_id, :telefone, :celular, :email_alternativo)
                    ");
                    $updateContactStmt->bindParam(':user_id', $userId);
                }
                
                // Garantir que os valores existam
                $telefone = isset($data['contato']['telefone']) ? $data['contato']['telefone'] : '';
                $celular = isset($data['contato']['celular']) ? $data['contato']['celular'] : '';
                $email_alternativo = isset($data['contato']['email_alternativo']) ? $data['contato']['email_alternativo'] : '';
                
                // Bind dos parâmetros comuns
                $updateContactStmt->bindParam(':telefone', $telefone);
                $updateContactStmt->bindParam(':celular', $celular);
                $updateContactStmt->bindParam(':email_alternativo', $email_alternativo);
                
                try {
                    $updateContactStmt->execute();
                    error_log('Contato salvo com sucesso');
                } catch (PDOException $e) {
                    error_log('Erro ao salvar contato: ' . $e->getMessage());
                    throw $e; // Relançar para ser capturado pelo catch externo
                }
            }
            
            // Verificar se o perfil está completo após a atualização
            $profileComplete = true;
            
            // Verificar se tem endereço
            $checkAddrStmt = $db->prepare("SELECT id FROM usuarios_endereco WHERE usuario_id = :user_id LIMIT 1");
            $checkAddrStmt->bindParam(':user_id', $userId);
            $checkAddrStmt->execute();
            if ($checkAddrStmt->rowCount() == 0) {
                $profileComplete = false;
                error_log('Perfil incompleto: Falta endereço');
            }
            
            // Verificar se tem contato
            $checkContactStmt = $db->prepare("SELECT id FROM usuarios_contato WHERE usuario_id = :user_id LIMIT 1");
            $checkContactStmt->bindParam(':user_id', $userId);
            $checkContactStmt->execute();
            if ($checkContactStmt->rowCount() == 0) {
                $profileComplete = false;
                error_log('Perfil incompleto: Falta contato');
            }
            
            // Verificar se tem telefone no cadastro principal
            $phoneStmt = $db->prepare("
                SELECT telefone FROM usuarios 
                WHERE id = :user_id AND (telefone IS NOT NULL AND telefone != '')
            ");
            $phoneStmt->bindParam(':user_id', $userId);
            $phoneStmt->execute();
            if ($phoneStmt->rowCount() == 0) {
                $profileComplete = false;
                error_log('Perfil incompleto: Falta telefone principal');
            }
            
            // Se o perfil estiver completo, marcar notificações relacionadas como lidas
            if ($profileComplete) {
                error_log('Perfil completo! Marcando notificações como lidas');
                
                // Verificar se a tabela de notificações tem a coluna 'lida'
                $tableColumnsStmt = $db->prepare("SHOW COLUMNS FROM notificacoes LIKE 'lida'");
                $tableColumnsStmt->execute();
                
                if ($tableColumnsStmt->rowCount() > 0) {
                    $updateNotificationsStmt = $db->prepare("
                        UPDATE notificacoes 
                        SET lida = 1, data_leitura = NOW() 
                        WHERE usuario_id = :user_id 
                        AND (
                            titulo LIKE '%perfil%' OR 
                            mensagem LIKE '%perfil%' OR 
                            mensagem LIKE '%dados cadastrais%'
                        )
                        AND lida = 0
                    ");
                    $updateNotificationsStmt->bindParam(':user_id', $userId);
                    $updateNotificationsStmt->execute();
                    
                    $affectedRows = $updateNotificationsStmt->rowCount();
                    error_log("$affectedRows notificações de perfil marcadas como lidas");
                } else {
                    error_log("Coluna 'lida' não encontrada na tabela notificacoes");
                }
            }
            
            // Confirmar transação
            $db->commit();
            error_log('Transação concluída com sucesso - Perfil atualizado');
            
            return ['status' => true, 'message' => 'Perfil atualizado com sucesso.'];
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            // Log detalhado do erro
            error_log('ERRO DETALHADO ao atualizar perfil: ' . $e->getMessage());
            error_log('Código do erro: ' . $e->getCode());
            error_log('Trace: ' . $e->getTraceAsString());
            
            // Em ambiente de desenvolvimento, mostrar erro detalhado
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                return ['status' => false, 'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
            } else {
                return ['status' => false, 'message' => 'Erro ao atualizar perfil. Tente novamente.'];
            }
        }
    }
    
    
    /**
     * Verifica e cria as tabelas necessárias se não existirem
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function ensureTablesExist($db) {
        try {
            // Verificar e criar tabela de endereço
            $stmt = $db->query("SHOW TABLES LIKE 'usuarios_endereco'");
            if ($stmt->rowCount() == 0) {
                error_log('Criando tabela usuarios_endereco');
                $createTable = "CREATE TABLE usuarios_endereco (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    cep VARCHAR(10) DEFAULT NULL,
                    logradouro VARCHAR(255) DEFAULT NULL,
                    numero VARCHAR(20) DEFAULT NULL,
                    complemento VARCHAR(100) DEFAULT NULL,
                    bairro VARCHAR(100) DEFAULT NULL,
                    cidade VARCHAR(100) DEFAULT NULL,
                    estado VARCHAR(50) DEFAULT NULL,
                    principal TINYINT(1) DEFAULT 1,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )";
                
                $db->exec($createTable);
            }
            
            // Verificar e criar tabela de contato
            $stmt = $db->query("SHOW TABLES LIKE 'usuarios_contato'");
            if ($stmt->rowCount() == 0) {
                error_log('Criando tabela usuarios_contato');
                $createTable = "CREATE TABLE usuarios_contato (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    telefone VARCHAR(20) DEFAULT NULL,
                    celular VARCHAR(20) DEFAULT NULL,
                    email_alternativo VARCHAR(100) DEFAULT NULL,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )";
                
                $db->exec($createTable);
            }
        } catch (PDOException $e) {
            error_log('Erro ao verificar/criar tabelas: ' . $e->getMessage());
            // Não é necessário relançar a exceção, apenas registrar o erro
        }
    }
    /**
     * Valida se o cliente existe e está ativo
     * 
     * @param int $userId ID do cliente
     * @return bool Verdadeiro se o cliente é válido, falso caso contrário
     */
    private static function isProfileIncomplete($userId) {
        try {
            $db = Database::getConnection();
            
            // Verificar se o usuário tem dados de contato
            $contactStmt = $db->prepare("
                SELECT id FROM usuarios_contato
                WHERE usuario_id = :user_id
                LIMIT 1
            ");
            $contactStmt->bindParam(':user_id', $userId);
            $contactStmt->execute();
            $hasContact = $contactStmt->rowCount() > 0;
            
            // Verificar se o usuário tem dados de endereço
            $addressStmt = $db->prepare("
                SELECT id FROM usuarios_endereco
                WHERE usuario_id = :user_id
                LIMIT 1
            ");
            $addressStmt->bindParam(':user_id', $userId);
            $addressStmt->execute();
            $hasAddress = $addressStmt->rowCount() > 0;
            
            // Verificar se o usuário tem número de telefone no cadastro principal
            $phoneStmt = $db->prepare("
                SELECT telefone FROM usuarios
                WHERE id = :user_id AND (telefone IS NOT NULL AND telefone != '')
            ");
            $phoneStmt->bindParam(':user_id', $userId);
            $phoneStmt->execute();
            $hasPhone = $phoneStmt->rowCount() > 0;
            
            // Perfil está incompleto se faltar um desses dados
            return !($hasContact && $hasAddress && $hasPhone);
            
        } catch (PDOException $e) {
            error_log('Erro ao verificar perfil incompleto: ' . $e->getMessage());
            return false; // Em caso de erro, assumimos que não está incompleto para não enviar notificações desnecessárias
        }
    }
    /**
    * Verifica se já existe uma notificação recente sobre completar o perfil
    * 
    * @param int $userId ID do usuário
    * @param int $dias Número de dias para considerar uma notificação recente
    * @return bool true se existe uma notificação recente
    */
    private static function hasRecentProfileNotification($userId, $dias = 7) {
        try {
            $db = Database::getConnection();
            
            // Criar tabela de notificações se não existir
            self::createNotificationsTableIfNotExists($db);
            
            // Calcular data limite para notificações recentes
            $dataLimite = date('Y-m-d H:i:s', strtotime("-$dias days"));
            
            // Verificar se existe notificação recente sobre preenchimento de perfil
            $stmt = $db->prepare("
                SELECT id FROM notificacoes
                WHERE usuario_id = :user_id 
                AND (titulo LIKE '%perfil%' OR mensagem LIKE '%perfil%')
                AND data_criacao >= :data_limite
                LIMIT 1
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':data_limite', $dataLimite);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log('Erro ao verificar notificações recentes: ' . $e->getMessage());
            return true; // Em caso de erro, assumimos que já existe para evitar enviar duplicadas
        }
    }
    /**
     * Registra uma nova transação de cashback
     * 
     * @param array $data Dados da transação
     * @return array Resultado da operação
     */
    public static function registerTransaction($data) {
        try {
            // Validar dados obrigatórios
            $requiredFields = ['usuario_id', 'loja_id', 'valor_total', 'codigo_transacao'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Dados da transação incompletos. Campo faltante: ' . $field];
                }
            }
            
            // Verificar se é um cliente válido
            if (!self::validateClient($data['usuario_id'])) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está aprovada
            $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = :loja_id AND status = :status");
            $storeStmt->bindParam(':loja_id', $data['loja_id']);
            $status = STORE_APPROVED;
            $storeStmt->bindParam(':status', $status);
            $storeStmt->execute();
            $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Verificar se o valor da transação é válido
            if ($data['valor_total'] < MIN_TRANSACTION_VALUE) {
                return ['status' => false, 'message' => 'Valor mínimo para transação é R$ ' . number_format(MIN_TRANSACTION_VALUE, 2, ',', '.')];
            }
            
            // Verificar se já existe uma transação com o mesmo código
            $checkStmt = $db->prepare("
                SELECT id FROM transacoes_cashback 
                WHERE codigo_transacao = :codigo_transacao AND loja_id = :loja_id
            ");
            $checkStmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $checkStmt->bindParam(':loja_id', $data['loja_id']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma transação com este código.'];
            }
            
            // Obter configurações de cashback
            $configStmt = $db->prepare("SELECT * FROM configuracoes_cashback ORDER BY id DESC LIMIT 1");
            $configStmt->execute();
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular valores de cashback
            $porcentagemTotal = isset($config['porcentagem_total']) ? $config['porcentagem_total'] : DEFAULT_CASHBACK_TOTAL;
            $porcentagemCliente = isset($config['porcentagem_cliente']) ? $config['porcentagem_cliente'] : DEFAULT_CASHBACK_CLIENT;
            $porcentagemAdmin = isset($config['porcentagem_admin']) ? $config['porcentagem_admin'] : DEFAULT_CASHBACK_ADMIN;
            // CORREÇÃO: Loja sempre recebe 0%
            $porcentagemLoja = 0.00;
            // Verificar se a loja tem porcentagem específica
            if (isset($store['porcentagem_cashback']) && $store['porcentagem_cashback'] > 0) {
                $porcentagemTotal = $store['porcentagem_cashback'];
                // Ajustar proporcionalmente mas loja continua em 0%
                $fator = $porcentagemTotal / DEFAULT_CASHBACK_TOTAL;
                $porcentagemCliente = DEFAULT_CASHBACK_CLIENT * $fator;
                $porcentagemAdmin = DEFAULT_CASHBACK_ADMIN * $fator;
                $porcentagemLoja = 0.00; // Loja sempre 0%
            }
            
            // Calcular valores
            $valorCashbackTotal = ($data['valor_total'] * $porcentagemTotal) / 100;
            $valorCashbackCliente = ($data['valor_total'] * $porcentagemCliente) / 100;
            $valorCashbackAdmin = ($data['valor_total'] * $porcentagemAdmin) / 100;
            $valorCashbackLoja = 0.00; // CORREÇÃO: Loja não recebe nada
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Registrar transação principal
            $stmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cashback,
                    codigo_transacao, data_transacao, status, descricao
                ) VALUES (
                    :usuario_id, :loja_id, :valor_total, :valor_cashback,
                    :codigo_transacao, NOW(), :status, :descricao
                )
            ");
            
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':loja_id', $data['loja_id']);
            $stmt->bindParam(':valor_total', $data['valor_total']);
            $stmt->bindParam(':valor_cashback', $valorCashbackCliente);
            $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $status = TRANSACTION_APPROVED; // Ou TRANSACTION_PENDING dependendo da lógica de negócio
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':descricao', $data['descricao'] ?? 'Compra na ' . $store['nome_fantasia']);
            $stmt->execute();
            
            $transactionId = $db->lastInsertId();
            
            // Registrar transação para o administrador (comissão admin)
            if ($valorCashbackAdmin > 0) {
                $adminStmt = $db->prepare("
                    INSERT INTO transacoes_comissao (
                        tipo_usuario, usuario_id, loja_id, transacao_id,
                        valor_total, valor_comissao, data_transacao, status
                    ) VALUES (
                        :tipo_usuario, :usuario_id, :loja_id, :transacao_id,
                        :valor_total, :valor_comissao, NOW(), :status
                    )
                ");
                
                $tipoAdmin = USER_TYPE_ADMIN;
                $adminStmt->bindParam(':tipo_usuario', $tipoAdmin);
                $adminId = 1; // Administrador padrão
                $adminStmt->bindParam(':usuario_id', $adminId);
                $adminStmt->bindParam(':loja_id', $data['loja_id']);
                $adminStmt->bindParam(':transacao_id', $transactionId);
                $adminStmt->bindParam(':valor_total', $data['valor_total']);
                $adminStmt->bindParam(':valor_comissao', $valorCashbackAdmin);
                $adminStmt->bindParam(':status', $status);
                $adminStmt->execute();
            }
            
            // Registrar transação para a loja (comissão loja)
            if ($valorCashbackLoja > 0) {
                $storeStmt = $db->prepare("
                    INSERT INTO transacoes_comissao (
                        tipo_usuario, usuario_id, loja_id, transacao_id,
                        valor_total, valor_comissao, data_transacao, status
                    ) VALUES (
                        :tipo_usuario, :usuario_id, :loja_id, :transacao_id,
                        :valor_total, :valor_comissao, NOW(), :status
                    )
                ");
                
                $tipoLoja = USER_TYPE_STORE;
                $storeStmt->bindParam(':tipo_usuario', $tipoLoja);
                $storeUserId = $store['usuario_id'] ?? $store['id']; // ID do usuário da loja ou da própria loja
                $storeStmt->bindParam(':usuario_id', $storeUserId);
                $storeStmt->bindParam(':loja_id', $data['loja_id']);
                $storeStmt->bindParam(':transacao_id', $transactionId);
                $storeStmt->bindParam(':valor_total', $data['valor_total']);
                $storeStmt->bindParam(':valor_comissao', $valorCashbackLoja);
                $storeStmt->bindParam(':status', $status);
                $storeStmt->execute();
            }
            
            // Enviar notificação ao cliente
            self::notifyClient($data['usuario_id'], 'Nova transação de cashback', 'Você recebeu R$ ' . number_format($valorCashbackCliente, 2, ',', '.') . ' de cashback na loja ' . $store['nome_fantasia']);
            
            // Enviar email de confirmação ao cliente
            $userStmt = $db->prepare("SELECT nome, email FROM usuarios WHERE id = :user_id");
            $userStmt->bindParam(':user_id', $data['usuario_id']);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $transactionData = [
                    'loja' => $store['nome_fantasia'],
                    'valor_total' => $data['valor_total'],
                    'valor_cashback' => $valorCashbackCliente,
                    'data_transacao' => date('Y-m-d H:i:s')
                ];
                
                Email::sendTransactionConfirmation($user['email'], $user['nome'], $transactionData);
            }
            
            // Confirmar transação
            $db->commit();
            
            return [
                'status' => true, 
                'message' => 'Transação registrada com sucesso.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'cashback_value' => $valorCashbackCliente
                ]
            ];
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao registrar transação: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao registrar transação. Tente novamente.'];
        }
    }
    
    /**
     * Obtém detalhes de uma transação específica
     * 
     * @param int $userId ID do cliente
     * @param int $transactionId ID da transação
     * @return array Dados da transação
     */
    public static function getTransactionDetails($userId, $transactionId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter detalhes da transação
            $stmt = $db->prepare("
                SELECT t.*, l.nome_fantasia as loja_nome, l.logo as loja_logo, l.categoria as loja_categoria
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = :transaction_id AND t.usuario_id = :user_id
            ");
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return ['status' => false, 'message' => 'Transação não encontrada ou não pertence a este usuário.'];
            }
            
            // Obter histórico de status, se existir
            $historyStmt = $db->prepare("
                SELECT *
                FROM transacoes_status_historico
                WHERE transacao_id = :transaction_id
                ORDER BY data_alteracao DESC
            ");
            $historyStmt->bindParam(':transaction_id', $transactionId);
            $historyStmt->execute();
            $statusHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'transacao' => $transaction,
                    'historico_status' => $statusHistory
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes da transação: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar detalhes da transação. Tente novamente.'];
        }
    }
    
    /**
     * Gera relatório de cashback para o cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para o relatório
     * @return array Dados do relatório
     */
    public static function generateCashbackReport($userId, $filters = []) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar condições da consulta
            $conditions = "WHERE t.usuario_id = :user_id";
            $params = [':user_id' => $userId];
            
            // Aplicar filtros de data
            if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                $conditions .= " AND t.data_transacao >= :data_inicio";
                $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
            }
            
            if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                $conditions .= " AND t.data_transacao <= :data_fim";
                $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
            }
            
            // Estatísticas gerais
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    AVG(valor_cashback) as media_cashback
                FROM transacoes_cashback t
                $conditions
                AND t.status = :status
            ";
            
            $statsStmt = $db->prepare($statsQuery);
            foreach ($params as $param => $value) {
                $statsStmt->bindValue($param, $value);
            }
            $status = TRANSACTION_APPROVED;
            $statsStmt->bindValue(':status', $status);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Cashback por loja
            $storesQuery = "
                SELECT 
                    l.id, l.nome_fantasia, l.categoria,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                $conditions
                AND t.status = :status
                GROUP BY l.id
                ORDER BY total_cashback DESC
            ";
            
            $storesStmt = $db->prepare($storesQuery);
            foreach ($params as $param => $value) {
                $storesStmt->bindValue($param, $value);
            }
            $storesStmt->bindValue(':status', $status);
            $storesStmt->execute();
            $storesData = $storesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cashback por mês
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(t.data_transacao, '%Y-%m') as mes,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                $conditions
                AND t.status = :status
                GROUP BY DATE_FORMAT(t.data_transacao, '%Y-%m')
                ORDER BY mes DESC
            ";
            
            $monthlyStmt = $db->prepare($monthlyQuery);
            foreach ($params as $param => $value) {
                $monthlyStmt->bindValue($param, $value);
            }
            $monthlyStmt->bindValue(':status', $status);
            $monthlyStmt->execute();
            $monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cashback por categoria
            $categoryQuery = "
                SELECT 
                    l.categoria,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                $conditions
                AND t.status = :status
                GROUP BY l.categoria
                ORDER BY total_cashback DESC
            ";
            
            $categoryStmt = $db->prepare($categoryQuery);
            foreach ($params as $param => $value) {
                $categoryStmt->bindValue($param, $value);
            }
            $categoryStmt->bindValue(':status', $status);
            $categoryStmt->execute();
            $categoryData = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'estatisticas' => $statistics,
                    'por_loja' => $storesData,
                    'por_mes' => $monthlyData,
                    'por_categoria' => $categoryData
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao gerar relatório: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao gerar relatório. Tente novamente.'];
        }
    }
    /**
    * Obtém o saldo completo do cliente com detalhes por loja
    * 
    * @param int $userId ID do cliente
    * @return array Resultado da operação
    */
    public static function getClientBalanceDetails($userId) {
        try {
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            require_once __DIR__ . '/../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            
            $balances = $balanceModel->getAllUserBalances($userId);
            $totalBalance = $balanceModel->getTotalBalance($userId);
            
            // Enriquecer dados com estatísticas
            foreach ($balances as &$balance) {
                $stats = $balanceModel->getBalanceStatistics($userId, $balance['loja_id']);
                $balance['estatisticas'] = $stats;
            }
            
            return [
                'status' => true,
                'data' => [
                    'saldo_total' => $totalBalance,
                    'saldos_por_loja' => $balances,
                    'total_lojas' => count($balances)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao obter detalhes do saldo: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar saldo.'];
        }
    }

    /**
    * Usa saldo do cliente em uma loja específica
    * 
    * @param int $userId ID do cliente
    * @param int $storeId ID da loja
    * @param float $amount Valor a ser usado
    * @param string $description Descrição do uso
    * @return array Resultado da operação
    */
    public static function useClientBalance($userId, $storeId, $amount, $description = '', $transactionId = null) {
        try {
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            require_once __DIR__ . '/../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            
            $currentBalance = $balanceModel->getStoreBalance($userId, $storeId);
            
            if ($currentBalance < $amount) {
                return [
                    'status' => false, 
                    'message' => 'Saldo insuficiente. Disponível: R$ ' . number_format($currentBalance, 2, ',', '.')
                ];
            }
            
            // Adicionar ID da transação na descrição se não fornecida
            if (empty($description) && $transactionId) {
                $description = "Uso do saldo - Transação #" . $transactionId;
            } elseif (empty($description)) {
                $description = "Uso do saldo de cashback";
            }
            
            if ($balanceModel->useBalance($userId, $storeId, $amount, $description, $transactionId)) {
                $newBalance = $balanceModel->getStoreBalance($userId, $storeId);
                
                return [
                    'status' => true,
                    'message' => 'Saldo usado com sucesso!',
                    'data' => [
                        'valor_usado' => $amount,
                        'saldo_anterior' => $currentBalance,
                        'saldo_atual' => $newBalance,
                        'transacao_id' => $transactionId
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Erro ao usar saldo. Tente novamente.'];
            }
            
        } catch (Exception $e) {
            error_log('Erro ao usar saldo: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar uso do saldo.'];
        }
    }

    /**
    * Obtém o histórico de movimentações do saldo de uma loja
    * 
    * @param int $userId ID do cliente
    * @param int $storeId ID da loja
    * @param int $page Página atual
    * @param int $limit Itens por página
    * @return array Resultado da operação
    */
    public static function getBalanceHistory($userId, $storeId, $page = 1, $limit = 20) {
        try {
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            require_once __DIR__ . '/../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            
            $offset = ($page - 1) * $limit;
            $history = $balanceModel->getMovementHistory($userId, $storeId, $limit, $offset);
            
            return [
                'status' => true,
                'data' => [
                    'movimentacoes' => $history,
                    'pagina_atual' => $page,
                    'itens_por_pagina' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao obter histórico: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar histórico.'];
        }
    }




    /**
     * Marca uma loja como favorita
     * 
     * @param int $userId ID do cliente
     * @param int $storeId ID da loja
     * @param bool $favorite true para favoritar, false para desfavoritar
     * @return array Resultado da operação
     */
    public static function toggleFavoriteStore($userId, $storeId, $favorite = true) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está aprovada
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE id = :store_id AND status = :status");
            $storeStmt->bindParam(':store_id', $storeId);
            $status = STORE_APPROVED;
            $storeStmt->bindParam(':status', $status);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Verificar se a tabela de favoritos existe, se não, criar
            self::createFavoritesTableIfNotExists($db);
            
            // Verificar se já está favoritada
            $checkStmt = $db->prepare("
                SELECT id FROM lojas_favoritas
                WHERE usuario_id = :user_id AND loja_id = :store_id
            ");
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':store_id', $storeId);
            $checkStmt->execute();
            $isFavorite = $checkStmt->rowCount() > 0;
            
            if ($favorite && !$isFavorite) {
                // Adicionar aos favoritos
                $addStmt = $db->prepare("
                    INSERT INTO lojas_favoritas (usuario_id, loja_id, data_criacao)
                    VALUES (:user_id, :store_id, NOW())
                ");
                $addStmt->bindParam(':user_id', $userId);
                $addStmt->bindParam(':store_id', $storeId);
                $addStmt->execute();
                
                return ['status' => true, 'message' => 'Loja adicionada aos favoritos.'];
            } else if (!$favorite && $isFavorite) {
                // Remover dos favoritos
                $removeStmt = $db->prepare("
                    DELETE FROM lojas_favoritas
                    WHERE usuario_id = :user_id AND loja_id = :store_id
                ");
                $removeStmt->bindParam(':user_id', $userId);
                $removeStmt->bindParam(':store_id', $storeId);
                $removeStmt->execute();
                
                return ['status' => true, 'message' => 'Loja removida dos favoritos.'];
            }
            
            return ['status' => true, 'message' => 'Nenhuma alteração necessária.'];
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar favoritos: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao atualizar favoritos. Tente novamente.'];
        }
    }
    
    /**
     * Obtém as lojas favoritas do cliente
     * 
     * @param int $userId ID do cliente
     * @return array Lista de lojas favoritas
     */
    public static function getFavoriteStores($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a tabela de favoritos existe
            self::createFavoritesTableIfNotExists($db);
            
            // Obter lojas favoritas
            $stmt = $db->prepare("
                SELECT l.*, f.data_criacao as data_favoritado
                FROM lojas_favoritas f
                JOIN lojas l ON f.loja_id = l.id
                WHERE f.usuario_id = :user_id AND l.status = :status
                ORDER BY f.data_criacao DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $status = STORE_APPROVED;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => $favorites
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter lojas favoritas: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar lojas favoritas. Tente novamente.'];
        }
    }
    
    /**
     * Valida se o usuário é um cliente ativo
     * 
     * @param int $userId ID do usuário
     * @return bool true se for cliente ativo, false caso contrário
     */
    private static function validateClient($userId) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT id FROM usuarios
                WHERE id = :user_id AND tipo = :tipo AND status = :status
            ");
            $stmt->bindParam(':user_id', $userId);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao validar cliente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações para o cliente
     * 
     * @param int $userId ID do cliente
     * @param int $limit Limite de notificações
     * @return array Lista de notificações
     */
    private static function getClientNotifications($userId, $limit = 5, $onlyUnread = true) {
        try {
            $db = Database::getConnection();
            
            // Verificar se a tabela de notificações existe
            self::createNotificationsTableIfNotExists($db);
            
            // Obter notificações
            $query = "
                SELECT *
                FROM notificacoes
                WHERE usuario_id = :user_id
            ";
            
            if ($onlyUnread) {
                $query .= " AND lida = 0";
            }
            
            $query .= " ORDER BY data_criacao DESC LIMIT :limit";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao obter notificações: ' . $e->getMessage());
            return [];
        }
    }
    /**
    * Obtém o saldo de cashback do cliente por loja
    * 
    * @param int $userId ID do cliente
    * @return array Saldos por loja e total
    */
    public static function getClientBalance($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter saldo por loja - apenas transações aprovadas
            $balanceQuery = "
                SELECT 
                    l.id as loja_id,
                    l.nome_fantasia,
                    l.logo,
                    l.categoria,
                    l.porcentagem_cashback,
                    SUM(t.valor_cashback) as saldo_disponivel,
                    COUNT(t.id) as total_transacoes,
                    MAX(t.data_transacao) as ultima_transacao,
                    SUM(t.valor_total) as total_compras
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id 
                AND t.status = :status
                GROUP BY l.id, l.nome_fantasia, l.logo, l.categoria, l.porcentagem_cashback
                HAVING saldo_disponivel > 0
                ORDER BY saldo_disponivel DESC
            ";
            
            $balanceStmt = $db->prepare($balanceQuery);
            $balanceStmt->bindParam(':user_id', $userId);
            $status = TRANSACTION_APPROVED;
            $balanceStmt->bindParam(':status', $status);
            $balanceStmt->execute();
            $saldosPorLoja = $balanceStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular saldo total
            $saldoTotal = 0;
            $totalTransacoes = 0;
            $totalCompras = 0;
            
            foreach ($saldosPorLoja as $loja) {
                $saldoTotal += $loja['saldo_disponivel'];
                $totalTransacoes += $loja['total_transacoes'];
                $totalCompras += $loja['total_compras'];
            }
            
            // Obter estatísticas gerais
            $estatisticasQuery = "
                SELECT 
                    COUNT(DISTINCT loja_id) as total_lojas_utilizadas,
                    COUNT(*) as total_transacoes_historico,
                    SUM(valor_total) as total_compras_historico,
                    SUM(valor_cashback) as total_cashback_historico,
                    AVG(valor_cashback) as media_cashback
                FROM transacoes_cashback
                WHERE usuario_id = :user_id
                AND status = :status
            ";
            
            $estatisticasStmt = $db->prepare($estatisticasQuery);
            $estatisticasStmt->bindParam(':user_id', $userId);
            $estatisticasStmt->bindParam(':status', $status);
            $estatisticasStmt->execute();
            $estatisticas = $estatisticasStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter saldo pendente (aguardando aprovação de pagamento)
            $saldoPendenteQuery = "
                SELECT 
                    l.nome_fantasia,
                    SUM(t.valor_cashback) as saldo_pendente
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id 
                AND t.status = :status_pendente
                GROUP BY l.id, l.nome_fantasia
                HAVING saldo_pendente > 0
                ORDER BY saldo_pendente DESC
            ";
            
            $saldoPendenteStmt = $db->prepare($saldoPendenteQuery);
            $saldoPendenteStmt->bindParam(':user_id', $userId);
            $statusPendente = TRANSACTION_PENDING;
            $saldoPendenteStmt->bindParam(':status_pendente', $statusPendente);
            $saldoPendenteStmt->execute();
            $saldosPendentes = $saldoPendenteStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'saldo_total' => $saldoTotal,
                    'saldos_por_loja' => $saldosPorLoja,
                    'saldos_pendentes' => $saldosPendentes,
                    'estatisticas' => [
                        'total_lojas_com_saldo' => count($saldosPorLoja),
                        'total_transacoes' => $totalTransacoes,
                        'total_compras' => $totalCompras,
                        'total_lojas_utilizadas' => $estatisticas['total_lojas_utilizadas'],
                        'total_transacoes_historico' => $estatisticas['total_transacoes_historico'],
                        'total_compras_historico' => $estatisticas['total_compras_historico'],
                        'total_cashback_historico' => $estatisticas['total_cashback_historico'],
                        'media_cashback' => $estatisticas['media_cashback']
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter saldo do cliente: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar saldo. Tente novamente.'];
        }
    }
    /**
    * Envia uma notificação para o cliente
    * 
    * @param int $userId ID do cliente
    * @param string $titulo Título da notificação
    * @param string $mensagem Mensagem da notificação
    * @param string $tipo Tipo da notificação (info, success, warning, error)
    * @param string $link Link associado à notificação (opcional)
    * @return bool Resultado da operação
    */
    private static function notifyClient($userId, $titulo, $mensagem, $tipo = 'info', $link = '') {
        try {
            $db = Database::getConnection();
            
            // Verificar se a tabela de notificações existe
            self::createNotificationsTableIfNotExists($db);
            
            // Inserir notificação
            $stmt = $db->prepare("
                INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link, data_criacao, lida)
                VALUES (:user_id, :titulo, :mensagem, :tipo, :link, NOW(), 0)
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensagem', $mensagem);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':link', $link);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao enviar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria a tabela de favoritos se não existir
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function createFavoritesTableIfNotExists($db) {
        try {
            // Verificar se a tabela existe
            $stmt = $db->prepare("SHOW TABLES LIKE 'lojas_favoritas'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Criar a tabela
                $createTable = "CREATE TABLE lojas_favoritas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    loja_id INT NOT NULL,
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_favorite (usuario_id, loja_id),
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                    FOREIGN KEY (loja_id) REFERENCES lojas(id)
                )";
                
                $db->exec($createTable);
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar tabela de favoritos: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria a tabela de notificações se não existir
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function createNotificationsTableIfNotExists($db) {
        try {
            // Verificar se a tabela existe
            $stmt = $db->prepare("SHOW TABLES LIKE 'notificacoes'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Criar a tabela com a coluna 'link'
                $createTable = "CREATE TABLE notificacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    titulo VARCHAR(100) NOT NULL,
                    mensagem TEXT NOT NULL,
                    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                    link VARCHAR(255) DEFAULT '',
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    lida TINYINT(1) DEFAULT 0,
                    data_leitura TIMESTAMP NULL,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )";
                
                $db->exec($createTable);
                error_log('Tabela notificacoes criada com sucesso');
            } else {
                // Verificar se a coluna 'link' existe
                $columnCheckStmt = $db->prepare("SHOW COLUMNS FROM notificacoes LIKE 'link'");
                $columnCheckStmt->execute();
                
                // Se a coluna não existir, adicionar
                if ($columnCheckStmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE notificacoes ADD COLUMN link VARCHAR(255) DEFAULT ''");
                    error_log('Coluna link adicionada à tabela notificacoes');
                }
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar/verificar tabela de notificações: ' . $e->getMessage());
        }
    }
}

// Processar requisições diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'ClientController.php') {
    // Verificar se o usuário está autenticado
    if (!AuthController::isAuthenticated()) {
        header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
        exit;
    }
    
    // Verificar se é um cliente
    if (AuthController::isAdmin() || AuthController::isStore()) {
        header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a clientes.'));
        exit;
    }
    
    $userId = AuthController::getCurrentUserId();
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {


        case 'get_balance_details':
            $result = self::getClientBalanceDetails($userId);
            echo json_encode($result);
            break;
            
        case 'get_store_balance':
            $storeId = intval($_GET['store_id'] ?? $_POST['store_id'] ?? 0);
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $balance = $balanceModel->getStoreBalance($userId, $storeId);
                $statistics = $balanceModel->getBalanceStatistics($userId, $storeId);
                
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'saldo_disponivel' => $balance,
                        'estatisticas' => $statistics
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao obter saldo da loja']);
            }
            break;
            
        case 'get_total_balance':
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $totalBalance = $balanceModel->getTotalBalance($userId);
                
                echo json_encode([
                    'status' => true,
                    'data' => ['saldo_total' => $totalBalance]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao obter saldo total']);
            }
            break;
            
        case 'use_balance':
            $storeId = intval($_POST['store_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $transactionId = intval($_POST['transaction_id'] ?? 0) ?: null;
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            if ($amount <= 0) {
                echo json_encode(['status' => false, 'message' => 'Valor deve ser maior que zero']);
                return;
            }
            
            $result = self::useClientBalance($userId, $storeId, $amount, $description, $transactionId);
            echo json_encode($result);
            break;
            
        case 'get_balance_history':
            $storeId = intval($_GET['store_id'] ?? 0);
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            $result = self::getBalanceHistory($userId, $storeId, $page, $limit);
            echo json_encode($result);
            break;
            
        case 'simulate_balance_use':
            // Simular uso do saldo (para calculadoras em formulários)
            $storeId = intval($_POST['store_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            
            if ($storeId <= 0 || $amount <= 0) {
                echo json_encode(['status' => false, 'message' => 'Parâmetros inválidos']);
                return;
            }
            
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $currentBalance = $balanceModel->getStoreBalance($userId, $storeId);
                
                $canUse = $currentBalance >= $amount;
                $remainingBalance = $canUse ? ($currentBalance - $amount) : $currentBalance;
                
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'pode_usar' => $canUse,
                        'saldo_atual' => $currentBalance,
                        'valor_solicitado' => $amount,
                        'saldo_restante' => $remainingBalance,
                        'valor_maximo_permitido' => $currentBalance
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao simular uso do saldo']);
            }
            break;
            
        case 'refresh_balances':
            // Sincronizar saldos com base nas transações (útil para correções)
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                
                if ($balanceModel->syncBalancesFromTransactions($userId)) {
                    echo json_encode([
                        'status' => true,
                        'message' => 'Saldos sincronizados com sucesso'
                    ]);
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Erro ao sincronizar saldos'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao sincronizar saldos']);
            }
            break;
            
        case 'validate_balance_use':
            // Validar se o cliente pode usar determinado valor
            $storeId = intval($_POST['store_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $currentBalance = $balanceModel->getStoreBalance($userId, $storeId);
                
                $validation = [
                    'valido' => $currentBalance >= $amount && $amount > 0,
                    'saldo_disponivel' => $currentBalance,
                    'valor_solicitado' => $amount,
                    'mensagem' => ''
                ];
                
                if ($amount <= 0) {
                    $validation['mensagem'] = 'Valor deve ser maior que zero';
                } elseif ($currentBalance < $amount) {
                    $validation['mensagem'] = 'Saldo insuficiente. Disponível: R$ ' . number_format($currentBalance, 2, ',', '.');
                } else {
                    $validation['mensagem'] = 'Valor válido para uso';
                }
                
                echo json_encode(['status' => true, 'data' => $validation]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao validar uso do saldo']);
            }
            break;
            
        case 'get_balance_widget_data':
            // Dados específicos para o widget de saldo
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : null;
            
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                
                if ($storeId) {
                    // Dados de uma loja específica
                    $balance = $balanceModel->getStoreBalance($userId, $storeId);
                    $statistics = $balanceModel->getBalanceStatistics($userId, $storeId);
                    
                    // Buscar dados da loja
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT nome_fantasia, logo, categoria FROM lojas WHERE id = :store_id");
                    $stmt->bindParam(':store_id', $storeId);
                    $stmt->execute();
                    $storeData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $data = [
                        'tipo' => 'loja_especifica',
                        'loja' => array_merge($storeData, [
                            'id' => $storeId,
                            'saldo_disponivel' => $balance,
                            'estatisticas' => $statistics
                        ]),
                        'saldo_total' => $balance
                    ];
                } else {
                    // Dados de todas as lojas
                    $balances = $balanceModel->getAllUserBalances($userId);
                    $totalBalance = $balanceModel->getTotalBalance($userId);
                    
                    $data = [
                        'tipo' => 'todas_lojas',
                        'lojas' => $balances,
                        'saldo_total' => $totalBalance,
                        'total_lojas' => count($balances)
                    ];
                }
                
                echo json_encode(['status' => true, 'data' => $data]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao obter dados do widget']);
            }
            break;
            
        case 'export_balance_history':
            // Exportar histórico de saldo em CSV
            $storeId = intval($_GET['store_id'] ?? 0);
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            try {
                require_once __DIR__ . '/../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                
                // Obter todo o histórico (sem limite)
                $history = $balanceModel->getMovementHistory($userId, $storeId, 999999, 0);
                
                // Gerar CSV
                $filename = 'historico_saldo_loja_' . $storeId . '_' . date('Y-m-d') . '.csv';
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: no-cache');
                
                $output = fopen('php://output', 'w');
                
                // Cabeçalho CSV
                fputcsv($output, [
                    'Data/Hora',
                    'Tipo',
                    'Valor',
                    'Saldo Anterior',
                    'Saldo Atual',
                    'Descrição'
                ]);
                
                // Dados
                foreach ($history as $movement) {
                    $type = '';
                    switch ($movement['tipo_operacao']) {
                        case 'credito': $type = 'Crédito'; break;
                        case 'uso': $type = 'Uso'; break;
                        case 'estorno': $type = 'Estorno'; break;
                    }
                    
                    fputcsv($output, [
                        date('d/m/Y H:i:s', strtotime($movement['data_operacao'])),
                        $type,
                        'R$ ' . number_format($movement['valor'], 2, ',', '.'),
                        'R$ ' . number_format($movement['saldo_anterior'], 2, ',', '.'),
                        'R$ ' . number_format($movement['saldo_atual'], 2, ',', '.'),
                        $movement['descricao']
                    ]);
                }
                
                fclose($output);
                exit;
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Erro ao exportar histórico']);
            }
            break;


        case 'dashboard':
            $result = ClientController::getDashboardData($userId);
            echo json_encode($result);
            break;
            
        case 'statement':
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = ClientController::getStatement($userId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'profile':
            $result = ClientController::getProfileData($userId);
            echo json_encode($result);
            break;
            
        case 'update_profile':
            $data = $_POST;
            $result = ClientController::updateProfile($userId, $data);
            echo json_encode($result);
            break;
            
        case 'stores':
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = ClientController::getPartnerStores($userId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'favorite_store':
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            $favorite = isset($_POST['favorite']) ? (bool)$_POST['favorite'] : true;
            $result = ClientController::toggleFavoriteStore($userId, $storeId, $favorite);
            echo json_encode($result);
            break;
            
        case 'favorites':
            $result = ClientController::getFavoriteStores($userId);
            echo json_encode($result);
            break;
            
        case 'transaction':
            $transactionId = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
            $result = ClientController::getTransactionDetails($userId, $transactionId);
            echo json_encode($result);
            break;
            
        case 'report':
            $filters = $_POST['filters'] ?? [];
            $result = ClientController::generateCashbackReport($userId, $filters);
            echo json_encode($result);
            break;
            
        case 'balance':
            $result = ClientController::getClientBalance($userId);
            echo json_encode($result);
            break;
        case 'store_balance_details':
            $lojaId = isset($_GET['loja_id']) ? intval($_GET['loja_id']) : 0;
            
            if ($lojaId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            $result = ClientController::getStoreBalanceDetails($userId, $lojaId);
            echo json_encode($result);
            break;
            
        case 'simulate_balance_use':
            $lojaId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
            
            if ($lojaId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                return;
            }
            
            $result = ClientController::simulateBalanceUse($userId, $lojaId, $valor);
            echo json_encode($result);
            break;
        default:
            // Acesso inválido ao controlador
            header('Location: ' . CLIENT_DASHBOARD_URL);
            exit;
    }

}


?>