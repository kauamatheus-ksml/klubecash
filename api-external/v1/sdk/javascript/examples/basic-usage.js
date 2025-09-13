/**
 * KlubeCash JavaScript SDK - Basic Usage Example
 * 
 * This example demonstrates basic usage of the KlubeCash JavaScript SDK
 * for both Node.js and browser environments.
 */

// Import the SDK (Node.js/ES modules)
// For Node.js: const { KlubeCashSDK, KlubeCashTransactionManager } = require('../klubecash-sdk.js');
// For ES modules: import { KlubeCashSDK, KlubeCashTransactionManager } from '../klubecash-sdk.js';
// For browser: Use the global KlubeCash object

// Detect environment
const isNode = typeof window === 'undefined';
const isModule = typeof module !== 'undefined';

// Initialize SDK based on environment
let KlubeCashSDK, KlubeCashTransactionManager, KlubeCashException, Utils;

if (isNode && isModule) {
    // Node.js CommonJS
    const sdk = require('../klubecash-sdk.js');
    ({ KlubeCashSDK, KlubeCashTransactionManager, KlubeCashException, Utils } = sdk);
} else if (typeof KlubeCash !== 'undefined') {
    // Browser global
    ({ KlubeCashSDK, KlubeCashTransactionManager, KlubeCashException, Utils } = KlubeCash);
} else {
    console.error('KlubeCash SDK not available');
}

// Configuration
const API_KEY = 'kc_live_123456789012345678901234567890123456789012345678901234567890';

/**
 * Basic SDK usage example
 */
