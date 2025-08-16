<?php
// classes/SaldoConsulta.php - VERSÃO FINAL CORRIGIDA

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../models/CashbackBalance.php';
require_once __DIR__ . '/ImageGenerator.php';

/**
 * Classe para Consulta de Saldo - Integração WhatsApp
 * Versão corrigida e testada
 */
class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Consulta saldo geral do usuário por telefone
     */
    public function consultarSaldoPorTelefone($telefone) {
        try {
            error_log("=== CONSULTA SALDO CONSOLIDADA ===");
            
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            error_log("USUÁRIO: {$usuario['nome']}");
            
            // Buscar saldos (consolidados se necessário)
            $saldosLojas = $this->buscarSaldosConsolidados($usuario);
            $saldoTotal = $this->calcularSaldoTotalForcado($saldosLojas);
            
            error_log("TOTAL LOJAS ENCONTRADAS: " . count($saldosLojas));
            
            if (empty($saldosLojas)) {
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            $mensagem = $this->gerarMensagemSaldoCompleto($usuario, $saldosLojas, $saldoTotal);
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'total_lojas' => count($saldosLojas),
                'saldo_total' => $saldoTotal
            ];
            
        } catch (Exception $e) {
            error_log('ERRO: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'message' => 'Erro interno.'
            ];
        }
    }

    /**
 * Determina o tipo de cliente baseado nos dados cadastrais
 */
public function determinarTipoCliente($usuario) {
        if (!$usuario) {
            return 'unknown';
        }
        
        // Cliente completo precisa ter: nome, telefone, email e senha
        if (!empty($usuario['email']) && !empty($usuario['senha_hash'])) {
            return 'completo';
        }
        
        return 'visitante';
    }

    /**
     * Busca dados do usuário para API (com user_data)
     * NOVO MÉTODO PARA RETORNAR DADOS COMPLETOS
     */
    public function buscarDadosParaAPI($telefone) {
        try {
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'user_found' => false,
                    'user_data' => null,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            // Buscar saldo para mensagem
            $resultado = $this->consultarSaldoPorTelefone($telefone);
            
            return [
                'success' => $resultado['success'],
                'user_found' => true,
                'user_data' => $usuario,
                'message' => $resultado['message']
            ];
            
        } catch (Exception $e) {
            error_log('ERRO buscarDadosParaAPI: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'user_data' => null,
                'message' => 'Erro interno.'
            ];
        }
    }
