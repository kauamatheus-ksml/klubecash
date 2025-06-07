<?php
/**
 * Controlador OpenPix para Klube Cash
 * Gerencia todas as operações relacionadas ao PIX via OpenPix
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/OpenPixClient.php';

class OpenPixController {
    
    private $db;
    private $openPix;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->openPix = new OpenPixClient();
    }
    
    /**
     * Criar cobrança PIX para um pagamento
     */
    public function createChargeForPayment($paymentId, $storeUserId = null) {
        try {
            // Validar se o pagamento existe e pertence à loja
            $payment = $this->getPaymentData($paymentId, $storeUserId);
            
            if (!$payment) {
                return [
                    'status' => false,
                    'message' => 'Pagamento não encontrado ou não autorizado'
                ];
            }
            
            // Verificar se já não existe PIX ativo
            if ($this->hasActivePix($paymentId)) {
                return [
                    'status' => false,
                    'message' => 'Já existe um PIX ativo para este pagamento'
                ];
            }
            
            // Preparar dados para criação da cobrança
            $chargeData = $this->prepareChargeData($payment);
            
            // Criar cobrança na OpenPix
            $result = $this->openPix->createCharge($chargeData);
            
            if ($result['status']) {
                // Salvar dados do PIX no banco
                $this->savePixData($paymentId, $result, $chargeData);
                
                // Log da operação
                $this->logPixEvent($paymentId, 'charge_created', [
                    'charge_id' => $result['charge_id'],
                    'correlation_id' => $chargeData['correlation_id'],
                    'value' => $chargeData['amount']
                ]);
                
                return [
                    'status' => true,
                    'message' => 'PIX criado com sucesso',
                    'data' => $result
                ];
            } else {
                // Log do erro
                $this->logPixEvent($paymentId, 'error', [
                    'error' => $result['message'],
                    'context' => 'create_charge'
                ]);
                
                return $result;
            }
            
        } catch (Exception $e) {
            $this->logPixEvent($paymentId, 'error', [
                'error' => $e->getMessage(),
                'context' => 'create_charge_exception'
            ]);
            
            return [
                'status' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar status de uma cobrança
     */
    public function checkChargeStatus($chargeId, $paymentId = null) {
        try {
            $result = $this->openPix->getChargeStatus($chargeId);
            
            if ($result['status']) {
                // Atualizar status no banco se fornecido paymentId
                if ($paymentId) {
                    $this->updatePixStatus($paymentId, $result);
                }
                
                $this->logPixEvent($paymentId, 'status_check', [
                    'charge_id' => $chargeId,
                    'status' => $result['charge_status']
                ]);
                
                return $result;
            } else {
                $this->logPixEvent($paymentId, 'error', [
                    'error' => $result['message'],
                    'context' => 'check_status'
                ]);
                
                return $result;
            }
            
        } catch (Exception $e) {
            $this->logPixEvent($paymentId, 'error', [
                'error' => $e->getMessage(),
                'context' => 'check_status_exception'
            ]);
            
            return [
                'status' => false,
                'message' => 'Erro ao verificar status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook recebido
     */
    public function processWebhook($payload, $ipAddress = null, $userAgent = null) {
        try {
            // Validar webhook
            if (!$this->openPix->validateWebhook($payload)) {
                return [
                    'status' => false,
                    'message' => 'Webhook inválido'
                ];
            }
            
            // Processar evento
            $event = $this->openPix->processWebhookEvent($payload);
            
            if (!$event['status']) {
                return [
                    'status' => false,
                    'message' => 'Evento não processável'
                ];
            }
            
            // Salvar evento para auditoria
            $this->saveWebhookEvent($event, $payload, $ipAddress, $userAgent);
            
            // Processar baseado no tipo de evento
            if ($event['event_type'] === 'charge' || $event['event_type'] === 'pix') {
                $this->processChargeEvent($event);
            }
            
            return [
                'status' => true,
                'message' => 'Webhook processado com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log('Erro no processamento de webhook OpenPix: ' . $e->getMessage());
            
            return [
                'status' => false,
                'message' => 'Erro interno no processamento'
            ];
        }
    }
    
    /**
     * Listar pagamentos PIX
     */
    public function listPixPayments($filters = []) {
        try {
            $sql = "SELECT * FROM v_openpix_payments WHERE 1=1";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters['loja_id'])) {
                $sql .= " AND loja_id = ?";
                $params[] = $filters['loja_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['data_inicio'])) {
                $sql .= " AND data_criacao >= ?";
                $params[] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $sql .= " AND data_criacao <= ?";
                $params[] = $filters['data_fim'];
            }
            
            // Ordenação e paginação
            $sql .= " ORDER BY data_criacao DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . (int)$filters['limit'];
                
                if (!empty($filters['offset'])) {
                    $sql .= " OFFSET " . (int)$filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'status' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro ao listar pagamentos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter estatísticas PIX
     */
    public function getPixStatistics($lojaId = null, $periodo = '30days') {
        try {
            $dateFilter = $this->getDateFilter($periodo);
            
            $sql = "
                SELECT 
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = 'aprovado' THEN 1 END) as paid_payments,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN pix_expires_at < NOW() AND status = 'pendente' THEN 1 END) as expired_payments,
                    SUM(valor_total) as total_value,
                    SUM(CASE WHEN status = 'aprovado' THEN valor_total ELSE 0 END) as paid_value,
                    AVG(CASE WHEN tempo_pagamento_minutos IS NOT NULL THEN tempo_pagamento_minutos END) as avg_payment_time
                FROM v_openpix_payments 
                WHERE data_criacao >= ?
            ";
            
            $params = [$dateFilter];
            
            if ($lojaId) {
                $sql .= " AND loja_id = ?";
                $params[] = $lojaId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular taxas
            $stats['success_rate'] = $stats['total_payments'] > 0 
                ? round(($stats['paid_payments'] / $stats['total_payments']) * 100, 2) 
                : 0;
            
            $stats['expired_rate'] = $stats['total_payments'] > 0 
                ? round(($stats['expired_payments'] / $stats['total_payments']) * 100, 2) 
                : 0;
            
            return [
                'status' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancelar PIX
     */
    public function cancelPix($paymentId, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE pagamentos_comissao 
                SET status = 'cancelado', 
                    pix_status = 'canceled',
                    observacoes = CONCAT(COALESCE(observacoes, ''), ' | PIX cancelado: ', ?)
                WHERE id = ? AND status = 'pendente'
            ");
            
            $stmt->execute([$reason, $paymentId]);
            
            if ($stmt->rowCount() > 0) {
                $this->logPixEvent($paymentId, 'canceled', ['reason' => $reason]);
                
                return [
                    'status' => true,
                    'message' => 'PIX cancelado com sucesso'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Pagamento não encontrado ou não pode ser cancelado'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro ao cancelar PIX: ' . $e->getMessage()
            ];
        }
    }
    
    // Métodos privados
    
    private function getPaymentData($paymentId, $storeUserId = null) {
        $sql = "
            SELECT p.*, l.nome_fantasia, l.email, l.telefone, l.cnpj 
            FROM pagamentos_comissao p
            JOIN lojas l ON p.loja_id = l.id
            WHERE p.id = ? AND p.status = 'pendente'
        ";
        
        $params = [$paymentId];
        
        if ($storeUserId) {
            $sql .= " AND l.usuario_id = ?";
            $params[] = $storeUserId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function hasActivePix($paymentId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM pagamentos_comissao 
            WHERE id = ? 
            AND pix_charge_id IS NOT NULL 
            AND (pix_expires_at IS NULL OR pix_expires_at > NOW())
            AND status = 'pendente'
        ");
        
        $stmt->execute([$paymentId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    private function prepareChargeData($payment) {
        return [
            'amount' => (float)$payment['valor_total'],
            'correlation_id' => "klube_payment_{$payment['id']}_" . time(),
            'comment' => "Comissão Klube Cash - {$payment['nome_fantasia']} - Pagamento #{$payment['id']}",
            'customer' => [
                'name' => $payment['nome_fantasia'],
                'email' => $payment['email'],
                'phone' => $payment['telefone'] ?? null,
                'cnpj' => $payment['cnpj'] ?? null
            ],
            'additional_info' => [
                [
                    'key' => 'payment_id',
                    'value' => $payment['id']
                ],
                [
                    'key' => 'store_id', 
                    'value' => $payment['loja_id']
                ],
                [
                    'key' => 'system',
                    'value' => 'Klube Cash v' . SYSTEM_VERSION
                ]
            ]
        ];
    }
    
    private function savePixData($paymentId, $pixResult, $chargeData) {
        $stmt = $this->db->prepare("
            UPDATE pagamentos_comissao 
            SET 
                pix_charge_id = ?,
                pix_correlation_id = ?,
                pix_transaction_id = ?,
                pix_qr_code = ?,
                pix_qr_code_image = ?,
                pix_payment_link = ?,
                pix_expires_at = ?,
                pix_status = 'active',
                metodo_pagamento = 'pix_openpix',
                data_atualizacao = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $pixResult['charge_id'],
            $chargeData['correlation_id'],
            $pixResult['transaction_id'],
            $pixResult['qr_code'],
            $pixResult['qr_code_image'],
            $pixResult['payment_link'],
            $pixResult['expires_at'],
            $paymentId
        ]);
    }
    
    private function updatePixStatus($paymentId, $statusResult) {
        $stmt = $this->db->prepare("
            UPDATE pagamentos_comissao 
            SET pix_status = ? 
            WHERE id = ?
        ");
        
        $pixStatus = strtolower($statusResult['charge_status']);
        $stmt->execute([$pixStatus, $paymentId]);
    }
    
    private function processChargeEvent($event) {
        if ($event['charge_status'] === 'COMPLETED' || $event['charge_status'] === 'CONFIRMED') {
            $this->processPaymentConfirmation($event);
        }
    }
    
    private function processPaymentConfirmation($event) {
        // Buscar pagamento
        $stmt = $this->db->prepare("
            SELECT * FROM pagamentos_comissao 
            WHERE (pix_correlation_id = ? OR pix_charge_id = ?) 
            AND status = 'pendente'
        ");
        
        $stmt->execute([$event['charge_id'], $event['charge_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            return;
        }
        
        // Aprovar pagamento
        $this->approvePayment($payment['id'], $event);
    }
    
    private function approvePayment($paymentId, $event) {
        try {
            $this->db->beginTransaction();
            
            // Atualizar pagamento
            $stmt = $this->db->prepare("
                UPDATE pagamentos_comissao 
                SET 
                    status = 'aprovado',
                    data_aprovacao = NOW(),
                    pix_paid_at = ?,
                    pix_status = 'completed',
                    observacoes = 'Pagamento PIX confirmado automaticamente via webhook OpenPix'
                WHERE id = ?
            ");
            
            $paidAt = $event['paid_at'] ?? date('Y-m-d H:i:s');
            $stmt->execute([$paidAt, $paymentId]);
            
            // Liberar cashback para clientes
            $this->releaseCashback($paymentId);
            
            $this->db->commit();
            
            $this->logPixEvent($paymentId, 'payment_confirmed', [
                'charge_id' => $event['charge_id'],
                'paid_at' => $paidAt
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function releaseCashback($paymentId) {
        // Buscar transações relacionadas e liberar cashback
        $stmt = $this->db->prepare("
            UPDATE transacoes_cashback t
            JOIN transacoes_comissao tc ON t.id = tc.transacao_id
            SET t.status = 'aprovado'
            WHERE tc.pagamento_id = ? AND t.status = 'pagamento_pendente'
        ");
        
        $stmt->execute([$paymentId]);
    }
    
    private function saveWebhookEvent($event, $payload, $ipAddress, $userAgent) {
        $stmt = $this->db->prepare("
            INSERT INTO openpix_webhook_events 
            (charge_id, event_type, status, payload, processed, processed_at, ip_address) 
            VALUES (?, ?, ?, ?, TRUE, NOW(), ?)
        ");
        
        $stmt->execute([
            $event['charge_id'],
            $event['event_type'],
            $event['charge_status'],
            json_encode($payload),
            $ipAddress
        ]);
    }
    
    private function logPixEvent($paymentId, $eventType, $data = null) {
        if (!LOG_PIX_TRANSACTIONS) {
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO openpix_logs 
            (payment_id, charge_id, event_type, data, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $paymentId,
            $data['charge_id'] ?? null,
            $eventType,
            $data ? json_encode($data) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    private function getDateFilter($periodo) {
        switch ($periodo) {
            case '7days':
                return date('Y-m-d', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d', strtotime('-90 days'));
            case '1year':
                return date('Y-m-d', strtotime('-1 year'));
            default:
                return date('Y-m-d', strtotime('-30 days'));
        }
    }
}
?>