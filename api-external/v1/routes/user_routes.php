<?php

// Rotas de usuários
$router->get('/', 'UserController@listUsers');
$router->get('/email', 'UserController@getUserByEmail');
$router->get('/{id}', 'UserController@getUser');
$router->post('/', 'UserController@createUser');
$router->put('/{id}', 'UserController@updateUser');
$router->delete('/{id}', 'UserController@deleteUser');
$router->get('/{id}/balance', 'UserController@getUserBalance');
$router->get('/{id}/transactions', 'UserController@getUserTransactions');
?>