/**
     * Busca saldos consolidados de múltiplos usuários
     */
    private function buscarSaldosConsolidados($usuario) {
        try {
            error_log("=== BUSCA SALDOS USANDO TABELA CASHBACK_SALDOS ===");
            
            if (isset($usuario['tipo_consolidado'])) {
                // Usuário consolidado (múltiplos IDs)
                error_log("USUÁRIO CONSOLIDADO DETECTADO");
                
                $usuariosIds = $usuario['usuarios_ids'];
                error_log("IDs para consolidar: " . implode(', ', $usuariosIds));
                
                $placeholders = implode(',', array_fill(0, count($usuariosIds), '?'));
                
                $stmt = $this->db->prepare("
                    SELECT 
                        cs.loja_id,
                        l.nome_fantasia,
                        l.logo,
                        l.categoria,
                        l.porcentagem_cashback,
                        SUM(cs.saldo_disponivel) as saldo_disponivel
                    FROM cashback_saldos cs
                    INNER JOIN lojas l ON cs.loja_id = l.id
                    WHERE cs.usuario_id IN ($placeholders)
                    AND cs.saldo_disponivel > 0
                    GROUP BY cs.loja_id, l.nome_fantasia, l.logo, l.categoria, l.porcentagem_cashback
                    ORDER BY saldo_disponivel DESC
                ");
                
                $stmt->execute($usuariosIds);
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("CONSOLIDADO: " . count($resultado) . " lojas encontradas");
                
            } else {
                // Usuário normal - USAR TABELA CASHBACK_SALDOS
                error_log("USUÁRIO NORMAL - USANDO TABELA CASHBACK_SALDOS");
                error_log("ID do usuário: {$usuario['id']}");
                
                $stmt = $this->db->prepare("
                    SELECT 
                        cs.loja_id,
                        l.nome_fantasia,
                        l.logo,
                        l.categoria,
                        l.porcentagem_cashback,
                        cs.saldo_disponivel
                    FROM cashback_saldos cs
                    INNER JOIN lojas l ON cs.loja_id = l.id
                    WHERE cs.usuario_id = :user_id
                    AND cs.saldo_disponivel > 0
                    ORDER BY cs.saldo_disponivel DESC, l.nome_fantasia ASC
                ");
                
                $stmt->bindParam(':user_id', $usuario['id']);
                $stmt->execute();
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("NORMAL: " . count($resultado) . " lojas encontradas na tabela");
                
                // Se não encontrou na tabela, usar transações como fallback
                if (empty($resultado)) {
                    error_log("FALLBACK: Buscando nas transações...");
                    
                    $stmt = $this->db->prepare("
                        SELECT 
                            t.loja_id,
                            l.nome_fantasia,
                            l.logo,
                            l.categoria,
                            l.porcentagem_cashback,
                            SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END) as saldo_disponivel
                        FROM transacoes_cashback t
                        INNER JOIN lojas l ON t.loja_id = l.id
                        WHERE t.usuario_id = :user_id
                        GROUP BY t.loja_id, l.nome_fantasia, l.logo, l.categoria, l.porcentagem_cashback
                        HAVING saldo_disponivel > 0
                        ORDER BY saldo_disponivel DESC
                    ");
                    
                    $stmt->bindParam(':user_id', $usuario['id']);
                    $stmt->execute();
                    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("FALLBACK: " . count($resultado) . " lojas encontradas nas transações");
                }
            }
            
            foreach ($resultado as $index => $saldo) {
                error_log("LOJA[{$index}]: {$saldo['nome_fantasia']} - R$ {$saldo['saldo_disponivel']}");
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("ERRO ao buscar saldos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Debug completo das transações
     */
    private function debugTransacoesCompletas($userId) {
        try {
            error_log("=== DEBUG TRANSAÇÕES COMPLETAS ===");
            
            $stmt = $this->db->prepare("
                SELECT t.id, t.loja_id, l.nome_fantasia, t.valor_cliente, t.status, t.data_transacao
                FROM transacoes_cashback t
                LEFT JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
                ORDER BY t.data_transacao DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("TOTAL TRANSAÇÕES USUÁRIO: " . count($transacoes));
            
            $lojas = [];
            foreach ($transacoes as $t) {
                if (!isset($lojas[$t['loja_id']])) {
                    $lojas[$t['loja_id']] = [
                        'nome' => $t['nome_fantasia'],
                        'aprovado' => 0,
                        'pendente' => 0
                    ];
                }
                
                if ($t['status'] === 'aprovado') {
                    $lojas[$t['loja_id']]['aprovado'] += $t['valor_cliente'];
                } elseif (in_array($t['status'], ['pendente', 'pagamento_pendente'])) {
                    $lojas[$t['loja_id']]['pendente'] += $t['valor_cliente'];
                }
                
                error_log("TRANS: {$t['nome_fantasia']} - R$ {$t['valor_cliente']} - {$t['status']}");
            }
            
            error_log("RESUMO POR LOJA:");
            foreach ($lojas as $lojaId => $dados) {
                error_log("LOJA {$lojaId}: {$dados['nome']} - Aprovado: R$ {$dados['aprovado']} - Pendente: R$ {$dados['pendente']}");
            }
            
        } catch (Exception $e) {
            error_log("ERRO debug: " . $e->getMessage());
        }
    }
    
    /**
     * Calcula saldo total forçado
     */
    private function calcularSaldoTotalForcado($saldos) {
        $total = 0;
        foreach ($saldos as $saldo) {
            $total += floatval($saldo['saldo_disponivel']);
        }
        return $total;
    }
    /**
     * Busca saldos forçando query direta
     */
    private function buscarSaldosForcado($userId) {
        try {
            error_log("=== BUSCA SALDOS FORÇADA ===");
            
            $stmt = $this->db->prepare("
                SELECT 
                    t.loja_id,
                    l.nome_fantasia,
                    l.logo,
                    l.categoria,
                    l.porcentagem_cashback,
                    SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END) as saldo_disponivel
                FROM transacoes_cashback t
                INNER JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
                GROUP BY t.loja_id, l.nome_fantasia, l.logo, l.categoria, l.porcentagem_cashback
                ORDER BY saldo_disponivel DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("RESULTADO BUSCA FORÇADA: " . count($resultado));
            
            foreach ($resultado as $r) {
                error_log("FORÇADO: {$r['nome_fantasia']} = R$ {$r['saldo_disponivel']}");
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("ERRO busca forçada: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta saldo específico por loja (VERSÃO FINAL CORRIGIDA)
     */
    public function consultarSaldoLoja($telefone, $identificacaoLoja) {
        try {
            error_log("=== CONSULTA SALDO LOJA CONSOLIDADA ===");
            error_log("PARAMETROS: telefone={$telefone}, loja={$identificacaoLoja}");
            
            // 1. Buscar usuário pelo telefone (COM CONSOLIDAÇÃO)
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                error_log("ERRO: Usuário não encontrado");
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            error_log("SUCCESS: Usuário encontrado: {$usuario['nome']}");
            
            // 2. USAR BUSCA CONSOLIDADA (igual ao consultarSaldoPorTelefone)
            $saldosLojas = $this->buscarSaldosConsolidados($usuario);
            
            error_log("INFO: Total de lojas com saldo: " . count($saldosLojas));
            
            if (empty($saldosLojas)) {
                error_log("AVISO: Usuário sem saldo em nenhuma loja");
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            // 3. LOG detalhado das lojas para debug
            foreach ($saldosLojas as $index => $loja) {
                error_log("LOJA[{$index}]: Nome='{$loja['nome_fantasia']}', Saldo=R$ {$loja['saldo_disponivel']}");
            }
            
            // 4. Verificar se é seleção por número
            if (is_numeric($identificacaoLoja)) {
                $numeroLoja = intval($identificacaoLoja);
                error_log("PROCESSANDO: Seleção por número: {$numeroLoja}");
                
                // Validar se o número está no range
                if ($numeroLoja >= 1 && $numeroLoja <= count($saldosLojas)) {
                    $indiceLoja = $numeroLoja - 1; // Converter 1->0, 2->1, etc.
                    
                    // VERIFICAÇÃO EXTRA: Certificar que o índice existe
                    if (isset($saldosLojas[$indiceLoja])) {
                        $lojaSelecionada = $saldosLojas[$indiceLoja];
                        error_log("SUCCESS: Loja selecionada pelo número {$numeroLoja}: '{$lojaSelecionada['nome_fantasia']}'");
                        
                        return $this->gerarRespostaLojaEspecifica($usuario, $lojaSelecionada);
                    } else {
                        error_log("ERRO: Índice {$indiceLoja} não existe no array de lojas");
                        return $this->criarRespostaErroIndice($usuario, $saldosLojas, $numeroLoja);
                    }
                } else {
                    error_log("ERRO: Número inválido {$numeroLoja}, máximo permitido: " . count($saldosLojas));
                    return [
                        'success' => true,
                        'user_found' => true,
                        'message' => $this->gerarMensagemNumeroInvalido($usuario, $saldosLojas, $numeroLoja)
                    ];
                }
            }
            
            // 5. Buscar por nome da loja
            error_log("PROCESSANDO: Busca por nome: '{$identificacaoLoja}'");
            $lojaEncontrada = $this->buscarLojaPorNome($saldosLojas, $identificacaoLoja);
            
            if ($lojaEncontrada) {
                error_log("SUCCESS: Loja encontrada por nome: '{$lojaEncontrada['nome_fantasia']}'");
                return $this->gerarRespostaLojaEspecifica($usuario, $lojaEncontrada);
            }
            
            // 6. Se não encontrou nada específico, mostrar todas as opções
            error_log("INFO: Loja '{$identificacaoLoja}' não encontrada, mostrando todas as opções");
            return [
                'success' => true,
                'user_found' => true,
                'message' => $this->gerarMensagemTodasAsLojas($usuario, $saldosLojas)
            ];
            
        } catch (Exception $e) {
            error_log('ERRO CRÍTICO em consultarSaldoLoja: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'message' => 'Ocorreu um erro interno. Tente novamente ou digite "saldo" para ver suas opções.'
            ];
        }
    }
    
    /**
     * Cria resposta de erro quando índice não existe (fallback de segurança)
     */
    private function criarRespostaErroIndice($usuario, $saldosLojas, $numeroDigitado) {
        $nome = explode(' ', $usuario['nome'])[0];
        $totalLojas = count($saldosLojas);
        
        $mensagem = "⚠️ *Erro Técnico*\n\n";
        $mensagem .= "Olá, *{$nome}*!\n\n";
        $mensagem .= "Houve um problema ao acessar a loja {$numeroDigitado}.\n\n";
        $mensagem .= "🏪 *Suas opções válidas:*\n\n";
        
        for ($i = 1; $i <= $totalLojas; $i++) {
            $loja = $saldosLojas[$i-1];
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            $mensagem .= "{$i}. *{$loja['nome_fantasia']}* - R$ {$saldo}\n";
        }
        
        $mensagem .= "\n💡 Tente novamente digitando um número de 1 a {$totalLojas}!";
        
        return [
            'success' => true,
            'user_found' => true,
            'message' => $mensagem
        ];
    }
    
    /**
     * Busca TODOS os usuários com o mesmo telefone (CORREÇÃO PARA VISITANTES)
     */
    private function buscarUsuarioPorTelefone($telefone) {
        try {
            error_log("=== BUSCA USUÁRIOS MÚLTIPLOS ===");
            error_log("TELEFONE: {$telefone}");
            
            // Limpar telefone
            $telefoneClean = preg_replace('/[^0-9]/', '', $telefone);
            
            // Variantes de busca
            $searchVariants = [
                $telefoneClean,
                '55' . $telefoneClean,
                (strlen($telefoneClean) >= 12 && substr($telefoneClean, 0, 2) === '55') ? substr($telefoneClean, 2) : $telefoneClean
            ];
            
            if (strlen($telefoneClean) === 12 && substr($telefoneClean, 0, 2) === '55') {
                $ddd = substr($telefoneClean, 2, 2);
                $numero = substr($telefoneClean, 4);
                $numeroComNove = $ddd . '9' . $numero;
                $searchVariants[] = $numeroComNove;
            }
            
            $searchVariants = array_unique($searchVariants);
            
            // BUSCAR TODOS OS USUÁRIOS (não apenas o primeiro)
            foreach ($searchVariants as $variant) {
                $stmt = $this->db->prepare("
                    SELECT id, nome, email, telefone, status, tipo_cliente
                    FROM usuarios 
                    WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') = :telefone
                    AND tipo = :tipo 
                    AND status = :status
                ");
                
                $stmt->bindParam(':telefone', $variant);
                $tipo = USER_TYPE_CLIENT;
                $stmt->bindParam(':tipo', $tipo);
                $status = USER_ACTIVE;
                $stmt->bindParam(':status', $status);
                
                $stmt->execute();
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($usuarios)) {
                    error_log("✅ ENCONTRADOS " . count($usuarios) . " usuários com telefone {$variant}");
                    
                    // Se encontrou múltiplos usuários visitantes, consolidar
                    if (count($usuarios) > 1) {
                        return $this->consolidarUsuariosVisitantes($usuarios, $telefone);
                    } else {
                        // Usuário único
                        error_log("USUÁRIO ÚNICO: {$usuarios[0]['nome']}");
                        return $usuarios[0];
                    }
                }
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log('ERRO ao buscar usuários: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Consolida múltiplos usuários visitantes em um virtual
     */
    private function consolidarUsuariosVisitantes($usuarios, $telefone) {
        try {
            error_log("=== CONSOLIDANDO USUÁRIOS VISITANTES ===");
            
            // Pegar o nome do primeiro usuário (geralmente é o mesmo)
            $nomeBase = $usuarios[0]['nome'];
            
            // Se os nomes forem diferentes, usar o primeiro nome
            $nomes = array_column($usuarios, 'nome');
            $nomesUnicos = array_unique($nomes);
            
            if (count($nomesUnicos) > 1) {
                error_log("NOMES DIFERENTES ENCONTRADOS: " . implode(', ', $nomesUnicos));
                // Usar o nome mais completo
                usort($nomesUnicos, function($a, $b) {
                    return strlen($b) - strlen($a);
                });
                $nomeBase = $nomesUnicos[0];
            }
            
            error_log("NOME CONSOLIDADO: {$nomeBase}");
            
            // Retornar usuário virtual consolidado
            return [
                'id' => 'CONSOLIDADO_' . implode('_', array_column($usuarios, 'id')),
                'nome' => $nomeBase,
                'email' => $usuarios[0]['email'], // Email do primeiro
                'telefone' => $telefone,
                'status' => 'ativo',
                'usuarios_ids' => array_column($usuarios, 'id'), // IDs para buscar saldos
                'tipo_consolidado' => true
            ];
            
        } catch (Exception $e) {
            error_log("ERRO ao consolidar usuários: " . $e->getMessage());
            return $usuarios[0]; // Fallback para o primeiro
        }
    }
    
    /**
     * Verifica saldos do usuário diretamente das transações
     */
    private function verificarSaldosUsuario($userId) {
        try {
            error_log("=== VERIFICANDO SALDOS USUÁRIO {$userId} ===");
            
            // Buscar saldos na tabela cashback_saldos
            $stmt1 = $this->db->prepare("
                SELECT cs.loja_id, l.nome_fantasia, cs.saldo_disponivel
                FROM cashback_saldos cs
                INNER JOIN lojas l ON cs.loja_id = l.id
                WHERE cs.usuario_id = :user_id
                ORDER BY cs.saldo_disponivel DESC
            ");
            $stmt1->bindParam(':user_id', $userId);
            $stmt1->execute();
            $saldosTabela = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("SALDOS NA TABELA: " . count($saldosTabela));
            
            // Buscar transações do usuário
            $stmt2 = $this->db->prepare("
                SELECT t.loja_id, l.nome_fantasia, 
                       SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END) as aprovado,
                       SUM(CASE WHEN t.status IN ('pendente', 'pagamento_pendente') THEN t.valor_cliente ELSE 0 END) as pendente,
                       COUNT(*) as total_transacoes
                FROM transacoes_cashback t
                INNER JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
                GROUP BY t.loja_id, l.nome_fantasia
                ORDER BY aprovado DESC
            ");
            $stmt2->bindParam(':user_id', $userId);
            $stmt2->execute();
            $transacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("TRANSAÇÕES POR LOJA: " . count($transacoes));
            
            foreach ($transacoes as $trans) {
                error_log("LOJA: {$trans['nome_fantasia']}, Aprovado: R$ {$trans['aprovado']}, Pendente: R$ {$trans['pendente']}");
            }
            
        } catch (Exception $e) {
            error_log("ERRO ao verificar saldos: " . $e->getMessage());
        }
    }
    
    /**
     * Busca loja por nome
     */
    private function buscarLojaPorNome($saldosLojas, $nomeLoja) {
        $nomeLoja = strtolower(trim($nomeLoja));
        
        foreach ($saldosLojas as $loja) {
            $nomeLojaAtual = strtolower($loja['nome_fantasia']);
            
            if ($nomeLojaAtual === $nomeLoja || strpos($nomeLojaAtual, $nomeLoja) !== false) {
                return $loja;
            }
        }
        
        return null;
    }
    


    /**
     * Gera resposta para loja específica COM SALDO PENDENTE (VERSÃO MELHORADA)
     */
    private function gerarRespostaLojaEspecifica($usuario, $loja) {
        try {
            error_log("=== GERANDO RESPOSTA LOJA ESPECÍFICA CONSOLIDADA ===");
            error_log("Usuário: {$usuario['nome']}");
            error_log("Loja: {$loja['nome_fantasia']}");
            error_log("Saldo Disponível: R$ {$loja['saldo_disponivel']}");
            
            $nome = explode(' ', $usuario['nome'])[0];
            $saldoDisponivel = number_format($loja['saldo_disponivel'], 2, ',', '.');
            
            // IDENTIFICAR CORRETAMENTE O ID DA LOJA
            $lojaId = $loja['loja_id'] ?? $loja['id'] ?? null;
            error_log("ID da loja identificado: {$lojaId}");
            
            if (!$lojaId) {
                error_log("ERRO: ID da loja não encontrado no array");
                $lojaId = $this->buscarIdLojaPorNome($loja['nome_fantasia']);
                error_log("ID encontrado por nome: {$lojaId}");
            }
            
            // BUSCAR SALDO PENDENTE (CONSOLIDADO SE NECESSÁRIO)
            $saldoPendente = 0;
            if ($lojaId) {
                if (isset($usuario['tipo_consolidado'])) {
                    // Para usuário consolidado, somar saldos pendentes de todos os IDs
                    foreach ($usuario['usuarios_ids'] as $userId) {
                        $saldoPendente += $this->buscarSaldoPendenteLoja($userId, $lojaId);
                    }
                } else {
                    // Usuário normal
                    $saldoPendente = $this->buscarSaldoPendenteLoja($usuario['id'], $lojaId);
                }
            }
            
            error_log("Saldo Pendente final: R$ {$saldoPendente}");
            
            // INÍCIO DA MENSAGEM
            $mensagem = "🏪 *{$loja['nome_fantasia']}*\n\n";
            $mensagem .= "👋 Olá, *{$nome}*!\n\n";
            
            // SEÇÃO DE SALDOS
            $mensagem .= "💰 *Seu saldo disponível:* R$ {$saldoDisponivel}\n";
            
            if ($saldoPendente > 0) {
                $saldoPendenteFormatado = number_format($saldoPendente, 2, ',', '.');
                $mensagem .= "⏳ *Saldo pendente:* R$ {$saldoPendenteFormatado}\n";
                $mensagem .= "   _aguardando confirmação da loja_\n\n";
                
                $saldoTotal = $loja['saldo_disponivel'] + $saldoPendente;
                $saldoTotalFormatado = number_format($saldoTotal, 2, ',', '.');
                $mensagem .= "💎 *Saldo total:* R$ {$saldoTotalFormatado}\n\n";
            } else {
                $mensagem .= "\n";
            }
            
            // INFORMAÇÕES DA LOJA
            $mensagem .= "📊 *Cashback:* {$loja['porcentagem_cashback']}%\n";
            $mensagem .= "📂 *Categoria:* " . ucfirst($loja['categoria'] ?? 'Geral') . "\n\n";
            
            // INSTRUÇÕES
            $mensagem .= "✨ *Como usar seu saldo:*\n";
            $mensagem .= "• Vá até a loja\n";
            $mensagem .= "• Informe que quer usar o Klube Cash\n";
            $mensagem .= "• Apresente seu CPF ou telefone\n\n";
            
            if ($saldoPendente > 0) {
                $mensagem .= "⏳ *Sobre o saldo pendente:*\n";
                $mensagem .= "• Aguardando confirmação da loja\n";
                $mensagem .= "• Será liberado em até 48h\n";
                $mensagem .= "• Você receberá notificação\n\n";
            }
            
            $mensagem .= "💡 _Este saldo só pode ser usado nesta loja específica._\n\n";
            $mensagem .= "Digite *saldo* para ver todas suas carteiras.";
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'send_image' => false
            ];
            
        } catch (Exception $e) {
            error_log("ERRO ao gerar resposta específica: " . $e->getMessage());
            return [
                'success' => true,
                'user_found' => true,
                'message' => "✅ Loja selecionada!\n\nDigite *saldo* para ver todas suas opções novamente."
            ];
        }
    }
    

    /**
     * Busca ID da loja pelo nome (fallback)
     */
    private function buscarIdLojaPorNome($nomeLoja) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM lojas WHERE nome_fantasia = :nome LIMIT 1");
            $stmt->bindParam(':nome', $nomeLoja);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar ID da loja: ' . $e->getMessage());
            return null;
        }
    }

        /**
     * Busca saldo pendente do usuário em uma loja específica (CORRIGIDO)
     * 
     * @param int $userId ID do usuário
     * @param int $lojaId ID da loja
     * @return float Valor do saldo pendente
     */
    private function buscarSaldoPendenteLoja($userId, $lojaId) {
        try {
            error_log("=== BUSCA SALDO PENDENTE ===");
            error_log("PARÂMETROS: usuário={$userId}, loja={$lojaId}");
            
            // PRIMEIRO: Verificar se a loja_id está correta
            // O campo pode vir de cashback_saldos, não da tabela de transações
            
            // BUSCA MAIS ABRANGENTE - tentar várias consultas
            $queries = [
                // Query 1: Transações pendentes direto
                "SELECT COALESCE(SUM(valor_cliente), 0) as saldo_pendente
                 FROM transacoes_cashback 
                 WHERE usuario_id = :user_id 
                 AND loja_id = :loja_id 
                 AND status IN ('pendente', 'pagamento_pendente')",
                
                // Query 2: Se o campo for diferente
                "SELECT COALESCE(SUM(valor_cashback), 0) as saldo_pendente
                 FROM transacoes_cashback 
                 WHERE usuario_id = :user_id 
                 AND loja_id = :loja_id 
                 AND status IN ('pendente', 'pagamento_pendente')",
                
                // Query 3: Busca por nome da loja (fallback)
                "SELECT COALESCE(SUM(t.valor_cliente), 0) as saldo_pendente
                 FROM transacoes_cashback t
                 INNER JOIN lojas l ON t.loja_id = l.id
                 WHERE t.usuario_id = :user_id 
                 AND l.id = :loja_id 
                 AND t.status IN ('pendente', 'pagamento_pendente')"
            ];
            
            foreach ($queries as $index => $query) {
                error_log("TENTANDO QUERY " . ($index + 1) . ": " . substr($query, 0, 100) . "...");
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':loja_id', $lojaId, PDO::PARAM_INT);
                
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && $result['saldo_pendente'] > 0) {
                    $saldoPendente = floatval($result['saldo_pendente']);
                    error_log("✅ SUCESSO Query " . ($index + 1) . ": R$ {$saldoPendente}");
                    return $saldoPendente;
                } else {
                    error_log("❌ Query " . ($index + 1) . " retornou: " . ($result['saldo_pendente'] ?? 'null'));
                }
            }
            
            // DEBUG: Mostrar todas as transações pendentes do usuário
            error_log("=== DEBUG: TODAS AS TRANSAÇÕES PENDENTES DO USUÁRIO ===");
            $debugStmt = $this->db->prepare("
                SELECT t.id, t.loja_id, l.nome_fantasia, t.valor_cliente, t.valor_cashback, t.status, t.data_transacao
                FROM transacoes_cashback t
                LEFT JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id 
                AND t.status IN ('pendente', 'pagamento_pendente')
                ORDER BY t.data_transacao DESC
            ");
            $debugStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $debugStmt->execute();
            
            while ($row = $debugStmt->fetch(PDO::FETCH_ASSOC)) {
                error_log("TRANSAÇÃO: ID={$row['id']}, Loja={$row['loja_id']} ({$row['nome_fantasia']}), Valor={$row['valor_cliente']}, Status={$row['status']}");
            }
            
            error_log("NENHUM SALDO PENDENTE ENCONTRADO PARA LOJA {$lojaId}");
            return 0.00;
            
        } catch (PDOException $e) {
            error_log('ERRO ao buscar saldo pendente: ' . $e->getMessage());
            return 0.00;
        }
    }
    /**
     * Mensagem para número inválido
     */
    private function gerarMensagemNumeroInvalido($usuario, $saldosLojas, $numeroInvalido) {
        $nome = explode(' ', $usuario['nome'])[0];
        $totalLojas = count($saldosLojas);
        
        $mensagem = "❌ *Opção Inválida*\n\n";
        $mensagem .= "Olá, *{$nome}*!\n\n";
        $mensagem .= "Você digitou *{$numeroInvalido}*, mas você só tem saldo em *{$totalLojas}* loja(s).\n\n";
        $mensagem .= "🏪 *Suas opções disponíveis:*\n\n";
        
        $contador = 1;
        foreach ($saldosLojas as $loja) {
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            $mensagem .= "{$contador}. *{$loja['nome_fantasia']}* - R$ {$saldo}\n";
            $contador++;
        }
        
        $mensagem .= "\n💡 *Digite um número válido (1 a {$totalLojas})* ou o nome da loja!";
        
        return $mensagem;
    }
    
    /**
     * Mensagem completa de saldo
     */
    private function gerarMensagemSaldoCompleto($usuario, $saldosLojas, $saldoTotal) {
        $nome = explode(' ', $usuario['nome'])[0];
        
        $mensagem = "💰 *Klube Cash - Seus Saldos*\n\n";
        $mensagem .= "Olá, *{$nome}*! 👋\n\n";
        $mensagem .= "💳 *Saldo Total:* R$ " . number_format($saldoTotal, 2, ',', '.') . "\n\n";
        $mensagem .= "🏪 *Suas carteiras por loja:*\n";
        
        $contador = 1;
        foreach ($saldosLojas as $loja) {
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            $mensagem .= "{$contador}. *{$loja['nome_fantasia']}*\n";
            $mensagem .= "   💰 R$ {$saldo}\n";
            $mensagem .= "   📊 {$loja['porcentagem_cashback']}% de cashback\n\n";
            $contador++;
        }
        
        $mensagem .= "📱 *Como usar:*\n";
        $mensagem .= "• Digite o *número* da loja para ver detalhes\n";
        $mensagem .= "• Digite o *nome* da loja\n";
        $mensagem .= "• Digite *saldo* para ver este resumo\n\n";
        $mensagem .= "💡 _Lembre-se: O saldo de cada loja só pode ser usado na própria loja!_";
        
        return $mensagem;
    }
    
    /**
     * Mensagem com todas as lojas
     */
    private function gerarMensagemTodasAsLojas($usuario, $saldosLojas) {
        $nome = explode(' ', $usuario['nome'])[0];
        
        $mensagem = "🏪 *Suas Lojas Disponíveis*\n\n";
        $mensagem .= "Olá, *{$nome}*!\n\n";
        $mensagem .= "Você tem saldo nas seguintes lojas:\n\n";
        
        $contador = 1;
        foreach ($saldosLojas as $loja) {
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            $mensagem .= "{$contador}. *{$loja['nome_fantasia']}* - R$ {$saldo}\n";
            $contador++;
        }
        
        $mensagem .= "\n💡 *Digite o número ou nome da loja* para ver detalhes específicos!";
        
        return $mensagem;
    }
    
    /**
     * Mensagem para usuário não encontrado
     */
    private function getMensagemUsuarioNaoEncontrado($telefone) {
        return "❌ *Usuário não encontrado*\n\n" .
               "O telefone *{$telefone}* não está cadastrado no Klube Cash.\n\n" .
               "📱 *Como se cadastrar:*\n" .
               "• Acesse: https://klubecash.com\n" .
               "• Clique em 'Cadastrar'\n" .
               "• Use este mesmo número de telefone\n\n" .
               "💬 *Precisa de ajuda?*\n" .
               "Entre em contato conosco!";
    }
    
    /**
     * Mensagem para usuário sem saldo
     */
    private function getMensagemSemSaldo($nomeUsuario) {
        $nome = explode(' ', $nomeUsuario)[0];
        
        return "👋 Olá, *{$nome}*!\n\n" .
               "💰 Você ainda não possui saldo de cashback.\n\n" .
               "🛍️ *Como ganhar cashback:*\n" .
               "• Faça compras nas lojas parceiras\n" .
               "• Informe seu CPF ou telefone\n" .
               "• Receba cashback automaticamente\n\n" .
               "🏪 *Ver lojas parceiras:*\n" .
               "https://klubecash.com/lojas\n\n" .
               "💡 _O cashback aparece aqui após a confirmação da loja._";
    }
}
?>