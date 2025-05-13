<?php
header('Content-Type: application/json; charset=UTF-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success'=>false,'message'=>'Requisição inválida']);
  exit;
}

// Valida email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
  echo json_encode(['success'=>false,'message'=>'Email inválido']);
  exit;
}

// Arquivo JSON
$file = __DIR__ . '/emails.json';

// Lê array existente
$data = [];
if (file_exists($file)) {
  $raw = file_get_contents($file);
  $data = json_decode($raw, true) ?: [];
}

// Adiciona novo registro
$data[] = [
  'email'     => $email,
  'timestamp' => date('c')
];

// Salva de volta
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Retorna sucesso
echo json_encode(['success'=>true]);
