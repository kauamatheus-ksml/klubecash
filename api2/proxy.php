<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$url = 'http://klubecash.com/api2/notifications.php?' . $_SERVER['QUERY_STRING'];
$data = file_get_contents($url);
echo $data;
?>