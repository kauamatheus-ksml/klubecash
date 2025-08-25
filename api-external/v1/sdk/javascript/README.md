# KlubeCash JavaScript SDK

Official JavaScript/Node.js SDK for integrating with the KlubeCash External API. Works in both browser and Node.js environments.

## 🚀 Installation

### Via npm

```bash
npm install @klubecash/javascript-sdk
```

### Via yarn

```bash
yarn add @klubecash/javascript-sdk
```

### Via CDN (Browser)

```html
<script src="https://unpkg.com/@klubecash/javascript-sdk@latest/klubecash-sdk.js"></script>
```

## 📋 Requirements

- **Node.js**: 14.0.0 or higher
- **Browser**: Modern browsers (ES2020 support)
- Valid KlubeCash API Key

## 🔧 Quick Start

### Node.js / ES Modules

```javascript
import { KlubeCashSDK } from '@klubecash/javascript-sdk';

// Initialize the SDK
const sdk = new KlubeCashSDK('your_api_key_here');

// Get API information
const info = await sdk.getApiInfo();
console.log('API Version:', info.data.version);

// List stores
const stores = await sdk.getStores();
stores.data.forEach(store => {
    console.log(store.trade_name);
});

// Calculate cashback
const cashback = await sdk.calculateCashback(59, 100.00);
console.log('Total cashback:', cashback.data.cashback_calculation.total_cashback);
```

### CommonJS (Node.js)

```javascript
const { KlubeCashSDK } = require('@klubecash/javascript-sdk');

const sdk = new KlubeCashSDK('your_api_key_here');

sdk.getStores().then(stores => {
    console.log('Found', stores.data.length, 'stores');
});
```

### Browser

```html
<!DOCTYPE html>
<html>
<head>
    <title>KlubeCash SDK Example</title>
    <script src="https://unpkg.com/@klubecash/javascript-sdk@latest/klubecash-sdk.js"></script>
</head>
<body>
    <script>
        const sdk = new KlubeCash.KlubeCashSDK('your_api_key_here');
        
        sdk.getStores().then(stores => {
            console.log('Found', stores.data.length, 'stores');
        });
    </script>
</body>
</html>
```

## 📖 Documentation

### Basic Usage

#### Initialize SDK

```javascript
import { KlubeCashSDK } from '@klubecash/javascript-sdk';

// Basic initialization
const sdk = new KlubeCashSDK('kc_live_your_api_key');

// With custom options
const sdk = new KlubeCashSDK('kc_live_your_api_key', {
    timeout: 60000,          // Request timeout in milliseconds
    debug: true,             // Enable debug logging
    cacheTtl: 600000,        // Cache TTL in milliseconds
    baseUrl: 'https://klubecash.com/api-external/v1'
});
```

#### API Information

```javascript
// Get API info (cached by default)
const info = await sdk.getApiInfo();

// Force fresh request
const info = await sdk.getApiInfo(false);

// Check API health
const health = await sdk.checkHealth();
console.log('Status:', health.data.status);
```

#### Working with Users

```javascript
// Get users list
const users = await sdk.getUsers();

users.data.forEach(user => {
    console.log(`User: ${user.name} (${user.email})`);
});
```

#### Working with Stores

```javascript
// Get all stores
const stores = await sdk.getStores();

// Get only approved stores
const approvedStores = await sdk.getApprovedStores();

// Get specific store
const store = await sdk.getStore(59);
if (store) {
    console.log(`Store: ${store.trade_name}`);
}

// Check if store is approved
if (await sdk.isStoreApproved(59)) {
    console.log('Store is approved for cashback');
}
```

#### Cashback Calculations

```javascript
// Simple cashback calculation
const result = await sdk.calculateCashback(59, 100.00);

const calc = result.data.cashback_calculation;
console.log(`Total cashback: ${calc.total_cashback}`);
console.log(`Client receives: ${calc.client_receives}`);

// Bulk calculations
const calculations = [
    { store_id: 59, amount: 100.00 },
    { store_id: 38, amount: 250.00 },
    { store_id: 34, amount: 50.00 }
];

const results = await sdk.bulkCalculateCashback(calculations);
Object.entries(results).forEach(([storeId, result]) => {
    if (result.data) {
        console.log(`Store ${storeId}: ${result.data.cashback_calculation.total_cashback}`);
    }
});
```

### Advanced Features

#### Transaction Manager

