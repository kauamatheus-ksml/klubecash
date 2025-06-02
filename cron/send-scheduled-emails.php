<?php
/**
 * Script de Envio Automático de Emails - Klube Cash
 * 
 * Este script é executado automaticamente pelo cron job do servidor.
 * Ele verifica campanhas agendadas e executa o envio de forma controlada.
 * 
 * Funciona como um "assistente digital" que trabalha nos bastidores,
 * enviando emails no horário certo sem você precisar se preocupar.
 */

// Configurar para não ter limite de tempo (envios podem demorar)
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../utils/Email.php';

/**
 * Função para registrar logs detalhados
 * Como um diário que registra tudo que acontece durante o envio
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    
    // Exibir no console (se executado via linha de comando)
    echo $logEntry;
    
    // Salvar em arquivo de log
    $logFile = '../logs/email-marketing-' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Também registrar no log de erro do PHP para casos críticos
    if ($level === 'ERROR') {
        error_log("Email Marketing - $message");
    }
}

logMessage("=== Iniciando processo de envio de emails agendados ===");

try {
    $db = Database::getConnection();
    
    // Buscar campanhas que estão prontas para envio
    // (agendadas para agora ou que já passaram da hora)
    $stmt = $db->prepare("
        SELECT * FROM email_campaigns 
        WHERE status = 'agendado' 
        AND data_agendamento <= NOW()
        ORDER BY data_agendamento ASC
    ");
    $stmt->execute();
    $campanhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($campanhas)) {
        logMessage("Nenhuma campanha agendada para envio neste momento");
        exit(0);
    }
    
    logMessage("Encontradas " . count($campanhas) . " campanha(s) para processar");
    
    // Processar cada campanha encontrada
    foreach ($campanhas as $campanha) {
        logMessage("Iniciando processamento da campanha: '{$campanha['titulo']}'");
        
        // Marcar campanha como "enviando" para evitar processamento duplo
        $db->prepare("UPDATE email_campaigns SET status = 'enviando' WHERE id = ?")->execute([$campanha['id']]);
        
        // Buscar emails pendentes para esta campanha
        $emailsStmt = $db->prepare("
            SELECT email FROM email_envios 
            WHERE campaign_id = ? AND status = 'pendente'
            ORDER BY id ASC
        ");
        $emailsStmt->execute([$campanha['id']]);
        $emails = $emailsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($emails)) {
            logMessage("Nenhum email pendente para a campanha {$campanha['id']}", 'WARNING');
            continue;
        }
        
        logMessage("Enviando para " . count($emails) . " destinatários");
        
        $enviados = 0;
        $falharam = 0;
        $totalEmails = count($emails);
        
        // Processar emails em lotes para não sobrecarregar o servidor
        $loteSize = defined('EMAIL_BATCH_SIZE') ? EMAIL_BATCH_SIZE : 50;
        $delay = defined('EMAIL_SEND_DELAY') ? EMAIL_SEND_DELAY : 100000; // microssegundos
        
        for ($i = 0; $i < $totalEmails; $i++) {
            $email = $emails[$i];
            
            logMessage("Enviando para: $email (" . ($i + 1) . "/$totalEmails)");
            
            // Tentar enviar o email
            $resultado = Email::send(
                $email,
                $campanha['assunto'],
                $campanha['conteudo_html'],
                '', // nome do destinatário (vazio para newsletter)
                [] // sem anexos
            );
            
            if ($resultado) {
                // Marcar como enviado com sucesso
                $stmt = $db->prepare("
                    UPDATE email_envios 
                    SET status = 'enviado', data_envio = NOW() 
                    WHERE campaign_id = ? AND email = ?
                ");
                $stmt->execute([$campanha['id'], $email]);
                $enviados++;
                
                logMessage("✅ Enviado com sucesso para: $email");
            } else {
                // Marcar como falhou e incrementar tentativas
                $stmt = $db->prepare("
                    UPDATE email_envios 
                    SET status = 'falhou', tentativas = tentativas + 1, erro_mensagem = ?
                    WHERE campaign_id = ? AND email = ?
                ");
                $stmt->execute(['Falha no envio via SMTP', $campanha['id'], $email]);
                $falharam++;
                
                logMessage("❌ Falha ao enviar para: $email", 'WARNING');
            }
            
            // Pausa entre emails para não ser interpretado como spam
            usleep($delay);
            
            // A cada lote, fazer uma pausa maior e atualizar estatísticas
            if (($i + 1) % $loteSize === 0) {
                logMessage("Lote de $loteSize emails processado. Pausa de 5 segundos...");
                sleep(5);
                
                // Atualizar estatísticas parciais
                $db->prepare("
                    UPDATE email_campaigns 
                    SET emails_enviados = ?, emails_falharam = ?
                    WHERE id = ?
                ")->execute([$enviados, $falharam, $campanha['id']]);
            }
        }
        
        // Atualizar estatísticas finais da campanha
        $db->prepare("
            UPDATE email_campaigns 
            SET status = 'enviado', emails_enviados = ?, emails_falharam = ?
            WHERE id = ?
        ")->execute([$enviados, $falharam, $campanha['id']]);
        
        logMessage("Campanha '{$campanha['titulo']}' finalizada:");
        logMessage("  ✅ Enviados com sucesso: $enviados");
        logMessage("  ❌ Falharam: $falharam");
        logMessage("  📊 Taxa de sucesso: " . round(($enviados / $totalEmails) * 100) . "%");
        
        // Se houver muitas falhas, registrar como alerta
        if ($falharam > ($totalEmails * 0.1)) { // Mais de 10% de falhas
            logMessage("ATENÇÃO: Taxa de falhas acima do normal para a campanha {$campanha['id']}", 'WARNING');
        }
    }
    
    logMessage("=== Processo de envio finalizado com sucesso ===");
    
} catch (Exception $e) {
    logMessage("ERRO CRÍTICO: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    
    // Em caso de erro, tentar resetar campanhas que ficaram "travadas" em "enviando"
    try {
        $db = Database::getConnection();
        $db->query("
            UPDATE email_campaigns 
            SET status = 'agendado' 
            WHERE status = 'enviando' 
            AND data_agendamento > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        logMessage("Campanhas 'travadas' foram resetadas para reprocessamento");
    } catch (Exception $resetError) {
        logMessage("Erro ao resetar campanhas: " . $resetError->getMessage(), 'ERROR');
    }
    
    exit(1); // Código de erro para o cron job
}
?>