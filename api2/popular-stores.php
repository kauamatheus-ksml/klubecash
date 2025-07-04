<?php
// public_html/api2/popular-stores.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$authenticatedUserId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
$simulatedAuthToken = 'seu_super_token_secreto_aqui_para_simulacao'; 
$simulatedUserId = 9; 

if ($token === $simulatedAuthToken) {
    $authenticatedUserId = $simulatedUserId;
}

if ($authenticatedUserId === null) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autorizado. Token ausente ou inválido.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

    try {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT
                 id,
                 nome_fantasia,
                 porcentagem_cashback,
                 logo
               FROM lojas
               WHERE status = 'aprovado'
               ORDER BY data_cadastro DESC
               LIMIT ?"
        );
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatar para float e ajustar URL do logo se necessário
        $formattedStores = array_map(function($s) {
            $s['porcentagem_cashback'] = (float)$s['porcentagem_cashback'];
            // Se 'logo' no DB for apenas o nome do arquivo, construa a URL completa aqui:
            // $s['logo'] = $s['logo'] ? SITE_URL . '/assets/images/logos/' . $s['logo'] : null;
            return $s;
        }, $stores);

        echo json_encode(['stores' => $formattedStores]);

    } catch (PDOException $e) {
        error_log('Erro em popular-stores.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Erro interno do servidor ao buscar lojas.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
}
?>