async function basicExample() {
    console.log('=== KLUBECASH JAVASCRIPT SDK - BASIC EXAMPLE ===\n');

    try {
        // 1. Initialize SDK
        console.log('1. Initializing SDK...');
        const sdk = new KlubeCashSDK(API_KEY, {
            debug: true,
            timeout: 30000,
            logger: (message, level) => {
                console.log(`[SDK][${level.toUpperCase()}] ${message}`);
            }
        });
        console.log('✅ SDK initialized successfully\n');

        // 2. Test connection
        console.log('2. Testing API connection...');
        const connectionTest = await sdk.testConnection();
        
        if (connectionTest.api_reachable) {
            console.log(`✅ API is reachable (${connectionTest.response_time}ms)`);
        } else {
            console.log('❌ API is not reachable');
        }
        
        if (connectionTest.authentication) {
            console.log('✅ Authentication successful');
        } else {
            console.log('❌ Authentication failed');
        }
        
        if (connectionTest.errors.length > 0) {
            console.log('Errors:');
            connectionTest.errors.forEach(error => console.log(`  - ${error}`));
        }
        console.log('');

        // 3. Get API information
        console.log('3. Getting API information...');
        const apiInfo = await sdk.getApiInfo();
        console.log(`API Name: ${apiInfo.data.api_name}`);
        console.log(`Version: ${apiInfo.data.version}`);
        console.log(`Base URL: ${apiInfo.data.base_url}`);
        console.log(`Requires API Key: ${apiInfo.data.requires_api_key ? 'Yes' : 'No'}\n`);

        // 4. Check API health
        console.log('4. Checking API health...');
        const health = await sdk.checkHealth();
        console.log(`Status: ${health.data.status}`);
        console.log(`Database: ${health.data.database}`);
        console.log(`Timestamp: ${health.data.timestamp}\n`);

        // 5. Get users
        console.log('5. Getting users...');
        const users = await sdk.getUsers();
        
        if (users.data && users.data.length > 0) {
            console.log(`Found ${users.data.length} users:`);
            users.data.slice(0, 3).forEach(user => {
                console.log(`  - ${user.name} (${user.email}) - ${user.status}`);
            });
        } else {
            console.log('No users found');
        }
        console.log('');

        // 6. Get stores
        console.log('6. Getting stores...');
        const stores = await sdk.getStores();
        
        if (stores.data && stores.data.length > 0) {
            console.log(`Total stores: ${stores.data.length}`);
            
            const approvedStores = await sdk.getApprovedStores();
            console.log(`Approved stores: ${approvedStores.length}`);
            
            approvedStores.forEach(store => {
                console.log(`  - ID: ${store.id} | ${store.trade_name} | ${store.status}`);
            });
        } else {
            console.log('No stores found');
        }
        console.log('');

        // 7. Calculate cashback
        console.log('7. Calculating cashback...');
        
        if (stores.data && stores.data.length > 0) {
            // Find first approved store
            const approvedStores = await sdk.getApprovedStores();
            
            if (approvedStores.length > 0) {
                const testStoreId = approvedStores[0].id;
                const testAmount = 100.00;
                
                console.log(`Store ID: ${testStoreId}`);
                console.log(`Purchase amount: ${Utils.formatCurrency(testAmount)}`);
                
                const cashbackResult = await sdk.calculateCashback(testStoreId, testAmount);
                
                if (cashbackResult.data) {
                    const calc = cashbackResult.data.cashback_calculation;
                    console.log(`Store cashback rate: ${cashbackResult.data.store_cashback_percentage}%`);
                    console.log(`Total cashback: ${Utils.formatCurrency(calc.total_cashback)}`);
                    console.log(`Client receives: ${Utils.formatCurrency(calc.client_receives)}`);
                    console.log(`Admin receives: ${Utils.formatCurrency(calc.admin_receives)}`);
                    console.log(`Store receives: ${Utils.formatCurrency(calc.store_receives)}`);
                }
            } else {
                console.log('No approved stores available for cashback calculation');
            }
        }
        console.log('');

        // 8. Bulk cashback calculations
        console.log('8. Bulk cashback calculations...');
        const approvedStores = await sdk.getApprovedStores();
        
        if (approvedStores.length >= 2) {
            const bulkCalculations = [
                { store_id: approvedStores[0].id, amount: 100.00 },
                { store_id: approvedStores[1].id, amount: 250.00 }
            ];
            
            if (approvedStores.length >= 3) {
                bulkCalculations.push({ store_id: approvedStores[2].id, amount: 50.00 });
            }
            
            const bulkResults = await sdk.bulkCalculateCashback(bulkCalculations);
            
            Object.entries(bulkResults).forEach(([storeId, result]) => {
                if (result.data) {
                    const calc = result.data.cashback_calculation;
                    console.log(`Store ${storeId}: ${Utils.formatCurrency(calc.total_cashback)} cashback`);
                } else {
                    console.log(`Store ${storeId}: Error - ${result.error}`);
                }
            });
        } else {
            console.log('Not enough approved stores for bulk calculation');
        }
        console.log('');

        // 9. Transaction Manager
        console.log('9. Using Transaction Manager...');
        
        const transactionManager = new KlubeCashTransactionManager(sdk);
        
        if (approvedStores.length > 0) {
            const storeId = approvedStores[0].id;
            const amount = 199.90;
            const customerEmail = Utils.generateTestEmail();
            const metadata = {
                order_id: `ORD-${Date.now()}`,
                payment_method: 'credit_card',
                source: 'sdk_example'
            };
            
            console.log('Processing transaction:');
            console.log(`  Store ID: ${storeId}`);
            console.log(`  Amount: ${Utils.formatCurrency(amount)}`);
            console.log(`  Customer: ${customerEmail}`);
            
            const transactionResult = await transactionManager.processTransaction(
                storeId, 
                amount, 
                customerEmail, 
                metadata
            );
            
            if (transactionResult.success) {
                const transaction = transactionResult.transaction;
                console.log('✅ Transaction processed successfully!');
                console.log(`  Transaction ID: ${transaction.id}`);
                console.log(`  Created at: ${transaction.created_at}`);
                console.log(`  Customer cashback: ${Utils.formatCurrency(transaction.cashback_data.cashback_calculation.client_receives)}`);
                
                // Retrieve transaction
                const retrieved = transactionManager.getTransaction(transaction.id);
                if (retrieved) {
                    console.log('  ✅ Transaction successfully retrieved from storage');
                }
            } else {
                console.log(`❌ Transaction failed: ${transactionResult.error}`);
            }
        }
        console.log('');

        // 10. Statistics
        console.log('10. Getting cashback statistics...');
        const stats = await sdk.getCashbackStatistics();
        console.log(`Total approved stores: ${stats.total_stores}`);
        console.log(`Average cashback rate: ${stats.average_cashback_rate.toFixed(2)}%`);
        console.log(`Min cashback rate: ${stats.min_cashback_rate}%`);
        console.log(`Max cashback rate: ${stats.max_cashback_rate}%`);
        
        console.log('\nTop stores by estimated rate:');
        stats.stores_by_rate.slice(0, 5).forEach(store => {
            console.log(`  - ${store.store_name}: ~${store.estimated_rate}%`);
        });
        console.log('');

        // 11. Cache demonstration
        console.log('11. Cache demonstration...');
        
        console.log('First request (API call):');
        let start = Date.now();
        await sdk.getStores(true);
        let time1 = Date.now() - start;
        console.log(`  Time: ${time1}ms`);
        
        console.log('Second request (from cache):');
        start = Date.now();
        await sdk.getStores(true);
        let time2 = Date.now() - start;
        console.log(`  Time: ${time2}ms`);
        
        console.log('Third request (cache cleared):');
        sdk.clearCache();
        start = Date.now();
        await sdk.getStores(true);
        let time3 = Date.now() - start;
        console.log(`  Time: ${time3}ms\n`);

        // 12. Rate limit info
        console.log('12. Rate limit information...');
        const rateLimitInfo = sdk.getRateLimitInfo();
        console.log(`Requests remaining: ${rateLimitInfo.remaining}/${rateLimitInfo.limit}`);
        console.log(`Reset time: ${new Date(rateLimitInfo.resetTime).toLocaleString()}\n`);

        console.log('=== ALL EXAMPLES COMPLETED SUCCESSFULLY ===\n');

    } catch (error) {
        console.error('\n❌ Error in basic example:');
        
        if (error instanceof KlubeCashException) {
            console.error(`KlubeCash API Error: ${error.message}`);
            console.error(`HTTP Code: ${error.statusCode}`);
            
            if (error.isAuthenticationError()) {
                console.error('This is an authentication error. Please check your API key.');
            } else if (error.isRateLimitError()) {
                console.error('Rate limit exceeded. Please wait before making more requests.');
            } else if (error.isClientError()) {
                console.error('Client error. Please check your request parameters.');
            } else if (error.isServerError()) {
                console.error('Server error. Please try again later.');
            }
            
            const errorData = error.getErrorData();
            if (errorData) {
                console.error('Additional error data:', errorData);
            }
        } else {
            console.error(`General Error: ${error.message}`);
            if (error.stack) {
                console.error('Stack trace:', error.stack);
            }
        }
    }
}

