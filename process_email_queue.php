<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/utils/Email.php';

echo "Iniciando processamento da fila de emails...\n";

$db = Database::getConnection();

// Buscar emails pendentes
$stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10");
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($emails)) {
    echo "Nenhum email na fila.\n";
    exit;
}

foreach ($emails as $email) {
    echo "Processando email ID: {$email['id']} para {$email['to_email']}...\n";

    // Marcar como enviando
    $updateStmt = $db->prepare("UPDATE email_queue SET status = 'sending', last_attempt = NOW(), attempts = attempts + 1 WHERE id = :id");
    $updateStmt->bindParam(':id', $email['id']);
    $updateStmt->execute();

    // Enviar email
    $result = Email::send($email['to_email'], $email['subject'], $email['message'], $email['to_name']);

    // Atualizar status
    $newStatus = $result ? 'sent' : 'failed';
    $updateStatusStmt = $db->prepare("UPDATE email_queue SET status = :status WHERE id = :id");
    $updateStatusStmt->bindParam(':status', $newStatus);
    $updateStatusStmt->bindParam(':id', $email['id']);
    $updateStatusStmt->execute();

    if ($result) {
        echo "Email enviado com sucesso!\n";
    } else {
        echo "Falha ao enviar email.\n";
    }
}

echo "Processamento da fila de emails concluído.\n";
?>
