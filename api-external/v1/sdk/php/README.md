# KlubeCash PHP SDK

Official PHP SDK for integrating with the KlubeCash External API.

## 🚀 Installation

### Via Composer (Recommended)

```bash
composer require klubecash/php-sdk
```

### Manual Installation

Download the `KlubeCashSDK.php` file and include it in your project:

```php
require_once 'path/to/KlubeCashSDK.php';
```

## 📋 Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- Valid KlubeCash API Key

## 🔧 Quick Start

```php
<?php
use KlubeCash\KlubeCashSDK;

// Initialize the SDK
$sdk = new KlubeCashSDK('your_api_key_here');

// Get API information
$info = $sdk->getApiInfo();
echo "API Version: " . $info['data']['version'];

// List stores
$stores = $sdk->getStores();
foreach ($stores['data'] as $store) {
    echo $store['trade_name'] . "\n";
}

// Calculate cashback
$cashback = $sdk->calculateCashback(59, 100.00);
echo "Total cashback: R$ " . $cashback['data']['cashback_calculation']['total_cashback'];
?>
```

## 📖 Documentation

### Basic Usage

#### Initialize SDK

```php
use KlubeCash\KlubeCashSDK;

// Basic initialization
$sdk = new KlubeCashSDK('kc_live_your_api_key');

// With custom options
$sdk = new KlubeCashSDK('kc_live_your_api_key', [
    'timeout' => 60,           // Request timeout in seconds
    'debug' => true,           // Enable debug logging
    'cache_ttl' => 600,        // Cache TTL in seconds
    'base_url' => 'https://klubecash.com/api-external/v1'
]);
```

#### API Information

```php
// Get API info (cached by default)
$info = $sdk->getApiInfo();

// Force fresh request
$info = $sdk->getApiInfo(false);

// Check API health
$health = $sdk->checkHealth();
echo "Status: " . $health['data']['status'];
```

#### Working with Users

```php
// Get users list
$users = $sdk->getUsers();

foreach ($users['data'] as $user) {
    echo "User: {$user['name']} ({$user['email']})\n";
}
```

#### Working with Stores

```php
// Get all stores
$stores = $sdk->getStores();

// Get only approved stores
$approvedStores = $sdk->getApprovedStores();

// Get specific store
$store = $sdk->getStore(59);
if ($store) {
    echo "Store: {$store['trade_name']}";
}

// Check if store is approved
if ($sdk->isStoreApproved(59)) {
    echo "Store is approved for cashback";
}
```

#### Cashback Calculations

```php
// Simple cashback calculation
$result = $sdk->calculateCashback(59, 100.00);

$calc = $result['data']['cashback_calculation'];
echo "Total cashback: R$ {$calc['total_cashback']}\n";
echo "Client receives: R$ {$calc['client_receives']}\n";

// Bulk calculations
$calculations = [
    ['store_id' => 59, 'amount' => 100.00],
    ['store_id' => 38, 'amount' => 250.00],
    ['store_id' => 34, 'amount' => 50.00]
];

$results = $sdk->bulkCalculateCashback($calculations);
foreach ($results as $storeId => $result) {
    if (isset($result['data'])) {
        echo "Store $storeId: R$ {$result['data']['cashback_calculation']['total_cashback']}\n";
    }
}
```

### Advanced Features

#### Transaction Manager

```php
use KlubeCash\KlubeCashTransactionManager;

$transactionManager = new KlubeCashTransactionManager($sdk);

// Process a complete transaction
$result = $transactionManager->processTransaction(
    59,                           // Store ID
    250.00,                       // Purchase amount
    'customer@email.com',         // Customer email
    ['order_id' => 'ORD-12345']  // Additional metadata
);

if ($result['success']) {
    $transaction = $result['transaction'];
    echo "Transaction ID: {$transaction['id']}\n";
    echo "Cashback: R$ {$transaction['cashback_data']['cashback_calculation']['client_receives']}\n";
}

// Retrieve transaction later
$transaction = $transactionManager->getTransaction($result['transaction_id']);
```

#### Caching

```php
// Clear all cache
$sdk->clearCache();

// Clear cache with pattern
$sdk->clearCache('stores_*');

// Disable cache for specific request
$stores = $sdk->getStores(false); // false = no cache
```

#### Error Handling

```php
use KlubeCash\KlubeCashException;

try {
    $result = $sdk->calculateCashback(999, 100);
} catch (KlubeCashException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "HTTP Code: " . $e->getCode() . "\n";
    
    // Check error type
    if ($e->isAuthenticationError()) {
        echo "Authentication failed - check your API key";
    } elseif ($e->isRateLimitError()) {
        echo "Rate limit exceeded - wait before retrying";
    } elseif ($e->isClientError()) {
        echo "Client error - check your request";
    } elseif ($e->isServerError()) {
        echo "Server error - try again later";
    }
    
    // Get additional error data
    $errorData = $e->getErrorData();
    if ($errorData) {
        print_r($errorData);
    }
}
```

