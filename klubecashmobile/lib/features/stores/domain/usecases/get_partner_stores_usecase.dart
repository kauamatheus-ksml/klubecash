// lib/features/stores/domain/usecases/get_partner_stores_usecase.dart
// Use case para obter lista de lojas parceiras

import 'package:dartz/dartz.dart';
import '../entities/store.dart';
import '../entities/store_category.dart';
import '../repositories/stores_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter lista de lojas parceiras
/// 
/// Implementa a lógica de negócio para buscar lojas parceiras
/// com filtros, paginação e validações adequadas
class GetPartnerStoresUseCase {
  final StoresRepository repository;

  const GetPartnerStoresUseCase(this.repository);

  /// Executa a busca de lojas parceiras
  ///
  /// [params] - Parâmetros opcionais para filtros e paginação
  /// 
  /// Retorna [StoreSearchResult] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> call([
    GetPartnerStoresParams? params,
  ]) async {
    final filters = params?.filters;
    final page = params?.page ?? 1;
    final limit = params?.limit ?? 20;

    // Validações básicas
    if (page < 1) {
      return Left(ValidationFailure('Número da página deve ser maior que zero'));
    }

    if (limit < 1 || limit > 100) {
      return Left(ValidationFailure('Limite deve estar entre 1 e 100 itens'));
    }

    // Validar filtros se fornecidos
    if (filters != null) {
      final filterValidation = _validateFilters(filters);
      if (filterValidation != null) {
        return Left(ValidationFailure(filterValidation));
      }
    }

    // Chamar repositório para buscar lojas
    return await repository.getPartnerStores(
      filters: filters,
      page: page,
      limit: limit,
    );
  }

  /// Valida os filtros fornecidos
  String? _validateFilters(StoreFilters filters) {
    // Validar texto de busca
    if (filters.searchQuery != null) {
      if (filters.searchQuery!.isEmpty) {
        return 'Texto de busca não pode estar vazio';
      }
      if (filters.searchQuery!.length < 2) {
        return 'Texto de busca deve ter pelo menos 2 caracteres';
      }
      if (filters.searchQuery!.length > 100) {
        return 'Texto de busca deve ter no máximo 100 caracteres';
      }
    }

    // Validar percentuais de cashback
    if (filters.minCashbackPercentage != null) {
      if (filters.minCashbackPercentage! < 0 || filters.minCashbackPercentage! > 100) {
        return 'Percentual mínimo de cashback deve estar entre 0% e 100%';
      }
    }

    if (filters.maxCashbackPercentage != null) {
      if (filters.maxCashbackPercentage! < 0 || filters.maxCashbackPercentage! > 100) {
        return 'Percentual máximo de cashback deve estar entre 0% e 100%';
      }
    }

    // Validar se percentual mínimo é menor que máximo
    if (filters.minCashbackPercentage != null && 
        filters.maxCashbackPercentage != null) {
      if (filters.minCashbackPercentage! > filters.maxCashbackPercentage!) {
        return 'Percentual mínimo deve ser menor que o máximo';
      }
    }

    // Validar ordenação
    if (filters.sortBy != null) {
      const validSortOptions = ['name', 'cashback', 'rating', 'newest', 'popular'];
      if (!validSortOptions.contains(filters.sortBy)) {
        return 'Opção de ordenação inválida';
      }
    }

    if (filters.sortOrder != null) {
      const validOrderOptions = ['asc', 'desc'];
      if (!validOrderOptions.contains(filters.sortOrder)) {
        return 'Direção de ordenação inválida (use "asc" ou "desc")';
      }
    }

    // Validar status
    if (filters.status != null) {
      const validStatuses = ['aprovado', 'pendente', 'rejeitado'];
      if (!validStatuses.contains(filters.status)) {
        return 'Status de loja inválido';
      }
    }

    return null; // Todos os filtros são válidos
  }
}

/// Parâmetros para busca de lojas parceiras
class GetPartnerStoresParams {
  final StoreFilters? filters;
  final int page;
  final int limit;

  const GetPartnerStoresParams({
    this.filters,
    this.page = 1,
    this.limit = 20,
  });

  /// Cria parâmetros apenas com filtro de categoria
  GetPartnerStoresParams.byCategory({
    required StoreCategory category,
    int page = 1,
    int limit = 20,
  }) : this(
          filters: StoreFilters(category: category),
          page: page,
          limit: limit,
        );

  /// Cria parâmetros apenas com texto de busca
  GetPartnerStoresParams.search({
    required String searchQuery,
    int page = 1,
    int limit = 20,
  }) : this(
          filters: StoreFilters(searchQuery: searchQuery),
          page: page,
          limit: limit,
        );

  /// Cria parâmetros para lojas favoritas
  GetPartnerStoresParams.favorites({
    int page = 1,
    int limit = 20,
  }) : this(
          filters: const StoreFilters(isFavoriteOnly: true),
          page: page,
          limit: limit,
        );

  /// Cria parâmetros para lojas novas
  GetPartnerStoresParams.newStores({
    int page = 1,
    int limit = 20,
  }) : this(
          filters: const StoreFilters(isNewOnly: true),
          page: page,
          limit: limit,
        );

  /// Cria parâmetros com filtro de cashback mínimo
  GetPartnerStoresParams.withMinCashback({
    required double minCashbackPercentage,
    int page = 1,
    int limit = 20,
  }) : this(
          filters: StoreFilters(minCashbackPercentage: minCashbackPercentage),
          page: page,
          limit: limit,
        );

  /// Cria uma cópia dos parâmetros com campos atualizados
  GetPartnerStoresParams copyWith({
    StoreFilters? filters,
    int? page,
    int? limit,
  }) {
    return GetPartnerStoresParams(
      filters: filters ?? this.filters,
      page: page ?? this.page,
      limit: limit ?? this.limit,
    );
  }

  /// Atualiza apenas a página mantendo outros parâmetros
  GetPartnerStoresParams nextPage() {
    return copyWith(page: page + 1);
  }

  /// Volta para página anterior mantendo outros parâmetros
  GetPartnerStoresParams previousPage() {
    return copyWith(page: page > 1 ? page - 1 : 1);
  }

  /// Retorna para primeira página mantendo outros parâmetros
  GetPartnerStoresParams firstPage() {
    return copyWith(page: 1);
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is GetPartnerStoresParams &&
        other.filters == filters &&
        other.page == page &&
        other.limit == limit;
  }

  @override
  int get hashCode {
    return Object.hash(filters, page, limit);
  }

  @override
  String toString() {
    return 'GetPartnerStoresParams(filters: $filters, page: $page, limit: $limit)';
  }
}