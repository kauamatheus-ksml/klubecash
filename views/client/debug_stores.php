// Em controllers/ClientController.php
public static function getPartnerStores($userId, $filters = [], $page = 1) {
    try {
        $db = Database::getConnection();
        
        // Verificar e criar tabelas necessárias se não existirem
        self::ensureBalanceTables($db);
        
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        
        // Query base
        $baseQuery = "FROM lojas l WHERE l.status = 'aprovado'";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['categoria'])) {
            $baseQuery .= " AND l.categoria = :categoria";
            $params[':categoria'] = $filters['categoria'];
        }
        
        if (!empty($filters['nome'])) {
            $baseQuery .= " AND l.nome_fantasia LIKE :nome";
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        
        if (!empty($filters['cashback_min'])) {
            $baseQuery .= " AND l.porcentagem_cashback >= :cashback_min";
            $params[':cashback_min'] = $filters['cashback_min'];
        }
        
        // Filtros específicos de saldo
        if (!empty($filters['tem_saldo'])) {
            switch ($filters['tem_saldo']) {
                case 'com_saldo':
                    $baseQuery .= " AND EXISTS (SELECT 1 FROM cashback_saldos cs WHERE cs.loja_id = l.id AND cs.usuario_id = :user_id_saldo AND cs.saldo_disponivel > 0)";
                    $params[':user_id_saldo'] = $userId;
                    break;
                case 'sem_saldo':
                    $baseQuery .= " AND NOT EXISTS (SELECT 1 FROM cashback_saldos cs WHERE cs.loja_id = l.id AND cs.usuario_id = :user_id_sem_saldo AND cs.saldo_disponivel > 0)";
                    $params[':user_id_sem_saldo'] = $userId;
                    break;
                case 'ja_usei':
                    $baseQuery .= " AND EXISTS (SELECT 1 FROM cashback_movimentacoes cm WHERE cm.loja_id = l.id AND cm.usuario_id = :user_id_usei AND cm.tipo_operacao = 'uso')";
                    $params[':user_id_usei'] = $userId;
                    break;
            }
        }
        
        // Ordenação
        $orderBy = " ORDER BY l.nome_fantasia ASC";
        if (!empty($filters['ordenar'])) {
            switch ($filters['ordenar']) {
                case 'cashback':
                    $orderBy = " ORDER BY l.porcentagem_cashback DESC";
                    break;
                case 'saldo':
                    $orderBy = " ORDER BY (SELECT COALESCE(cs.saldo_disponivel, 0) FROM cashback_saldos cs WHERE cs.loja_id = l.id AND cs.usuario_id = :user_id_order) DESC";
                    $params[':user_id_order'] = $userId;
                    break;
                case 'uso':
                    $orderBy = " ORDER BY (SELECT COALESCE(cs.total_usado, 0) FROM cashback_saldos cs WHERE cs.loja_id = l.id AND cs.usuario_id = :user_id_uso) DESC";
                    $params[':user_id_uso'] = $userId;
                    break;
            }
        }
        
        // Query para contar total
        $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalItems = $countStmt->fetch()['total'];
        
        // Query para buscar lojas
        $selectQuery = "
            SELECT 
                l.*,
                (SELECT 1 FROM favorites f WHERE f.user_id = :user_id AND f.store_id = l.id LIMIT 1) as is_favorite
            " . $baseQuery . $orderBy . "
            LIMIT :limit OFFSET :offset
        ";
        
        $params[':user_id'] = $userId;
        $params[':limit'] = ITEMS_PER_PAGE;
        $params[':offset'] = $offset;
        
        $stmt = $db->prepare($selectQuery);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar categorias disponíveis
        $categoriesQuery = "SELECT DISTINCT categoria FROM lojas WHERE status = 'aprovado' ORDER BY categoria";
        $categoriesStmt = $db->query($categoriesQuery);
        $categorias = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Estatísticas
        $statsQuery = "
            SELECT 
                COUNT(*) as total_lojas,
                AVG(porcentagem_cashback) as media_cashback,
                MAX(porcentagem_cashback) as maior_cashback,
                MIN(porcentagem_cashback) as menor_cashback
            FROM lojas 
            WHERE status = 'aprovado'
        ";
        $statsStmt = $db->query($statsQuery);
        $estatisticas = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Paginação
        $totalPages = ceil($totalItems / ITEMS_PER_PAGE);
        $paginacao = [
            'pagina_atual' => $page,
            'total_paginas' => $totalPages,
            'total_itens' => $totalItems,
            'itens_por_pagina' => ITEMS_PER_PAGE
        ];
        
        return [
            'status' => true,
            'data' => [
                'lojas' => $lojas,
                'categorias' => $categorias,
                'estatisticas' => $estatisticas,
                'paginacao' => $paginacao
            ]
        ];
        
    } catch (Exception $e) {
        error_log('Erro em getPartnerStores: ' . $e->getMessage());
        return [
            'status' => false,
            'message' => 'Erro ao carregar dados das lojas parceiras: ' . $e->getMessage()
        ];
    }
}

// Método para garantir que as tabelas de saldo existam
private static function ensureBalanceTables($db) {
    try {
        // Verificar se cashback_saldos existe
        $result = $db->query("SHOW TABLES LIKE 'cashback_saldos'");
        if ($result->rowCount() == 0) {
            $createSaldos = "
                CREATE TABLE cashback_saldos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    loja_id INT NOT NULL,
                    saldo_disponivel DECIMAL(10,2) DEFAULT 0.00,
                    total_creditado DECIMAL(10,2) DEFAULT 0.00,
                    total_usado DECIMAL(10,2) DEFAULT 0.00,
                    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_store (usuario_id, loja_id),
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    FOREIGN KEY (loja_id) REFERENCES lojas(id) ON DELETE CASCADE
                )
            ";
            $db->exec($createSaldos);
        }
        
        // Verificar se cashback_movimentacoes existe
        $result = $db->query("SHOW TABLES LIKE 'cashback_movimentacoes'");
        if ($result->rowCount() == 0) {
            $createMovimentacoes = "
                CREATE TABLE cashback_movimentacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    loja_id INT NOT NULL,
                    tipo_operacao ENUM('credito', 'uso', 'estorno') NOT NULL,
                    valor DECIMAL(10,2) NOT NULL,
                    transacao_origem_id INT NULL,
                    transacao_uso_id INT NULL,
                    descricao TEXT,
                    data_operacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    FOREIGN KEY (loja_id) REFERENCES lojas(id) ON DELETE CASCADE,
                    FOREIGN KEY (transacao_origem_id) REFERENCES transacoes_cashback(id) ON DELETE SET NULL,
                    FOREIGN KEY (transacao_uso_id) REFERENCES transacoes_cashback(id) ON DELETE SET NULL
                )
            ";
            $db->exec($createMovimentacoes);
        }
        
        // Verificar se favorites existe
        $result = $db->query("SHOW TABLES LIKE 'favorites'");
        if ($result->rowCount() == 0) {
            $createFavorites = "
                CREATE TABLE favorites (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    store_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_favorite (user_id, store_id),
                    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    FOREIGN KEY (store_id) REFERENCES lojas(id) ON DELETE CASCADE
                )
            ";
            $db->exec($createFavorites);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar tabelas de saldo: ' . $e->getMessage());
        throw $e;
    }
}