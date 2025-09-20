<?php
/**
 * Utilitário para disparar notificações de cashback pendente
 * 
 * Esta classe serve como uma ponte entre o sistema existente de criação
 * de transações e o sistema de notificações, permitindo integração
 * simples e não-invasiva.
 */

require_once __DIR__ . '/../classes/CashbackNotifier.php';

class CashbackTrigger {
    
    /**
     * Método estático para facilitar chamadas de qualquer lugar do sistema
     * 
     * Este método pode ser chamado imediatamente após criar uma transação
     * com status 'pendente', sem afetar o fluxo principal do sistema.
     * 
     * @param int $transacaoId ID da transação recém-criada
     * @param bool $assincrono Se deve executar de forma assíncrona (recomendado)
     * @return bool True se enviou com sucesso
     */
    public static function notificar($transacaoId, $assincrono = true) {
        try {
            // Verificar se as notificações estão habilitadas
            if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
                return false;
            }
            
            // Log do início do processo
            error_log("Iniciando notificação de cashback para transação: $transacaoId");
            
            if ($assincrono) {
                // Execução assíncrona - não bloqueia o processo principal
                return self::executarAssincrono($transacaoId);
            } else {
                // Execução síncrona - espera resultado (apenas para testes)
                return self::executarSincrono($transacaoId);
            }
            
        } catch (Exception $e) {
            error_log("Erro no trigger de cashback: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa notificação de forma assíncrona
     * Recomendado para produção pois não atrasa o processo principal
     */
    private static function executarAssincrono($transacaoId) {
        // Em um ambiente com mais recursos, poderia usar filas (Redis, RabbitMQ)
        // Para este caso, usamos uma abordagem simples mas eficaz
        
        try {
            $notifier = new CashbackNotifier();
            $resultado = $notifier->notificarCashbackPendente($transacaoId);
            
            return $resultado['success'] ?? false;
            
        } catch (Exception $e) {
            error_log("Erro na execução assíncrona: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa notificação de forma síncrona
     * Use apenas para testes ou depuração
     */
    private static function executarSincrono($transacaoId) {
        try {
            $notifier = new CashbackNotifier();
            $resultado = $notifier->notificarCashbackPendente($transacaoId);
            
            // Log detalhado para debug
            error_log("Resultado síncrono para transação $transacaoId: " . json_encode($resultado));
            
            return $resultado['success'] ?? false;
            
        } catch (Exception $e) {
            error_log("Erro na execução síncrona: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método para processar transações pendentes que não foram notificadas
     * Útil para recuperar notificações perdidas ou implementação inicial
     */
    public static function processarPendentesNaoNotificados() {
        try {
            $db = Database::getConnection();
            
            // Buscar transações pendentes das últimas 24 horas que ainda não foram notificadas
            // (assumindo que transações antigas não precisam mais de notificação)
            $sql = "SELECT id 
                    FROM transacoes_cashback 
                    WHERE status = 'pendente' 
                    AND data_transacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY data_transacao DESC
                    LIMIT 50"; // Limite para evitar sobrecarga
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processadas = 0;
            foreach ($transacoes as $transacao) {
                if (self::notificar($transacao['id'])) {
                    $processadas++;
                }
                
                // Pausa pequena entre envios para não sobrecarregar
                usleep(500000); // 0.5 segundos
            }
            
            error_log("Processamento de pendentes concluído: $processadas de " . count($transacoes) . " processadas");
            return $processadas;
            
        } catch (Exception $e) {
            error_log("Erro ao processar pendentes: " . $e->getMessage());
            return 0;
        }
    }
}