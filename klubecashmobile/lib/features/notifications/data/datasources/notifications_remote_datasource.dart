// lib/features/notifications/data/datasources/notifications_remote_datasource.dart
// ARQUIVO #117 - NotificationsRemoteDataSource - Fonte de dados remota para notificações

import 'package:dio/dio.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/notification_model.dart';

/// Interface do datasource remoto de notificações
abstract class NotificationsRemoteDataSource {
  /// Obtém notificações do usuário
  Future<Map<String, dynamic>> getNotifications({
    int page = 1,
    int limit = 20,
    bool? isRead,
  });

  /// Marca notificação como lida
  Future<Map<String, dynamic>> markAsRead(int notificationId);

  /// Marca todas as notificações como lidas
  Future<Map<String, dynamic>> markAllAsRead();

  /// Obtém contagem de notificações não lidas
  Future<Map<String, dynamic>> getUnreadCount();

  /// Deleta uma notificação
  Future<Map<String, dynamic>> deleteNotification(int notificationId);

  /// Obtém detalhes de uma notificação específica
  Future<NotificationModel> getNotificationDetails(int notificationId);
}

/// Implementação do datasource remoto de notificações
class NotificationsRemoteDataSourceImpl implements NotificationsRemoteDataSource {
  final ApiClient _apiClient;

  NotificationsRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<Map<String, dynamic>> getNotifications({
    int page = 1,
    int limit = 20,
    bool? isRead,
  }) async {
    try {
      final response = await _apiClient.get(
        ApiConstants.notificationsEndpoint,
        queryParameters: {
          'page': page,
          'limit': limit,
          if (isRead != null) 'lida': isRead ? 1 : 0,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true) {
          return data;
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar notificações',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar notificações: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> markAsRead(int notificationId) async {
    try {
      final response = await _apiClient.put(
        '${ApiConstants.notificationsEndpoint}/$notificationId/read',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true) {
          return data;
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao marcar notificação como lida',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao marcar notificação como lida: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> markAllAsRead() async {
    try {
      final response = await _apiClient.put(
        '${ApiConstants.notificationsEndpoint}/read-all',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true) {
          return data;
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao marcar todas as notificações como lidas',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao marcar todas as notificações como lidas: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getUnreadCount() async {
    try {
      final response = await _apiClient.get(
        '${ApiConstants.notificationsEndpoint}/unread-count',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true) {
          return data;
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar contagem de notificações não lidas',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar contagem de notificações não lidas: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> deleteNotification(int notificationId) async {
    try {
      final response = await _apiClient.delete(
        '${ApiConstants.notificationsEndpoint}/$notificationId',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true) {
          return data;
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao deletar notificação',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao deletar notificação: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<NotificationModel> getNotificationDetails(int notificationId) async {
    try {
      final response = await _apiClient.get(
        '${ApiConstants.notificationsEndpoint}/$notificationId',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true && data['data'] != null) {
          return NotificationModel.fromJson(data['data']);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar detalhes da notificação',
            statusCode: 400,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar detalhes da notificação: $e',
        statusCode: 500,
      );
    }
  }

  /// Valida se a resposta da API é válida
  Map<String, dynamic> _validateResponse(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      return responseData;
    } else {
      throw ServerException(
        message: 'Formato de resposta inválido',
        statusCode: 500,
      );
    }
  }

  /// Trata erros do Dio e converte para ServerException
  ServerException _handleDioException(DioException dioException) {
    switch (dioException.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return ServerException(
          message: 'Tempo limite excedido. Verifique sua conexão.',
          statusCode: 408,
        );

      case DioExceptionType.badResponse:
        final statusCode = dioException.response?.statusCode ?? 500;
        String message = 'Erro no servidor';

        if (dioException.response?.data is Map<String, dynamic>) {
          final data = dioException.response!.data as Map<String, dynamic>;
          message = data['message'] ?? data['error'] ?? message;
        }

        return ServerException(
          message: message,
          statusCode: statusCode,
        );

      case DioExceptionType.cancel:
        return ServerException(
          message: 'Requisição cancelada',
          statusCode: 499,
        );

      case DioExceptionType.connectionError:
        return ServerException(
          message: 'Erro de conexão. Verifique sua internet.',
          statusCode: 503,
        );

      default:
        return ServerException(
          message: 'Erro de rede: ${dioException.message}',
          statusCode: 500,
        );
    }
  }
}