<?php
/**
 * Sistema de Log Personalizado para WhatsApp
 * 
 * Esta classe substitui os logs tradicionais do servidor, criando nosso próprio
 * sistema de monitoramento que podemos acessar através de uma interface web.
 * Versão corrigida com todas as dependências necessárias.
 */

// Incluir todas as dependências necessárias para funcionamento completo
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class WhatsAppLogger {
    
    /**
     * Registra uma atividade WhatsApp em nossa base de dados
     * 
     * Este método funciona como escrever uma entrada detalhada em um diário.
     * Cada evento importante é registrado com todas as informações relevantes
     * para que possamos analisar o que aconteceu posteriormente.
     */
    public static function log($type, $phone, $message, $result, $additionalData = []) {
        try {
            // Agora a classe Database estará disponível devido aos includes acima
            $db = Database::getConnection();
            
            // Criar a tabela de logs se ela não existir
            self::createLogTableIfNotExists($db);
            
            // Preparar os dados para registro
            $logData = [
                'type' => $type, // Tipo de notificação (nova_transacao, cashback_liberado, teste)
                'phone' => $phone, // Número de telefone do destinatário
                'message_preview' => substr($message, 0, 100), // Prévia da mensagem enviada
                'success' => $result['success'] ? 1 : 0, // Se o envio foi bem-sucedido
                'message_id' => $result['messageId'] ?? null, // ID da mensagem (se disponível)
                'error_message' => $result['error'] ?? null, // Mensagem de erro (se houver)
                'simulation_mode' => isset($result['simulation']) ? 1 : 0, // Se estava em modo simulação
                'additional_data' => json_encode($additionalData), // Dados extras (valor, loja, etc.)
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', // IP de onde veio a solicitação
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', // Navegador ou sistema
                'timestamp' => date('Y-m-d H:i:s') // Momento exato do evento
            ];
            
            // Inserir o registro na base de dados
            $stmt = $db->prepare("
                INSERT INTO whatsapp_logs (
                    type, phone, message_preview, success, message_id, 
                    error_message, simulation_mode, additional_data, 
                    ip_address, user_agent, created_at
                ) VALUES (
                    :type, :phone, :message_preview, :success, :message_id,
                    :error_message, :simulation_mode, :additional_data,
                    :ip_address, :user_agent, :timestamp
                )
            ");
            
            return $stmt->execute($logData);
            
        } catch (Exception $e) {
            // Se houver erro no nosso sistema de log, usar fallback
            error_log("WhatsAppLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria a tabela de logs se ela não existir
     * 
     * Esta função é como preparar um caderno novo sempre que precisamos.
     * Ela garante que temos um lugar para escrever nossos registros.
     */
    private static function createLogTableIfNotExists($db) {
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS whatsapp_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                message_preview TEXT,
                success TINYINT(1) NOT NULL DEFAULT 0,
                message_id VARCHAR(100),
                error_message TEXT,
                simulation_mode TINYINT(1) NOT NULL DEFAULT 0,
                additional_data JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_phone (phone),
                INDEX idx_created_at (created_at),
                INDEX idx_success (success)
            )
        ";
        
        $db->exec($createTableSQL);
    }
    
    /**
     * Obtém logs recentes com filtros opcionais
     * 
     * Este método é como folhear nosso diário para encontrar informações específicas.
     * Podemos procurar por período, tipo de atividade, número de telefone, etc.
     */
    public static function getRecentLogs($limit = 50, $filters = []) {
        try {
            $db = Database::getConnection();
            
            // Construir a consulta base
            $sql = "SELECT * FROM whatsapp_logs WHERE 1=1";
            $params = [];
            
            // Adicionar filtros conforme solicitado
            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['phone'])) {
                $sql .= " AND phone LIKE ?";
                $params[] = '%' . $filters['phone'] . '%';
            }
            
            if (!empty($filters['success'])) {
                $sql .= " AND success = ?";
                $params[] = $filters['success'] === 'true' ? 1 : 0;
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Ordenar por mais recente primeiro e limitar resultados
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("WhatsAppLogger GetLogs Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas resumidas dos logs
     * 
     * Este método é como criar um relatório executivo do nosso diário,
     * mostrando os pontos mais importantes de forma resumida.
     */
    public static function getStatistics($period = '7 days') {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_messages,
                    SUM(success) as successful_messages,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_messages,
                    SUM(simulation_mode) as simulated_messages,
                    COUNT(DISTINCT phone) as unique_phones,
                    COUNT(DISTINCT type) as message_types
                FROM whatsapp_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            // Converter período em dias
            $days = $period === '24 hours' ? 1 : ($period === '7 days' ? 7 : 30);
            $stmt->execute([$days]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("WhatsAppLogger Statistics Error: " . $e->getMessage());
            return [];
        }
    }
}
?>