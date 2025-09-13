<?php

// Rotas de cashback
$router->post('/calculate', 'CashbackController@calculateCashback');
$router->get('/user/{user_id}', 'CashbackController@getUserCashback');
?>