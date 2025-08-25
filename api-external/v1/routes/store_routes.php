<?php

// Rotas de lojas/parceiros
$router->get('/', 'StoreController@listStores');
$router->get('/cnpj', 'StoreController@getStoreByCNPJ');
$router->get('/{id}', 'StoreController@getStore');
$router->post('/', 'StoreController@createStore');
$router->put('/{id}', 'StoreController@updateStore');
$router->delete('/{id}', 'StoreController@deleteStore');
$router->get('/{id}/stats', 'StoreController@getStoreStats');
$router->get('/{id}/transactions', 'StoreController@getStoreTransactions');
$router->get('/{id}/cashback-rules', 'StoreController@getCashbackRules');
?>