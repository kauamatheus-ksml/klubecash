// lib/features/notifications/domain/repositories/notifications_repository.dart
// Arquivo: Interface do Repositório de Notificações - Define contratos para operações de notificações

import 'package:dartz/dartz.dart';
import '../entities/notification.dart';
import '../../../../core/errors/failures.dart';

/// Modelo para filtros de notificações
class NotificationFilters {
  final NotificationType? type;
  final bool? isReadFilter; // null = todas, true = apenas lidas, false = apenas não lidas
  final DateTime? startDate;
  final DateTime? endDate;
  final String? searchQuery;

  const NotificationFilters({
    this.type,
    this.isReadFilter,
    this.startDate,
    this.endDate,
    this.searchQuery,
  });

  /// Cria uma cópia dos filtros com campos atualizados
  NotificationFilters copyWith({
    NotificationType? type,
    bool? isReadFilter,
    DateTime? startDate,
    DateTime? endDate,
    String? searchQuery,
  }) {
    return NotificationFilters(
      type: type ?? this.type,
      isReadFilter: isReadFilter ?? this.isReadFilter,
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      searchQuery: searchQuery ?? this.searchQuery,
    );
  }

  /// Remove todos os filtros
  NotificationFilters clear() {
    return const NotificationFilters();
  }

  /// Verifica se há filtros aplicados
  bool get hasActiveFilters =>
      type != null ||
      isReadFilter != null ||
      startDate != null ||
      endDate != null ||
      (searchQuery != null && searchQuery!.isNotEmpty);
}

/// Modelo para resultado paginado de notificações
class NotificationResult {
  final List<Notification> notifications;
  final int total;
  final int currentPage;
  final int totalPages;
  final bool hasNextPage;
  final bool hasPreviousPage;
  final int unreadCount;

  const NotificationResult({
    required this.notifications,
    required this.total,
    required this.currentPage,
    required this.totalPages,
    required this.hasNextPage,
    required this.hasPreviousPage,
    required this.unreadCount,
  });
}

/// Modelo para estatísticas de notificações
class NotificationStatistics {
  final int totalNotifications;
  final int unreadCount;
  final int readCount;
  final Map<NotificationType, int> countByType;
  final int todayCount;
  final int weekCount;
  final int monthCount;

  const NotificationStatistics({
    required this.totalNotifications,
    required this.unreadCount,
    required this.readCount,
    required this.countByType,
    required this.todayCount,
    required this.weekCount,
    required this.monthCount,
  });
}

/// Interface do repositório de notificações
///
/// Define os contratos para operações relacionadas às notificações
/// do sistema Klube Cash que serão implementados na camada de dados
abstract class NotificationsRepository {
  /// Obtém lista de notificações com filtros e paginação
  ///
  /// [filters] - Filtros de busca (opcional)
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 20)
  ///
  /// Retorna [NotificationResult] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, NotificationResult>> getNotifications({
    NotificationFilters? filters,
    int page = 1,
    int limit = 20,
  });

  /// Obtém uma notificação específica pelo ID
  ///
  /// [notificationId] - ID da notificação
  ///
  /// Retorna [Notification] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Notification>> getNotificationById({
    required String notificationId,
  });

  /// Marca uma notificação como lida
  ///
  /// [notificationId] - ID da notificação
  ///
  /// Retorna [Notification] atualizada em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Notification>> markAsRead({
    required String notificationId,
  });

  /// Marca uma notificação como não lida
  ///
  /// [notificationId] - ID da notificação
  ///
  /// Retorna [Notification] atualizada em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Notification>> markAsUnread({
    required String notificationId,
  });

  /// Marca todas as notificações como lidas
  ///
  /// [notificationIds] - Lista de IDs das notificações (opcional, se não fornecida marca todas)
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> markAllAsRead({
    List<String>? notificationIds,
  });

  /// Obtém a quantidade de notificações não lidas
  ///
  /// Retorna [int] com a contagem ou [Failure] em caso de erro
  Future<Either<Failure, int>> getUnreadCount();

  /// Obtém estatísticas das notificações
  ///
  /// [period] - Período para análise (opcional)
  ///
  /// Retorna [NotificationStatistics] ou [Failure] em caso de erro
  Future<Either<Failure, NotificationStatistics>> getNotificationStatistics({
    String? period,
  });

  /// Remove uma notificação
  ///
  /// [notificationId] - ID da notificação
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> deleteNotification({
    required String notificationId,
  });

  /// Remove múltiplas notificações
  ///
  /// [notificationIds] - Lista de IDs das notificações
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> deleteMultipleNotifications({
    required List<String> notificationIds,
  });

  /// Remove todas as notificações lidas
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> deleteAllReadNotifications();

  /// Atualiza as configurações de notificações do usuário
  ///
  /// [settings] - Configurações de notificações
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> updateNotificationSettings({
    required Map<String, dynamic> settings,
  });

  /// Obtém as configurações de notificações do usuário
  ///
  /// Retorna [Map] com as configurações ou [Failure] em caso de erro
  Future<Either<Failure, Map<String, dynamic>>> getNotificationSettings();
}