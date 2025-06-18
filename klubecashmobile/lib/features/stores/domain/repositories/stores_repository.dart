// lib/features/stores/domain/repositories/stores_repository.dart
// Interface do repositório de lojas parceiras

import 'package:dartz/dartz.dart';
import '../entities/store.dart';
import '../entities/store_category.dart';
import '../../../../core/errors/failures.dart';

/// Modelo para filtros de busca de lojas
class StoreFilters {
  final String? searchQuery;
  final StoreCategory? category;
  final String? status;
  final double? minCashbackPercentage;
  final double? maxCashbackPercentage;
  final bool? isFavoriteOnly;
  final bool? isNewOnly;
  final String? sortBy; // 'name', 'cashback', 'rating', 'newest'
  final String? sortOrder; // 'asc', 'desc'

  const StoreFilters({
    this.searchQuery,
    this.category,
    this.status,
    this.minCashbackPercentage,
    this.maxCashbackPercentage,
    this.isFavoriteOnly,
    this.isNewOnly,
    this.sortBy,
    this.sortOrder,
  });

  /// Cria uma cópia dos filtros com campos atualizados
  StoreFilters copyWith({
    String? searchQuery,
    StoreCategory? category,
    String? status,
    double? minCashbackPercentage,
    double? maxCashbackPercentage,
    bool? isFavoriteOnly,
    bool? isNewOnly,
    String? sortBy,
    String? sortOrder,
  }) {
    return StoreFilters(
      searchQuery: searchQuery ?? this.searchQuery,
      category: category ?? this.category,
      status: status ?? this.status,
      minCashbackPercentage: minCashbackPercentage ?? this.minCashbackPercentage,
      maxCashbackPercentage: maxCashbackPercentage ?? this.maxCashbackPercentage,
      isFavoriteOnly: isFavoriteOnly ?? this.isFavoriteOnly,
      isNewOnly: isNewOnly ?? this.isNewOnly,
      sortBy: sortBy ?? this.sortBy,
      sortOrder: sortOrder ?? this.sortOrder,
    );
  }

  /// Remove todos os filtros
  StoreFilters clear() {
    return const StoreFilters();
  }

  /// Verifica se há filtros aplicados
  bool get hasActiveFilters =>
      searchQuery != null ||
      category != null ||
      status != null ||
      minCashbackPercentage != null ||
      maxCashbackPercentage != null ||
      isFavoriteOnly == true ||
      isNewOnly == true;
}

/// Modelo para resultado paginado de lojas
class StoreSearchResult {
  final List<Store> stores;
  final List<StoreCategory> availableCategories;
  final int total;
  final int currentPage;
  final int totalPages;
  final bool hasNextPage;
  final bool hasPreviousPage;

  const StoreSearchResult({
    required this.stores,
    required this.availableCategories,
    required this.total,
    required this.currentPage,
    required this.totalPages,
    required this.hasNextPage,
    required this.hasPreviousPage,
  });
}

/// Modelo para estatísticas de lojas
class StoreStatistics {
  final int totalPartnerStores;
  final int totalActiveStores;
  final double averageCashbackPercentage;
  final int totalCategoriesWithStores;
  final List<StoreCategory> topCategories;
  final List<Store> featuredStores;
  final List<Store> newStores;

  const StoreStatistics({
    required this.totalPartnerStores,
    required this.totalActiveStores,
    required this.averageCashbackPercentage,
    required this.totalCategoriesWithStores,
    required this.topCategories,
    required this.featuredStores,
    required this.newStores,
  });
}

