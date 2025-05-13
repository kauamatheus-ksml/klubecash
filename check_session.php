
<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'Não definido');
?>