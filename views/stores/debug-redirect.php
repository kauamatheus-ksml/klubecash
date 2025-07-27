<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>🔍 DEBUG REDIRECT - Funcionário</h1>";

echo "<h2>URL Atual:</h2>";
echo "<p>" . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Headers enviados:</h2>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

echo "<h2>Teste direto do dashboard:</h2>";
echo "<a href='/store/dashboard/'>Acessar Dashboard Direto</a>";
?>