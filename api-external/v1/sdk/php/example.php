<?php
/**
 * KlubeCash PHP SDK - Example Usage
 * 
 * This file demonstrates how to use the KlubeCash PHP SDK
 * with real-world examples and best practices.
 */

require_once 'KlubeCashSDK.php';

use KlubeCash\KlubeCashSDK;
use KlubeCash\KlubeCashException;
use KlubeCash\KlubeCashTransactionManager;

// Configuration
$apiKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';

echo "=== KLUBECASH PHP SDK - EXAMPLE USAGE ===\n\n";

try {
    // 1. Initialize SDK with custom options
    echo "1. Initializing SDK...\n";
    $sdk = new KlubeCashSDK($apiKey, [
        'debug' => true,
        'timeout' => 30,
        'cache_ttl' => 300,
        'logger' => function($message, $level) {
            echo "[LOG][$level] $message\n";
        }
    ]);
    echo "✅ SDK initialized successfully\n\n";

    // 2. Test connection
    echo "2. Testing API connection...\n";
    $connectionTest = $sdk->testConnection();
    
    if ($connectionTest['api_reachable']) {
        echo "✅ API is reachable (Response time: " . round($connectionTest['response_time'] * 1000, 2) . "ms)\n";
    } else {
        echo "❌ API is not reachable\n";
    }
    
    if ($connectionTest['authentication']) {
        echo "✅ Authentication successful\n";
    } else {
        echo "❌ Authentication failed\n";
    }
    
    if (!empty($connectionTest['errors'])) {
        echo "Errors found:\n";
        foreach ($connectionTest['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    echo "\n";

    // 3. Get API information
    echo "3. Getting API information...\n";
    $apiInfo = $sdk->getApiInfo();
    echo "API Name: {$apiInfo['data']['api_name']}\n";
    echo "Version: {$apiInfo['data']['version']}\n";
    echo "Base URL: {$apiInfo['data']['base_url']}\n";
    echo "Requires API Key: " . ($apiInfo['data']['requires_api_key'] ? 'Yes' : 'No') . "\n\n";

    // 4. Check API health
    echo "4. Checking API health...\n";
    $health = $sdk->checkHealth();
    echo "Status: {$health['data']['status']}\n";
    echo "Database: {$health['data']['database']}\n";
    echo "Timestamp: {$health['data']['timestamp']}\n\n";

    // 5. Work with users
    echo "5. Getting users...\n";
    $users = $sdk->getUsers();
    
    if (isset($users['data']) && !empty($users['data'])) {
        echo "Found " . count($users['data']) . " users:\n";
        foreach (array_slice($users['data'], 0, 3) as $user) {
            echo "  - {$user['name']} ({$user['email']}) - {$user['status']}\n";
        }
    } else {
        echo "No users found\n";
    }
    echo "\n";

    // 6. Work with stores
    echo "6. Working with stores...\n";
    $stores = $sdk->getStores();
    
    if (isset($stores['data']) && !empty($stores['data'])) {
        echo "Total stores: " . count($stores['data']) . "\n";
        
        // Get only approved stores
        $approvedStores = $sdk->getApprovedStores();
        echo "Approved stores: " . count($approvedStores) . "\n";
        
        // Display approved stores
        foreach ($approvedStores as $store) {
            echo "  - ID: {$store['id']} | {$store['trade_name']} | {$store['status']}\n";
        }
        
        // Test specific store
        $firstStoreId = $approvedStores[0]['id'] ?? 59;
        echo "\nTesting specific store (ID: $firstStoreId):\n";
        
        $specificStore = $sdk->getStore($firstStoreId);
        if ($specificStore) {
            echo "  Found: {$specificStore['trade_name']}\n";
            echo "  Status: {$specificStore['status']}\n";
            echo "  Is approved: " . ($sdk->isStoreApproved($firstStoreId) ? 'Yes' : 'No') . "\n";
        } else {
            echo "  Store not found\n";
        }
    } else {
        echo "No stores found\n";
    }
    echo "\n";

    // 7. Calculate cashback
    echo "7. Calculating cashback...\n";
    
    if (!empty($approvedStores)) {
        $testStoreId = $approvedStores[0]['id'];
        $testAmount = 100.00;
        
        echo "Store ID: $testStoreId\n";
        echo "Purchase amount: R$ " . number_format($testAmount, 2, ',', '.') . "\n";
        
        $cashbackResult = $sdk->calculateCashback($testStoreId, $testAmount);
        
        if (isset($cashbackResult['data'])) {
            $calc = $cashbackResult['data']['cashback_calculation'];
            echo "Store cashback rate: {$cashbackResult['data']['store_cashback_percentage']}%\n";
            echo "Total cashback: R$ " . number_format($calc['total_cashback'], 2, ',', '.') . "\n";
            echo "Client receives: R$ " . number_format($calc['client_receives'], 2, ',', '.') . "\n";
            echo "Admin receives: R$ " . number_format($calc['admin_receives'], 2, ',', '.') . "\n";
            echo "Store receives: R$ " . number_format($calc['store_receives'], 2, ',', '.') . "\n";
        }
    } else {
        echo "No approved stores available for cashback calculation\n";
    }
    echo "\n";

    // 8. Bulk cashback calculations
    echo "8. Bulk cashback calculations...\n";
    
    if (count($approvedStores) >= 2) {
        $bulkCalculations = [
            ['store_id' => $approvedStores[0]['id'], 'amount' => 100.00],
            ['store_id' => $approvedStores[1]['id'], 'amount' => 250.00]
        ];
        
        if (count($approvedStores) >= 3) {
            $bulkCalculations[] = ['store_id' => $approvedStores[2]['id'], 'amount' => 50.00];
        }
        
        $bulkResults = $sdk->bulkCalculateCashback($bulkCalculations);
        
        foreach ($bulkResults as $storeId => $result) {
            if (isset($result['data'])) {
                $calc = $result['data']['cashback_calculation'];
                echo "Store $storeId: R$ {$calc['total_cashback']} cashback\n";
            } else {
                echo "Store $storeId: Error - {$result['error']}\n";
            }
        }
    } else {
        echo "Not enough approved stores for bulk calculation\n";
    }
    echo "\n";

    // 9. Transaction Manager Example
    echo "9. Using Transaction Manager...\n";
    
    $transactionManager = new KlubeCashTransactionManager($sdk);
    
    if (!empty($approvedStores)) {
        $storeId = $approvedStores[0]['id'];
        $amount = 199.90;
        $customerEmail = 'customer@example.com';
        $metadata = [
            'order_id' => 'ORD-' . date('Ymd') . '-' . rand(1000, 9999),
            'payment_method' => 'credit_card',
            'customer_id' => 'CUST-12345'
        ];
        
        echo "Processing transaction:\n";
        echo "  Store ID: $storeId\n";
        echo "  Amount: R$ " . number_format($amount, 2, ',', '.') . "\n";
        echo "  Customer: $customerEmail\n";
        
        $transactionResult = $transactionManager->processTransaction($storeId, $amount, $customerEmail, $metadata);
        
        if ($transactionResult['success']) {
            $transaction = $transactionResult['transaction'];
            echo "✅ Transaction processed successfully!\n";
            echo "  Transaction ID: {$transaction['id']}\n";
            echo "  Created at: {$transaction['created_at']}\n";
            echo "  Customer cashback: R$ " . number_format($transaction['cashback_data']['cashback_calculation']['client_receives'], 2, ',', '.') . "\n";
            
            // Retrieve transaction later
            $retrievedTransaction = $transactionManager->getTransaction($transaction['id']);
            if ($retrievedTransaction) {
                echo "  ✅ Transaction successfully retrieved from storage\n";
            }
        } else {
            echo "❌ Transaction failed: {$transactionResult['error']}\n";
        }
    }
    echo "\n";

    // 10. Statistics and Analytics
    echo "10. Getting cashback statistics...\n";
    
    $stats = $sdk->getCashbackStatistics();
    echo "Total approved stores: {$stats['total_stores']}\n";
    echo "Average cashback rate: " . number_format($stats['average_cashback_rate'], 2) . "%\n";
    echo "Min cashback rate: {$stats['min_cashback_rate']}%\n";
    echo "Max cashback rate: {$stats['max_cashback_rate']}%\n";
    
    echo "\nStores by estimated rate:\n";
    foreach (array_slice($stats['stores_by_rate'], 0, 5) as $store) {
        echo "  - {$store['store_name']}: ~{$store['estimated_rate']}%\n";
    }
    echo "\n";

    // 11. Cache management
    echo "11. Cache management...\n";
    echo "Making multiple requests to test cache...\n";
    
    // First request (will hit API)
    $start = microtime(true);
    $stores1 = $sdk->getStores(true);
    $time1 = microtime(true) - $start;
    echo "First request: " . round($time1 * 1000, 2) . "ms\n";
    
    // Second request (should hit cache)
    $start = microtime(true);
    $stores2 = $sdk->getStores(true);
    $time2 = microtime(true) - $start;
    echo "Second request (cached): " . round($time2 * 1000, 2) . "ms\n";
    
    // Clear cache and make third request
    $sdk->clearCache();
    $start = microtime(true);
    $stores3 = $sdk->getStores(true);
    $time3 = microtime(true) - $start;
    echo "Third request (cache cleared): " . round($time3 * 1000, 2) . "ms\n\n";

    // 12. Rate limit information
    echo "12. Rate limit information...\n";
    $rateLimitInfo = $sdk->getRateLimitInfo();
    echo "Requests remaining: {$rateLimitInfo['remaining']}/{$rateLimitInfo['limit']}\n";
    echo "Reset time: " . date('Y-m-d H:i:s', $rateLimitInfo['reset_time']) . "\n\n";

    // 13. Error handling examples
    echo "13. Testing error handling...\n";
    
    // Test with invalid store ID
    try {
        echo "Testing invalid store ID...\n";
        $sdk->calculateCashback(99999, 100);
    } catch (KlubeCashException $e) {
        echo "✅ Caught expected error: {$e->getMessage()}\n";
        echo "   HTTP Code: {$e->getCode()}\n";
        echo "   Is client error: " . ($e->isClientError() ? 'Yes' : 'No') . "\n";
    }
    
    // Test with invalid amount
    try {
        echo "Testing invalid amount...\n";
        $sdk->calculateCashback(59, -10);
    } catch (Exception $e) {
        echo "✅ Caught expected error: {$e->getMessage()}\n";
    }
    echo "\n";

    echo "=== ALL EXAMPLES COMPLETED SUCCESSFULLY ===\n";

} catch (KlubeCashException $e) {
    echo "\n❌ KlubeCash API Error:\n";
    echo "Message: {$e->getMessage()}\n";
    echo "HTTP Code: {$e->getCode()}\n";
    
    if ($e->isAuthenticationError()) {
        echo "This is an authentication error. Please check your API key.\n";
    } elseif ($e->isRateLimitError()) {
        echo "Rate limit exceeded. Please wait before making more requests.\n";
    } elseif ($e->isClientError()) {
        echo "Client error. Please check your request parameters.\n";
    } elseif ($e->isServerError()) {
        echo "Server error. Please try again later.\n";
    }
    
    $errorData = $e->getErrorData();
    if ($errorData) {
        echo "Additional error data:\n";
        print_r($errorData);
    }
    
} catch (Exception $e) {
    echo "\n❌ General Error:\n";
    echo "Message: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getCode() !== 0) {
        echo "Code: {$e->getCode()}\n";
    }
}

echo "\n=== EXAMPLE SCRIPT FINISHED ===\n";

/**
 * Helper function to format currency
 */
function formatCurrency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

/**
 * Helper function to simulate a real-world integration scenario
 */
function simulateEcommerceCheckout(KlubeCashSDK $sdk, $storeId, $purchaseAmount, $customerEmail) {
    echo "=== E-COMMERCE CHECKOUT SIMULATION ===\n";
    echo "Store ID: $storeId\n";
    echo "Purchase Amount: " . formatCurrency($purchaseAmount) . "\n";
    echo "Customer: $customerEmail\n";
    
    try {
        // 1. Validate store
        if (!$sdk->isStoreApproved($storeId)) {
            throw new Exception("Store is not approved for cashback");
        }
        
        // 2. Calculate cashback
        $cashback = $sdk->calculateCashback($storeId, $purchaseAmount);
        $clientCashback = $cashback['data']['cashback_calculation']['client_receives'];
        
        // 3. Process transaction
        $transactionManager = new KlubeCashTransactionManager($sdk);
        $transaction = $transactionManager->processTransaction(
            $storeId, 
            $purchaseAmount, 
            $customerEmail,
            ['checkout_simulation' => true]
        );
        
        if ($transaction['success']) {
            echo "✅ Checkout completed successfully!\n";
            echo "Transaction ID: {$transaction['transaction']['id']}\n";
            echo "Customer will receive: " . formatCurrency($clientCashback) . " in cashback\n";
            return $transaction;
        } else {
            throw new Exception($transaction['error']);
        }
        
    } catch (Exception $e) {
        echo "❌ Checkout failed: {$e->getMessage()}\n";
        return false;
    }
}

// Run simulation if we have approved stores
if (!empty($approvedStores)) {
    echo "\n";
    simulateEcommerceCheckout($sdk, $approvedStores[0]['id'], 299.90, 'checkout@example.com');
}
?>