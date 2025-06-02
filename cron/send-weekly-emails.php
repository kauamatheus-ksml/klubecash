<?php
// cron/send-weekly-emails.php
// Este script deve ser executado via cron job toda semana

require_once '../config/database.php';
require_once '../config/email.php';

// Configurar para não ter limite de tempo (envios podem demorar)
set_time_limit(0);

// Log do processo
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    error_log("[$timestamp] Email Marketing: $message");
}

logMessage("Iniciando processo de envio de emails semanais");

try {
    $db = Database::getConnection();
    
    // Buscar campanhas agendadas para agora ou que já passaram da hora
    $stmt = $db->prepare("
        SELECT * FROM email_campaigns 
        WHERE status = 'agendado' 
        AND data_agendamento <= NOW()
        ORDER BY data_agendamento ASC
    ");
    $stmt->execute();
    $campanhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($campanhas)) {
        logMessage("Nenhuma campanha agendada para envio");
        exit;
    }
    
    foreach ($campanhas as $campanha) {
        logMessage("Processando campanha: " . $campanha['titulo']);
        
        // Marcar campanha como "enviando"
        $db->prepare("UPDATE email_campaigns SET status = 'enviando' WHERE id = ?")->execute([$campanha['id']]);
        
        // Buscar emails pendentes para esta campanha
        $emailsStmt = $db->prepare("
            SELECT email FROM email_envios 
            WHERE campaign_id = ? AND status = 'pendente'
            ORDER BY id ASC
        ");
        $emailsStmt->execute([$campanha['id']]);
        $emails = $emailsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $enviados = 0;
        $falharam = 0;
        
        foreach ($emails as $email) {
            logMessage("Enviando para: $email");
            
            // Tentar enviar o email
            $resultado = Email::send(
                $email,
                $campanha['assunto'],
                $campanha['conteudo_html']
            );
            
            if ($resultado) {
                // Marcar como enviado
                $db->prepare("
                    UPDATE email_envios 
                    SET status = 'enviado', data_envio = NOW() 
                    WHERE campaign_id = ? AND email = ?
                ")->execute([$campanha['id'], $email]);
                $enviados++;
                logMessage("✅ Enviado com sucesso para: $email");
            } else {
                // Marcar como falhou e incrementar tentativas
                $db->prepare("
                    UPDATE email_envios 
                    SET status = 'falhou', tentativas = tentativas + 1, erro_mensagem = ?
                    WHERE campaign_id = ? AND email = ?
                ")->execute(['Erro no envio', $campanha['id'], $email]);
                $falharam++;
                logMessage("❌ Falha ao enviar para: $email");
            }
            
            // Pausa pequena entre emails para não sobrecarregar o servidor
            usleep(100000); // 0.1 segundo
        }
        
        // Atualizar estatísticas da campanha
        $db->prepare("
            UPDATE email_campaigns 
            SET status = 'enviado', emails_enviados = ?, emails_falharam = ?
            WHERE id = ?
        ")->execute([$enviados, $falharam, $campanha['id']]);
        
        logMessage("Campanha '{$campanha['titulo']}' finalizada. Enviados: $enviados, Falharam: $falharam");
    }
    
    logMessage("Processo de envio finalizado com sucesso");
    
} catch (Exception $e) {
    logMessage("ERRO: " . $e->getMessage());
    error_log("Erro no envio de emails: " . $e->getMessage());
}
?>