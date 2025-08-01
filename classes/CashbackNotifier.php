<?php
/**
 * Classe CashbackNotifier - Sistema de Notificação Automática de Cashback
 * 
 * Esta classe é responsável por enviar notificações via WhatsApp quando novas
 * transações de cashback são registradas no sistema. Segue o mesmo padrão
 * da classe SaldoConsulta que já está funcionando.
 * 
 * FUNCIONALIDADES:
 * - Detecta perfil do cliente (novo, VIP, regular)
 * - Gera mensagens personalizadas para cada situação
 * - Integra com o bot WhatsApp existente
 * - Trata erros sem afetar a transação principal
 * 
 * Localização: classes/CashbackNotifier.php
 * Autor: Sistema Klube Cash
 * Versão: 1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class CashbackNotifier {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Método principal para enviar notificação de nova transação
     * 
     * Este é o método que será chamado imediatamente após uma transação
     * ser criada no sistema. Ele busca os dados, determina o tipo de mensagem
     * apropriada e envia via WhatsApp.
     * 
     * @param int $transactionId ID da transação recém-criada
     * @return array Resultado da operação
     */
    public function notifyNewTransaction($transactionId) {
        try {
            // Buscar dados completos da transação
            $transactionData = $this->getTransactionData($transactionId);
            
            if (!$transactionData) {
                return [
                    'success' => false,
                    'message' => 'Transação não encontrada',
                    'transaction_id' => $transactionId
                ];
            }
            
            // Buscar histórico do cliente para determinar perfil
            $clientProfile = $this->getClientProfile($transactionData['usuario_id']);
            
            // Determinar tipo de mensagem baseado no perfil e valor
            $messageType = $this->determineMessageType($transactionData, $clientProfile);
            
            // Gerar mensagem personalizada
            $message = $this->generateMessage($messageType, $transactionData, $clientProfile);
            
            // Enviar via WhatsApp usando a infraestrutura existente
            $whatsappResult = $this->sendWhatsAppMessage($transactionData['telefone'], $message);
            
            // Registrar log da notificação
            $this->logNotification($transactionId, $messageType, $whatsappResult);
            
            return [
                'success' => $whatsappResult['success'],
                'message' => $whatsappResult['message'],
                'transaction_id' => $transactionId,
                'message_type' => $messageType,
                'phone' => $transactionData['telefone']
            ];
            
        } catch (Exception $e) {
            error_log('Erro no CashbackNotifier: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage(),
                'transaction_id' => $transactionId
            ];
        }
    }
    
    /**
     * Busca dados completos da transação e usuário
     * 
     * Este método faz um JOIN para trazer todas as informações necessárias
     * em uma única consulta, incluindo dados da transação, usuário e loja.
     * 
     * @param int $transactionId ID da transação
     * @return array|null Dados da transação ou null se não encontrada
     */
    private function getTransactionData($transactionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.id,
                    t.usuario_id,
                    t.loja_id,
                    t.valor_total,
                    t.valor_cashback,
                    t.valor_cliente,
                    t.data_transacao,
                    t.status,
                    u.nome as cliente_nome,
                    u.telefone,
                    u.email,
                    l.nome_fantasia as loja_nome,
                    l.porcentagem_cashback as loja_percentual
                FROM transacoes_cashback t
                INNER JOIN usuarios u ON t.usuario_id = u.id
                INNER JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = :transaction_id
            ");
            
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Formatar telefone para padrão brasileiro
                $result['telefone'] = $this->formatPhoneNumber($result['telefone']);
                return $result;
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar dados da transação: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Analisa o perfil do cliente baseado no histórico
     * 
     * Este método verifica quantas transações o cliente já fez,
     * o valor total de cashback acumulado e outras métricas para
     * determinar se é cliente novo, regular ou VIP.
     * 
     * @param int $userId ID do usuário
     * @return array Perfil detalhado do cliente
     */
    private function getClientProfile($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(valor_cashback) as total_cashback_acumulado,
                    SUM(CASE WHEN status = 'aprovado' THEN valor_cliente ELSE 0 END) as cashback_disponivel,
                    MIN(data_transacao) as primeira_compra,
                    MAX(data_transacao) as ultima_compra
                FROM transacoes_cashback 
                WHERE usuario_id = :user_id
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determinar categoria do cliente
            $isFirstPurchase = ($profile['total_transacoes'] <= 1);
            $isVipClient = ($profile['total_cashback_acumulado'] > 500.00 || $profile['total_transacoes'] > 20);
            $isRegularClient = (!$isFirstPurchase && !$isVipClient);
            
            return [
                'is_first_purchase' => $isFirstPurchase,
                'is_vip_client' => $isVipClient,
                'is_regular_client' => $isRegularClient,
                'total_transactions' => intval($profile['total_transacoes']),
                'total_cashback' => floatval($profile['total_cashback_acumulado']),
                'available_cashback' => floatval($profile['cashback_disponivel']),
                'member_since' => $profile['primeira_compra']
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar perfil do cliente: ' . $e->getMessage());
            return [
                'is_first_purchase' => true,
                'is_vip_client' => false,
                'is_regular_client' => false,
                'total_transactions' => 0,
                'total_cashback' => 0.00,
                'available_cashback' => 0.00,
                'member_since' => null
            ];
        }
    }
    
    /**
     * Determina qual tipo de mensagem enviar
     * 
     * Baseado no perfil do cliente e valor da compra, decide se a mensagem
     * deve ser educativa (cliente novo), celebrativa (compra grande),
     * concisa (cliente VIP) ou padrão (cliente regular).
     * 
     * @param array $transactionData Dados da transação
     * @param array $clientProfile Perfil do cliente
     * @return string Tipo da mensagem
     */
    private function determineMessageType($transactionData, $clientProfile) {
        // Primeira compra - mensagem educativa
        if ($clientProfile['is_first_purchase']) {
            return 'first_purchase';
        }
        
        // Compra grande (acima de R$ 200) - mensagem celebrativa
        if ($transactionData['valor_total'] > 200.00) {
            return 'big_purchase';
        }
        
        // Cliente VIP - mensagem concisa
        if ($clientProfile['is_vip_client']) {
            return 'vip_client';
        }
        
        // Padrão para clientes regulares
        return 'regular_client';
    }
    
    /**
     * Gera a mensagem personalizada baseada no tipo
     * 
     * Cada tipo de mensagem tem um tom e conteúdo específico.
     * As mensagens são cuidadosamente elaboradas para educar,
     * tranquilizar e engajar o cliente.
     * 
     * @param string $messageType Tipo da mensagem
     * @param array $transactionData Dados da transação
     * @param array $clientProfile Perfil do cliente
     * @return string Mensagem formatada
     */
    private function generateMessage($messageType, $transactionData, $clientProfile) {
        $nome = $transactionData['cliente_nome'];
        $loja = $transactionData['loja_nome'];
        $valorCompra = $this->formatCurrency($transactionData['valor_total']);
        $valorCashback = $this->formatCurrency($transactionData['valor_cliente']);
        $percentual = $transactionData['loja_percentual'];
        
        switch ($messageType) {
            case 'first_purchase':
                return "🎉 *Parabéns {$nome}!*\n\n" .
                       "Sua primeira compra no *Klube Cash* foi registrada com sucesso!\n\n" .
                       "📋 *Detalhes da sua compra:*\n" .
                       "🏪 Loja: {$loja}\n" .
                       "💰 Valor: {$valorCompra}\n" .
                       "🎁 Seu cashback: *{$valorCashback}*\n\n" .
                       "ℹ️ *Como funciona:*\n" .
                       "1️⃣ Sua compra está sendo validada pela loja\n" .
                       "2️⃣ Em até 7 dias seu cashback estará disponível\n" .
                       "3️⃣ Você poderá usar o dinheiro em novas compras na mesma loja\n\n" .
                       "📱 Acompanhe pelo app: " . SITE_URL . "\n\n" .
                       "Bem-vindo(a) ao *Klube Cash*! 💜";
                       
            case 'big_purchase':
                return "🚀 *Uau, {$nome}!*\n\n" .
                       "Que compra incrível na *{$loja}*!\n\n" .
                       "💎 *Sua economia foi de {$valorCashback}*\n" .
                       "💳 Valor da compra: {$valorCompra}\n" .
                       "🎯 Cashback de {$percentual}%\n\n" .
                       "🕐 *Prazo de liberação:* até 7 dias úteis\n\n" .
                       "💡 *Dica:* Com esse valor você já pode fazer uma nova compra na {$loja} usando seu cashback!\n\n" .
                       "Continue economizando no *Klube Cash*! 🌟";
                       
            case 'vip_client':
                return "⭐ *{$nome}*, sua compra foi registrada!*\n\n" .
                       "🏪 {$loja}\n" .
                       "💰 Compra: {$valorCompra}\n" .
                       "🎁 Cashback: *{$valorCashback}*\n\n" .
                       "⏰ Liberação em até 7 dias úteis.\n\n" .
                       "Obrigado por ser um cliente *Klube Cash*! 💜";
                       
            default: // regular_client
                return "✅ *{$nome}, tudo certo!*\n\n" .
                       "Sua compra na *{$loja}* foi registrada no sistema.\n\n" .
                       "💰 Valor da compra: {$valorCompra}\n" .
                       "🎁 Seu cashback: *{$valorCashback}*\n\n" .
                       "🕐 *Status:* Aguardando validação da loja\n" .
                       "📅 *Previsão:* Até 7 dias úteis para liberação\n\n" .
                       "📱 Acompanhe no app: " . SITE_URL . "\n\n" .
                       "Qualquer dúvida, estamos aqui! 💜";
        }
    }
    
    /**
     * Envia mensagem via WhatsApp usando a infraestrutura existente
     * 
     * Este método reutiliza exatamente a mesma lógica que funciona
     * na consulta de saldo, garantindo compatibilidade total.
     * 
     * @param string $phone Telefone do destinatário
     * @param string $message Mensagem a ser enviada
     * @return array Resultado do envio
     */
    private function sendWhatsAppMessage($phone, $message) {
        try {
            // Dados para envio via WhatsApp Bot
            $postData = [
                'secret' => WHATSAPP_BOT_SECRET,
                'phone' => $phone,
                'message' => $message,
                'type' => 'cashback_notification'
            ];
            
            // Configurar requisição cURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => WHATSAPP_BOT_URL . '/send-message',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => WHATSAPP_TIMEOUT,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: KlubeCash-Notifier/1.0'
                ],
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            // Verificar se houve erro na requisição
            if ($curlError) {
                throw new Exception("Erro cURL: " . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode);
            }
            
            $responseData = json_decode($response, true);
            
            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                return [
                    'success' => true,
                    'message' => 'Notificação enviada com sucesso',
                    'whatsapp_response' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Falha no envio WhatsApp: ' . ($responseData['message'] ?? 'Erro desconhecido'),
                    'whatsapp_response' => $responseData
                ];
            }
            
        } catch (Exception $e) {
            error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registra log da notificação para auditoria
     * 
     * Mantém histórico de todas as notificações enviadas para
     * análise de performance e resolução de problemas.
     * 
     * @param int $transactionId ID da transação
     * @param string $messageType Tipo da mensagem
     * @param array $result Resultado do envio
     */
    private function logNotification($transactionId, $messageType, $result) {
        try {
            // Log em arquivo para debug
            $logMessage = sprintf(
                "[%s] Notificação Cashback - Transação: %d, Tipo: %s, Sucesso: %s, Detalhes: %s",
                date('Y-m-d H:i:s'),
                $transactionId,
                $messageType,
                $result['success'] ? 'SIM' : 'NÃO',
                $result['message']
            );
            
            error_log($logMessage);
            
            // Aqui você pode adicionar uma tabela de logs se desejar
            // Por enquanto mantemos só o log em arquivo
            
        } catch (Exception $e) {
            error_log('Erro ao registrar log de notificação: ' . $e->getMessage());
        }
    }
    
    /**
     * Formata número de telefone para padrão brasileiro
     * 
     * Garante que o telefone esteja no formato correto para
     * o bot WhatsApp (55 + DDD + número).
     * 
     * @param string $phone Telefone original
     * @return string Telefone formatado
     */
    private function formatPhoneNumber($phone) {
        // Remove todos os caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Se já tem código do país (55), manter
        if (strlen($phone) == 13 && substr($phone, 0, 2) == '55') {
            return $phone;
        }
        
        // Se tem 11 dígitos (DDD + número), adicionar 55
        if (strlen($phone) == 11) {
            return '55' . $phone;
        }
        
        // Se tem 10 dígitos (sem 9 no celular), adicionar 55 e 9
        if (strlen($phone) == 10) {
            $ddd = substr($phone, 0, 2);
            $numero = substr($phone, 2);
            return '55' . $ddd . '9' . $numero;
        }
        
        // Retornar como está se não conseguir formatar
        return $phone;
    }
    
    /**
     * Formata valor monetário para exibição
     * 
     * @param float $value Valor numérico
     * @return string Valor formatado (R$ 123,45)
     */
    private function formatCurrency($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
?>