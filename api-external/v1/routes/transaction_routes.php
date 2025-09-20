<?php

// Rotas de transações
$router->get('/', 'TransactionController@listTransactions');
$router->get('/stats', 'TransactionController@getTransactionStats');
$router->get('/{id}', 'TransactionController@getTransaction');
$router->post('/', 'TransactionController@createTransaction');
$router->put('/{id}/status', 'TransactionController@updateTransactionStatus');
?>