/**
 * Advanced error handling example
 */
async function errorHandlingExample() {
    console.log('\n=== ERROR HANDLING EXAMPLES ===\n');
    
    const sdk = new KlubeCashSDK(API_KEY);
    
    // Test different types of errors
    console.log('Testing error scenarios...\n');
    
    // 1. Invalid store ID
    try {
        console.log('1. Testing invalid store ID...');
        await sdk.calculateCashback(99999, 100);
    } catch (error) {
        if (error instanceof KlubeCashException) {
            console.log(`✅ Caught expected error: ${error.message}`);
            console.log(`   HTTP Code: ${error.statusCode}`);
            console.log(`   Is client error: ${error.isClientError()}`);
        }
    }
    
    // 2. Invalid amount
    try {
        console.log('\n2. Testing invalid amount...');
        await sdk.calculateCashback(59, -10);
    } catch (error) {
        console.log(`✅ Caught expected error: ${error.message}`);
    }
    
    // 3. Network timeout simulation
    try {
        console.log('\n3. Testing timeout (this will take a moment)...');
        const shortTimeoutSdk = new KlubeCashSDK(API_KEY, { timeout: 1 }); // 1ms timeout
        await shortTimeoutSdk.getStores();
    } catch (error) {
        console.log(`✅ Caught timeout error: ${error.message}`);
    }
    
    console.log('\n=== ERROR HANDLING EXAMPLES COMPLETED ===\n');
}

