// lib/features/notifications/presentation/providers/notifications_provider.dart
// 🔔 Notifications Provider - Gerenciamento de estado das notificações usando Riverpod

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../domain/entities/notification.dart';
import '../../domain/usecases/get_notifications_usecase.dart';
import '../../domain/usecases/mark_as_read_usecase.dart';
import '../../data/repositories/notifications_repository_impl.dart';
import '../../data/datasources/notifications_remote_datasource.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/network_info.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/usecases/usecase.dart';

part 'notifications_provider.g.dart';

// ==================== STATE CLASSES ====================

/// Estado das notificações do usuário
class NotificationsState {
  final bool isLoading;
  final bool isRefreshing;
  final bool isMarkingAsRead;
  final List<AppNotification> notifications;
  final List<AppNotification> unreadNotifications;
  final String? errorMessage;
  final DateTime? lastUpdate;

  const NotificationsState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.isMarkingAsRead = false,
    this.notifications = const [],
    this.unreadNotifications = const [],
    this.errorMessage,
    this.lastUpdate,
  });

  NotificationsState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    bool? isMarkingAsRead,
    List<AppNotification>? notifications,
    List<AppNotification>? unreadNotifications,
    String? errorMessage,
    DateTime? lastUpdate,
  }) {
    return NotificationsState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      isMarkingAsRead: isMarkingAsRead ?? this.isMarkingAsRead,
      notifications: notifications ?? this.notifications,
      unreadNotifications: unreadNotifications ?? this.unreadNotifications,
      errorMessage: errorMessage,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }

  /// Verifica se há notificações carregadas
  bool get hasNotifications => notifications.isNotEmpty;

  /// Verifica se há erro
  bool get hasError => errorMessage != null;

  /// Verifica se há notificações não lidas
  bool get hasUnreadNotifications => unreadNotifications.isNotEmpty;

  /// Conta de notificações não lidas
  int get unreadCount => unreadNotifications.length;

  /// Verifica se está em estado inicial
  bool get isInitial => !isLoading && !hasNotifications && !hasError;
}

// ==================== DEPENDENCY PROVIDERS ====================

/// Provider para o repositório de notificações
@riverpod
NotificationsRepositoryImpl notificationsRepository(
  NotificationsRepositoryRef ref,
) {
  final apiClient = ref.watch(apiClientProvider);
  final networkInfo = ref.watch(networkInfoProvider);
  final dataSource = NotificationsRemoteDataSource(apiClient);
  return NotificationsRepositoryImpl(
    remoteDataSource: dataSource,
    networkInfo: networkInfo,
  );
}

/// Provider para use cases de notificações
@riverpod
GetNotificationsUsecase getNotificationsUsecase(
  GetNotificationsUsecaseRef ref,
) {
  final repository = ref.watch(notificationsRepositoryProvider);
  return GetNotificationsUsecase(repository);
}

@riverpod
MarkAsReadUsecase markAsReadUsecase(MarkAsReadUsecaseRef ref) {
  final repository = ref.watch(notificationsRepositoryProvider);
  return MarkAsReadUsecase(repository);
}

// ==================== MAIN PROVIDER ====================

/// Provider principal das notificações
@riverpod
class NotificationsNotifier extends _$NotificationsNotifier {
  @override
  NotificationsState build() {
    // Carrega as notificações iniciais
    loadNotifications();
    return const NotificationsState(isLoading: true);
  }

  /// Carrega todas as notificações do usuário
  Future<void> loadNotifications({bool showOnly50 = false}) async {
    if (state.isRefreshing) return;

    state = state.copyWith(
      isLoading: state.isInitial,
      isRefreshing: !state.isInitial,
      errorMessage: null,
    );

    try {
      final getNotificationsUsecase = ref.read(getNotificationsUsecaseProvider);
      final result = await getNotificationsUsecase.call(
        GetNotificationsParams(limit: showOnly50 ? 50 : null),
      );

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (notifications) {
          final unreadNotifications = notifications
              .where((notification) => !notification.isRead)
              .toList();

          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            notifications: notifications,
            unreadNotifications: unreadNotifications,
            lastUpdate: DateTime.now(),
            errorMessage: null,
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isRefreshing: false,
        errorMessage: 'Erro inesperado ao carregar notificações',
      );
    }
  }

  /// Marca uma notificação como lida
  Future<bool> markNotificationAsRead(String notificationId) async {
    if (state.isMarkingAsRead) return false;

    state = state.copyWith(isMarkingAsRead: true);

    try {
      final markAsReadUsecase = ref.read(markAsReadUsecaseProvider);
      final result = await markAsReadUsecase.call(
        MarkAsReadParams(notificationId: notificationId),
      );

      bool success = false;
      result.fold(
        (failure) {
          state = state.copyWith(
            isMarkingAsRead: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (isMarked) {
          if (isMarked) {
            // Atualiza as listas de notificações localmente
            final updatedNotifications = state.notifications.map((notification) {
              if (notification.id == notificationId) {
                return notification.copyWith(isRead: true, readAt: DateTime.now());
              }
              return notification;
            }).toList();

            final updatedUnreadNotifications = state.unreadNotifications
                .where((notification) => notification.id != notificationId)
                .toList();

            state = state.copyWith(
              isMarkingAsRead: false,
              notifications: updatedNotifications,
              unreadNotifications: updatedUnreadNotifications,
              errorMessage: null,
            );
            success = true;
          }
        },
      );

      return success;
    } catch (e) {
      state = state.copyWith(
        isMarkingAsRead: false,
        errorMessage: 'Erro inesperado ao marcar notificação',
      );
      return false;
    }
  }

  /// Marca todas as notificações como lidas
  Future<bool> markAllAsRead() async {
    if (state.isMarkingAsRead || state.unreadNotifications.isEmpty) return false;

    bool allMarked = true;
    
    for (final notification in state.unreadNotifications) {
      final success = await markNotificationAsRead(notification.id);
      if (!success) {
        allMarked = false;
      }
    }

    return allMarked;
  }

  /// Atualiza as notificações (pull to refresh)
  Future<void> refreshNotifications() async {
    await loadNotifications();
  }

  /// Limpa os erros
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }

  /// Converte failures em mensagens de erro amigáveis
  String _getFailureMessage(Failure failure) {
    switch (failure.runtimeType) {
      case ServerFailure:
        return 'Erro no servidor. Tente novamente.';
      case NetworkFailure:
        return 'Sem conexão com a internet.';
      case CacheFailure:
        return 'Erro ao carregar dados salvos.';
      case ValidationFailure:
        return 'Dados inválidos.';
      default:
        return 'Erro inesperado. Tente novamente.';
    }
  }
}

// ==================== HELPER PROVIDERS ====================

/// Provider para contagem de notificações não lidas
@riverpod
int unreadNotificationsCount(UnreadNotificationsCountRef ref) {
  final notificationsState = ref.watch(notificationsNotifierProvider);
  return notificationsState.unreadCount;
}

/// Provider para verificar se há notificações não lidas
@riverpod
bool hasUnreadNotifications(HasUnreadNotificationsRef ref) {
  final notificationsState = ref.watch(notificationsNotifierProvider);
  return notificationsState.hasUnreadNotifications;
}

// ==================== PARAMS CLASSES ====================

/// Parâmetros para buscar notificações
class GetNotificationsParams {
  final int? limit;
  final bool? onlyUnread;

  const GetNotificationsParams({
    this.limit,
    this.onlyUnread,
  });
}

/// Parâmetros para marcar como lida
class MarkAsReadParams {
  final String notificationId;

  const MarkAsReadParams({
    required this.notificationId,
  });
}