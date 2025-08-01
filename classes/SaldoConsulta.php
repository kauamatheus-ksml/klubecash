<?php
// classes/SaldoConsulta.php - Versão Final e Otimizada

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function consultarSaldoPorTelefone($telefone) {
        try {
            $telefoneLimpo = $this->limparTelefone($telefone);
            $usuario = $this->buscarUsuarioPorTelefone($telefoneLimpo);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => $this->gerarMensagemUsuarioNaoEncontrado(),
                    'user_found' => false
                ];
            }
            
            // Lógica de cálculo corrigida usando o método híbrido
            $saldos = $this->calcularSaldosHibrido($usuario['id']);
            
            // Mensagem aprimorada para o usuário
            $mensagem = $this->gerarMensagemSaldoCompleto($usuario['nome'], $saldos);
            
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
     * Calcula os saldos usando a abordagem híbrida:
     * - Saldo Disponível: Pega o valor exato da tabela `cashback_saldos`.
     * - Saldo Pendente: Calcula somando as transações pendentes de `transacoes_cashback`.
     */
    private function calcularSaldosHibrido($usuarioId) {
        // 1. Busca o Saldo Disponível (valor correto e rápido)
        $sqlDisponivel = "SELECT COALESCE(SUM(saldo_disponivel), 0) as saldo_disponivel
                          FROM cashback_saldos
                          WHERE usuario_id = :usuario_id";
        
        $stmtDisponivel = $this->db->prepare($sqlDisponivel);
        $stmtDisponivel->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtDisponivel->execute();
        $resultDisponivel = $stmtDisponivel->fetch(PDO::FETCH_ASSOC);
        $saldoDisponivel = floatval($resultDisponivel['saldo_disponivel']);

        // 2. Calcula o Saldo Pendente (único lugar com essa informação)
        $sqlPendente = "SELECT COALESCE(SUM(valor_cashback), 0) as saldo_pendente
                        FROM transacoes_cashback
                        WHERE usuario_id = :usuario_id AND status = 'pendente'";
                        
        $stmtPendente = $this->db->prepare($sqlPendente);
        $stmtPendente->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtPendente->execute();
        $resultPendente = $stmtPendente->fetch(PDO::FETCH_ASSOC);
        $saldoPendente = floatval($resultPendente['saldo_pendente']);

        // 3. Retorna o array de saldos completo e correto
        return [
            'disponivel' => $saldoDisponivel,
            'pendente'   => $saldoPendente,
            'total'      => $saldoDisponivel + $saldoPendente,
        ];
    }

    /**
     * Gera uma mensagem inteligente e completa para o usuário final.
     */
    private function gerarMensagemSaldoCompleto($nomeUsuario, $saldos) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
        // Caso o usuário não tenha nenhum tipo de saldo
        if ($saldos['total'] == 0) {
            return "💰 *Klube Cash - Seu Saldo*\n\n" .
                   "👋 Olá, {$nome}!\n\n" .
                   "💳 Você ainda não possui cashback acumulado.\n\n" .
                   "🛍️ Faça compras em nossas lojas parceiras e comece a ganhar!";
        }
        
        $mensagem = "💰 *Klube Cash - Seu Saldo*\n\n";
        $mensagem .= "👋 Olá, {$nome}!\n\n";
        
        if ($saldos['disponivel'] > 0) {
            $mensagem .= "✅ *Saldo Disponível:* R$ " . number_format($saldos['disponivel'], 2, ',', '.') . "\n";
        }
        
        if ($saldos['pendente'] > 0) {
            $mensagem .= "⏳ *Saldo Pendente:* R$ " . number_format($saldos['pendente'], 2, ',', '.') . "\n";
        }
        
        $mensagem .= "\n";
        
        if ($saldos['disponivel'] > 0) {
            $mensagem .= "Você já pode usar seu saldo disponível em suas próximas compras!\n\n";
        } else {
             $mensagem .= "Assim que seu saldo pendente for aprovado, ele ficará disponível para uso!\n\n";
        }
        
        $mensagem .= "🎯 *Klube Cash - Seu dinheiro de volta!*";
        
        return $mensagem;
    }
    
    // --- MÉTODOS AUXILIARES (sem necessidade de alteração) ---

    private function buscarUsuarioPorTelefone($telefoneLimpo) {
        $sql = "SELECT id, nome FROM usuarios WHERE status = 'ativo' AND (RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 9) = :telefone1 OR RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 8) = :telefone2 OR telefone LIKE :telefone3) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':telefone1', $telefoneLimpo);
        $stmt->bindValue(':telefone2', $telefoneLimpo);
        $stmt->bindValue(':telefone3', '%' . $telefoneLimpo . '%');
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function limparTelefone($telefone) {
        $limpo = preg_replace('/\D/', '', $telefone);
        if (strlen($limpo) >= 11 && substr($limpo, 0, 2) == '55') { $limpo = substr($limpo, 2); }
        if (strlen($limpo) == 11) { $limpo = substr($limpo, -9); } 
        elseif (strlen($limpo) == 10) { $limpo = substr($limpo, -8); }
        return $limpo;
    }
    
    private function gerarMensagemUsuarioNaoEncontrado() {
        return "🔍 *Klube Cash*\n\n" .
               "❌ Não encontramos seu cadastro com este número de telefone.\n\n" .
               "📱 *Faça seu cadastro gratuito:*\nhttps://klubecash.com/registro";
    }
    
    private function gerarMensagemErro() {
        return "⚠️ *Klube Cash*\n\n" .
               "Ocorreu um erro temporário ao consultar seu saldo.\n\n" .
               "🔄 Tente novamente em alguns instantes.";
    }
}