/**
 * Retry mechanism example
 */
async function retryExample() {
    console.log('=== RETRY MECHANISM EXAMPLE ===\n');
    
    const sdk = new KlubeCashSDK(API_KEY);
    
    console.log('Using retry utility for robust API calls...');
    
    try {
        const result = await Utils.retry(async () => {
            console.log('Attempting API call...');
            return await sdk.getStores();
        }, 3, 1000);
        
        console.log(`✅ Success after retry: Found ${result.data.length} stores`);
        
    } catch (error) {
        console.log(`❌ Failed after all retries: ${error.message}`);
    }
    
    console.log('\n=== RETRY EXAMPLE COMPLETED ===\n');
}

/**
 * E-commerce integration example
 */
async function ecommerceExample() {
    console.log('=== E-COMMERCE INTEGRATION EXAMPLE ===\n');
    
    const sdk = new KlubeCashSDK(API_KEY);
    const transactionManager = new KlubeCashTransactionManager(sdk);
    
    // Simulate an e-commerce checkout process
    console.log('Simulating e-commerce checkout process...\n');
    
    try {
        // 1. Customer selects store
        const approvedStores = await sdk.getApprovedStores();
        if (approvedStores.length === 0) {
            console.log('❌ No approved stores available');
            return;
        }
        
        const selectedStore = approvedStores[0];
        console.log(`1. Customer selected store: ${selectedStore.trade_name} (ID: ${selectedStore.id})`);
        
        // 2. Calculate cashback for cart
        const cartAmount = 299.90;
        console.log(`2. Cart total: ${Utils.formatCurrency(cartAmount)}`);
        
        const cashbackPreview = await sdk.calculateCashback(selectedStore.id, cartAmount);
        const clientCashback = cashbackPreview.data.cashback_calculation.client_receives;
        
        console.log(`3. Cashback preview: Customer will receive ${Utils.formatCurrency(clientCashback)}`);
        
        // 3. Process payment and transaction
        console.log('4. Processing payment...');
        const customerEmail = Utils.generateTestEmail();
        const orderMetadata = {
            order_id: `ORD-${Date.now()}`,
            payment_method: 'credit_card',
            items: [
                { name: 'Product A', price: 199.90 },
                { name: 'Product B', price: 100.00 }
            ],
            shipping_address: 'São Paulo, SP'
        };
        
        const transaction = await transactionManager.processTransaction(
            selectedStore.id,
            cartAmount,
            customerEmail,
            orderMetadata
        );
        
        if (transaction.success) {
            console.log('✅ Payment and cashback processed successfully!');
            console.log(`   Transaction ID: ${transaction.transaction.id}`);
            console.log(`   Customer: ${customerEmail}`);
            console.log(`   Cashback earned: ${Utils.formatCurrency(clientCashback)}`);
            console.log('5. Order confirmation email would be sent to customer');
        } else {
            console.log(`❌ Payment processing failed: ${transaction.error}`);
        }
        
    } catch (error) {
        console.error('E-commerce integration error:', error.message);
    }
    
    console.log('\n=== E-COMMERCE EXAMPLE COMPLETED ===\n');
}

/**
 * Main execution function
 */
async function main() {
    try {
        await basicExample();
        await errorHandlingExample();
        await retryExample();
        await ecommerceExample();
        
        console.log('🎉 All examples completed successfully!');
        
    } catch (error) {
        console.error('❌ Fatal error in main execution:', error.message);
        process.exit(1);
    }
}

// Run examples if this file is executed directly
if (isNode && require.main === module) {
    main();
} else if (!isNode && typeof document !== 'undefined') {
    // Browser environment - run when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', main);
    } else {
        main();
    }
}

// Export functions for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        basicExample,
        errorHandlingExample,
        retryExample,
        ecommerceExample,
        main
    };
}