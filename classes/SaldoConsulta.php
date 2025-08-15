<?php
// classes/SaldoConsulta.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../models/CashbackBalance.php';
require_once __DIR__ . '/ImageGenerator.php';

/**
 * Classe para Consulta de Saldo - Integração WhatsApp
 * 
 * Esta classe gerencia as consultas de saldo via WhatsApp Bot
 * Inclui consulta geral e consulta específica por loja
 */
class SaldoConsulta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Consulta saldo geral do usuário por telefone
     * Mostra resumo total e lista de lojas disponíveis
     * 
     * @param string $telefone Número do telefone (sem formatação)
     * @return array Resultado da consulta
     */
    public function consultarSaldoPorTelefone($telefone) {
        try {
            // Buscar usuário pelo telefone
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            // Obter saldos do usuário
            $balanceModel = new CashbackBalance();
            $saldosLojas = $balanceModel->getAllUserBalances($usuario['id']);
            $saldoTotal = $balanceModel->getTotalBalance($usuario['id']);
            
            if (empty($saldosLojas)) {
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            // Gerar mensagem completa
            $mensagem = $this->gerarMensagemSaldoCompleto($usuario, $saldosLojas, $saldoTotal);
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'total_lojas' => count($saldosLojas),
                'saldo_total' => $saldoTotal
            ];
            
        } catch (Exception $e) {
            error_log('Erro na consulta de saldo por telefone: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'message' => 'Ocorreu um erro interno. Tente novamente em alguns instantes.'
            ];
        }
    }
    
        /**
     * Consulta saldo específico por loja (MÉTODO CORRIGIDO - v2)
     * Corrige o problema de seleção por número
     * 
     * @param string $telefone Número do telefone
     * @param string $identificacaoLoja Número, nome ou palavra-chave da loja
     * @return array Resultado da consulta
     */
    public function consultarSaldoLoja($telefone, $identificacaoLoja) {
        try {
            error_log("DEBUG consultarSaldoLoja: telefone={$telefone}, loja={$identificacaoLoja}");
            
            // Buscar usuário pelo telefone
            $usuario = $this->buscarUsuarioPorTelefone($telefone);
            
            if (!$usuario) {
                error_log("DEBUG: Usuário não encontrado");
                return [
                    'success' => false,
                    'user_found' => false,
                    'message' => $this->getMensagemUsuarioNaoEncontrado($telefone)
                ];
            }
            
            error_log("DEBUG: Usuário encontrado: {$usuario['nome']} (ID: {$usuario['id']})");
            
            // Obter todos os saldos do usuário
            $balanceModel = new CashbackBalance();
            $saldosLojas = $balanceModel->getAllUserBalances($usuario['id']);
            
            error_log("DEBUG: Total de lojas com saldo: " . count($saldosLojas));
            
            if (empty($saldosLojas)) {
                error_log("DEBUG: Usuário sem saldo em nenhuma loja");
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            // LOG dos saldos para debug
            foreach ($saldosLojas as $index => $loja) {
                error_log("DEBUG: Loja[{$index}]: {$loja['nome_fantasia']} - R$ {$loja['saldo_disponivel']}");
            }
            
            // CORREÇÃO: Verificar se é consulta por número específico (1-9)
            if (is_numeric($identificacaoLoja)) {
                $numeroLoja = intval($identificacaoLoja);
                error_log("DEBUG: Seleção por número: {$numeroLoja}");
                
                // Verificar se o número está dentro do range válido
                if ($numeroLoja >= 1 && $numeroLoja <= count($saldosLojas)) {
                    $indiceLoja = $numeroLoja - 1; // Converter para índice do array (base 0)
                    $lojaSelecionada = $saldosLojas[$indiceLoja];
                    
                    error_log("DEBUG: Loja selecionada: {$lojaSelecionada['nome_fantasia']} (índice {$indiceLoja})");
                    
                    return $this->gerarRespostaLojaEspecifica($usuario, $lojaSelecionada);
                } else {
                    error_log("DEBUG: Número de loja inválido: {$numeroLoja} (máximo: " . count($saldosLojas) . ")");
                    
                    // Número inválido - mostrar opções disponíveis
                    return [
                        'success' => true,
                        'user_found' => true,
                        'message' => $this->gerarMensagemNumeroInvalido($usuario, $saldosLojas, $numeroLoja),
                        'send_image' => false
                    ];
                }
            }
            
            // CORREÇÃO: Buscar por nome da loja
            $lojaEncontrada = $this->buscarLojaPorNome($saldosLojas, $identificacaoLoja);
            
            if ($lojaEncontrada) {
                error_log("DEBUG: Loja encontrada por nome: {$lojaEncontrada['nome_fantasia']}");
                return $this->gerarRespostaLojaEspecifica($usuario, $lojaEncontrada);
            }
            
            error_log("DEBUG: Loja não encontrada por nome: {$identificacaoLoja}");
            
            // Se não encontrou loja específica, mostrar todas as opções
            return [
                'success' => true,
                'user_found' => true,
                'message' => $this->gerarMensagemTodasAsLojas($usuario, $saldosLojas),
                'send_image' => false
            ];
            
        } catch (Exception $e) {
            error_log('Erro na consulta de saldo por loja: ' . $e->getMessage());
            return [
                'success' => false,
                'user_found' => false,
                'message' => 'Ocorreu um erro interno. Tente novamente.'
            ];
        }
    }
    /**
     * Gera mensagem para número de loja inválido
     * 
     * @param array $usuario Dados do usuário
     * @param array $saldosLojas Lista de saldos por loja
     * @param int $numeroInvalido Número que foi digitado
     * @return string Mensagem formatada
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
     * Busca usuário pelo número de telefone
     * 
     * @param string $telefone Telefone para buscar
     * @return array|null Dados do usuário ou null se não encontrado
     */
    private function buscarUsuarioPorTelefone($telefone) {
        try {
            // Log para debug
            error_log("SaldoConsulta: Buscando telefone original: {$telefone}");
            
            // Limpar telefone (manter apenas números)
            $telefone = preg_replace('/[^0-9]/', '', $telefone);
            error_log("SaldoConsulta: Telefone limpo: {$telefone}");
            
            $db = Database::getConnection();
            
            // BUSCA MAIS ABRANGENTE - várias tentativas
            $searchVariants = [
                $telefone,                                    // Original
                '55' . $telefone,                            // Com DDI Brasil
                (strlen($telefone) > 10 && substr($telefone, 0, 2) === '55') ? substr($telefone, 2) : $telefone, // Sem DDI
                (strlen($telefone) === 13) ? substr($telefone, 2) : $telefone,  // Remove 55 se tiver 13 dígitos
                (strlen($telefone) === 12) ? substr($telefone, 1) : $telefone,  // Remove primeiro dígito se tiver 12
            ];
            
            // Remover duplicatas
            $searchVariants = array_unique($searchVariants);
            
            error_log("SaldoConsulta: Variantes para busca: " . implode(', ', $searchVariants));
            
            foreach ($searchVariants as $variant) {
                // Query mais simples e eficiente
                $stmt = $db->prepare("
                    SELECT id, nome, email, telefone, status 
                    FROM usuarios 
                    WHERE (
                        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') = :telefone
                    )
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
                    error_log("SaldoConsulta: Usuário encontrado com variante {$variant}: {$result['nome']}");
                    return $result;
                }
            }
            
            // Se não encontrou, fazer uma busca mais flexível usando LIKE
            $stmt = $db->prepare("
                SELECT id, nome, email, telefone, status 
                FROM usuarios 
                WHERE (
                    telefone LIKE :telefone_like_1
                    OR telefone LIKE :telefone_like_2
                    OR telefone LIKE :telefone_like_3
                )
                AND tipo = :tipo 
                AND status = :status
                LIMIT 1
            ");
            
            $like1 = '%' . substr($telefone, -8) . '%';  // Últimos 8 dígitos
            $like2 = '%' . substr($telefone, -9) . '%';  // Últimos 9 dígitos  
            $like3 = '%' . $telefone . '%';              // Telefone completo
            
            $stmt->bindParam(':telefone_like_1', $like1);
            $stmt->bindParam(':telefone_like_2', $like2);
            $stmt->bindParam(':telefone_like_3', $like3);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("SaldoConsulta: Usuário encontrado via LIKE: {$result['nome']}");
            } else {
                error_log("SaldoConsulta: Nenhum usuário encontrado para telefone: {$telefone}");
            }
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log('Erro ao buscar usuário por telefone: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca loja por nome dentro da lista de saldos
     * 
     * @param array $saldosLojas Lista de saldos por loja
     * @param string $nomeLoja Nome da loja para buscar
     * @return array|null Dados da loja encontrada
     */
    private function buscarLojaPorNome($saldosLojas, $nomeLoja) {
        $nomeLoja = strtolower(trim($nomeLoja));
        
        foreach ($saldosLojas as $loja) {
            $nomeLojaAtual = strtolower($loja['nome_fantasia']);
            
            // Busca exata
            if ($nomeLojaAtual === $nomeLoja) {
                return $loja;
            }
            
            // Busca parcial (contém)
            if (strpos($nomeLojaAtual, $nomeLoja) !== false) {
                return $loja;
            }
        }
        
        return null;
    }
    
    /**
     * Gera mensagem completa de saldo com todas as lojas
     * 
     * @param array $usuario Dados do usuário
     * @param array $saldosLojas Lista de saldos por loja
     * @param float $saldoTotal Saldo total
     * @return string Mensagem formatada
     */
    private function gerarMensagemSaldoCompleto($usuario, $saldosLojas, $saldoTotal) {
        $nome = explode(' ', $usuario['nome'])[0]; // Primeiro nome
        
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
     * Gera mensagem com todas as lojas disponíveis (quando não encontra loja específica)
     * 
     * @param array $usuario Dados do usuário
     * @param array $saldosLojas Lista de saldos por loja
     * @return string Mensagem formatada
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
        
        $mensagem .= "\n💡 *Digite o número ou nome da loja* para ver detalhes específicos!\n\n";
        $mensagem .= "Exemplo: Digite *1* ou *{$saldosLojas[0]['nome_fantasia']}*";
        
        return $mensagem;
    }
    
    /**
     * Gera resposta para loja específica selecionada (MÉTODO CORRIGIDO)
     * 
     * @param array $usuario Dados do usuário
     * @param array $loja Dados da loja selecionada
     * @return array Resposta completa
     */
    private function gerarRespostaLojaEspecifica($usuario, $loja) {
        try {
            $nome = explode(' ', $usuario['nome'])[0];
            $saldo = number_format($loja['saldo_disponivel'], 2, ',', '.');
            
            error_log("DEBUG gerarRespostaLojaEspecifica: {$loja['nome_fantasia']} - R$ {$saldo}");
            
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
            
            // Tentar gerar imagem específica da loja
            $imageResult = $this->tentarGerarImagemLoja($usuario, $loja);
            
            return [
                'success' => true,
                'user_found' => true,
                'message' => $mensagem,
                'send_image' => $imageResult['success'] ?? false,
                'image_url' => $imageResult['image_url'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Erro em gerarRespostaLojaEspecifica: " . $e->getMessage());
            
            // Fallback simples em caso de erro
            return [
                'success' => true,
                'user_found' => true,
                'message' => "Erro ao gerar detalhes da loja. Digite *saldo* para ver suas opções.",
                'send_image' => false
            ];
        }
    }

    
    /**
     * Tenta gerar imagem específica para a loja
     * 
     * @param array $usuario Dados do usuário
     * @param array $loja Dados da loja
     * @return array Resultado da geração de imagem
     */
    private function tentarGerarImagemLoja($usuario, $loja) {
        try {
            // Preparar dados para geração de imagem
            $dadosLoja = [
                'nome' => $loja['nome_fantasia'],
                'saldo' => $loja['saldo_disponivel'],
                'porcentagem' => $loja['porcentagem_cashback'],
                'categoria' => $loja['categoria'] ?? 'Geral'
            ];
            
            return ImageGenerator::gerarImagemSaldoLoja($usuario, $dadosLoja);
            
        } catch (Exception $e) {
            error_log('Erro ao gerar imagem da loja: ' . $e->getMessage());
            return ['success' => false];
        }
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