#### Custom Logging

```php
$sdk->setLogger(function($message, $level) {
    // Write to your custom log
    file_put_contents('klubecash.log', date('Y-m-d H:i:s') . " [$level] $message\n", FILE_APPEND);
});
```

#### Connection Testing

```php
$testResults = $sdk->testConnection();

if ($testResults['api_reachable']) {
    echo "✅ API is reachable\n";
    echo "Response time: " . round($testResults['response_time'] * 1000, 2) . "ms\n";
} else {
    echo "❌ Cannot reach API\n";
}

if ($testResults['authentication']) {
    echo "✅ Authentication successful\n";
} else {
    echo "❌ Authentication failed\n";
}

if (!empty($testResults['errors'])) {
    echo "Errors:\n";
    foreach ($testResults['errors'] as $error) {
        echo "  - $error\n";
    }
}
```

#### Statistics and Analytics

```php
$stats = $sdk->getCashbackStatistics();

echo "Total approved stores: {$stats['total_stores']}\n";
echo "Average cashback rate: {$stats['average_cashback_rate']}%\n";
echo "Min rate: {$stats['min_cashback_rate']}%\n";
echo "Max rate: {$stats['max_cashback_rate']}%\n";

foreach ($stats['stores_by_rate'] as $store) {
    echo "{$store['store_name']}: ~{$store['estimated_rate']}%\n";
}
```

#### Rate Limit Monitoring

```php
// Make some requests...
$stores = $sdk->getStores();
$users = $sdk->getUsers();

// Check rate limit info
$rateLimitInfo = $sdk->getRateLimitInfo();
echo "Requests remaining: {$rateLimitInfo['remaining']}/{$rateLimitInfo['limit']}\n";
echo "Reset time: " . date('Y-m-d H:i:s', $rateLimitInfo['reset_time']) . "\n";
```

## 🛠️ Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `base_url` | string | `https://klubecash.com/api-external/v1` | API base URL |
| `timeout` | int | `30` | Request timeout in seconds |
| `debug` | bool | `false` | Enable debug logging |
| `cache_ttl` | int | `300` | Cache time-to-live in seconds |
| `logger` | callable | `null` | Custom logger function |

## 🚨 Error Handling

The SDK throws `KlubeCashException` for API-related errors:

### Exception Methods

- `getMessage()` - Error message
- `getCode()` - HTTP status code
- `getErrorData()` - Additional error data from API
- `isAuthenticationError()` - Check if 401 error
- `isRateLimitError()` - Check if 429 error
- `isClientError()` - Check if 4xx error
- `isServerError()` - Check if 5xx error

### Common Error Codes

- `401` - Invalid or expired API Key
- `404` - Endpoint or resource not found
- `429` - Rate limit exceeded
- `500` - Internal server error

## 🎯 Best Practices

### 1. Use Caching Wisely

```php
// Cache long-lived data like store lists
$stores = $sdk->getStores(true);

// Don't cache real-time calculations
$cashback = $sdk->calculateCashback(59, 100); // No cache parameter = no cache
```

### 2. Handle Errors Gracefully

```php
try {
    $result = $sdk->calculateCashback($storeId, $amount);
    return $result['data'];
} catch (KlubeCashException $e) {
    // Log error
    error_log("KlubeCash API Error: " . $e->getMessage());
    
    // Return default or throw custom exception
    throw new YourAppException("Cashback calculation failed", 0, $e);
}
```

### 3. Monitor Rate Limits

```php
function makeRequestWithRetry($callable, $maxRetries = 3) {
    for ($i = 0; $i < $maxRetries; $i++) {
        try {
            return $callable();
        } catch (KlubeCashException $e) {
            if ($e->isRateLimitError() && $i < $maxRetries - 1) {
                sleep(pow(2, $i)); // Exponential backoff
                continue;
            }
            throw $e;
        }
    }
}

$result = makeRequestWithRetry(function() use ($sdk) {
    return $sdk->getStores();
});
```

### 4. Use Transaction Manager for Complex Operations

```php
$transactionManager = new KlubeCashTransactionManager($sdk);

// This handles validation, calculation, and logging
$result = $transactionManager->processTransaction($storeId, $amount, $customerEmail);
```

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Static analysis
composer phpstan

# Code style check
composer cs-check

# Fix code style
composer cs-fix
```

## 📞 Support

- **Documentation**: https://klubecash.com/api-external/v1/docs
- **Support Email**: suporte@klubecash.com
- **GitHub Issues**: https://github.com/klubecash/php-sdk/issues

## 📄 License

This SDK is released under the MIT License. See LICENSE file for details.

---

**KlubeCash PHP SDK v1.0.0** - Built with ❤️ by the KlubeCash team