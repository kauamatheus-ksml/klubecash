// lib/core/network/api_client.dart
// Este arquivo contém a configuração do cliente HTTP usando Dio.
// Responsável por centralizar todas as configurações de rede, headers e interceptadores.

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../constants/api_constants.dart';
import '../errors/exceptions.dart';
import 'network_info.dart';

/// Cliente HTTP configurado para o Klube Cash
/// Centraliza todas as configurações de rede e requisições HTTP
class ApiClient {
  late final Dio _dio;
  final NetworkInfo _networkInfo;
  final FlutterSecureStorage _secureStorage;

  ApiClient({
    required NetworkInfo networkInfo,
    required FlutterSecureStorage secureStorage,
    Dio? dio,
  })  : _networkInfo = networkInfo,
        _secureStorage = secureStorage {
    _dio = dio ?? Dio();
    _configureDio();
  }
  /// Salva o token de autenticação no armazenamento seguro
    Future<void> setAuthToken(String token) async {
      await _secureStorage.write(key: 'auth_token', value: token);
    }

    /// Limpa o token de autenticação do armazenamento seguro
    Future<void> clearAuthToken() async {
      await _secureStorage.delete(key: 'auth_token');
    }
  /// Getter para acessar a instância do Dio
  Dio get dio => _dio;

  /// Configura as opções base do Dio
  void _configureDio() {
    _dio.options = BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: const Duration(milliseconds: ApiConstants.connectTimeout),
      receiveTimeout: const Duration(milliseconds: ApiConstants.receiveTimeout),
      sendTimeout: const Duration(milliseconds: ApiConstants.sendTimeout),
      headers: {
        'Content-Type': ApiConstants.contentTypeJson,
        'User-Agent': ApiConstants.userAgent,
        'Accept': ApiConstants.contentTypeJson,
      },
      responseType: ResponseType.json,
      followRedirects: true,
      validateStatus: (status) {
        // Considera sucesso status codes de 200-299
        return status != null && status >= 200 && status < 300;
      },
    );
  }

  /// Adiciona interceptadores ao Dio
  void addInterceptors(List<Interceptor> interceptors) {
    _dio.interceptors.addAll(interceptors);
  }

  /// Verifica conectividade antes de fazer requisições
  Future<void> _checkConnectivity() async {
    if (!await _networkInfo.isConnected) {
      throw const NetworkException('Sem conexão com a internet');
    }
  }

  /// Obtém o token de autenticação do armazenamento seguro
  Future<String?> _getAuthToken() async {
    return await _secureStorage.read(key: 'auth_token');
  }

  /// Adiciona header de autorização se o token existir
  Future<Options> _getRequestOptions({Options? options}) async {
    final token = await _getAuthToken();
    final headers = <String, dynamic>{
      ...?options?.headers,
    };

    if (token != null) {
      headers[ApiConstants.authorizationHeader] = '${ApiConstants.bearerPrefix}$token';
    }

    return Options(
      headers: headers,
      method: options?.method,
      sendTimeout: options?.sendTimeout,
      receiveTimeout: options?.receiveTimeout,
      extra: options?.extra,
      followRedirects: options?.followRedirects,
      listFormat: options?.listFormat,
      maxRedirects: options?.maxRedirects,
      persistentConnection: options?.persistentConnection,
      receiveDataWhenStatusError: options?.receiveDataWhenStatusError,
      requestEncoder: options?.requestEncoder,
      responseDecoder: options?.responseDecoder,
      responseType: options?.responseType,
      validateStatus: options?.validateStatus,
    );
  }

  /// Realiza requisição GET
  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onReceiveProgress,
  }) async {
    await _checkConnectivity();

    try {
      final requestOptions = await _getRequestOptions(options: options);
      
      return await _dio.get<T>(
        path,
        queryParameters: queryParameters,
        options: requestOptions,
        cancelToken: cancelToken,
        onReceiveProgress: onReceiveProgress,
      );
    } on DioException catch (e) {
      throw _handleDioException(e);
    }
  }

  /// Realiza requisição POST
  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    await _checkConnectivity();

    try {
      final requestOptions = await _getRequestOptions(options: options);
      
      return await _dio.post<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: requestOptions,
        cancelToken: cancelToken,
        onSendProgress: onSendProgress,
        onReceiveProgress: onReceiveProgress,
      );
    } on DioException catch (e) {
      throw _handleDioException(e);
    }
  }

  /// Realiza requisição PUT
  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    await _checkConnectivity();

    try {
      final requestOptions = await _getRequestOptions(options: options);
      
      return await _dio.put<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: requestOptions,
        cancelToken: cancelToken,
        onSendProgress: onSendProgress,
        onReceiveProgress: onReceiveProgress,
      );
    } on DioException catch (e) {
      throw _handleDioException(e);
    }
  }

  /// Realiza requisição DELETE
  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
  }) async {
    await _checkConnectivity();

    try {
      final requestOptions = await _getRequestOptions(options: options);
      
      return await _dio.delete<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: requestOptions,
        cancelToken: cancelToken,
      );
    } on DioException catch (e) {
      throw _handleDioException(e);
    }
  }

  /// Realiza requisição PATCH
  Future<Response<T>> patch<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    await _checkConnectivity();

    try {
      final requestOptions = await _getRequestOptions(options: options);
      
      return await _dio.patch<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: requestOptions,
        cancelToken: cancelToken,
        onSendProgress: onSendProgress,
        onReceiveProgress: onReceiveProgress,
      );
    } on DioException catch (e) {
      throw _handleDioException(e);
    }
  }

  /// Converte DioException em exceções customizadas da aplicação
  Exception _handleDioException(DioException dioException) {
    switch (dioException.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const NetworkException('Timeout na conexão');
      
      case DioExceptionType.badResponse:
        final statusCode = dioException.response?.statusCode;
        final message = dioException.response?.data?['message'] ?? 
                      'Erro no servidor';
        
        switch (statusCode) {
          case ApiConstants.statusUnauthorized:
            return const ServerException('Token inválido ou expirado');
          case ApiConstants.statusForbidden:
            return const ServerException('Acesso negado');
          case ApiConstants.statusNotFound:
            return const ServerException('Recurso não encontrado');
          case ApiConstants.statusInternalServerError:
            return const ServerException('Erro interno do servidor');
          default:
            return ServerException(message);
        }
      
      case DioExceptionType.cancel:
        return const NetworkException('Requisição cancelada');
      
      case DioExceptionType.connectionError:
        return const NetworkException('Erro de conexão');
      
      case DioExceptionType.badCertificate:
        return const NetworkException('Certificado SSL inválido');
      
      case DioExceptionType.unknown:
        return NetworkException(
          dioException.message ?? 'Erro desconhecido na rede'
        );
    }
  }

  /// Limpa todos os interceptadores
  void clearInterceptors() {
    _dio.interceptors.clear();
  }

  /// Fecha o cliente e limpa recursos
  void dispose() {
    _dio.close();
  }
}