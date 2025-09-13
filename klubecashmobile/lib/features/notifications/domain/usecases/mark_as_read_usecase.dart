// lib/features/notifications/domain/usecases/mark_as_read_usecase.dart
// Arquivo: Use Case para marcar notificações como lidas no sistema Klube Cash

import 'package:dartz/dartz.dart';
import '../entities/notification.dart';
import '../repositories/notifications_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por marcar uma notificação como lida
/// 
/// Implementa a lógica de negócio para marcar notificações específicas
/// como lidas, incluindo validações e atualizações de estado
class MarkAsReadUseCase {
  final NotificationsRepository repository;

  const MarkAsReadUseCase(this.repository);

  /// Executa a marcação da notificação como lida
  /// 
  /// [params] - Parâmetros incluindo ID da notificação
  /// 
  /// Retorna [Notification] atualizada em caso de sucesso 
  /// ou [Failure] em caso de erro
  Future<Either<Failure, Notification>> call(MarkAsReadParams params) async {
    // Validações básicas
    if (params.notificationId.isEmpty) {
      return Left(ValidationFailure('ID da notificação é obrigatório'));
    }

    if (params.notificationId.trim().isEmpty) {
      return Left(ValidationFailure('ID da notificação inválido'));
    }

    // Sanitizar ID
    final cleanId = params.notificationId.trim();

    // Chamar repositório para marcar como lida
    return await repository.markAsRead(notificationId: cleanId);
  }
}

/// Parâmetros para marcar notificação como lida
class MarkAsReadParams {
  final String notificationId;

  const MarkAsReadParams({
    required this.notificationId,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is MarkAsReadParams && 
        other.notificationId == notificationId;
  }

  @override
  int get hashCode => notificationId.hashCode;

  @override
  String toString() => 'MarkAsReadParams(notificationId: $notificationId)';
}

/// Use case para marcar múltiplas notificações como lidas
class MarkMultipleAsReadUseCase {
  final NotificationsRepository repository;

  const MarkMultipleAsReadUseCase(this.repository);

  /// Executa a marcação de múltiplas notificações como lidas
  /// 
  /// [params] - Parâmetros incluindo lista de IDs
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> call(MarkMultipleAsReadParams params) async {
    // Validações básicas
    if (params.notificationIds.isEmpty) {
      return Left(ValidationFailure('Lista de notificações não pode estar vazia'));
    }

    if (params.notificationIds.length > 100) {
      return Left(ValidationFailure('Máximo de 100 notificações por operação'));
    }

    // Validar cada ID
    for (final id in params.notificationIds) {
      if (id.trim().isEmpty) {
        return Left(ValidationFailure('ID de notificação inválido encontrado'));
      }
    }

    // Sanitizar IDs
    final cleanIds = params.notificationIds
        .map((id) => id.trim())
        .where((id) => id.isNotEmpty)
        .toList();

    if (cleanIds.isEmpty) {
      return Left(ValidationFailure('Nenhum ID válido após limpeza'));
    }

    // Marcar cada notificação como lida individualmente
    for (final id in cleanIds) {
      final result = await repository.markAsRead(notificationId: id);
      if (result.isLeft()) {
        // Se uma falhar, retornar o erro
        return result.fold(
          (failure) => Left(failure),
          (_) => throw StateError('Não deveria chegar aqui'),
        );
      }
    }

    return const Right(null);
  }
}

/// Parâmetros para marcar múltiplas notificações como lidas
class MarkMultipleAsReadParams {
  final List<String> notificationIds;

  const MarkMultipleAsReadParams({
    required this.notificationIds,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is MarkMultipleAsReadParams && 
        _listEquals(other.notificationIds, notificationIds);
  }

  @override
  int get hashCode => Object.hashAll(notificationIds);

  @override
  String toString() => 'MarkMultipleAsReadParams(count: ${notificationIds.length})';

  /// Compara duas listas
  bool _listEquals<T>(List<T>? a, List<T>? b) {
    if (a == null) return b == null;
    if (b == null || a.length != b.length) return false;
    for (int index = 0; index < a.length; index += 1) {
      if (a[index] != b[index]) return false;
    }
    return true;
  }
}

/// Use case para marcar todas as notificações como lidas
class MarkAllAsReadUseCase {
  final NotificationsRepository repository;

  const MarkAllAsReadUseCase(this.repository);

  /// Executa a marcação de todas as notificações como lidas
  /// 
  /// [params] - Parâmetros opcionais para filtros específicos
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> call([MarkAllAsReadParams? params]) async {
    // Chamar repositório para marcar todas como lidas
    return await repository.markAllAsRead(
      notificationIds: params?.specificIds,
    );
  }
}

/// Parâmetros opcionais para marcar todas as notificações como lidas
class MarkAllAsReadParams {
  final List<String>? specificIds;

  const MarkAllAsReadParams({
    this.specificIds,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is MarkAllAsReadParams && 
        _listEquals(other.specificIds, specificIds);
  }

  @override
  int get hashCode => specificIds?.hashCode ?? 0;

  @override
  String toString() => 'MarkAllAsReadParams(specificIds: ${specificIds?.length})';

  /// Compara duas listas
  bool _listEquals<T>(List<T>? a, List<T>? b) {
    if (a == null) return b == null;
    if (b == null || a.length != b.length) return false;
    for (int index = 0; index < a.length; index += 1) {
      if (a[index] != b[index]) return false;
    }
    return true;
  }
}

/// Use case para marcar notificação como não lida
class MarkAsUnreadUseCase {
  final NotificationsRepository repository;

  const MarkAsUnreadUseCase(this.repository);

  /// Executa a marcação da notificação como não lida
  /// 
  /// [params] - Parâmetros incluindo ID da notificação
  /// 
  /// Retorna [Notification] atualizada em caso de sucesso 
  /// ou [Failure] em caso de erro
  Future<Either<Failure, Notification>> call(MarkAsUnreadParams params) async {
    // Validações básicas
    if (params.notificationId.isEmpty) {
      return Left(ValidationFailure('ID da notificação é obrigatório'));
    }

    if (params.notificationId.trim().isEmpty) {
      return Left(ValidationFailure('ID da notificação inválido'));
    }

    // Sanitizar ID
    final cleanId = params.notificationId.trim();

    // Chamar repositório para marcar como não lida
    return await repository.markAsUnread(notificationId: cleanId);
  }
}

/// Parâmetros para marcar notificação como não lida
class MarkAsUnreadParams {
  final String notificationId;

  const MarkAsUnreadParams({
    required this.notificationId,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is MarkAsUnreadParams && 
        other.notificationId == notificationId;
  }

  @override
  int get hashCode => notificationId.hashCode;

  @override
  String toString() => 'MarkAsUnreadParams(notificationId: $notificationId)';
}