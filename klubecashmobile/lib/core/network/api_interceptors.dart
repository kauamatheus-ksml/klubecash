// lib/core/network/api_interceptors.dart
// Este arquivo contém os interceptadores do Dio para autenticação, logs e retry automático.
// Responsável por interceptar requisições e respostas para adicionar funcionalidades transversais.

import 'dart:developer' as dev;
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../constants/api_constants.dart';

/// Interceptador para adicionar automaticamente o token de autenticação
class AuthInterceptor extends Interceptor {
  final FlutterSecureStorage _secureStorage;

  AuthInterceptor(this._secureStorage);

  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Endpoints que não precisam de autenticação
    final publicEndpoints = [
      ApiConstants.loginEndpoint,
      ApiConstants.registerEndpoint,
      ApiConstants.recoverPasswordEndpoint,
    ];

    // Verifica se o endpoint atual precisa de autenticação
    final isPublicEndpoint = publicEndpoints.any(
      (endpoint) => options.path.contains(endpoint),
    );

    if (!isPublicEndpoint) {
      // Busca o token do armazenamento seguro
      final token = await _secureStorage.read(key: 'auth_token');

      if (token != null) {
        options.headers[ApiConstants.authorizationHeader] =
            '${ApiConstants.bearerPrefix}$token';
      }
    }

    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    // Se retornar 401 (não autorizado), remove o token inválido
    if (err.response?.statusCode == ApiConstants.statusUnauthorized) {
      _secureStorage.delete(key: 'auth_token');
      _secureStorage.delete(key: 'refresh_token');
    }

    handler.next(err);
  }
}

/// Interceptador para fazer logs detalhados das requisições
class LoggingInterceptor extends Interceptor {
  final bool _enableLogs;

  LoggingInterceptor({bool enableLogs = true}) : _enableLogs = enableLogs;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    if (_enableLogs) {
      dev.log(
        '🚀 REQUEST\n'
        'Method: ${options.method}\n'
        'URL: ${options.baseUrl}${options.path}\n'
        'Headers: ${_sanitizeHeaders(options.headers)}\n'
        'Query Parameters: ${options.queryParameters}\n'
        'Data: ${_sanitizeData(options.data)}',
        name: 'ApiClient',
      );
    }
    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    if (_enableLogs) {
      dev.log(
        '✅ RESPONSE\n'
        'Status Code: ${response.statusCode}\n'
        'URL: ${response.requestOptions.baseUrl}${response.requestOptions.path}\n'
        'Headers: ${response.headers}\n'
        'Data: ${_truncateData(response.data)}',
        name: 'ApiClient',
      );
    }
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (_enableLogs) {
      dev.log(
        '❌ ERROR\n'
        'Type: ${err.type}\n'
        'Message: ${err.message}\n'
        'URL: ${err.requestOptions.baseUrl}${err.requestOptions.path}\n'
        'Status Code: ${err.response?.statusCode}\n'
        'Response Data: ${err.response?.data}',
        name: 'ApiClient',
      );
    }
    handler.next(err);
  }

  /// Remove informações sensíveis dos headers para logging
  Map<String, dynamic> _sanitizeHeaders(Map<String, dynamic> headers) {
    final sanitized = Map<String, dynamic>.from(headers);

    // Remove tokens de autenticação dos logs
    if (sanitized.containsKey(ApiConstants.authorizationHeader)) {
      sanitized[ApiConstants.authorizationHeader] = '***TOKEN***';
    }

    return sanitized;
  }

  /// Remove informações sensíveis dos dados para logging
  dynamic _sanitizeData(dynamic data) {
    if (data is Map) {
      final sanitized = Map.from(data);

      // Remove campos sensíveis
      const sensitiveFields = ['password', 'senha', 'token', 'cpf', 'phone'];

      for (final field in sensitiveFields) {
        if (sanitized.containsKey(field)) {
          sanitized[field] = '***HIDDEN***';
        }
      }

      return sanitized;
    }

    return data;
  }

  /// Trunca dados muito grandes para evitar logs excessivos
  String _truncateData(dynamic data) {
    final dataString = data.toString();
    const maxLength = 1000;

    if (dataString.length <= maxLength) {
      return dataString;
    }

    return '${dataString.substring(0, maxLength)}... [TRUNCATED]';
  }
}

/// Interceptador para retry automático em caso de falhas
class RetryInterceptor extends Interceptor {
  final int _maxRetries;
  final int _retryDelay;

