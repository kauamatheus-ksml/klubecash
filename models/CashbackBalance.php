<?php
// models/CashbackBalance.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Modelo para gestão de saldo de cashback por loja
 * Controla créditos, usos e histórico do saldo de cada cliente por loja específica
 */
class CashbackBalance {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Obtém o saldo disponível de um usuário em uma loja específica
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @return float Saldo disponível
     */
    public function getStoreBalance($userId, $storeId) {
        try {
            $stmt = $this->db->prepare("
                SELECT saldo_disponivel 
                FROM cashback_saldos 
                WHERE usuario_id = :user_id AND loja_id = :store_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['saldo_disponivel']) : 0.00;
            
        } catch (PDOException $e) {
            error_log('Erro ao obter saldo da loja: ' . $e->getMessage());
            return 0.00;
        }
    }
    
    /**
     * Obtém todos os saldos de um usuário agrupados por loja
     * 
     * @param int $userId ID do usuário
     * @return array Saldos detalhados por loja
     */
    public function getAllUserBalances($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cs.*,
                    l.nome_fantasia,
                    l.logo,
                    l.categoria,
                    l.porcentagem_cashback
                FROM cashback_saldos cs
                JOIN lojas l ON cs.loja_id = l.id
                WHERE cs.usuario_id = :user_id
                AND cs.saldo_disponivel > 0
                ORDER BY cs.saldo_disponivel DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Erro ao obter todos os saldos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém o saldo total consolidado de um usuário (soma de todas as lojas)
     * 
     * @param int $userId ID do usuário
     * @return float Saldo total
     */
    public function getTotalBalance($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(saldo_disponivel) as total
                FROM cashback_saldos 
                WHERE usuario_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['total']) : 0.00;
            
        } catch (PDOException $e) {
            error_log('Erro ao obter saldo total: ' . $e->getMessage());
            return 0.00;
        }
    }
    
    /**
     * Adiciona saldo de cashback para um usuário em uma loja específica
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @param float $amount Valor a ser creditado
     * @param string $description Descrição da operação
     * @param int|null $transactionId ID da transação origem
     * @return bool Sucesso da operação
     */
    public function addBalance($userId, $storeId, $amount, $description = '', $transactionId = null) {
        if ($amount <= 0) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Obter saldo atual
            $currentBalance = $this->getStoreBalance($userId, $storeId);
            $newBalance = $currentBalance + $amount;
            
            // Atualizar ou inserir saldo
            $stmt = $this->db->prepare("
                INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel, total_creditado)
                VALUES (:user_id, :store_id, :amount, :amount)
                ON DUPLICATE KEY UPDATE
                    saldo_disponivel = saldo_disponivel + :amount,
                    total_creditado = total_creditado + :amount,
                    ultima_atualizacao = CURRENT_TIMESTAMP
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->bindParam(':amount', $amount);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar saldo');
            }
            
            // Registrar movimentação
            $this->recordMovement($userId, $storeId, 'credito', $amount, $currentBalance, $newBalance, $description, $transactionId);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao adicionar saldo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Usa saldo de cashback em uma compra na loja específica
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @param float $amount Valor a ser usado
     * @param string $description Descrição da operação
     * @param int|null $transactionId ID da transação de uso
     * @return bool Sucesso da operação
     */
    public function useBalance($userId, $storeId, $amount, $description = '', $transactionId = null) {
        if ($amount <= 0) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Verificar saldo disponível
            $currentBalance = $this->getStoreBalance($userId, $storeId);
            
            if ($currentBalance < $amount) {
                throw new Exception('Saldo insuficiente');
            }
            
            $newBalance = $currentBalance - $amount;
            
            // Atualizar saldo
            $stmt = $this->db->prepare("
                UPDATE cashback_saldos 
                SET saldo_disponivel = saldo_disponivel - :amount,
                    total_usado = total_usado + :amount,
                    ultima_atualizacao = CURRENT_TIMESTAMP
                WHERE usuario_id = :user_id AND loja_id = :store_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->bindParam(':amount', $amount);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar saldo');
            }
            
            // Registrar movimentação
            $this->recordMovement($userId, $storeId, 'uso', $amount, $currentBalance, $newBalance, $description, null, $transactionId);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao usar saldo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Estorna o uso de saldo (reverter operação de uso)
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @param float $amount Valor a ser estornado
     * @param string $description Descrição da operação
     * @param int|null $transactionId ID da transação relacionada
     * @return bool Sucesso da operação
     */
    public function refundBalance($userId, $storeId, $amount, $description = '', $transactionId = null) {
        if ($amount <= 0) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Obter saldo atual
            $currentBalance = $this->getStoreBalance($userId, $storeId);
            $newBalance = $currentBalance + $amount;
            
            // Atualizar saldo
            $stmt = $this->db->prepare("
                UPDATE cashback_saldos 
                SET saldo_disponivel = saldo_disponivel + :amount,
                    total_usado = total_usado - :amount,
                    ultima_atualizacao = CURRENT_TIMESTAMP
                WHERE usuario_id = :user_id AND loja_id = :store_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->bindParam(':amount', $amount);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar saldo');
            }
            
            // Registrar movimentação
            $this->recordMovement($userId, $storeId, 'estorno', $amount, $currentBalance, $newBalance, $description, null, $transactionId);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao estornar saldo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma movimentação no histórico
     * 
     * @param int $userId
     * @param int $storeId
     * @param string $type
     * @param float $amount
     * @param float $previousBalance
     * @param float $newBalance
     * @param string $description
     * @param int|null $originTransactionId
     * @param int|null $useTransactionId
     * @return bool
     */
    private function recordMovement($userId, $storeId, $type, $amount, $previousBalance, $newBalance, $description = '', $originTransactionId = null, $useTransactionId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO cashback_movimentacoes (
                    usuario_id, loja_id, tipo_operacao, valor,
                    saldo_anterior, saldo_atual, descricao,
                    transacao_origem_id, transacao_uso_id
                ) VALUES (
                    :user_id, :store_id, :type, :amount,
                    :previous_balance, :new_balance, :description,
                    :origin_transaction_id, :use_transaction_id
                )
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':previous_balance', $previousBalance);
            $stmt->bindParam(':new_balance', $newBalance);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':origin_transaction_id', $originTransactionId);
            $stmt->bindParam(':use_transaction_id', $useTransactionId);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Erro ao registrar movimentação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o histórico de movimentações de um usuário em uma loja
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @param int $limit Limite de registros
     * @param int $offset Offset para paginação
     * @return array Histórico de movimentações
     */
    public function getMovementHistory($userId, $storeId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cm.*,
                    to_table.codigo_transacao as transacao_origem_codigo,
                    to_table.valor_total as transacao_origem_valor,
                    to_table.data_transacao as transacao_origem_data,
                    tu_table.codigo_transacao as transacao_uso_codigo,
                    tu_table.valor_total as transacao_uso_valor,
                    tu_table.data_transacao as transacao_uso_data
                FROM cashback_movimentacoes cm
                LEFT JOIN transacoes_cashback to_table ON cm.transacao_origem_id = to_table.id
                LEFT JOIN transacoes_cashback tu_table ON cm.transacao_uso_id = tu_table.id
                WHERE cm.usuario_id = :user_id AND cm.loja_id = :store_id
                ORDER BY cm.data_operacao DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Erro ao obter histórico: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas de uso do cashback por loja
     * 
     * @param int $userId ID do usuário
     * @param int $storeId ID da loja
     * @return array Estatísticas
     */
    public function getBalanceStatistics($userId, $storeId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cs.*,
                    COUNT(cm.id) as total_movimentacoes,
                    MAX(cm.data_operacao) as ultima_movimentacao,
                    SUM(CASE WHEN cm.tipo_operacao = 'credito' THEN cm.valor ELSE 0 END) as total_creditado_historico,
                    SUM(CASE WHEN cm.tipo_operacao = 'uso' THEN cm.valor ELSE 0 END) as total_usado_historico,
                    AVG(CASE WHEN cm.tipo_operacao = 'credito' THEN cm.valor ELSE NULL END) as media_credito,
                    AVG(CASE WHEN cm.tipo_operacao = 'uso' THEN cm.valor ELSE NULL END) as media_uso
                FROM cashback_saldos cs
                LEFT JOIN cashback_movimentacoes cm ON cs.usuario_id = cm.usuario_id AND cs.loja_id = cm.loja_id
                WHERE cs.usuario_id = :user_id AND cs.loja_id = :store_id
                GROUP BY cs.id
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter estatísticas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sincroniza saldos com base nas transações aprovadas
     * Útil para correções ou migrações de dados
     * 
     * @param int|null $userId ID do usuário específico (null para todos)
     * @return bool Sucesso da operação
     */
    public function syncBalancesFromTransactions($userId = null) {
        try {
            $this->db->beginTransaction();
            
            // Query para recalcular saldos baseado em transações aprovadas
            $whereClause = $userId ? "WHERE t.usuario_id = :user_id" : "";
            
            $stmt = $this->db->prepare("
                INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel, total_creditado)
                SELECT 
                    t.usuario_id,
                    t.loja_id,
                    SUM(t.valor_cliente) as saldo_disponivel,
                    SUM(t.valor_cliente) as total_creditado
                FROM transacoes_cashback t
                $whereClause
                AND t.status = 'aprovado'
                GROUP BY t.usuario_id, t.loja_id
                ON DUPLICATE KEY UPDATE
                    saldo_disponivel = VALUES(saldo_disponivel),
                    total_creditado = VALUES(total_creditado),
                    ultima_atualizacao = CURRENT_TIMESTAMP
            ");
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            }
            
            $stmt->execute();
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao sincronizar saldos: ' . $e->getMessage());
            return false;
        }
    }
}
?>