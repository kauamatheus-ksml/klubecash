<?php
// debug/whatsapp-debug.php
// Script para debug do WhatsApp - teste específico

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../classes/SaldoConsulta.php';
require_once __DIR__ . '/../config/constants.php';

class WhatsAppDebug {
    private $telefone = '34991191534';
    
    public function executarDebug() {
        echo "=== DEBUG WHATSAPP KLUBE CASH ===\n";
        echo "Telefone: {$this->telefone}\n";
        echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 1. Testar busca do usuário
        $this->testarBuscaUsuario();
        
        // 2. Testar API de saldo
        $this->testarAPISaldo();
        
        // 3. Testar determinação de tipo
        $this->testarTipoCliente();
        
        // 4. Testar menu que deveria aparecer
        $this->testarMenu();
    }
    
    private function testarBuscaUsuario() {
        echo "1. TESTANDO BUSCA DO USUÁRIO:\n";
        echo "--------------------------------\n";
        
        try {
            $saldoConsulta = new SaldoConsulta();
            $usuario = $saldoConsulta->buscarUsuarioPorTelefone($this->telefone);
            
            if ($usuario) {
                echo "✅ USUÁRIO ENCONTRADO:\n";
                echo "- ID: " . ($usuario['id'] ?? 'N/A') . "\n";
                echo "- Nome: " . ($usuario['nome'] ?? 'N/A') . "\n";
                echo "- Email: " . ($usuario['email'] ?? 'VAZIO') . "\n";
                echo "- Telefone: " . ($usuario['telefone'] ?? 'N/A') . "\n";
                echo "- Senha Hash: " . (empty($usuario['senha_hash']) ? 'VAZIO' : 'PREENCHIDO') . "\n";
                echo "- CPF: " . ($usuario['cpf'] ?? 'VAZIO') . "\n";
                echo "- Status: " . ($usuario['status'] ?? 'N/A') . "\n";
                echo "- Tipo: " . ($usuario['tipo'] ?? 'N/A') . "\n";
                echo "- Tipo Cliente: " . ($usuario['tipo_cliente'] ?? 'N/A') . "\n";
            } else {
                echo "❌ USUÁRIO NÃO ENCONTRADO\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testarAPISaldo() {
        echo "2. TESTANDO API DE SALDO:\n";
        echo "-------------------------\n";
        
        try {
            $saldoConsulta = new SaldoConsulta();
            $resultado = $saldoConsulta->consultarSaldoPorTelefone($this->telefone);
            
            echo "Resultado da consulta:\n";
            echo "- Success: " . ($resultado['success'] ? 'true' : 'false') . "\n";
            echo "- User Found: " . ($resultado['user_found'] ? 'true' : 'false') . "\n";
            echo "- Message: " . ($resultado['message'] ?? 'N/A') . "\n";
            
            if (isset($resultado['user_data'])) {
                echo "- User Data disponível: SIM\n";
                $userData = $resultado['user_data'];
                echo "  * Nome: " . ($userData['nome'] ?? 'N/A') . "\n";
                echo "  * Email: " . ($userData['email'] ?? 'VAZIO') . "\n";
            } else {
                echo "- User Data disponível: NÃO\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testarTipoCliente() {
        echo "3. TESTANDO DETERMINAÇÃO DO TIPO:\n";
        echo "---------------------------------\n";
        
        try {
            $saldoConsulta = new SaldoConsulta();
            $usuario = $saldoConsulta->buscarUsuarioPorTelefone($this->telefone);
            
            if ($usuario) {
                // Verificar se método exists
                if (method_exists($saldoConsulta, 'determinarTipoCliente')) {
                    $tipo = $saldoConsulta->determinarTipoCliente($usuario);
                    echo "✅ Tipo determinado: {$tipo}\n";
                } else {
                    echo "❌ Método determinarTipoCliente não existe\n";
                    
                    // Fazer determinação manual
                    if (!empty($usuario['email']) && !empty($usuario['senha_hash'])) {
                        $tipo = 'completo';
                    } else {
                        $tipo = 'visitante';
                    }
                    echo "✅ Tipo determinado manualmente: {$tipo}\n";
                }
                
                echo "\nAnálise dos campos:\n";
                echo "- Email preenchido: " . (!empty($usuario['email']) ? 'SIM' : 'NÃO') . "\n";
                echo "- Senha preenchida: " . (!empty($usuario['senha_hash']) ? 'SIM' : 'NÃO') . "\n";
                
            } else {
                echo "❌ Não foi possível determinar tipo - usuário não encontrado\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testarMenu() {
        echo "4. TESTANDO MENU QUE DEVERIA APARECER:\n";
        echo "-------------------------------------\n";
        
        try {
            $saldoConsulta = new SaldoConsulta();
            $usuario = $saldoConsulta->buscarUsuarioPorTelefone($this->telefone);
            
            if ($usuario) {
                // Determinar tipo
                if (!empty($usuario['email']) && !empty($usuario['senha_hash'])) {
                    $tipo = 'completo';
                } else {
                    $tipo = 'visitante';
                }
                
                echo "Tipo de cliente: {$tipo}\n\n";
                
                if ($tipo === 'visitante') {
                    echo "MENU PARA VISITANTE:\n";
                    echo "🏪 *Klube Cash* - Bem-vindo!\n\n";
                    echo "Digite o número da opção desejada:\n\n";
                    echo "1️⃣ Consultar Saldo\n";
                    echo "2️⃣ Completar Cadastro\n";
                } else {
                    echo "MENU PARA CLIENTE COMPLETO:\n";
                    echo "🏪 *Klube Cash* - Bem-vindo!\n\n";
                    echo "Digite o número da opção desejada:\n\n";
                    echo "1️⃣ Consultar Saldo\n";
                    echo "2️⃣ Atualizar Cadastro\n";
                }
                
            } else {
                echo "MENU PADRÃO (usuário não encontrado):\n";
                echo "🏪 *Klube Cash* - Bem-vindo!\n\n";
                echo "Digite o número da opção desejada:\n\n";
                echo "1️⃣ Consultar Saldo\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Executar debug
$debug = new WhatsAppDebug();
$debug->executarDebug();

echo "=== FIM DO DEBUG ===\n";
?>