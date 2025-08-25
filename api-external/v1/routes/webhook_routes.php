<?php

// Rotas de webhooks (implementação básica)
$router->get('/', function() {
    Response::success([
        'message' => 'Webhook system - coming soon',
        'available_events' => [
            'transaction.created',
            'transaction.approved',
            'transaction.cancelled',
            'user.created',
            'store.approved'
        ]
    ]);
});
?>