<?php
require_once 'config/database.php';

$db = Database::getConnection();
$stmt = $db->query('SELECT nome, email, senat FROM usuarios WHERE senat = "Sim" LIMIT 1');
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo 'Usuario SEST SENAT: ' . $user['nome'] . ' (' . $user['email'] . ')' . PHP_EOL;
} else {
    echo 'Nenhum usuario SEST SENAT encontrado' . PHP_EOL;
}
?>