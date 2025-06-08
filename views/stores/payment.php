<?php
require_once '../../config/constants.php';
require_once '../../config/database.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL);
    exit;
}

$transactions = $_GET['transactions'] ?? '';
if (empty($transactions)) {
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

$transactionIds = explode(',', $transactions);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pagamento - Klube Cash</title>
</head>
<body>
    <h1>Página de Pagamento</h1>
    <p>Transações selecionadas: <?php echo count($transactionIds); ?></p>
    <pre><?php print_r($transactionIds); ?></pre>
</body>
</html>