```javascript
import { KlubeCashTransactionManager } from '@klubecash/javascript-sdk';

const transactionManager = new KlubeCashTransactionManager(sdk);

// Process a complete transaction
const result = await transactionManager.processTransaction(
    59,                           // Store ID
    250.00,                       // Purchase amount
    'customer@email.com',         // Customer email
    { order_id: 'ORD-12345' }    // Additional metadata
);

if (result.success) {
    const transaction = result.transaction;
    console.log(`Transaction ID: ${transaction.id}`);
    console.log(`Cashback: ${transaction.cashback_data.cashback_calculation.client_receives}`);
}

// Retrieve transaction later
const transaction = transactionManager.getTransaction(result.transaction_id);
```

#### Error Handling

```javascript
import { KlubeCashException } from '@klubecash/javascript-sdk';

try {
    const result = await sdk.calculateCashback(999, 100);
} catch (error) {
    if (error instanceof KlubeCashException) {
        console.log('Error:', error.message);
        console.log('HTTP Code:', error.statusCode);
        
        // Check error type
        if (error.isAuthenticationError()) {
            console.log('Authentication failed - check your API key');
        } else if (error.isRateLimitError()) {
            console.log('Rate limit exceeded - wait before retrying');
        } else if (error.isClientError()) {
            console.log('Client error - check your request');
        } else if (error.isServerError()) {
            console.log('Server error - try again later');
        }
        
        // Get additional error data
        const errorData = error.getErrorData();
        if (errorData) {
            console.log('Error data:', errorData);
        }
    } else {
        console.log('Unexpected error:', error.message);
    }
}
```

#### Custom Logging

```javascript
sdk.setLogger((message, level) => {
    // Write to your custom log
    console.log(`[${level.toUpperCase()}] ${message}`);
});
```

#### Connection Testing

```javascript
const testResults = await sdk.testConnection();

if (testResults.api_reachable) {
    console.log('✅ API is reachable');
    console.log(`Response time: ${testResults.response_time}ms`);
} else {
    console.log('❌ Cannot reach API');
}

if (testResults.authentication) {
    console.log('✅ Authentication successful');
} else {
    console.log('❌ Authentication failed');
}

if (testResults.errors.length > 0) {
    console.log('Errors:');
    testResults.errors.forEach(error => {
        console.log(`  - ${error}`);
    });
}
```

#### Statistics and Analytics

```javascript
const stats = await sdk.getCashbackStatistics();

console.log(`Total approved stores: ${stats.total_stores}`);
console.log(`Average cashback rate: ${stats.average_cashback_rate}%`);
console.log(`Min rate: ${stats.min_cashback_rate}%`);
console.log(`Max rate: ${stats.max_cashback_rate}%`);

stats.stores_by_rate.forEach(store => {
    console.log(`${store.store_name}: ~${store.estimated_rate}%`);
});
```

#### Cache Management

```javascript
// Clear all cache
sdk.clearCache();

// Clear cache with pattern
sdk.clearCache('stores_*');

// Disable cache for specific request
const stores = await sdk.getStores(false); // false = no cache
```

#### Rate Limit Monitoring

```javascript
// Make some requests...
const stores = await sdk.getStores();
const users = await sdk.getUsers();

// Check rate limit info
const rateLimitInfo = sdk.getRateLimitInfo();
console.log(`Requests remaining: ${rateLimitInfo.remaining}/${rateLimitInfo.limit}`);
console.log(`Reset time: ${new Date(rateLimitInfo.resetTime)}`);
```

### React Integration

```javascript
import { KlubeCashReactHook } from '@klubecash/javascript-sdk';

function useKlubeCash(apiKey) {
    const hook = new KlubeCashReactHook(apiKey);
    return hook.useKlubeCashStores();
}

// In your React component
function StoreList() {
    const klubeCash = useKlubeCash('your_api_key');
    const [stores, setStores] = useState([]);
    
    useEffect(() => {
        klubeCash.fetchStores()
            .then(setStores)
            .catch(console.error);
    }, []);
    
    return (
        <div>
            {stores.map(store => (
                <div key={store.id}>{store.trade_name}</div>
            ))}
        </div>
    );
}
```

### Utility Functions

```javascript
import { Utils } from '@klubecash/javascript-sdk';

// Format currency
const formatted = Utils.formatCurrency(123.45); // "R$ 123,45"

// Validate email
const isValid = Utils.validateEmail('user@example.com'); // true

// Generate test email
const testEmail = Utils.generateTestEmail(); // "test_1642782956789@klubecash.com"

// Retry with exponential backoff
const result = await Utils.retry(async () => {
    return await sdk.getStores();
}, 3, 1000);
```

