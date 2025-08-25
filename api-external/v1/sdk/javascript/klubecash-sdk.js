/**
 * KlubeCash JavaScript/Node.js SDK
 * 
 * Official JavaScript SDK for integrating with the KlubeCash External API
 * Compatible with both browser and Node.js environments
 * 
 * @version 1.0.0
 * @author KlubeCash Development Team
 * @license MIT
 */

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
    typeof define === 'function' && define.amd ? define(['exports'], factory) :
    (global = global || self, factory(global.KlubeCash = {}));
}(this, (function (exports) {
    'use strict';

    /**
     * KlubeCash SDK Exception
     */
    class KlubeCashException extends Error {
        constructor(message, statusCode = 0, errorData = null) {
            super(message);
            this.name = 'KlubeCashException';
            this.statusCode = statusCode;
            this.errorData = errorData;
        }

        /**
         * Check if error is due to authentication
         */
        isAuthenticationError() {
            return this.statusCode === 401;
        }

        /**
         * Check if error is due to rate limiting
         */
        isRateLimitError() {
            return this.statusCode === 429;
        }

        /**
         * Check if error is a client error (4xx)
         */
        isClientError() {
            return this.statusCode >= 400 && this.statusCode < 500;
        }

        /**
         * Check if error is a server error (5xx)
         */
        isServerError() {
            return this.statusCode >= 500 && this.statusCode < 600;
        }

        /**
         * Get additional error data
         */
        getErrorData() {
            return this.errorData;
        }
    }

    /**
     * Main KlubeCash SDK Class
     */
    class KlubeCashSDK {
        static VERSION = '1.0.0';
        static USER_AGENT = 'KlubeCash-JS-SDK/1.0.0';

        constructor(apiKey, options = {}) {
            if (!apiKey || typeof apiKey !== 'string') {
                throw new Error('API Key is required and must be a string');
            }

            if (!apiKey.startsWith('kc_')) {
                throw new Error('Invalid API Key format. Must start with "kc_"');
            }

            this.apiKey = apiKey;
            this.baseUrl = options.baseUrl || 'https://klubecash.com/api-external/v1';
            this.timeout = options.timeout || 30000; // 30 seconds
            this.debug = options.debug || false;
            this.cacheTtl = options.cacheTtl || 300000; // 5 minutes in ms
            this.logger = options.logger || null;

            // Cache storage
            this.cache = new Map();
            
            // Rate limit info
            this.rateLimitInfo = {
                remaining: 1000,
                limit: 1000,
                resetTime: Date.now() + 3600000
            };

            // Default fetch options
            this.defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey,
                    'Accept': 'application/json',
                    'User-Agent': KlubeCashSDK.USER_AGENT
                }
            };
        }

        /**
         * Make HTTP request to API
         */
        async makeRequest(endpoint, method = 'GET', data = null, useCache = false) {
            const url = this.baseUrl + endpoint;
            const cacheKey = `${method}:${endpoint}:${JSON.stringify(data)}`;

            // Check cache for GET requests
            if (useCache && method === 'GET' && this.isCached(cacheKey)) {
                return this.getFromCache(cacheKey);
            }

            this.log(`Making ${method} request to: ${url}`);

            const options = {
                method,
                ...this.defaultOptions,
                signal: this.createAbortController().signal
            };

            // Add body for POST/PUT/PATCH requests
            if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
                options.body = JSON.stringify(data);
            }

            const startTime = Date.now();

            try {
                const response = await fetch(url, options);
                const duration = Date.now() - startTime;
                
                this.log(`Request completed in ${duration}ms`);

                // Extract rate limit info from headers
                this.extractRateLimitInfo(response);

                // Parse response
                let responseData;
                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    responseData = await response.json();
                } else {
                    const text = await response.text();
                    throw new KlubeCashException(`Invalid JSON response: ${text}`, response.status);
                }

                // Handle HTTP errors
                if (!response.ok) {
                    const errorMessage = responseData.message || 'API Error';
                    this.log(`API Error (HTTP ${response.status}): ${errorMessage}`, 'error');
                    throw new KlubeCashException(errorMessage, response.status, responseData);
                }

                // Cache successful GET requests
                if (useCache && method === 'GET') {
                    this.storeInCache(cacheKey, responseData);
                }

                return responseData;

            } catch (error) {
                if (error.name === 'AbortError') {
                    throw new KlubeCashException('Request timeout', 0);
                }
                
                if (error instanceof KlubeCashException) {
                    throw error;
                }

                // Network errors
                this.log(`Network error: ${error.message}`, 'error');
                throw new KlubeCashException(`Network error: ${error.message}`, 0);
            }
        }

        /**
         * Get API information
         */
        async getApiInfo(useCache = true) {
            return await this.makeRequest('/auth/info', 'GET', null, useCache);
        }

        /**
         * Check API health
         */
        async checkHealth() {
            return await this.makeRequest('/auth/health', 'GET');
        }

        /**
         * Get users list
         */
        async getUsers(useCache = true) {
            return await this.makeRequest('/users', 'GET', null, useCache);
        }

        /**
         * Get stores list
         */
        async getStores(useCache = true) {
            return await this.makeRequest('/stores', 'GET', null, useCache);
        }

        /**
         * Get specific store by ID
         */
        async getStore(storeId) {
            const stores = await this.getStores();
            
            if (!stores.data || !Array.isArray(stores.data)) {
                return null;
            }

            return stores.data.find(store => store.id == storeId) || null;
        }

        /**
         * Get approved stores only
         */
        async getApprovedStores(useCache = true) {
            const stores = await this.getStores(useCache);
            
            if (!stores.data || !Array.isArray(stores.data)) {
                return [];
            }

            return stores.data.filter(store => store.status === 'aprovado');
        }

        /**
         * Calculate cashback for a purchase
         */
        async calculateCashback(storeId, amount) {
            if (!Number.isInteger(storeId) || storeId <= 0) {
                throw new Error('Store ID must be a positive integer');
            }

            if (typeof amount !== 'number' || amount <= 0) {
                throw new Error('Amount must be a positive number');
            }

            const data = {
                store_id: parseInt(storeId),
                amount: parseFloat(amount)
            };

            return await this.makeRequest('/cashback/calculate', 'POST', data);
        }

        /**
         * Validate if a store exists and is approved
         */
        async isStoreApproved(storeId) {
            const store = await this.getStore(storeId);
            return store !== null && store.status === 'aprovado';
        }

        /**
         * Bulk calculate cashback for multiple stores
         */
        async bulkCalculateCashback(calculations) {
            const results = {};

            // Process calculations in parallel with concurrency limit
            const chunks = this.chunkArray(calculations, 5); // Max 5 concurrent requests
            
            for (const chunk of chunks) {
                const promises = chunk.map(async calc => {
                    if (!calc.store_id || !calc.amount) {
                        return { store_id: calc.store_id, success: false, error: 'Missing store_id or amount' };
                    }

                    try {
                        const result = await this.calculateCashback(calc.store_id, calc.amount);
                        return { store_id: calc.store_id, success: true, data: result };
                    } catch (error) {
                        return { store_id: calc.store_id, success: false, error: error.message };
                    }
                });

                const chunkResults = await Promise.all(promises);
                chunkResults.forEach(result => {
                    results[result.store_id] = result.success ? result.data : { success: false, error: result.error };
                });
            }

            return results;
        }

        /**
         * Get cashback statistics
         */
        async getCashbackStatistics() {
            const stores = await this.getApprovedStores();
            const stats = {
                total_stores: stores.length,
                average_cashback_rate: 0,
                min_cashback_rate: null,
                max_cashback_rate: null,
                stores_by_rate: []
            };

            if (stores.length === 0) {
                return stats;
            }

            const rates = [];
            stores.forEach(store => {
                // Simulate cashback rate based on store ID (in production, this would come from API)
                const sampleRate = (store.id % 10) + 1; // 1-10%
                rates.push(sampleRate);
                
                stats.stores_by_rate.push({
                    store_id: store.id,
                    store_name: store.trade_name,
                    estimated_rate: sampleRate
                });
            });

            stats.average_cashback_rate = rates.reduce((a, b) => a + b, 0) / rates.length;
            stats.min_cashback_rate = Math.min(...rates);
            stats.max_cashback_rate = Math.max(...rates);

            return stats;
        }

        /**
         * Clear cache
         */
        clearCache(pattern = null) {
            if (pattern === null) {
                this.cache.clear();
            } else {
                for (const [key] of this.cache) {
                    if (this.matchPattern(key, pattern)) {
                        this.cache.delete(key);
                    }
                }
            }
        }

        /**
         * Get rate limit information
         */
        getRateLimitInfo() {
            return { ...this.rateLimitInfo };
        }

        /**
         * Set custom logger
         */
        setLogger(logger) {
            this.logger = logger;
        }

        /**
         * Test API connection
         */
        async testConnection() {
            const results = {
                api_reachable: false,
                authentication: false,
                response_time: 0,
                errors: []
            };

            try {
                // Test basic connectivity
                const startTime = Date.now();
                const info = await this.getApiInfo(false);
                results.response_time = Date.now() - startTime;
                results.api_reachable = true;

                // Test authentication
                const users = await this.getUsers(false);
                results.authentication = users.success === true;

            } catch (error) {
                results.errors.push(error.message);
            }

            return results;
        }

        // Private helper methods

        createAbortController() {
            if (typeof AbortController !== 'undefined') {
                const controller = new AbortController();
                setTimeout(() => controller.abort(), this.timeout);
                return controller;
            }
            return { signal: null }; // Fallback for older environments
        }

        isCached(key) {
            const cached = this.cache.get(key);
            return cached && (Date.now() - cached.timestamp) < this.cacheTtl;
        }

        getFromCache(key) {
            const cached = this.cache.get(key);
            this.log(`Cache hit for key: ${key}`);
            return cached.data;
        }

        storeInCache(key, data) {
            this.cache.set(key, {
                data,
                timestamp: Date.now()
            });
        }

        extractRateLimitInfo(response) {
            // In a real implementation, you would extract this from response headers
            const remaining = response.headers.get('X-RateLimit-Remaining');
            const limit = response.headers.get('X-RateLimit-Limit');
            const reset = response.headers.get('X-RateLimit-Reset');

            if (remaining) this.rateLimitInfo.remaining = parseInt(remaining);
            if (limit) this.rateLimitInfo.limit = parseInt(limit);
            if (reset) this.rateLimitInfo.resetTime = parseInt(reset) * 1000;
        }

        chunkArray(array, size) {
            const chunks = [];
            for (let i = 0; i < array.length; i += size) {
                chunks.push(array.slice(i, i + size));
            }
            return chunks;
        }

        matchPattern(str, pattern) {
            // Simple pattern matching with wildcards
            const regex = new RegExp(pattern.replace(/\*/g, '.*'));
            return regex.test(str);
        }

        log(message, level = 'info') {
            if (!this.debug && level !== 'error') {
                return;
            }

            const timestamp = new Date().toISOString();
            const formatted = `[${timestamp}] [${level}] KlubeCashSDK: ${message}`;

            if (this.logger && typeof this.logger === 'function') {
                this.logger(formatted, level);
            } else if (this.debug) {
                console.log(formatted);
            }
        }
    }

    /**
     * Transaction manager for advanced cashback operations
     */
    class KlubeCashTransactionManager {
        constructor(sdk) {
            this.sdk = sdk;
            this.transactions = new Map();
        }

        /**
         * Process a complete transaction with cashback
         */
        async processTransaction(storeId, amount, customerEmail, metadata = {}) {
            const transactionId = this.generateTransactionId();

            try {
                // Validate store
                if (!(await this.sdk.isStoreApproved(storeId))) {
                    throw new Error(`Store ID ${storeId} is not approved`);
                }

                // Calculate cashback
                const cashbackResult = await this.sdk.calculateCashback(storeId, amount);

                if (!cashbackResult.data) {
                    throw new Error('Failed to calculate cashback');
                }

                // Create transaction record
                const transaction = {
                    id: transactionId,
                    store_id: storeId,
                    customer_email: customerEmail,
                    purchase_amount: amount,
                    cashback_data: cashbackResult.data,
                    metadata,
                    created_at: new Date().toISOString(),
                    status: 'completed'
                };

                // Store transaction
                this.transactions.set(transactionId, transaction);

                return {
                    success: true,
                    transaction_id: transactionId,
                    transaction
                };

            } catch (error) {
                return {
                    success: false,
                    error: error.message,
                    transaction_id: transactionId
                };
            }
        }

        /**
         * Get transaction by ID
         */
        getTransaction(transactionId) {
            return this.transactions.get(transactionId) || null;
        }

        /**
         * Generate unique transaction ID
         */
        generateTransactionId() {
            const timestamp = Date.now().toString(36);
            const random = Math.random().toString(36).substr(2, 9);
            return `tx_${timestamp}_${random}`;
        }
    }

    /**
     * React Hook for KlubeCash integration
     */
    class KlubeCashReactHook {
        constructor(apiKey) {
            this.sdk = new KlubeCashSDK(apiKey);
        }

        /**
         * Create a hook-like interface for React
         */
        useKlubeCashStores() {
            return {
                fetchStores: async () => {
                    try {
                        const response = await this.sdk.getStores();
                        return response.data;
                    } catch (error) {
                        throw error;
                    }
                },
                calculateCashback: async (storeId, amount) => {
                    try {
                        const response = await this.sdk.calculateCashback(storeId, amount);
                        return response.data;
                    } catch (error) {
                        throw error;
                    }
                }
            };
        }
    }

    /**
     * Utility functions
     */
    const Utils = {
        /**
         * Format currency for Brazilian Real
         */
        formatCurrency(amount) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(amount);
        },

        /**
         * Validate email format
         */
        validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * Generate random customer email for testing
         */
        generateTestEmail() {
            const timestamp = Date.now();
            return `test_${timestamp}@klubecash.com`;
        },

        /**
         * Retry function with exponential backoff
         */
        async retry(fn, maxRetries = 3, initialDelay = 1000) {
            for (let i = 0; i < maxRetries; i++) {
                try {
                    return await fn();
                } catch (error) {
                    if (i === maxRetries - 1 || !error.isRateLimitError?.()) {
                        throw error;
                    }
                    const delay = initialDelay * Math.pow(2, i);
                    await new Promise(resolve => setTimeout(resolve, delay));
                }
            }
        }
    };

    // Export for different module systems
    exports.KlubeCashSDK = KlubeCashSDK;
    exports.KlubeCashException = KlubeCashException;
    exports.KlubeCashTransactionManager = KlubeCashTransactionManager;
    exports.KlubeCashReactHook = KlubeCashReactHook;
    exports.Utils = Utils;

    // Default export
    exports.default = KlubeCashSDK;

})));

// Browser global fallback
if (typeof window !== 'undefined' && !window.KlubeCash) {
    window.KlubeCash = {
        KlubeCashSDK: exports.KlubeCashSDK,
        KlubeCashException: exports.KlubeCashException,
        KlubeCashTransactionManager: exports.KlubeCashTransactionManager,
        KlubeCashReactHook: exports.KlubeCashReactHook,
        Utils: exports.Utils
    };
}