<?php
// classes/SaldoConsulta.php - Versão com Debug Detalhado

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Versão com debug detalhado para investigar problemas de saldo
     */
    public function consultarSaldoPorTelefone($telefone) {
        try {
            // === ETAPA 1: LIMPEZA DO TELEFONE ===
            $telefoneLimpo = $this->limparTelefone($telefone);
            
            // Log detalhado da limpeza
            error_log("=== DEBUG SALDO ===");
            error_log("Telefone original: " . $telefone);
            error_log("Telefone limpo: " . $telefoneLimpo);
            
            // === ETAPA 2: BUSCA DO USUÁRIO ===
            $usuario = $this->buscarUsuarioPorTelefoneComDebug($telefoneLimpo);
            
            if (!$usuario) {
                error_log("RESULTADO: Usuário não encontrado");
                return [
                    'success' => false,
                    'message' => $this->gerarMensagemUsuarioNaoEncontrado(),
                    'user_found' => false
                ];
            }
            
            // Log do usuário encontrado
            error_log("USUÁRIO ENCONTRADO:");
            error_log("- ID: " . $usuario['id']);
            error_log("- Nome: " . $usuario['nome']);
            error_log("- Telefone cadastrado: " . $usuario['telefone']);
            
            // === ETAPA 3 e 4: CÁLCULO DOS SALDOS ===
            $saldos = $this->calcularSaldosComDebug($usuario['id']);
            
            // Gerar mensagem de resposta
            $mensagem = $this->gerarMensagemSaldo($usuario['nome'], $saldos);
            
            error_log("=== FIM DEBUG SALDO ===");
            
            return [
                'success' => true,
                'message' => $mensagem,
                'user_found' => true,
                'user_id' => $usuario['id'],
                'saldos' => $saldos
            ];
            
        } catch (Exception $e) {
            error_log('ERRO na consulta de saldo: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Busca usuário com logs detalhados para debug
     */
    private function buscarUsuarioPorTelefoneComDebug($telefoneLimpo) {
        // Primeiro, vamos ver quantos usuários têm telefone cadastrado
        $sqlCount = "SELECT COUNT(*) as total FROM usuarios WHERE telefone IS NOT NULL";
        $stmt = $this->db->prepare($sqlCount);
        $stmt->execute();
        $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        error_log("Total de usuários com telefone: " . $totalUsuarios);
        
        // Agora vamos fazer a busca atual
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
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log da busca
        error_log("BUSCA POR TELEFONE:");
        error_log("- Procurando por: " . $telefoneLimpo);
        error_log("- SQL executado: " . $sql);
        error_log("- Resultado encontrado: " . ($resultado ? 'SIM' : 'NÃO'));
        
        // Se não encontrou, vamos listar alguns telefones para comparar
        if (!$resultado) {
            error_log("TELEFONES CADASTRADOS (primeiros 5):");
            $sqlTelefones = "SELECT id, nome, telefone FROM usuarios WHERE telefone IS NOT NULL LIMIT 5";
            $stmtTel = $this->db->prepare($sqlTelefones);
            $stmtTel->execute();
            $telefones = $stmtTel->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($telefones as $tel) {
                $telLimpo = preg_replace('/\D/', '', $tel['telefone']);
                error_log("- ID {$tel['id']}: {$tel['nome']} - {$tel['telefone']} (limpo: $telLimpo)");
            }
        }
        
        return $resultado;
    }
    
    /**
     * Calcula saldos com debug detalhado
     */
    private function calcularSaldosComDebug($usuarioId) {
        error_log("CALCULANDO SALDOS PARA USUÁRIO ID: " . $usuarioId);
        
        // === SALDO DISPONÍVEL ===
        $sqlDisponivel = "SELECT 
                            COUNT(*) as quantidade_aprovadas,
                            COALESCE(SUM(valor_cashback), 0) as saldo_disponivel 
                         FROM transacoes_cashback 
                         WHERE usuario_id = :usuario_id 
                         AND status = 'aprovado'";
        
        $stmt = $this->db->prepare($sqlDisponivel);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $resultDisponivel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("TRANSAÇÕES APROVADAS:");
        error_log("- Quantidade: " . $resultDisponivel['quantidade_aprovadas']);
        error_log("- Valor total: R$ " . number_format($resultDisponivel['saldo_disponivel'], 2, ',', '.'));
        
        // === SALDO PENDENTE ===
        $sqlPendente = "SELECT 
                          COUNT(*) as quantidade_pendentes,
                          COALESCE(SUM(valor_cashback), 0) as saldo_pendente 
                       FROM transacoes_cashback 
                       WHERE usuario_id = :usuario_id 
                       AND status = 'pendente'";
        
        $stmt = $this->db->prepare($sqlPendente);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $resultPendente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("TRANSAÇÕES PENDENTES:");
        error_log("- Quantidade: " . $resultPendente['quantidade_pendentes']);
        error_log("- Valor total: R$ " . number_format($resultPendente['saldo_pendente'], 2, ',', '.'));
        
        // === DETALHES DAS ÚLTIMAS TRANSAÇÕES ===
        $sqlDetalhes = "SELECT 
                          id, 
                          valor_total, 
                          valor_cashback, 
                          status, 
                          data_transacao,
                          loja_id
                       FROM transacoes_cashback 
                       WHERE usuario_id = :usuario_id 
                       ORDER BY data_transacao DESC 
                       LIMIT 5";
        
        $stmt = $this->db->prepare($sqlDetalhes);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("ÚLTIMAS 5 TRANSAÇÕES:");
        foreach ($transacoes as $trans) {
            error_log("- ID {$trans['id']}: R$ {$trans['valor_cashback']} ({$trans['status']}) - {$trans['data_transacao']}");
        }
        
        // Buscar última transação para mostrar data
        $ultimaTransacao = count($transacoes) > 0 ? $transacoes[0]['data_transacao'] : null;
        
        $saldos = [
            'disponivel' => floatval($resultDisponivel['saldo_disponivel']),
            'pendente' => floatval($resultPendente['saldo_pendente']),
            'total' => floatval($resultDisponivel['saldo_disponivel']) + floatval($resultPendente['saldo_pendente']),
            'ultima_transacao' => $ultimaTransacao
        ];
        
        error_log("RESUMO FINAL DOS SALDOS:");
        error_log("- Disponível: R$ " . number_format($saldos['disponivel'], 2, ',', '.'));
        error_log("- Pendente: R$ " . number_format($saldos['pendente'], 2, ',', '.'));
        error_log("- Total: R$ " . number_format($saldos['total'], 2, ',', '.'));
        
        return $saldos;
    }
    
    // === MÉTODOS AUXILIARES (mantidos iguais) ===
    
    private function limparTelefone($telefone) {
        $limpo = preg_replace('/\D/', '', $telefone);
        
        if (strlen($limpo) >= 11 && substr($limpo, 0, 2) == '55') {
            $limpo = substr($limpo, 2);
        }
        
        if (strlen($limpo) == 11) {
            $limpo = substr($limpo, -9);
        } elseif (strlen($limpo) == 10) {
            $limpo = substr($limpo, -8);
        }
        
        return $limpo;
    }
    
    private function gerarMensagemSaldo($nomeUsuario, $saldos) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
        if ($saldos['total'] == 0) {
            return "💰 *Klube Cash - Seu Saldo*\n\n" .
                   "👋 Olá, {$nome}!\n\n" .
                   "💳 Você ainda não possui cashback acumulado.\n\n" .
                   "🛍️ Faça compras em nossas lojas parceiras e ganhe cashback em cada transação!\n\n" .
                   "📱 Acesse: https://klubecash.com\n\n" .
                   "🎯 *Klube Cash - Seu dinheiro de volta!*";
        }
        
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
    
    private function gerarMensagemErro() {
        return "⚠️ *Klube Cash*\n\n" .
               "Ocorreu um erro temporário ao consultar seu saldo.\n\n" .
               "🔄 Tente novamente em alguns instantes ou acesse:\nhttps://klubecash.com\n\n" .
               "📞 Se o problema persistir, entre em contato:\nhttps://klubecash.com/contato\n\n" .
               "🎯 *Klube Cash - Seu dinheiro de volta!*";
    }
}