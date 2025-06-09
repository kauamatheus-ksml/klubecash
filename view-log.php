<?php
// view-log.php (criar na raiz)
if (file_exists('/tmp/openpix.log')) {
    echo "<pre>" . file_get_contents('/tmp/openpix.log') . "</pre>";
} else {
    echo "Log não encontrado";
}
?>