## 🛠️ Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `baseUrl` | string | `https://klubecash.com/api-external/v1` | API base URL |
| `timeout` | number | `30000` | Request timeout in milliseconds |
| `debug` | boolean | `false` | Enable debug logging |
| `cacheTtl` | number | `300000` | Cache time-to-live in milliseconds |
| `logger` | function | `null` | Custom logger function |

## 🚨 Error Handling

The SDK throws `KlubeCashException` for API-related errors:

### Exception Methods

- `message` - Error message
- `statusCode` - HTTP status code
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

### 1. Use Async/Await

```javascript
// ✅ Good
try {
    const stores = await sdk.getStores();
    const cashback = await sdk.calculateCashback(stores.data[0].id, 100);
} catch (error) {
    console.error('Error:', error.message);
}

// ❌ Avoid
sdk.getStores().then(stores => {
    sdk.calculateCashback(stores.data[0].id, 100).then(cashback => {
        // Callback hell
    });
});
```

### 2. Handle Errors Gracefully

```javascript
async function safeCalculateCashback(storeId, amount) {
    try {
        const result = await sdk.calculateCashback(storeId, amount);
        return result.data;
    } catch (error) {
        if (error instanceof KlubeCashException) {
            console.error('KlubeCash API Error:', error.message);
            
            if (error.isRateLimitError()) {
                // Wait and retry
                await new Promise(resolve => setTimeout(resolve, 5000));
                return safeCalculateCashback(storeId, amount);
            }
        }
        throw new Error('Cashback calculation failed');
    }
}
```

### 3. Use Caching Wisely

```javascript
// Cache long-lived data like store lists
const stores = await sdk.getStores(true);

// Don't cache real-time calculations
const cashback = await sdk.calculateCashback(59, 100); // No cache parameter = no cache
```

### 4. Monitor Rate Limits

```javascript
async function makeRequestWithRetry(requestFn, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await requestFn();
        } catch (error) {
            if (error.isRateLimitError() && i < maxRetries - 1) {
                const delay = Math.pow(2, i) * 1000; // Exponential backoff
                await new Promise(resolve => setTimeout(resolve, delay));
                continue;
            }
            throw error;
        }
    }
}

const result = await makeRequestWithRetry(() => sdk.getStores());
```

### 5. Use Transaction Manager for Complex Operations

```javascript
const transactionManager = new KlubeCashTransactionManager(sdk);

// This handles validation, calculation, and logging
const result = await transactionManager.processTransaction(storeId, amount, customerEmail);
```

## 🌐 Browser Compatibility

The SDK is compatible with modern browsers that support:

- ES2020 features
- Fetch API
- Promises/async-await
- ES6 Classes

For older browsers, you may need polyfills for:
- `fetch` (use `whatwg-fetch`)
- `AbortController` (use `abortcontroller-polyfill`)

## 📦 Bundle Size

- **Minified**: ~15KB
- **Gzipped**: ~5KB

## 🧪 Testing

```bash
# Run tests
npm test

# Run with coverage
npm test:coverage

# Watch mode
npm test:watch

# Lint code
npm run lint

# Fix lint issues
npm run lint:fix
```

## 📄 TypeScript Support

The SDK includes TypeScript definitions:

```typescript
import { KlubeCashSDK, KlubeCashException } from '@klubecash/javascript-sdk';

const sdk = new KlubeCashSDK('your_api_key');

interface CashbackResult {
    data: {
        store_id: number;
        purchase_amount: number;
        cashback_calculation: {
            total_cashback: number;
            client_receives: number;
            admin_receives: number;
            store_receives: number;
        };
    };
}

const result: CashbackResult = await sdk.calculateCashback(59, 100);
```

## 📞 Support

- **Documentation**: https://klubecash.com/api-external/v1/docs
- **Support Email**: suporte@klubecash.com
- **GitHub Issues**: https://github.com/klubecash/javascript-sdk/issues
- **NPM Package**: https://www.npmjs.com/package/@klubecash/javascript-sdk

## 📄 License

This SDK is released under the MIT License. See LICENSE file for details.

---

**KlubeCash JavaScript SDK v1.0.0** - Built with ❤️ by the KlubeCash team