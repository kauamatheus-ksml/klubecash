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
            error_log("=== INICIO consultarSaldoPorTelefone: {$telefone} ===");
            
            // Buscar usuário pelo telefone
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                error_log("ERRO: Usuário não encontrado para telefone: {$telefone}");
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            error_log("SUCCESS: Usuário encontrado: {$usuario['nome']} (ID: {$usuario['id']})");
            
            // Obter saldos do usuário
            $balanceModel = new CashbackBalance();
            $saldosLojas = $balanceModel->getAllUserBalances($usuario['id']);
            $saldoTotal = $balanceModel->getTotalBalance($usuario['id']);
            
            error_log("INFO: Total de lojas com saldo: " . count($saldosLojas));
            error_log("INFO: Saldo total: R$ {$saldoTotal}");
            
            if (empty($saldosLojas)) {
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            // Gerar mensagem completa
            $mensagem = $this->gerarMensagemSaldoCompleto($usuario, $saldosLojas, $saldoTotal);
            
            error_log("=== FIM consultarSaldoPorTelefone ===");
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'total_lojas' => count($saldosLojas),
                'saldo_total' => $saldoTotal
            ];
            
        } catch (Exception $e) {
            error_log('ERRO na consulta de saldo por telefone: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'message' => 'Ocorreu um erro interno. Tente novamente em alguns instantes.'
            ];
        }
    }
    
    /**
     * Consulta saldo específico por loja (VERSÃO FINAL CORRIGIDA)
     */
    public function consultarSaldoLoja($telefone, $identificacaoLoja) {
        try {
            error_log("=== INICIO consultarSaldoLoja ===");
            error_log("PARAMETROS: telefone={$telefone}, loja={$identificacaoLoja}");
            
            // 1. Buscar usuário pelo telefone
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                error_log("ERRO: Usuário não encontrado");
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            error_log("SUCCESS: Usuário encontrado: {$usuario['nome']} (ID: {$usuario['id']})");
            
            // 2. Obter todos os saldos do usuário
            $balanceModel = new CashbackBalance();
            $saldosLojas = $balanceModel->getAllUserBalances($usuario['id']);
            
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
            error_log('Stack trace: ' . $e->getTraceAsString());
            
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
     * Busca usuário pelo telefone (método mantido igual)
     */
    private function buscarUsuarioPorTelefone($telefone) {
        try {
            error_log("=== BUSCA DE TELEFONE ===");
            error_log("TELEFONE RECEBIDO: {$telefone}");
            
            // Limpar telefone (manter apenas números)
            $telefoneClean = preg_replace('/[^0-9]/', '', $telefone);
            error_log("TELEFONE LIMPO: {$telefoneClean}");
            
            // CRIAR TODAS AS VARIANTES POSSÍVEIS
            $searchVariants = [];
            
            // 1. Número original
            $searchVariants[] = $telefoneClean;
            
            // 2. Com DDI 55
            $searchVariants[] = '55' . $telefoneClean;
            
            // 3. Sem DDI (remover 55 do início se tiver)
            if (strlen($telefoneClean) >= 12 && substr($telefoneClean, 0, 2) === '55') {
                $searchVariants[] = substr($telefoneClean, 2);
            }
            
            // 4. CORREÇÃO ESPECÍFICA: Se tiver 12 dígitos e começar com 55, pode estar faltando um dígito
            if (strlen($telefoneClean) === 12 && substr($telefoneClean, 0, 2) === '55') {
                // 553891045205 -> extrair DDD e número
                $ddd = substr($telefoneClean, 2, 2); // 38
                $numero = substr($telefoneClean, 4);  // 91045205
                
                // Reconstruir com 9 adicional para celular
                $numeroComNove = $ddd . '9' . $numero; // 38991045205
                $searchVariants[] = $numeroComNove;
                
                error_log("CORREÇÃO CELULAR: {$telefoneClean} -> {$numeroComNove}");
            }
            
            // 5. Se tiver 13 dígitos, remover DDI
            if (strlen($telefoneClean) === 13 && substr($telefoneClean, 0, 2) === '55') {
                $searchVariants[] = substr($telefoneClean, 2);
            }
            
            // 6. Se tiver 11 dígitos, pode precisar adicionar DDI
            if (strlen($telefoneClean) === 11) {
                $searchVariants[] = '55' . $telefoneClean;
            }
            
            // 7. VARIANTE ESPECIAL para o caso específico do usuário
            // Se receber 553891045205, tentar 38991045205
            if ($telefoneClean === '553891045205') {
                $searchVariants[] = '38991045205';
            }
            
            // Remover duplicatas e logar
            $searchVariants = array_unique($searchVariants);
            error_log("VARIANTES PARA BUSCA: " . implode(', ', $searchVariants));
            
            // TENTAR BUSCAR COM CADA VARIANTE
            foreach ($searchVariants as $variant) {
                error_log("TESTANDO VARIANTE: {$variant}");
                
                $stmt = $this->db->prepare("
                    SELECT id, nome, email, telefone, status 
                    FROM usuarios 
                    WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') = :telefone
                    AND tipo = :tipo 
                    AND status = :status
                    LIMIT 1
                ");
                
                $stmt->bindParam(':telefone', $variant);
                $tipo = USER_TYPE_CLIENT;
                $stmt->bindParam(':tipo', $tipo);
                $status = USER_ACTIVE;
                $stmt->bindParam(':status', $status);
                
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    error_log("✅ SUCESSO! Usuário encontrado com variante: {$variant}");
                    error_log("USUÁRIO: {$result['nome']} - ID: {$result['id']}");
                    return $result;
                } else {
                    error_log("❌ Não encontrado com variante: {$variant}");
                }
            }
            
            // SE NÃO ENCONTROU, TENTAR BUSCA MAIS FLEXÍVEL
            error_log("TENTANDO BUSCA FLEXÍVEL COM LIKE...");
            
            // Pegar últimos 8 dígitos para busca flexível
            $ultimosDigitos = substr($telefoneClean, -8);
            error_log("ÚLTIMOS 8 DÍGITOS: {$ultimosDigitos}");
            
            $stmt = $this->db->prepare("
                SELECT id, nome, email, telefone, status 
                FROM usuarios 
                WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') LIKE :telefone_like
                AND tipo = :tipo 
                AND status = :status
                LIMIT 1
            ");
            
            $likePattern = '%' . $ultimosDigitos;
            $stmt->bindParam(':telefone_like', $likePattern);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("✅ SUCESSO COM LIKE! Usuário: {$result['nome']}");
                return $result;
            }
            
            error_log("❌ NENHUM USUÁRIO ENCONTRADO PARA: {$telefone}");
            
            // DEBUG: Mostrar todos os telefones cadastrados para comparação
            $allPhones = $this->db->query("SELECT telefone FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo'");
            error_log("=== TELEFONES CADASTRADOS NO SISTEMA ===");
            while ($phone = $allPhones->fetch(PDO::FETCH_ASSOC)) {
                $phoneClean = preg_replace('/[^0-9]/', '', $phone['telefone']);
                error_log("TELEFONE BD: {$phone['telefone']} -> LIMPO: {$phoneClean}");
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log('ERRO ao buscar usuário: ' . $e->getMessage());
            return null;
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
     * Gera resposta para loja específica (VERSÃO EXTRA ROBUSTA)
     */
    private function gerarRespostaLojaEspecifica($usuario, $loja) {
        try {
            error_log("=== GERANDO RESPOSTA LOJA ESPECÍFICA ===");
            error_log("Usuário: {$usuario['nome']}");
            error_log("Loja: {$loja['nome_fantasia']}");
            error_log("Saldo: R$ {$loja['saldo_disponivel']}");
            
            $nome = explode(' ', $usuario['nome'])[0];
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            
            $mensagem = "🏪 *{$loja['nome_fantasia']}*\n\n";
            $mensagem .= "👋 Olá, *{$nome}*!\n\n";
            $mensagem .= "💰 *Seu saldo:* R$ {$saldo}\n";
            $mensagem .= "📊 *Cashback:* {$loja['porcentagem_cashback']}%\n";
            $mensagem .= "📂 *Categoria:* " . ucfirst($loja['categoria'] ?? 'Geral') . "\n\n";
            
            $mensagem .= "✨ *Como usar seu saldo:*\n";
            $mensagem .= "• Vá até a loja\n";
            $mensagem .= "• Informe que quer usar o Klube Cash\n";
            $mensagem .= "• Apresente seu CPF ou telefone\n\n";
            
            $mensagem .= "💡 _Este saldo só pode ser usado nesta loja específica._\n\n";
            $mensagem .= "Digite *saldo* para ver todas suas carteiras.";
            
            error_log("SUCCESS: Mensagem gerada com sucesso");
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'send_image' => false // Simplificado por enquanto
            ];
            
        } catch (Exception $e) {
            error_log("ERRO ao gerar resposta específica: " . $e->getMessage());
            
            // Fallback ultra simples
            return [
                'success' => true,
                'user_found' => true,
                'message' => "✅ Loja selecionada!\n\nDigite *saldo* para ver todas suas opções novamente."
            ];
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