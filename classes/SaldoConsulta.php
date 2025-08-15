<?php
/**
 * Classe para consultar o saldo de cashback de um usuário por telefone.
 * * Utiliza uma abordagem híbrida para garantir precisão e performance:
 * - Saldo Disponível: É obtido da tabela pré-calculada `cashback_saldos`.
 * - Saldo Pendente: É calculado em tempo real a partir da tabela `transacoes_cashback`.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class SaldoConsulta {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Ponto de entrada principal para consultar o saldo.
     *
     * @param string $telefone O número de telefone do usuário.
     * @return array Um array com o resultado da operação.
     */
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
            
            // Buscar saldos por loja
            $saldosPorLoja = $this->obterSaldosPorLoja($usuario['id']);
            
            if (empty($saldosPorLoja)) {
                return [
                    'success' => true,
                    'message' => $this->gerarMensagemSemSaldo($usuario['nome']),
                    'user_found' => true,
                    'user_id' => $usuario['id']
                ];
            }
            
            // Gerar mensagem com opções de lojas
            $mensagem = $this->gerarMensagemOpcoesLojas($usuario['nome'], $saldosPorLoja);
            
            return [
                'success' => true,
                'message' => $mensagem,
                'user_found' => true,
                'user_id' => $usuario['id'],
                'type' => 'menu_lojas',
                'lojas' => $saldosPorLoja
            ];
            
        } catch (Exception $e) {
            error_log('ERRO GRAVE na consulta de saldo: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mensagem quando não tem saldo
     */
    private function gerarMensagemSemSaldo($nomeUsuario) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
        return "💰 *Klube Cash - Seu Saldo*\n\n" .
            "👋 Olá, {$nome}!\n\n" .
            "💳 Você ainda não possui cashback acumulado.\n\n" .
            "🛍️ Faça compras em nossas lojas parceiras e comece a ganhar!";
    }
    /**
     * Obter saldos separados por loja
     */
    private function obterSaldosPorLoja($usuarioId) {
        $sql = "
            SELECT 
                l.id as loja_id,
                l.nome_fantasia,
                cs.saldo_disponivel,
                COALESCE(SUM(t.valor_cliente), 0) as saldo_pendente
            FROM lojas l
            JOIN cashback_saldos cs ON l.id = cs.loja_id
            LEFT JOIN transacoes_cashback t ON l.id = t.loja_id 
                AND t.usuario_id = cs.usuario_id 
                AND t.status IN ('pendente', 'pagamento_pendente')
            WHERE cs.usuario_id = :usuario_id 
            AND (cs.saldo_disponivel > 0 OR COALESCE(SUM(t.valor_cliente), 0) > 0)
            GROUP BY l.id, l.nome_fantasia, cs.saldo_disponivel
            ORDER BY cs.saldo_disponivel DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        
        $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar dados
        foreach ($lojas as &$loja) {
            $loja['saldo_disponivel'] = floatval($loja['saldo_disponivel']);
            $loja['saldo_pendente'] = floatval($loja['saldo_pendente']);
            $loja['total'] = $loja['saldo_disponivel'] + $loja['saldo_pendente'];
        }
        
        return $lojas;
    }

    /**
     * Gerar mensagem com opções de lojas
     */
    private function gerarMensagemOpcoesLojas($nomeUsuario, $lojas) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
        $mensagem = "💰 *Klube Cash - Suas Lojas*\n\n";
        $mensagem .= "👋 Olá, {$nome}!\n\n";
        $mensagem .= "Você possui saldo nas seguintes lojas:\n\n";
        
        $contador = 1;
        foreach ($lojas as $loja) {
            $mensagem .= "*{$contador}. {$loja['nome_fantasia']}*\n";
            
            if ($loja['saldo_disponivel'] > 0) {
                $mensagem .= "💳 Disponível: R$ " . number_format($loja['saldo_disponivel'], 2, ',', '.') . "\n";
            }
            
            if ($loja['saldo_pendente'] > 0) {
                $mensagem .= "⏳ Pendente: R$ " . number_format($loja['saldo_pendente'], 2, ',', '.') . "\n";
            }
            
            $mensagem .= "\n";
            $contador++;
        }
        
        $mensagem .= "📋 *Como consultar:*\n";
        $mensagem .= "• Digite o *número* da loja (ex: 1, 2, 3)\n";
        $mensagem .= "• Ou digite o *nome* da loja\n\n";
        $mensagem .= "🔄 Digite *saldo* para ver este menu novamente";
        
        return $mensagem;
    }
    /**
     * Buscar loja específica por número ou nome
     */
    private function buscarLojaEspecifica($usuarioId, $identificacao) {
        // Se é número, buscar por posição
        if (is_numeric($identificacao)) {
            $posicao = intval($identificacao) - 1; // Converter para índice (1 = posição 0)
            
            $sql = "
                SELECT 
                    l.id as loja_id,
                    l.nome_fantasia,
                    cs.saldo_disponivel,
                    COALESCE(SUM(t.valor_cliente), 0) as saldo_pendente
                FROM lojas l
                JOIN cashback_saldos cs ON l.id = cs.loja_id
                LEFT JOIN transacoes_cashback t ON l.id = t.loja_id 
                    AND t.usuario_id = cs.usuario_id 
                    AND t.status IN ('pendente', 'pagamento_pendente')
                WHERE cs.usuario_id = :usuario_id 
                AND (cs.saldo_disponivel > 0 OR COALESCE(SUM(t.valor_cliente), 0) > 0)
                GROUP BY l.id, l.nome_fantasia, cs.saldo_disponivel
                ORDER BY cs.saldo_disponivel DESC
                LIMIT 1 OFFSET :posicao
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':posicao', $posicao, PDO::PARAM_INT);
            
        } else {
            // Buscar por nome
            $sql = "
                SELECT 
                    l.id as loja_id,
                    l.nome_fantasia,
                    cs.saldo_disponivel,
                    COALESCE(SUM(t.valor_cliente), 0) as saldo_pendente
                FROM lojas l
                JOIN cashback_saldos cs ON l.id = cs.loja_id
                LEFT JOIN transacoes_cashback t ON l.id = t.loja_id 
                    AND t.usuario_id = cs.usuario_id 
                    AND t.status IN ('pendente', 'pagamento_pendente')
                WHERE cs.usuario_id = :usuario_id 
                AND l.nome_fantasia LIKE :nome_loja
                AND (cs.saldo_disponivel > 0 OR COALESCE(SUM(t.valor_cliente), 0) > 0)
                GROUP BY l.id, l.nome_fantasia, cs.saldo_disponivel
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $nomeParam = '%' . $identificacao . '%';
            $stmt->bindParam(':nome_loja', $nomeParam);
        }
        
        $stmt->execute();
        $loja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($loja) {
            $loja['saldo_disponivel'] = floatval($loja['saldo_disponivel']);
            $loja['saldo_pendente'] = floatval($loja['saldo_pendente']);
            $loja['total'] = $loja['saldo_disponivel'] + $loja['saldo_pendente'];
        }
        
        return $loja;
    }

    /**
     * Gerar mensagem para saldo de loja específica
     */
    private function gerarMensagemSaldoLoja($nomeUsuario, $loja) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
        $mensagem = "🏪 *{$loja['nome_fantasia']}*\n\n";
        $mensagem .= "👋 Olá, {$nome}!\n\n";
        
        if ($loja['saldo_disponivel'] > 0) {
            $mensagem .= "💳 *Saldo Disponível*\n";
            $mensagem .= "R$ " . number_format($loja['saldo_disponivel'], 2, ',', '.') . "\n\n";
        }
        
        if ($loja['saldo_pendente'] > 0) {
            $mensagem .= "⏳ *Aguardando Liberação*\n";
            $mensagem .= "R$ " . number_format($loja['saldo_pendente'], 2, ',', '.') . "\n\n";
            $mensagem .= "ℹ️ _Será liberado após a loja pagar a comissão_\n\n";
        }
        
        $mensagem .= "📊 *Total Acumulado*\n";
        $mensagem .= "R$ " . number_format($loja['total'], 2, ',', '.') . "\n\n";
        
        $mensagem .= "💡 *Lembre-se:* Este saldo só pode ser usado nesta loja.\n\n";
        $mensagem .= "🔄 Digite *saldo* para ver todas as suas lojas";
        
        return $mensagem;
    }
    /**
     * Consultar saldo específico de uma loja
     */
    public function consultarSaldoLoja($telefone, $lojaIdentificacao) {
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
            
            // Buscar loja específica
            $loja = $this->buscarLojaEspecifica($usuario['id'], $lojaIdentificacao);
            
            if (!$loja) {
                return [
                    'success' => true,
                    'message' => "❌ Loja não encontrada ou você não possui saldo nela.\n\nDigite *saldo* para ver suas opções.",
                    'user_found' => true
                ];
            }
            
            // Gerar imagem para esta loja específica
            require_once __DIR__ . '/ImageGenerator.php';
            $imagemResult = ImageGenerator::gerarImagemSaldoLoja($usuario, $loja);
            
            $mensagem = $this->gerarMensagemSaldoLoja($usuario['nome'], $loja);
            
            $response = [
                'success' => true,
                'message' => $mensagem,
                'user_found' => true,
                'type' => 'saldo_loja',
                'loja' => $loja
            ];
            
            // Adicionar dados da imagem se gerada com sucesso
            if ($imagemResult['success']) {
                $response['send_image'] = true;
                $response['image_url'] = $imagemResult['file_url'];
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log('Erro ao consultar saldo da loja: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro()
            ];
        }
    }
    /**
     * Calcula os saldos usando a abordagem híbrida.
     *
     * @param int $usuarioId O ID do usuário.
     * @return array Array com 'disponivel', 'pendente' e 'total'.
     */
    private function calcularSaldosHibrido($usuarioId) {
        // 1. Busca o Saldo Disponível (valor exato e rápido da tabela de saldos)
        $sqlDisponivel = "SELECT COALESCE(SUM(saldo_disponivel), 0) as saldo_disponivel
                        FROM cashback_saldos
                        WHERE usuario_id = :usuario_id";
        
        $stmtDisponivel = $this->db->prepare($sqlDisponivel);
        $stmtDisponivel->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtDisponivel->execute();
        $saldoDisponivel = floatval($stmtDisponivel->fetch(PDO::FETCH_ASSOC)['saldo_disponivel']);

        // 2. CORREÇÃO: Usa valor_cliente ao invés de valor_cashback para evitar duplicação
        $sqlPendente = "SELECT COALESCE(SUM(valor_cliente), 0) as saldo_pendente
                        FROM transacoes_cashback
                        WHERE usuario_id = :usuario_id AND status IN ('pendente', 'pagamento_pendente')";
                        
        $stmtPendente = $this->db->prepare($sqlPendente);
        $stmtPendente->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtPendente->execute();
        $saldoPendente = floatval($stmtPendente->fetch(PDO::FETCH_ASSOC)['saldo_pendente']);

        // 3. Retorna o array de saldos completo e correto
        return [
            'disponivel' => $saldoDisponivel,
            'pendente'   => $saldoPendente,
            'total'      => $saldoDisponivel + $saldoPendente,
        ];
    }

    /**
     * Formata e retorna a mensagem final para o usuário.
     *
     * @param string $nomeUsuario Nome do usuário.
     * @param array $saldos Array contendo os saldos.
     * @return string A mensagem formatada.
     */
    private function gerarMensagemSaldoCompleto($nomeUsuario, $saldos) {
        $nome = ucfirst(explode(' ', $nomeUsuario)[0]);
        
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
            $mensagem .= "Você já pode usar seu saldo disponível em novas compras!\n\n";
        } else {
             $mensagem .= "Assim que seu saldo pendente for aprovado, ele ficará disponível para uso!\n\n";
        }
        
        $mensagem .= "🎯 *Klube Cash - Seu dinheiro de volta!*";
        
        return $mensagem;
    }
    
    /**
     * Busca um usuário ativo pelo número de telefone.
     *
     * @param string $telefoneLimpo O telefone sem formatação.
     * @return array|false Os dados do usuário ou false se não encontrar.
     */
    private function buscarUsuarioPorTelefone($telefoneLimpo) {
        $sql = "SELECT id, nome FROM usuarios WHERE status = 'ativo' AND telefone IS NOT NULL AND (RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 9) = :telefone1 OR RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 8) = :telefone2 OR telefone LIKE :telefone3) LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':telefone1', $telefoneLimpo);
        $stmt->bindValue(':telefone2', $telefoneLimpo);
        $stmt->bindValue(':telefone3', '%' . $telefoneLimpo . '%');
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Limpa e padroniza o número de telefone.
     *
     * @param string $telefone Telefone com qualquer formato.
     * @return string Telefone contendo apenas os 8 ou 9 últimos dígitos.
     */
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
    
    /**
     * Gera mensagem padrão para usuário não encontrado.
     */
    private function gerarMensagemUsuarioNaoEncontrado() {
        return "🔍 *Klube Cash*\n\n" .
               "❌ Não encontramos seu cadastro com este número de telefone.\n\n" .
               "📱 *Faça seu cadastro gratuito:*\nhttps://klubecash.com/registro";
    }
    
    /**
     * Gera mensagem padrão para erro no sistema.
     */
    private function gerarMensagemErro() {
        return "⚠️ *Klube Cash*\n\n" .
               "Ocorreu um erro temporário ao consultar seu saldo.\n\n" .
               "🔄 Tente novamente em alguns instantes.";
    }
}