// lib/features/stores/domain/usecases/search_stores_usecase.dart
// Use case para busca de lojas por texto

import 'package:dartz/dartz.dart';
import '../entities/store.dart';
import '../entities/store_category.dart';
import '../repositories/stores_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por buscar lojas por texto
/// 
/// Implementa a lógica de negócio para busca textual de lojas,
/// incluindo validações, sanitização e filtros adicionais
class SearchStoresUseCase {
  final StoresRepository repository;

  const SearchStoresUseCase(this.repository);

  /// Executa a busca de lojas por texto
  ///
  /// [params] - Parâmetros de busca incluindo termo e filtros
  /// 
  /// Retorna [StoreSearchResult] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> call(SearchStoresParams params) async {
    // Validações básicas
    final validationResult = _validateParams(params);
    if (validationResult != null) {
      return Left(ValidationFailure(validationResult));
    }

    // Sanitizar texto de busca
    final sanitizedQuery = _sanitizeSearchQuery(params.query);

    // Verificar se após sanitização ainda há texto válido
    if (sanitizedQuery.isEmpty) {
      return Left(ValidationFailure('Termo de busca inválido após limpeza'));
    }

    // Verificar se é um termo muito comum (opcional - pode ser removido se não desejado)
    if (_isCommonTerm(sanitizedQuery)) {
      return Left(ValidationFailure(
        'Termo muito genérico. Seja mais específico na busca',
      ));
    }

    // Chamar repositório para realizar busca
    return await repository.searchStores(
      query: sanitizedQuery,
      page: params.page,
      limit: params.limit,
    );
  }

  /// Valida os parâmetros de busca
  String? _validateParams(SearchStoresParams params) {
    // Validar texto de busca
    if (params.query.isEmpty) {
      return 'Termo de busca é obrigatório';
    }

    if (params.query.length < 2) {
      return 'Termo de busca deve ter pelo menos 2 caracteres';
    }

    if (params.query.length > 100) {
      return 'Termo de busca deve ter no máximo 100 caracteres';
    }

    // Validar se não é apenas espaços
    if (params.query.trim().isEmpty) {
      return 'Termo de busca não pode ser apenas espaços';
    }

    // Validar paginação
    if (params.page < 1) {
      return 'Número da página deve ser maior que zero';
    }

    if (params.limit < 1 || params.limit > 100) {
      return 'Limite deve estar entre 1 e 100 itens';
    }

    // Validar categoria se fornecida
    if (params.category != null && params.category!.id.isEmpty) {
      return 'ID da categoria não pode estar vazio';
    }

    return null; // Todos os parâmetros são válidos
  }

  /// Sanitiza o texto de busca removendo caracteres inválidos
  String _sanitizeSearchQuery(String query) {
    // Remover espaços extras no início e fim
    String sanitized = query.trim();

    // Remover múltiplos espaços consecutivos
    sanitized = sanitized.replaceAll(RegExp(r'\s+'), ' ');

    // Remover caracteres especiais perigosos mas manter acentos
    sanitized = sanitized.replaceAll(RegExp(r'[<>"\';\\]'), '');

    // Remover caracteres de controle
    sanitized = sanitized.replaceAll(RegExp(r'[\x00-\x1F\x7F]'), '');

    return sanitized;
  }

  /// Verifica se o termo é muito comum/genérico
  bool _isCommonTerm(String query) {
    final lowerQuery = query.toLowerCase();
    
    // Lista de termos muito genéricos
    const commonTerms = [
      'a', 'o', 'e', 'de', 'do', 'da', 'em', 'um', 'uma', 'para',
      'loja', 'store', 'shop', 'compra', 'venda',
    ];

    return commonTerms.contains(lowerQuery);
  }
}

/// Parâmetros para busca de lojas
class SearchStoresParams {
  final String query;
  final StoreCategory? category;
  final int page;
  final int limit;
  final String? sortBy;
  final String? sortOrder;
  final bool? onlyFavorites;
  final bool? onlyNewStores;
  final double? minCashbackPercentage;

  const SearchStoresParams({
    required this.query,
    this.category,
    this.page = 1,
    this.limit = 20,
    this.sortBy,
    this.sortOrder,
    this.onlyFavorites,
    this.onlyNewStores,
    this.minCashbackPercentage,
  });

