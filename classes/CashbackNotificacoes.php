<?php
// classes/CashbackNotificacoes.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/WhatsAppBot.php';

/**
 * Classe responsável por enviar notificações automáticas quando um cashback é registrado
 * Envia mensagens personalizadas baseadas no perfil do cliente
 */
class CashbackNotificacoes {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Envia notificação de cashback registrado para o cliente
     * 
     * @param int $transacaoId ID da transação que foi criada
     * @return array Resultado do envio
     */
    public function enviarNotificacaoCashback($transacaoId) {
        try {
            // Buscar dados completos da transação
            $transacao = $this->buscarDadosTransacao($transacaoId);
            
            if (!$transacao) {
                throw new Exception("Transação não encontrada: ID $transacaoId");
            }
            
            // Verificar se já foi enviada notificação para esta transação
            if ($this->jaFoiNotificada($transacaoId)) {
                return [
                    'success' => true,
                    'message' => 'Notificação já foi enviada anteriormente',
                    'duplicate' => true
                ];
            }
            
            // Verificar se usuário tem telefone cadastrado
            if (empty($transacao['telefone'])) {
                $this->registrarTentativa($transacaoId, 'erro', 'Usuário sem telefone cadastrado');
                return [
                    'success' => false,
                    'error' => 'Usuário não possui telefone cadastrado'
                ];
            }
            
            // Determinar tipo de mensagem baseado no perfil
            $tipoMensagem = $this->determinarTipoMensagem($transacao);
            
            // Gerar mensagem personalizada
            $mensagem = $this->gerarMensagemCashback($transacao, $tipoMensagem);
            
            // Enviar via WhatsApp
            $resultado = WhatsAppBot::sendMessage($transacao['telefone'], $mensagem);
            
            // Registrar tentativa no banco
            $status = $resultado['success'] ? 'enviada' : 'erro';
            $observacao = $resultado['success'] ? 'Notificação enviada com sucesso' : $resultado['error'];
            $this->registrarTentativa($transacaoId, $status, $observacao);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log('Erro ao enviar notificação de cashback: ' . $e->getMessage());
            
            // Registrar erro no banco
            $this->registrarTentativa($transacaoId, 'erro', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Busca dados completos da transação e usuário
     */
    private function buscarDadosTransacao($transacaoId) {
        $sql = "SELECT 
                    t.id,
                    t.usuario_id,
                    t.valor_total,
                    t.valor_cashback,
                    t.data_transacao,
                    t.status,
                    u.nome as usuario_nome,
                    u.telefone,
                    u.data_criacao as usuario_desde,
                    l.nome_fantasia as loja_nome,
                    l.porcentagem_cashback
                FROM transacoes_cashback t
                INNER JOIN usuarios u ON t.usuario_id = u.id
                LEFT JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = :transacao_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':transacao_id', $transacaoId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica se já foi enviada notificação para esta transação
     */
    private function jaFoiNotificada($transacaoId) {
        $sql = "SELECT COUNT(*) as total 
                FROM cashback_notificacoes 
                WHERE transacao_id = :transacao_id 
                AND status = 'enviada'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':transacao_id', $transacaoId);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }
    
    /**
     * Determina qual tipo de mensagem enviar baseado no perfil do cliente
     */
    private function determinarTipoMensagem($transacao) {
        // Verificar se é primeira compra (usuário criado há menos de 7 dias E primeira transação)
        $usuarioRecente = (strtotime($transacao['usuario_desde']) > strtotime('-7 days'));
        
        if ($usuarioRecente) {
            $sql = "SELECT COUNT(*) as total FROM transacoes_cashback WHERE usuario_id = :usuario_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario_id', $transacao['usuario_id']);
            $stmt->execute();
            $totalTransacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($totalTransacoes <= 1) {
                return 'primeira_compra';
            }
        }
        
        // Verificar se é compra grande (acima de R$ 500)
        if ($transacao['valor_total'] >= 500) {
            return 'compra_grande';
        }
        
        // Verificar se é cliente VIP (mais de R$ 500 em cashback acumulado nos últimos 6 meses)
        $sql = "SELECT COALESCE(SUM(valor_cashback), 0) as total_cashback
                FROM transacoes_cashback 
                WHERE usuario_id = :usuario_id 
                AND data_transacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND status = 'aprovado'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $transacao['usuario_id']);
        $stmt->execute();
        $totalCashback = $stmt->fetch(PDO::FETCH_ASSOC)['total_cashback'];
        
        if ($totalCashback >= 500) {
            return 'vip';
        }
        
        return 'padrao';
    }
    
    /**
     * Gera mensagem personalizada baseada no tipo determinado
     */
    private function gerarMensagemCashback($transacao, $tipo) {
        $nome = ucfirst(explode(' ', $transacao['usuario_nome'])[0]);
        $valorCashback = number_format($transacao['valor_cashback'], 2, ',', '.');
        $valorTotal = number_format($transacao['valor_total'], 2, ',', '.');
        $percentual = number_format($transacao['porcentagem_cashback'], 0);
        $lojaNome = $transacao['loja_nome'] ?: 'Loja Parceira';
        
        switch ($tipo) {
            case 'primeira_compra':
                return "🎊 *Parabéns! Sua primeira compra no Klube Cash!*\n\n" .
                       "Que emoção! Você acabou de ganhar seu primeiro cashback:\n\n" .
                       "🛍️ *{$lojaNome}:* R$ {$valorTotal}\n" .
                       "💸 *Seu cashback:* R$ {$valorCashback} ({$percentual}%)\n\n" .
                       "📚 *Como funciona o processo:*\n" .
                       "1. ✅ Sua compra foi registrada (feito!)\n" .
                       "2. 🔍 A loja confirma a transação\n" .
                       "3. 💰 Seu cashback fica disponível\n" .
                       "4. 🎯 Você usa em novas compras!\n\n" .
                       "⏰ *Prazo:* Normalmente leva de 1 a 3 dias úteis.\n\n" .
                       "💡 *Dica:* Durante a espera, que tal descobrir outras lojas parceiras? Digite \"lojas\" para ver mais opções!\n\n" .
                       "Bem-vindo à família Klube Cash! 🎉";
                       
            case 'compra_grande':
                return "🎯 *WOW! Que compra espetacular!*\n\n" .
                       "🤑 *Sua economia hoje:*\n" .
                       "• *Compra:* R$ {$valorTotal}\n" .
                       "• *Cashback:* R$ {$valorCashback} ({$percentual}%)\n\n" .
                       "💰 *Com esse valor você já pode:*\n" .
                       "✓ Fazer uma compra de R$ {$valorCashback} de graça\n" .
                       "✓ Acumular para algo maior\n" .
                       "✓ Indicar amigos e multiplicar ainda mais\n\n" .
                       "⏰ *Liberação:* Em 2-3 dias este valor estará disponível para uso.\n\n" .
                       "🎊 Você está arrasando no Klube Cash!\n\n" .
                       "🎯 *Klube Cash - Seu dinheiro de volta!*";
                       
            case 'vip':
                return "🌟 *Cashback VIP Registrado*\n\n" .
                       "Olá {$nome}! Mais uma compra inteligente:\n\n" .
                       "💎 *{$lojaNome}:* R$ {$valorTotal} → Cashback: R$ {$valorCashback}\n\n" .
                       "⚡ *Status VIP:* Sua validação é prioritária\n" .
                       "📅 *Liberação estimada:* 24h úteis\n\n" .
                       "Continue assim! 💪\n\n" .
                       "🎯 *Klube Cash - Seu dinheiro de volta!*";
                       
            default: // padrao
                return "🎉 *Oba! Seu cashback foi registrado!*\n\n" .
                       "👋 Olá {$nome}!\n\n" .
                       "✅ *Acabamos de receber sua compra:*\n" .
                       "• *Loja:* {$lojaNome}\n" .
                       "• *Valor:* R$ {$valorTotal}\n" .
                       "• *Seu cashback:* R$ {$valorCashback} ({$percentual}%)\n\n" .
                       "⏰ *O que acontece agora?*\n" .
                       "Sua compra está sendo validada pela loja. Este é um processo normal de segurança que garante que tudo está correto.\n\n" .
                       "📅 *Prazo:* Em até 3 dias úteis seu cashback será liberado para uso!\n\n" .
                       "💰 Enquanto isso, você já pode acompanhar pelo site ou digitando \"saldo\" aqui no WhatsApp.\n\n" .
                       "🎯 *Klube Cash - Seu dinheiro de volta!*";
        }
    }
    
    /**
     * Registra tentativa de envio no banco de dados
     */
    private function registrarTentativa($transacaoId, $status, $observacao) {
        try {
            $sql = "INSERT INTO cashback_notificacoes 
                    (transacao_id, status, observacao, data_tentativa) 
                    VALUES (:transacao_id, :status, :observacao, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':transacao_id', $transacaoId);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':observacao', $observacao);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log('Erro ao registrar tentativa de notificação: ' . $e->getMessage());
        }
    }
    
    /**
     * Reenviar notificações que falharam (para ser chamada por cron job)
     */
    public function reenviarNotificacoesFalhadas() {
        $sql = "SELECT DISTINCT transacao_id 
                FROM cashback_notificacoes 
                WHERE status = 'erro' 
                AND data_tentativa > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND transacao_id NOT IN (
                    SELECT transacao_id FROM cashback_notificacoes WHERE status = 'enviada'
                )
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $transacoesFalhadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados = [];
        foreach ($transacoesFalhadas as $transacao) {
            $resultados[] = $this->enviarNotificacaoCashback($transacao['transacao_id']);
        }
        
        return $resultados;
    }
}