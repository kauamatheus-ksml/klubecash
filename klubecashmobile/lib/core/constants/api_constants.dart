// lib/core/constants/api_constants.dart
// Este arquivo contém todas as constantes relacionadas às APIs do sistema Klube Cash
// Inclui URLs base, endpoints, timeouts e configurações de rede

class ApiConstants {
  // ==================== URLs BASE ====================
  static const String baseUrl = 'https://klubecash.com';
  static const String apiBaseUrl = '$baseUrl/api';
  
  // ==================== ENDPOINTS PRINCIPAIS ====================
  
  // Auth endpoints
  static const String loginEndpoint = '/auth/login';
  static const String registerEndpoint = '/auth/register';
  static const String logoutEndpoint = '/auth/logout';
  static const String recoverPasswordEndpoint = '/auth/recover-password';
  static const String refreshTokenEndpoint = '/auth/refresh-token';
  
  // Google OAuth endpoints
  static const String googleAuthEndpoint = '/auth/google/auth';
  static const String googleCallbackEndpoint = '/auth/google/callback';
  
  // User endpoints
  static const String userProfileEndpoint = '/user/profile';
  static const String userBalanceEndpoint = '/user/balance';
  static const String userActionsEndpoint = '/user/actions';
  
  // Dashboard endpoints
  static const String dashboardEndpoint = '/dashboard';
  static const String balanceEndpoint = '/balance';
  static const String transactionsEndpoint = '/transactions';
  static const String cashbackSummaryEndpoint = '/cashback/summary';
  
  // Store endpoints
  static const String storesEndpoint = '/stores';
  static const String partnerStoresEndpoint = '/stores/partners';
  static const String storeDetailsEndpoint = '/stores'; // + /{id}
  
  // Cashback endpoints
  static const String earnCashbackEndpoint = '/cashback/earn';
  static const String cashbackHistoryEndpoint = '/cashback/history';
  static const String cashbackWithdrawEndpoint = '/cashback/withdraw';
  
  // Mercado Pago endpoints
  static const String mercadopagoEndpoint = '/mercadopago';
  static const String createPaymentEndpoint = '/mercadopago?action=create_payment';
  static const String paymentStatusEndpoint = '/mercadopago?action=status';
  static const String mercadopagoWebhookEndpoint = '/mercadopago-webhook';
  
  // Notifications endpoints
  static const String notificationsEndpoint = '/notifications';
  static const String markNotificationReadEndpoint = '/notifications/read'; // + /{id}
  
  // ==================== URLs COMPLETAS ====================
  
  // Auth URLs
  static const String loginUrl = '$baseUrl/login';
  static const String registerUrl = '$baseUrl/registro';
  static const String recoverPasswordUrl = '$baseUrl/recuperar-senha';
  
  // Client URLs
  static const String clientDashboardUrl = '$baseUrl/cliente/dashboard';
  static const String clientStatementUrl = '$baseUrl/cliente/extrato';
  static const String clientStoresUrl = '$baseUrl/cliente/lojas-parceiras';
  static const String clientProfileUrl = '$baseUrl/cliente/perfil';
  static const String clientBalanceUrl = '$baseUrl/cliente/saldo';
  
  // Store registration
  static const String storeRegisterUrl = '$baseUrl/lojas/cadastro';
  
  // ==================== CONFIGURAÇÕES DE REDE ====================
  
  // Timeouts
  static const int connectTimeout = 30000; // 30 segundos
  static const int receiveTimeout = 30000; // 30 segundos
  static const int sendTimeout = 30000; // 30 segundos
  
  // Retry configuration
  static const int maxRetries = 3;
  static const int retryDelay = 1000; // 1 segundo
  
  // Headers
  static const String contentTypeJson = 'application/json';
  static const String authorizationHeader = 'Authorization';
  static const String bearerPrefix = 'Bearer ';
  static const String userAgent = 'KlubeCash-Flutter/1.0.0';
  
  // ==================== STATUS CODES ====================
  
  static const int statusOk = 200;
  static const int statusCreated = 201;
  static const int statusNoContent = 204;
  static const int statusBadRequest = 400;
  static const int statusUnauthorized = 401;
  static const int statusForbidden = 403;
  static const int statusNotFound = 404;
  static const int statusInternalServerError = 500;
  
  // ==================== CONFIGURAÇÕES MERCADO PAGO ====================
  
  static const String mpBaseUrl = 'https://api.mercadopago.com';
  static const String mpEnvironment = 'production'; // ou 'sandbox'
  static const String mpPlatformId = 'mp-ecom';
  static const String mpCorporationId = 'klubecash';
  static const String mpIntegrationType = 'direct';
  static const int mpTimeout = 30;
  static const String mpUserAgent = 'KlubeCash/2.1 (Mercado Pago Integration Optimized)';
  
  // ==================== LIMITES E VALIDAÇÕES ====================
  
  static const double minTransactionValue = 5.00;
  static const double minWithdrawalValue = 20.00;
  static const int itemsPerPage = 10;
  
  // ==================== CACHE E STORAGE ====================
  
  static const String tokenKey = 'auth_token';
  static const String refreshTokenKey = 'refresh_token';
  static const String userDataKey = 'user_data';
  static const String themeKey = 'theme_mode';
  static const String languageKey = 'language_code';
  
  // Cache durations (em segundos)
  static const int shortCacheDuration = 300; // 5 minutos
  static const int mediumCacheDuration = 1800; // 30 minutos
  static const int longCacheDuration = 3600; // 1 hora
  
  // ==================== ASSETS E VERSIONING ====================
  
  static const String assetsVersion = '2.1.0';
  static const String apiVersion = 'v1';
  
  // ==================== MÉTODOS AUXILIARES ====================
  
  /// Constrói URL completa do endpoint
  static String buildApiUrl(String endpoint) {
    return '$apiBaseUrl$endpoint';
  }
  
  /// Constrói cabeçalho de autorização
  static String buildAuthHeader(String token) {
    return '$bearerPrefix$token';
  }
  
  /// Constrói URL com parâmetros de paginação
  static String buildPaginatedUrl(String endpoint, int page, {int? limit}) {
    final pageLimit = limit ?? itemsPerPage;
    return '$endpoint?page=$page&limit=$pageLimit';
  }
  
  /// Constrói URL de detalhes com ID
  static String buildDetailUrl(String endpoint, String id) {
    return '$endpoint/$id';
  }
}