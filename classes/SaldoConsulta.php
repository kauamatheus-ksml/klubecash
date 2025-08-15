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
     * Consulta saldo específico por loja (MÉTODO CORRIGIDO)
     * Agora mostra todas as lojas onde o usuário tem saldo, não apenas uma
     * 
     * @param string $telefone Número do telefone
     * @param string $identificacaoLoja Número, nome ou palavra-chave da loja
     * @return array Resultado da consulta
     */
    public function consultarSaldoLoja($telefone, $identificacaoLoja) {
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
            
            // Obter todos os saldos do usuário
            $balanceModel = new CashbackBalance();
            $saldosLojas = $balanceModel->getAllUserBalances($usuario['id']);
            
            if (empty($saldosLojas)) {
                return [
                    'success' => true,
                    'user_found' => true,
                    'message' => $this->getMensagemSemSaldo($usuario['nome'])
                ];
            }
            
            // CORREÇÃO: Verificar se é consulta por número específico (1-9)
            if (is_numeric($identificacaoLoja) && $identificacaoLoja >= 1 && $identificacaoLoja <= count($saldosLojas)) {
                // Usuário escolheu uma loja específica pelo número
                $lojaSelecionada = $saldosLojas[$identificacaoLoja - 1]; // Array começa em 0
                return $this->gerarRespostaLojaEspecifica($usuario, $lojaSelecionada);
            }
            
            // CORREÇÃO: Buscar por nome da loja
            $lojaEncontrada = $this->buscarLojaPorNome($saldosLojas, $identificacaoLoja);
            
            if ($lojaEncontrada) {
                return $this->gerarRespostaLojaEspecifica($usuario, $lojaEncontrada);
            }
            
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
     * Busca usuário pelo número de telefone
     * 
     * @param string $telefone Telefone para buscar
     * @return array|null Dados do usuário ou null se não encontrado
     */
    private function buscarUsuarioPorTelefone($telefone) {
        try {
            // Limpar telefone (manter apenas números)
            $telefone = preg_replace('/[^0-9]/', '', $telefone);
            
            // Buscar usuário por telefone (com diferentes formatos)
            $stmt = $this->db->prepare("
                SELECT id, nome, email, telefone, status 
                FROM usuarios 
                WHERE (
                    REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone
                    OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone_with_55
                    OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone_without_55
                ) 
                AND tipo = :tipo 
                AND status = :status
            ");
            
            $stmt->bindParam(':telefone', $telefone);
            $telefoneWith55 = '55' . $telefone;
            $stmt->bindParam(':telefone_with_55', $telefoneWith55);
            $telefoneWithout55 = substr($telefone, 2); // Remove 55 se existir
            $stmt->bindParam(':telefone_without_55', $telefoneWithout55);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
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
     * Gera resposta para loja específica selecionada
     * 
     * @param array $usuario Dados do usuário
     * @param array $loja Dados da loja selecionada
     * @return array Resposta completa
     */
    private function gerarRespostaLojaEspecifica($usuario, $loja) {
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
        
        // Tentar gerar imagem específica da loja
        $imageResult = $this->tentarGerarImagemLoja($usuario, $loja);
        
        return [
            'success' => true,
            'user_found' => true,
            'message' => $mensagem,
            'send_image' => $imageResult['success'] ?? false,
            'image_url' => $imageResult['image_url'] ?? null
        ];
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