/// Interface do repositório de lojas parceiras
///
/// Define os contratos para operações relacionadas às lojas
/// parceiras do sistema Klube Cash
abstract class StoresRepository {
  /// Obtém lista de lojas parceiras com filtros e paginação
  ///
  /// [filters] - Filtros de busca (opcional)
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 20)
  ///
  /// Retorna [StoreSearchResult] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> getPartnerStores({
    StoreFilters? filters,
    int page = 1,
    int limit = 20,
  });

  /// Busca lojas por texto
  ///
  /// [query] - Texto de busca
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 20)
  ///
  /// Retorna lista de lojas encontradas ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> searchStores({
    required String query,
    int page = 1,
    int limit = 20,
  });

  /// Obtém detalhes de uma loja específica
  ///
  /// [storeId] - ID da loja
  ///
  /// Retorna [Store] com detalhes completos ou [Failure] em caso de erro
  Future<Either<Failure, Store>> getStoreDetails({
    required String storeId,
  });

  /// Obtém lojas por categoria
  ///
  /// [category] - Categoria desejada
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 20)
  ///
  /// Retorna lista de lojas da categoria ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> getStoresByCategory({
    required StoreCategory category,
    int page = 1,
    int limit = 20,
  });

  /// Obtém todas as categorias disponíveis
  ///
  /// Retorna lista de categorias com contador de lojas ou [Failure] em caso de erro
  Future<Either<Failure, List<StoreCategory>>> getStoreCategories();

  /// Adiciona loja aos favoritos
  ///
  /// [storeId] - ID da loja
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> addToFavorites({
    required String storeId,
  });

  /// Remove loja dos favoritos
  ///
  /// [storeId] - ID da loja
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> removeFromFavorites({
    required String storeId,
  });

  /// Alterna status de favorito da loja
  ///
  /// [storeId] - ID da loja
  /// [isFavorite] - Se deve ser favoritada ou não
  ///
  /// Retorna [bool] indicando novo status ou [Failure] em caso de erro
  Future<Either<Failure, bool>> toggleFavorite({
    required String storeId,
    required bool isFavorite,
  });

  /// Obtém lojas favoritas do usuário
  ///
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 20)
  ///
  /// Retorna lista de lojas favoritas ou [Failure] em caso de erro
  Future<Either<Failure, StoreSearchResult>> getFavoriteStores({
    int page = 1,
    int limit = 20,
  });

  /// Obtém lojas recém-parceiras
  ///
  /// [limit] - Quantidade de itens (padrão: 10)
  ///
  /// Retorna lista de lojas novas ou [Failure] em caso de erro
  Future<Either<Failure, List<Store>>> getNewPartnerStores({
    int limit = 10,
  });

  /// Obtém lojas em destaque
  ///
  /// [limit] - Quantidade de itens (padrão: 5)
  ///
  /// Retorna lista de lojas em destaque ou [Failure] em caso de erro
  Future<Either<Failure, List<Store>>> getFeaturedStores({
    int limit = 5,
  });

  /// Obtém lojas com maior percentual de cashback
  ///
  /// [limit] - Quantidade de itens (padrão: 10)
  ///
  /// Retorna lista de lojas ordenadas por cashback ou [Failure] em caso de erro
  Future<Either<Failure, List<Store>>> getTopCashbackStores({
    int limit = 10,
  });

  /// Obtém estatísticas gerais das lojas
  ///
  /// Retorna [StoreStatistics] com dados consolidados ou [Failure] em caso de erro
  Future<Either<Failure, StoreStatistics>> getStoreStatistics();

  /// Atualiza dados das lojas (cache refresh)
  ///
  /// Força uma atualização dos dados do servidor
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> refreshStores();

  /// Obtém lojas próximas (baseado em localização)
  ///
  /// [latitude] - Latitude da localização atual
  /// [longitude] - Longitude da localização atual
  /// [radiusKm] - Raio de busca em quilômetros (padrão: 10)
  /// [limit] - Quantidade de itens (padrão: 20)
  ///
  /// Retorna lista de lojas próximas ou [Failure] em caso de erro
  Future<Either<Failure, List<Store>>> getNearbyStores({
    required double latitude,
    required double longitude,
    double radiusKm = 10.0,
    int limit = 20,
  });

  /// Reporta problema com uma loja
  ///
  /// [storeId] - ID da loja
  /// [problemType] - Tipo do problema
  /// [description] - Descrição do problema
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> reportStoreProblem({
    required String storeId,
    required String problemType,
    required String description,
  });

  /// Avalia uma loja
  ///
  /// [storeId] - ID da loja
  /// [rating] - Nota de 1 a 5
  /// [comment] - Comentário opcional
  ///
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> rateStore({
    required String storeId,
    required double rating,
    String? comment,
  });

  /// Obtém avaliações de uma loja
  ///
  /// [storeId] - ID da loja
  /// [page] - Número da página (padrão: 1)
  /// [limit] - Quantidade de itens por página (padrão: 10)
  ///
  /// Retorna lista de avaliações ou [Failure] em caso de erro
  Future<Either<Failure, Map<String, dynamic>>> getStoreReviews({
    required String storeId,
    int page = 1,
    int limit = 10,
  });
}