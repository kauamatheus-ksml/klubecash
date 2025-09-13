// lib/features/notifications/domain/usecases/get_notifications_usecase.dart
// Arquivo: Use Case para obter notificações do usuário com filtros e paginação

import 'package:dartz/dartz.dart';
import '../entities/notification.dart';
import '../repositories/notifications_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter notificações do usuário
/// 
/// Implementa a lógica de negócio para recuperar notificações
/// com filtros opcionais, paginação e validações
class GetNotificationsUseCase {
  final NotificationsRepository repository;

  const GetNotificationsUseCase(this.repository);

  /// Executa a obtenção das notificações
  /// 
  /// [params] - Parâmetros opcionais para filtros e paginação
  /// 
  /// Retorna [NotificationResult] em caso de sucesso 
  /// ou [Failure] em caso de erro
  Future<Either<Failure, NotificationResult>> call([
    GetNotificationsParams? params,
  ]) async {
    // Usar valores padrão se parâmetros não fornecidos
    final page = params?.page ?? 1;
    final limit = params?.limit ?? 20;
    final filters = params?.filters;

    // Validações básicas
    if (page < 1) {
      return Left(ValidationFailure('Página deve ser maior que zero'));
    }

    if (limit < 1 || limit > 100) {
      return Left(ValidationFailure('Limite deve estar entre 1 e 100'));
    }

    // Validar filtros se fornecidos
    if (filters != null) {
      final filterValidation = _validateFilters(filters);
      if (filterValidation != null) {
        return Left(ValidationFailure(filterValidation));
      }
    }

    // Chamar repositório para obter notificações
    return await repository.getNotifications(
      filters: filters,
      page: page,
      limit: limit,
    );
  }

  /// Valida os filtros fornecidos
  String? _validateFilters(NotificationFilters filters) {
    // Validar intervalo de datas se fornecido
    if (filters.startDate != null && filters.endDate != null) {
      if (filters.startDate!.isAfter(filters.endDate!)) {
        return 'Data inicial deve ser anterior à data final';
      }

      // Verificar se o intervalo não é muito grande (opcional)
      final difference = filters.endDate!.difference(filters.startDate!);
      if (difference.inDays > 365) {
        return 'Intervalo de datas não pode ser maior que 1 ano';
      }
    }

    // Validar query de busca se fornecida
    if (filters.searchQuery != null && filters.searchQuery!.isNotEmpty) {
      final cleanQuery = filters.searchQuery!.trim();
      if (cleanQuery.length < 2) {
        return 'Termo de busca deve ter pelo menos 2 caracteres';
      }
      if (cleanQuery.length > 100) {
        return 'Termo de busca deve ter no máximo 100 caracteres';
      }
    }

    return null;
  }
}

/// Parâmetros para obtenção de notificações
class GetNotificationsParams {
  final NotificationFilters? filters;
  final int page;
  final int limit;

  const GetNotificationsParams({
    this.filters,
    this.page = 1,
    this.limit = 20,
  });

  /// Cria uma cópia dos parâmetros com campos atualizados
  GetNotificationsParams copyWith({
    NotificationFilters? filters,
    int? page,
    int? limit,
  }) {
    return GetNotificationsParams(
      filters: filters ?? this.filters,
      page: page ?? this.page,
      limit: limit ?? this.limit,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is GetNotificationsParams &&
        other.filters == filters &&
        other.page == page &&
        other.limit == limit;
  }

  @override
  int get hashCode => Object.hash(filters, page, limit);

  @override
  String toString() => 'GetNotificationsParams('
      'filters: $filters, '
      'page: $page, '
      'limit: $limit)';
}

/// Use case para obter apenas notificações não lidas
class GetUnreadNotificationsUseCase {
  final NotificationsRepository repository;

  const GetUnreadNotificationsUseCase(this.repository);

  /// Executa a obtenção das notificações não lidas
  /// 
  /// [params] - Parâmetros opcionais para paginação
  /// 
  /// Retorna [NotificationResult] com apenas notificações não lidas
  Future<Either<Failure, NotificationResult>> call([
    GetUnreadNotificationsParams? params,
  ]) async {
    final page = params?.page ?? 1;
    final limit = params?.limit ?? 20;

    // Criar filtros para apenas notificações não lidas
    const filters = NotificationFilters(isReadFilter: false);

    // Usar o use case principal
    final getNotificationsUseCase = GetNotificationsUseCase(repository);
    return await getNotificationsUseCase.call(
      GetNotificationsParams(
        filters: filters,
        page: page,
        limit: limit,
      ),
    );
  }
}

/// Parâmetros para obtenção de notificações não lidas
class GetUnreadNotificationsParams {
  final int page;
  final int limit;

  const GetUnreadNotificationsParams({
    this.page = 1,
    this.limit = 20,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is GetUnreadNotificationsParams &&
        other.page == page &&
        other.limit == limit;
  }

  @override
  int get hashCode => Object.hash(page, limit);

  @override
  String toString() => 'GetUnreadNotificationsParams('
      'page: $page, '
      'limit: $limit)';
}