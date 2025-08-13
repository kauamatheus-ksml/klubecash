<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';  // ou USER_TYPE_STORE

// Simular POST
$_POST['action'] = 'create_visitor_client';
$_POST['nome'] = 'Teste Direto';
$_POST['telefone'] = '11999888777';
$_POST['store_id'] = 1;

// Chamar a API diretamente
include 'api/store-client-search.php';
?>