<?php
/**
 * Classe para consultar o saldo de cashback de um usuário por telefone.
 * Versão corrigida com tratamento robusto de erros
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
     */
    public function consultarSaldoPorTelefone($telefone) {
        try {
            error_log("SaldoConsulta: Iniciando consulta para telefone: {$telefone}");
            
            $telefoneLimpo = $this->limparTelefone($telefone);
            error_log("SaldoConsulta: Telefone limpo: {$telefoneLimpo}");
            
            $usuario = $this->buscarUsuarioPorTelefone($telefoneLimpo);
            
            if (!$usuario) {
                error_log("SaldoConsulta: Usuário não encontrado");
                return [
                    'success' => false,
                    'message' => $this->gerarMensagemUsuarioNaoEncontrado(),
                    'user_found' => false
                ];
            }
            
            error_log("SaldoConsulta: Usuário encontrado - ID: {$usuario['id']}, Nome: {$usuario['nome']}");
            
            // Buscar saldos por loja
            $saldosPorLoja = $this->obterSaldosPorLoja($usuario['id']);
            error_log("SaldoConsulta: Encontradas " . count($saldosPorLoja) . " lojas com saldo");
            
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
            error_log('SaldoConsulta: ERRO - ' . $e->getMessage());
            error_log('SaldoConsulta: Stack trace - ' . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obter saldos separados por loja - VERSÃO CORRIGIDA
     */
    private function obterSaldosPorLoja($usuarioId) {
        try {
            error_log("SaldoConsulta: Buscando saldos por loja para usuário {$usuarioId}");
            
            // VERSÃO SIMPLIFICADA - Primeiro verificar se as tabelas existem
            $sql = "
                SELECT 
                    l.id as loja_id,
                    l.nome_fantasia,
                    COALESCE(cs.saldo_disponivel, 0) as saldo_disponivel
                FROM lojas l
                LEFT JOIN cashback_saldos cs ON l.id = cs.loja_id AND cs.usuario_id = :usuario_id
                WHERE cs.saldo_disponivel > 0
                ORDER BY cs.saldo_disponivel DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("SaldoConsulta: Query executada, " . count($lojas) . " lojas encontradas");
            
            // Buscar saldo pendente separadamente para cada loja
            foreach ($lojas as &$loja) {
                $pendenteSql = "
                    SELECT COALESCE(SUM(valor_cliente), 0) as saldo_pendente
                    FROM transacoes_cashback 
                    WHERE usuario_id = :usuario_id 
                    AND loja_id = :loja_id 
                    AND status IN ('pendente', 'pagamento_pendente')
                ";
                
                $pendenteStmt = $this->db->prepare($pendenteSql);
                $pendenteStmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $pendenteStmt->bindParam(':loja_id', $loja['loja_id'], PDO::PARAM_INT);
                $pendenteStmt->execute();
                
                $resultPendente = $pendenteStmt->fetch(PDO::FETCH_ASSOC);
                $loja['saldo_pendente'] = floatval($resultPendente['saldo_pendente'] ?? 0);
                
                // Processar dados
                $loja['saldo_disponivel'] = floatval($loja['saldo_disponivel']);
                $loja['total'] = $loja['saldo_disponivel'] + $loja['saldo_pendente'];
                
                error_log("SaldoConsulta: Loja {$loja['nome_fantasia']} - Disponível: {$loja['saldo_disponivel']}, Pendente: {$loja['saldo_pendente']}");
            }
            
            // Filtrar lojas que realmente têm saldo (disponível ou pendente)
            $lojas = array_filter($lojas, function($loja) {
                return $loja['total'] > 0;
            });
            
            return array_values($lojas); // Reindexar array
            
        } catch (Exception $e) {
            error_log("SaldoConsulta: Erro em obterSaldosPorLoja - " . $e->getMessage());
            return [];
        }
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
     * Consultar saldo específico de uma loja
     */
    public function consultarSaldoLoja($telefone, $lojaIdentificacao) {
        try {
            error_log("SaldoConsulta: Consultando loja específica - {$lojaIdentificacao}");
            
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
            
            $mensagem = $this->gerarMensagemSaldoLoja($usuario['nome'], $loja);
            
            $response = [
                'success' => true,
                'message' => $mensagem,
                'user_found' => true,
                'type' => 'saldo_loja',
                'loja' => $loja,
                'send_image' => false // Por enquanto sem imagem para evitar erros
            ];
            
            return $response;
            
        } catch (Exception $e) {
            error_log('SaldoConsulta: Erro ao consultar saldo da loja - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->gerarMensagemErro()
            ];
        }
    }

    /**
     * Buscar loja específica por número ou nome
     */
    private function buscarLojaEspecifica($usuarioId, $identificacao) {
        try {
            error_log("SaldoConsulta: Buscando loja específica - {$identificacao}");
            
            // Se é número, buscar por posição
            if (is_numeric($identificacao)) {
                $posicao = intval($identificacao) - 1; // Converter para índice (1 = posição 0)
                
                // Primeiro obter todas as lojas
                $todasLojas = $this->obterSaldosPorLoja($usuarioId);
                
                if (isset($todasLojas[$posicao])) {
                    return $todasLojas[$posicao];
                } else {
                    return null;
                }
                
            } else {
                // Buscar por nome
                $sql = "
                    SELECT 
                        l.id as loja_id,
                        l.nome_fantasia,
                        COALESCE(cs.saldo_disponivel, 0) as saldo_disponivel
                    FROM lojas l
                    LEFT JOIN cashback_saldos cs ON l.id = cs.loja_id AND cs.usuario_id = :usuario_id
                    WHERE l.nome_fantasia LIKE :nome_loja
                    AND cs.saldo_disponivel > 0
                    LIMIT 1
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $nomeParam = '%' . $identificacao . '%';
                $stmt->bindParam(':nome_loja', $nomeParam);
                $stmt->execute();
                
                $loja = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loja) {
                    // Buscar saldo pendente
                    $pendenteSql = "
                        SELECT COALESCE(SUM(valor_cliente), 0) as saldo_pendente
                        FROM transacoes_cashback 
                        WHERE usuario_id = :usuario_id 
                        AND loja_id = :loja_id 
                        AND status IN ('pendente', 'pagamento_pendente')
                    ";
                    
                    $pendenteStmt = $this->db->prepare($pendenteSql);
                    $pendenteStmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $pendenteStmt->bindParam(':loja_id', $loja['loja_id'], PDO::PARAM_INT);
                    $pendenteStmt->execute();
                    
                    $resultPendente = $pendenteStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $loja['saldo_disponivel'] = floatval($loja['saldo_disponivel']);
                    $loja['saldo_pendente'] = floatval($resultPendente['saldo_pendente'] ?? 0);
                    $loja['total'] = $loja['saldo_disponivel'] + $loja['saldo_pendente'];
                }
                
                return $loja;
            }
            
        } catch (Exception $e) {
            error_log("SaldoConsulta: Erro em buscarLojaEspecifica - " . $e->getMessage());
            return null;
        }
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
     * Busca um usuário ativo pelo número de telefone.
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
?>