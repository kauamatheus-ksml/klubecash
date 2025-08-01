<?php
// classes/SaldoConsulta.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Classe SaldoConsulta - Gerencia consultas de saldo via WhatsApp
 * 
 * Esta classe é responsável por buscar e calcular o saldo de cashback
 * dos usuários quando eles enviam mensagem "saldo" no WhatsApp
 */
class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Busca usuário por telefone e retorna mensagem de saldo formatada
     * 
     * @param string $telefone Número de telefone do usuário
     * @return array Array com success, message e dados do usuário
     */
    public function consultarSaldoPorTelefone($telefone) {
        try {
            // Limpar telefone - remover máscaras e caracteres especiais
            $telefoneLimpo = $this->limparTelefone($telefone);
            
            // Buscar usuário por telefone (busca inteligente pelos últimos 9 dígitos)
            $usuario = $this->buscarUsuarioPorTelefone($telefoneLimpo);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => $this->gerarMensagemUsuarioNaoEncontrado(),
                    'user_found' => false
                ];
            }
            
            // Calcular saldos do usuário
            $saldos = $this->calcularSaldos($usuario['id']);
            
            // Gerar mensagem de resposta
            $mensagem = $this->gerarMensagemSaldo($usuario['nome'], $saldos);
            
            return [
                'success' => true,
                'message' => $mensagem,
                'user_found' => true,
                'user_id' => $usuario['id'],
                'saldos' => $saldos
            ];
            
        } catch (Exception $e) {
            error_log('Erro na consulta de saldo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpa o telefone removendo caracteres especiais e formatações
     */
    private function limparTelefone($telefone) {
        // Remove tudo que não é número
        $limpo = preg_replace('/\D/', '', $telefone);
        
        // Remove código do país se presente (55)
        if (strlen($limpo) >= 11 && substr($limpo, 0, 2) == '55') {
            $limpo = substr($limpo, 2);
        }
        
        // Remove código de área se tiver 11 dígitos (deixa apenas os 9 últimos)
        if (strlen($limpo) == 11) {
            $limpo = substr($limpo, -9);
        } elseif (strlen($limpo) == 10) {
            $limpo = substr($limpo, -8); // Para números fixos
        }
        
        return $limpo;
    }
    
    /**
     * Busca usuário por telefone usando busca inteligente
     */
    private function buscarUsuarioPorTelefone($telefoneLimpo) {
        // Busca pelos últimos dígitos do telefone
        $sql = "SELECT id, nome, email, telefone, status 
                FROM usuarios 
                WHERE status = 'ativo' 
                AND telefone IS NOT NULL 
                AND (
                    RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 9) = :telefone1
                    OR RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 8) = :telefone2
                    OR telefone LIKE :telefone3
                )
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':telefone1', $telefoneLimpo);
        $stmt->bindValue(':telefone2', $telefoneLimpo);
        $stmt->bindValue(':telefone3', '%' . $telefoneLimpo . '%');
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula os saldos disponível e pendente do usuário
     */
    private function calcularSaldos($usuarioId) {
        // Saldo disponível (transações aprovadas)
        $sqlDisponivel = "SELECT COALESCE(SUM(valor_cashback), 0) as saldo_disponivel 
                         FROM transacoes_cashback 
                         WHERE usuario_id = :usuario_id 
                         AND status = 'aprovado'";
        
        $stmt = $this->db->prepare($sqlDisponivel);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $saldoDisponivel = $stmt->fetch(PDO::FETCH_ASSOC)['saldo_disponivel'];
        
        // Saldo pendente (transações pendentes)
        $sqlPendente = "SELECT COALESCE(SUM(valor_cashback), 0) as saldo_pendente 
                       FROM transacoes_cashback 
                       WHERE usuario_id = :usuario_id 
                       AND status = 'pendente'";
        
        $stmt = $this->db->prepare($sqlPendente);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $saldoPendente = $stmt->fetch(PDO::FETCH_ASSOC)['saldo_pendente'];
        
        // Buscar última transação para mostrar data
        $sqlUltima = "SELECT data_transacao 
                     FROM transacoes_cashback 
                     WHERE usuario_id = :usuario_id 
                     ORDER BY data_transacao DESC 
                     LIMIT 1";
        
        $stmt = $this->db->prepare($sqlUltima);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $ultimaTransacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'disponivel' => floatval($saldoDisponivel),
            'pendente' => floatval($saldoPendente),
            'total' => floatval($saldoDisponivel) + floatval($saldoPendente),
            'ultima_transacao' => $ultimaTransacao ? $ultimaTransacao['data_transacao'] : null
        ];
    }
    
    /**
     * Gera mensagem formatada com o saldo do usuário
     */
    private function gerarMensagemSaldo($nomeUsuario, $saldos) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]); // Primeiro nome
        
        // Se não tem saldo nenhum
        if ($saldos['total'] == 0) {
            return "💰 *Klube Cash - Seu Saldo*\n\n" .
                   "👋 Olá, {$nome}!\n\n" .
                   "💳 Você ainda não possui cashback acumulado.\n\n" .
                   "🛍️ Faça compras em nossas lojas parceiras e ganhe cashback em cada transação!\n\n" .
                   "📱 Acesse: https://klubecash.com\n\n" .
                   "🎯 *Klube Cash - Seu dinheiro de volta!*";
        }
        
        // Se tem saldo
        $mensagem = "💰 *Klube Cash - Seu Saldo*\n\n";
        $mensagem .= "👋 Olá, {$nome}!\n\n";
        
        if ($saldos['disponivel'] > 0) {
            $mensagem .= "💳 *Saldo Disponível:* R$ " . number_format($saldos['disponivel'], 2, ',', '.') . "\n";
        }
        
        if ($saldos['pendente'] > 0) {
            $mensagem .= "⏳ *Saldo Pendente:* R$ " . number_format($saldos['pendente'], 2, ',', '.') . "\n";
        }
        
        $mensagem .= "\n";
        
        if ($saldos['disponivel'] > 0) {
            $mensagem .= "✅ Você pode usar seu saldo disponível em suas próximas compras!\n\n";
        }
        
        if ($saldos['pendente'] > 0) {
            $mensagem .= "⏰ Seu saldo pendente será liberado após confirmação da loja.\n\n";
        }
        
        $mensagem .= "📱 Acesse: https://klubecash.com\n\n";
        $mensagem .= "🎯 *Klube Cash - Seu dinheiro de volta!*";
        
        return $mensagem;
    }
    
    /**
     * Mensagem para usuário não encontrado
     */
    private function gerarMensagemUsuarioNaoEncontrado() {
        return "🔍 *Klube Cash*\n\n" .
               "❌ Não encontramos seu cadastro com este número de telefone.\n\n" .
               "📱 *Faça seu cadastro gratuito:*\nhttps://klubecash.com/registro\n\n" .
               "💰 Após o cadastro, você poderá:\n" .
               "• Ganhar cashback em cada compra\n" .
               "• Consultar seu saldo pelo WhatsApp\n" .
               "• Usar o cashback em novas compras\n\n" .
               "📞 *Dúvidas?* Entre em contato:\nhttps://klubecash.com/contato\n\n" .
               "🎯 *Klube Cash - Seu dinheiro de volta!*";
    }
    
    /**
     * Mensagem de erro genérico
     */
    private function gerarMensagemErro() {
        return "⚠️ *Klube Cash*\n\n" .
               "Ocorreu um erro temporário ao consultar seu saldo.\n\n" .
               "🔄 Tente novamente em alguns instantes ou acesse:\nhttps://klubecash.com\n\n" .
               "📞 Se o problema persistir, entre em contato:\nhttps://klubecash.com/contato\n\n" .
               "🎯 *Klube Cash - Seu dinheiro de volta!*";
    }
}