  RetryInterceptor({
    int maxRetries = ApiConstants.maxRetries,
    int retryDelay = ApiConstants.retryDelay,
  })  : _maxRetries = maxRetries,
        _retryDelay = retryDelay;

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    // Verifica se deve tentar novamente
    if (_shouldRetry(err)) {
      final retryCount = err.requestOptions.extra['retry_count'] ?? 0;

      if (retryCount < _maxRetries) {
        dev.log(
          '🔄 RETRY ${retryCount + 1}/$_maxRetries for ${err.requestOptions.path}',
          name: 'RetryInterceptor',
        );

        // Aguarda antes de tentar novamente
        await Future.delayed(Duration(milliseconds: _retryDelay));

        // Incrementa contador de tentativas
        err.requestOptions.extra['retry_count'] = retryCount + 1;

        try {
          // Cria nova instância do Dio para a tentativa
          final dio = Dio();
          final response = await dio.request(
            err.requestOptions.path,
            data: err.requestOptions.data,
            queryParameters: err.requestOptions.queryParameters,
            options: Options(
              method: err.requestOptions.method,
              headers: err.requestOptions.headers,
              contentType: err.requestOptions.contentType,
              responseType: err.requestOptions.responseType,
              followRedirects: err.requestOptions.followRedirects,
              maxRedirects: err.requestOptions.maxRedirects,
              persistentConnection: err.requestOptions.persistentConnection,
              receiveDataWhenStatusError:
                  err.requestOptions.receiveDataWhenStatusError,
              sendTimeout: err.requestOptions.sendTimeout,
              receiveTimeout: err.requestOptions.receiveTimeout,
              extra: err.requestOptions.extra,
              validateStatus: err.requestOptions.validateStatus,
              listFormat: err.requestOptions.listFormat,
            ),
          );

          handler.resolve(response);
          return;
        } catch (e) {
          // Se falhou novamente, continua com o erro original
          dev.log(
            '❌ Retry ${retryCount + 1} failed: $e',
            name: 'RetryInterceptor',
          );
        }
      }
    }

    handler.next(err);
  }

  /// Determina se uma requisição deve ser tentada novamente
  bool _shouldRetry(DioException err) {
    // Não tenta novamente para erros de autenticação ou cliente
    if (err.response?.statusCode != null) {
      final statusCode = err.response!.statusCode!;

      // Não tenta novamente para erros 4xx (cliente)
      if (statusCode >= 400 && statusCode < 500) {
        return false;
      }
    }

    // Tenta novamente para:
    // - Timeouts
    // - Erros de conexão
    // - Erros 5xx (servidor)
    return err.type == DioExceptionType.connectionTimeout ||
        err.type == DioExceptionType.receiveTimeout ||
        err.type == DioExceptionType.sendTimeout ||
        err.type == DioExceptionType.connectionError ||
        (err.response?.statusCode != null && err.response!.statusCode! >= 500);
  }
}

/// Interceptador para cache de requisições GET
class CacheInterceptor extends Interceptor {
  final Map<String, CacheItem> _cache = {};
  final int _cacheDurationMinutes;

  CacheInterceptor({int cacheDurationMinutes = 5})
      : _cacheDurationMinutes = cacheDurationMinutes;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    // Só faz cache de requisições GET
    if (options.method.toUpperCase() != 'GET') {
      handler.next(options);
      return;
    }

    final cacheKey = _generateCacheKey(options);
    final cachedItem = _cache[cacheKey];

    // Verifica se tem cache válido
    if (cachedItem != null && !cachedItem.isExpired) {
      dev.log(
        '📦 Cache HIT for ${options.path}',
        name: 'CacheInterceptor',
      );

      handler.resolve(cachedItem.response);
      return;
    }

    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    // Só cacheia respostas GET com sucesso
    if (response.requestOptions.method.toUpperCase() == 'GET' &&
        response.statusCode == 200) {
      final cacheKey = _generateCacheKey(response.requestOptions);

      _cache[cacheKey] = CacheItem(
        response: response,
        expiry: DateTime.now().add(Duration(minutes: _cacheDurationMinutes)),
      );

      dev.log(
        '📦 Cache STORED for ${response.requestOptions.path}',
        name: 'CacheInterceptor',
      );
    }

    handler.next(response);
  }

  /// Gera chave única para cache baseada na URL e query parameters
  String _generateCacheKey(RequestOptions options) {
    final uri = Uri(
      path: options.path,
      queryParameters:
          options.queryParameters.isEmpty ? null : options.queryParameters,
    );
    return uri.toString();
  }

  /// Limpa cache expirado
  void cleanExpiredCache() {
    _cache.removeWhere((key, value) => value.isExpired);
  }

  /// Limpa todo o cache
  void clearCache() {
    _cache.clear();
  }
}

/// Item de cache com expiração
class CacheItem {
  final Response response;
  final DateTime expiry;

  CacheItem({required this.response, required this.expiry});

  bool get isExpired => DateTime.now().isAfter(expiry);
}
