<?php
// classes/SaldoConsulta.php - Versão CORRIGIDA para usar a tabela 'cashback_saldos'

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function consultarSaldoPorTelefone($telefone) {
        try {
            // Etapa 1: Limpeza do telefone (sem alteração)
            $telefoneLimpo = $this->limparTelefone($telefone);
            error_log("=== DEBUG SALDO (v2) ===");
            error_log("Telefone original: " . $telefone);
            error_log("Telefone limpo: " . $telefoneLimpo);
            
            // Etapa 2: Busca do usuário (sem alteração)
            $usuario = $this->buscarUsuarioPorTelefoneComDebug($telefoneLimpo);
            
            if (!$usuario) {
                error_log("RESULTADO: Usuário não encontrado");
                return [
                    'success' => false,
                    'message' => $this->gerarMensagemUsuarioNaoEncontrado(),
                    'user_found' => false
                ];
            }
            
            error_log("USUÁRIO ENCONTRADO: ID " . $usuario['id'] . " - " . $usuario['nome']);
            
            // Etapa 3: Cálculo do saldo (MÉTODO CORRIGIDO)
            $saldos = $this->calcularSaldos($usuario['id']);
            
            // Etapa 4: Gerar mensagem de resposta (sem alteração na lógica, mas se beneficia do novo saldo)
            $mensagem = $this->gerarMensagemSaldo($usuario['nome'], $saldos);
            
            error_log("=== FIM DEBUG SALDO (v2) ===");
            
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
     * MÉTODO PRINCIPAL DA CORREÇÃO
     * Agora consulta a tabela 'cashback_saldos' que já tem o saldo pré-calculado.
     * É mais simples, rápido e correto de acordo com o seu novo banco de dados.
     */
    private function calcularSaldos($usuarioId) {
        error_log("CALCULANDO SALDOS PARA USUÁRIO ID: " . $usuarioId);
        error_log("Fonte de dados: Tabela 'cashback_saldos'");

        // A nova query soma o 'saldo_disponivel' de todas as lojas para aquele usuário.
        $sql = "SELECT 
                    COALESCE(SUM(saldo_disponivel), 0) as saldo_total_disponivel,
                    MAX(ultima_atualizacao) as ultima_atualizacao_saldo
                FROM cashback_saldos
                WHERE usuario_id = :usuario_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // O conceito de 'saldo pendente' não existe mais nesta tabela, o que simplifica tudo.
        $saldos = [
            'disponivel' => floatval($result['saldo_total_disponivel']),
            'pendente'   => 0, // Não há saldo pendente neste modelo
            'total'      => floatval($result['saldo_total_disponivel']),
            'ultima_transacao' => $result['ultima_atualizacao_saldo']
        ];
        
        error_log("RESUMO FINAL DOS SALDOS:");
        error_log("- Disponível: R$ " . number_format($saldos['disponivel'], 2, ',', '.'));
        error_log("- Última atualização de saldo: " . $saldos['ultima_transacao']);
        
        return $saldos;
    }
    
    // Métodos auxiliares que permanecem os mesmos
    private function buscarUsuarioPorTelefoneComDebug($telefoneLimpo) {
        // ... (código original sem alterações)
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

    private function limparTelefone($telefone) {
        // ... (código original sem alterações)
        $limpo = preg_replace('/\D/', '', $telefone);
        if (strlen($limpo) >= 11 && substr($limpo, 0, 2) == '55') { $limpo = substr($limpo, 2); }
        if (strlen($limpo) == 11) { $limpo = substr($limpo, -9); }
        elseif (strlen($limpo) == 10) { $limpo = substr($limpo, -8); }
        return $limpo;
    }
    
    private function gerarMensagemSaldo($nomeUsuario, $saldos) {
        // Este método agora funciona perfeitamente com a estrutura de saldos simplificada.
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
            $mensagem .= "💳 *Saldo Disponível:* R$ " . number_format($saldos['disponivel'], 2, ',', '.') . "\n\n";
            $mensagem .= "✅ Você pode usar seu saldo disponível em suas próximas compras!\n\n";
        }
        
        $mensagem .= "📱 Acesse: https://klubecash.com\n\n";
        $mensagem .= "🎯 *Klube Cash - Seu dinheiro de volta!*";
        
        return $mensagem;
    }

    private function gerarMensagemUsuarioNaoEncontrado() {
        // ... (código original sem alterações)
        return "🔍 *Klube Cash*\n\n" .
               "❌ Não encontramos seu cadastro com este número de telefone.\n\n" .
               "📱 *Faça seu cadastro gratuito:*\nhttps://klubecash.com/registro\n\n" .
               "🎯 *Klube Cash - Seu dinheiro de volta!*";
    }
    
    private function gerarMensagemErro() {
        // ... (código original sem alterações)
        return "⚠️ *Klube Cash*\n\n" .
               "Ocorreu um erro temporário ao consultar seu saldo.\n\n" .
               "🔄 Tente novamente em alguns instantes.\n\n" .
               "🎯 *Klube Cash - Seu dinheiro de volta!*";
    }
}