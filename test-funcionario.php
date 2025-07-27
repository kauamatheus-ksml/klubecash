<?php
session_start();
if ($_SESSION['user_type'] === 'funcionario' && $_SESSION['store_id']) {
    echo "✅ FUNCIONÁRIO OK - Store: " . $_SESSION['store_id'];
    echo "<br><a href='/views/stores/dashboard.php'>Dashboard direto</a>";
} else {
    echo "❌ Sessão inválida";
}
?>