// lib/features/notifications/data/repositories/notifications_repository_impl.dart
// ARQUIVO #118 - NotificationsRepositoryImpl - Implementação do repositório de notificações

import 'package:dartz/dartz.dart';

import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';
import '../../domain/entities/notification.dart';
import '../../domain/repositories/notifications_repository.dart';
import '../datasources/notifications_remote_datasource.dart';
import '../models/notification_model.dart';

/// Implementação concreta do repositório de notificações
/// 
/// Responsável por coordenar operações entre o datasource remoto
/// e a camada de domínio, convertendo models em entities e 
/// exceptions em failures apropriadas.
class NotificationsRepositoryImpl implements NotificationsRepository {
  final NotificationsRemoteDataSource _remoteDataSource;
  final NetworkInfo _networkInfo;

  NotificationsRepositoryImpl({
    required NotificationsRemoteDataSource remoteDataSource,
    required NetworkInfo networkInfo,
  })  : _remoteDataSource = remoteDataSource,
        _networkInfo = networkInfo;

  @override
  Future<Either<Failure, NotificationsResult>> getNotifications({
    int page = 1,
    int limit = 20,
    bool? isRead,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getNotifications(
        page: page,
        limit: limit,
        isRead: isRead,
      );

      // Parse dos dados de resposta
      final data = result['data'] as Map<String, dynamic>? ?? {};
      
      // Converte notifications
      final notificationsData = data['notificacoes'] as List<dynamic>? ?? [];
      final notifications = NotificationModel
          .fromJsonList(notificationsData)
          .map((model) => model as Notification)
          .toList();
      
      // Parse da paginação
      final paginationData = data['paginacao'] as Map<String, dynamic>? ?? {};
      final pagination = PaginationInfo(
        currentPage: paginationData['pagina_atual'] as int? ?? page,
        totalPages: paginationData['total_paginas'] as int? ?? 1,
        totalItems: paginationData['total'] as int? ?? 0,
        itemsPerPage: paginationData['por_pagina'] as int? ?? limit,
        hasNextPage: (paginationData['pagina_atual'] as int? ?? page) < 
            (paginationData['total_paginas'] as int? ?? 1),
        hasPreviousPage: (paginationData['pagina_atual'] as int? ?? page) > 1,
      );

      // Parse do resumo se disponível
      final summaryData = data['resumo'] as Map<String, dynamic>? ?? {};
      final summary = NotificationsSummary(
        total: summaryData['total'] as int? ?? 0,
        unread: summaryData['nao_lidas'] as int? ?? 0,
        read: summaryData['lidas'] as int? ?? 0,
        recent: summaryData['recentes'] as int? ?? 0,
      );

      return Right(NotificationsResult(
        notifications: notifications,
        pagination: pagination,
        summary: summary,
      ));
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao buscar notificações'));
    }
  }

  @override
  Future<Either<Failure, bool>> markAsRead(int notificationId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.markAsRead(notificationId);
      
      if (result['status'] == true) {
        return const Right(true);
      } else {
        return Left(ServerFailure(
          result['message'] ?? 'Erro ao marcar notificação como lida'
        ));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao marcar notificação como lida'));
    }
  }

  @override
  Future<Either<Failure, bool>> markAllAsRead() async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.markAllAsRead();
      
      if (result['status'] == true) {
        return const Right(true);
      } else {
        return Left(ServerFailure(
          result['message'] ?? 'Erro ao marcar todas as notificações como lidas'
        ));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao marcar todas as notificações como lidas'));
    }
  }

  @override
  Future<Either<Failure, int>> getUnreadCount() async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getUnreadCount();
      
      if (result['status'] == true) {
        final count = result['data']?['count'] as int? ?? 0;
        return Right(count);
      } else {
        return Left(ServerFailure(
          result['message'] ?? 'Erro ao buscar contagem de notificações não lidas'
        ));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao buscar contagem de notificações não lidas'));
    }
  }

  @override
  Future<Either<Failure, bool>> deleteNotification(int notificationId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.deleteNotification(notificationId);
      
      if (result['status'] == true) {
        return const Right(true);
      } else {
        return Left(ServerFailure(
          result['message'] ?? 'Erro ao deletar notificação'
        ));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao deletar notificação'));
    }
  }

  @override
  Future<Either<Failure, Notification>> getNotificationDetails(int notificationId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final notificationModel = await _remoteDataSource.getNotificationDetails(notificationId);
      return Right(notificationModel as Notification);
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao buscar detalhes da notificação'));
    }
  }

  /// Converte ServerException para Failure apropriada
  Failure _mapServerExceptionToFailure(ServerException exception) {
    switch (exception.statusCode) {
      case 400:
        return ValidationFailure(exception.message);
      case 401:
        return AuthFailure('Sessão expirada. Faça login novamente.');
      case 403:
        return AuthFailure('Acesso negado para esta operação.');
      case 404:
        return NotFoundFailure('Notificação não encontrada.');
      case 422:
        return ValidationFailure(exception.message);
      case 500:
      case 502:
      case 503:
        return ServerFailure('Erro interno do servidor. Tente novamente.');
      default:
        return ServerFailure(exception.message);
    }
  }
}

/// Classe auxiliar para resultado de notificações com paginação
class NotificationsResult {
  final List<Notification> notifications;
  final PaginationInfo pagination;
  final NotificationsSummary summary;

  const NotificationsResult({
    required this.notifications,
    required this.pagination,
    required this.summary,
  });
}

/// Classe auxiliar para resumo de notificações
class NotificationsSummary {
  final int total;
  final int unread;
  final int read;
  final int recent;

  const NotificationsSummary({
    required this.total,
    required this.unread,
    required this.read,
    required this.recent,
  });
}

/// Informações de paginação
class PaginationInfo {
  final int currentPage;
  final int totalPages;
  final int totalItems;
  final int itemsPerPage;
  final bool hasNextPage;
  final bool hasPreviousPage;

  const PaginationInfo({
    required this.currentPage,
    required this.totalPages,
    required this.totalItems,
    required this.itemsPerPage,
    required this.hasNextPage,
    required this.hasPreviousPage,
  });
}