  /// Construtor para busca simples apenas com texto
  const SearchStoresParams.simple({
    required String query,
    int page = 1,
    int limit = 20,
  }) : this(
          query: query,
          page: page,
          limit: limit,
        );

  /// Construtor para busca com categoria específica
  const SearchStoresParams.withCategory({
    required String query,
    required StoreCategory category,
    int page = 1,
    int limit = 20,
  }) : this(
          query: query,
          category: category,
          page: page,
          limit: limit,
        );

  /// Construtor para busca com ordenação específica
  const SearchStoresParams.withSort({
    required String query,
    required String sortBy,
    String sortOrder = 'asc',
    int page = 1,
    int limit = 20,
  }) : this(
          query: query,
          sortBy: sortBy,
          sortOrder: sortOrder,
          page: page,
          limit: limit,
        );

  /// Construtor para busca apenas em favoritas
  const SearchStoresParams.favoritesOnly({
    required String query,
    int page = 1,
    int limit = 20,
  }) : this(
          query: query,
          onlyFavorites: true,
          page: page,
          limit: limit,
        );

  /// Construtor para busca com cashback mínimo
  const SearchStoresParams.withMinCashback({
    required String query,
    required double minCashbackPercentage,
    int page = 1,
    int limit = 20,
  }) : this(
          query: query,
          minCashbackPercentage: minCashbackPercentage,
          page: page,
          limit: limit,
        );

  /// Cria uma cópia dos parâmetros com campos atualizados
  SearchStoresParams copyWith({
    String? query,
    StoreCategory? category,
    int? page,
    int? limit,
    String? sortBy,
    String? sortOrder,
    bool? onlyFavorites,
    bool? onlyNewStores,
    double? minCashbackPercentage,
  }) {
    return SearchStoresParams(
      query: query ?? this.query,
      category: category ?? this.category,
      page: page ?? this.page,
      limit: limit ?? this.limit,
      sortBy: sortBy ?? this.sortBy,
      sortOrder: sortOrder ?? this.sortOrder,
      onlyFavorites: onlyFavorites ?? this.onlyFavorites,
      onlyNewStores: onlyNewStores ?? this.onlyNewStores,
      minCashbackPercentage: minCashbackPercentage ?? this.minCashbackPercentage,
    );
  }

  /// Navega para próxima página mantendo outros parâmetros
  SearchStoresParams nextPage() {
    return copyWith(page: page + 1);
  }

  /// Navega para página anterior mantendo outros parâmetros
  SearchStoresParams previousPage() {
    return copyWith(page: page > 1 ? page - 1 : 1);
  }

  /// Retorna para primeira página mantendo outros parâmetros
  SearchStoresParams firstPage() {
    return copyWith(page: 1);
  }

  /// Remove todos os filtros mantendo apenas a busca
  SearchStoresParams clearFilters() {
    return SearchStoresParams.simple(
      query: query,
      page: 1,
      limit: limit,
    );
  }

  /// Converte para StoreFilters para uso no repositório
  StoreFilters toStoreFilters() {
    return StoreFilters(
      searchQuery: query,
      category: category,
      isFavoriteOnly: onlyFavorites,
      isNewOnly: onlyNewStores,
      minCashbackPercentage: minCashbackPercentage,
      sortBy: sortBy,
      sortOrder: sortOrder,
    );
  }

  /// Verifica se há filtros aplicados além da busca básica
  bool get hasAdditionalFilters =>
      category != null ||
      onlyFavorites == true ||
      onlyNewStores == true ||
      minCashbackPercentage != null ||
      sortBy != null;

  /// Retorna uma versão simplificada da busca para exibição
  String get displayQuery => query.length > 30 ? '${query.substring(0, 30)}...' : query;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is SearchStoresParams &&
        other.query == query &&
        other.category == category &&
        other.page == page &&
        other.limit == limit &&
        other.sortBy == sortBy &&
        other.sortOrder == sortOrder &&
        other.onlyFavorites == onlyFavorites &&
        other.onlyNewStores == onlyNewStores &&
        other.minCashbackPercentage == minCashbackPercentage;
  }

  @override
  int get hashCode {
    return Object.hash(
      query,
      category,
      page,
      limit,
      sortBy,
      sortOrder,
      onlyFavorites,
      onlyNewStores,
      minCashbackPercentage,
    );
  }

  @override
  String toString() {
    return 'SearchStoresParams(query: "$displayQuery", page: $page, limit: $limit)';
  }
}