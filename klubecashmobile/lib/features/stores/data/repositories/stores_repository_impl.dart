// lib/features/stores/data/repositories/stores_repository_impl.dart
// Arquivo: Implementação do Repository de Stores - Camada Data

import 'package:dartz/dartz.dart';

import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';
import '../../domain/entities/store.dart';
import '../../domain/entities/store_category.dart';
import '../../domain/repositories/stores_repository.dart';
import '../datasources/stores_remote_datasource.dart';
import '../models/store_model.dart';
import '../models/store_category_model.dart';

/// Implementação concreta do repositório de lojas
/// 
/// Responsável por coordenar operações entre o datasource remoto
/// e a camada de domínio, convertendo models em entities e 
/// exceptions em failures apropriadas.
class StoresRepositoryImpl implements StoresRepository {
  final StoresRemoteDataSource _remoteDataSource;
  final NetworkInfo _networkInfo;

  StoresRepositoryImpl({
    required StoresRemoteDataSource remoteDataSource,
    required NetworkInfo networkInfo,
  })  : _remoteDataSource = remoteDataSource,
        _networkInfo = networkInfo;

  @override
  Future<Either<Failure, StoresResult>> getPartnerStores({
    String? category,
    String? searchTerm,
    int page = 1,
    int limit = 10,
    String? sortBy,
    String? sortOrder,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getPartnerStores(
        category: category,
        searchTerm: searchTerm,
        page: page,
        limit: limit,
        sortBy: sortBy,
        sortOrder: sortOrder,
      );

      // Parse dos dados de resposta
      final data = result['data'] as Map<String, dynamic>? ?? {};
      
      // Converte stores
      final storesData = data['lojas'] as List<dynamic>? ?? [];
      final stores = StoreModel
          .fromJsonList(storesData)
          .map((model) => model as Store)
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

      // Parse das categorias se disponíveis
      final categoriesData = data['categorias'] as List<dynamic>? ?? [];
      final categories = categoriesData
          .map((category) => StoreCategoryModel.fromJson({
                'id': categoriesData.indexOf(category) + 1,
                'nome': category.toString(),
                'slug': _generateSlug(category.toString()),
              }))
          .cast<StoreCategory>()
          .toList();

      final storesResult = StoresResult(
        stores: stores,
        pagination: pagination,
        categories: categories,
        totalStores: pagination.totalItems,
      );

      return Right(storesResult);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar lojas parceiras: $e'));
    }
  }

  @override
  Future<Either<Failure, Store>> getStoreDetails(int storeId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final storeModel = await _remoteDataSource.getStoreDetails(storeId);
      return Right(storeModel as Store);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar detalhes da loja: $e'));
    }
  }

  @override
  Future<Either<Failure, StoresResult>> getStoresByCategory(
    String categorySlug, {
    int page = 1,
    int limit = 10,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getStoresByCategory(
        categorySlug,
        page: page,
        limit: limit,
      );

      return _mapToStoresResult(result, page, limit);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar lojas por categoria: $e'));
    }
  }

  @override
  Future<Either<Failure, StoresResult>> searchStores(
    String searchTerm, {
    String? category,
    int page = 1,
    int limit = 10,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.searchStores(
        searchTerm,
        category: category,
        page: page,
        limit: limit,
      );

      return _mapToStoresResult(result, page, limit);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao pesquisar lojas: $e'));
    }
  }

  @override
  Future<Either<Failure, List<StoreCategory>>> getStoreCategories() async {
    try {
      final categoriesModels = await _remoteDataSource.getStoreCategories();
      final categories = categoriesModels
          .map((model) => model as StoreCategory)
          .toList();
      
      return Right(categories);
    } on ServerException catch (e) {
      // Em caso de erro, retornar categorias padrão
      final defaultCategories = StoreCategoryModel.getDefaultCategories()
          .map((model) => model as StoreCategory)
          .toList();
      return Right(defaultCategories);
    } on CacheException catch (e) {
      // Em caso de erro, retornar categorias padrão
      final defaultCategories = StoreCategoryModel.getDefaultCategories()
          .map((model) => model as StoreCategory)
          .toList();
      return Right(defaultCategories);
    } catch (e) {
      // Em caso de erro, retornar categorias padrão
      final defaultCategories = StoreCategoryModel.getDefaultCategories()
          .map((model) => model as StoreCategory)
          .toList();
      return Right(defaultCategories);
    }
  }

  @override
  Future<Either<Failure, bool>> favoriteStore(int storeId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.favoriteStore(storeId);
      return Right(result['status'] == true);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao favoritar loja: $e'));
    }
  }

  @override
  Future<Either<Failure, bool>> unfavoriteStore(int storeId) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.unfavoriteStore(storeId);
      return Right(result['status'] == true);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao remover loja dos favoritos: $e'));
    }
  }

  @override
  Future<Either<Failure, StoresResult>> getFavoriteStores({
    int page = 1,
    int limit = 10,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getFavoriteStores(
        page: page,
        limit: limit,
      );

      return _mapToStoresResult(result, page, limit);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar lojas favoritas: $e'));
    }
  }

  @override
  Future<Either<Failure, bool>> isStoreFavorite(int storeId) async {
    try {
      final isFavorite = await _remoteDataSource.isStoreFavorite(storeId);
      return Right(isFavorite);
    } catch (e) {
      // Em caso de erro, considerar como não favoritado
      return const Right(false);
    }
  }

  @override
  Future<Either<Failure, Map<String, dynamic>>> getStoreStatistics(
    int storeId,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getStoreStatistics(storeId);
      final data = result['data'] as Map<String, dynamic>? ?? {};
      return Right(data);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar estatísticas da loja: $e'));
    }
  }

  @override
  Future<Either<Failure, StoreReviewsResult>> getStoreReviews(
    int storeId, {
    int page = 1,
    int limit = 10,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getStoreReviews(
        storeId,
        page: page,
        limit: limit,
      );

      final data = result['data'] as Map<String, dynamic>? ?? {};
      
      // Parse das avaliações
      final reviewsData = data['reviews'] as List<dynamic>? ?? [];
      final reviews = reviewsData
          .map((review) => StoreReview.fromJson(review as Map<String, dynamic>))
          .toList();
      
      // Parse da paginação
      final paginationData = data['pagination'] as Map<String, dynamic>? ?? {};
      final pagination = PaginationInfo(
        currentPage: paginationData['pagina_atual'] as int? ?? page,
        totalPages: paginationData['total_paginas'] as int? ?? 1,
        totalItems: paginationData['total'] as int? ?? 0,
        itemsPerPage: paginationData['por_pagina'] as int? ?? limit,
        hasNextPage: (paginationData['pagina_atual'] as int? ?? page) < 
            (paginationData['total_paginas'] as int? ?? 1),
        hasPreviousPage: (paginationData['pagina_atual'] as int? ?? page) > 1,
      );

      // Parse do resumo das avaliações
      final summaryData = data['summary'] as Map<String, dynamic>? ?? {};
      final reviewsSummary = StoreReviewsSummary(
        averageRating: _parseDouble(summaryData['media_avaliacao'] ?? summaryData['average_rating']),
        totalReviews: summaryData['total_avaliacoes'] as int? ?? 0,
        ratingDistribution: Map<int, int>.from(summaryData['distribuicao'] ?? {}),
      );

      final reviewsResult = StoreReviewsResult(
        reviews: reviews,
        pagination: pagination,
        summary: reviewsSummary,
      );

      return Right(reviewsResult);
    } on ServerException catch (e) {
      return Left(ServerFailure(
        e.message,
        statusCode: e.statusCode,
        code: e.code,
      ));
    } on CacheException catch (e) {
      return Left(CacheFailure(e.message, code: e.code));
    } catch (e) {
      return Left(UnknownFailure('Erro inesperado ao buscar avaliações da loja: $e'));
    }
  }

  /// Mapeia resultado genérico para StoresResult
  Either<Failure, StoresResult> _mapToStoresResult(
    Map<String, dynamic> result,
    int page,
    int limit,
  ) {
    try {
      final data = result['data'] as Map<String, dynamic>? ?? {};
      
      // Converte stores
      final storesData = data['lojas'] as List<dynamic>? ?? [];
      final stores = StoreModel
          .fromJsonList(storesData)
          .map((model) => model as Store)
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

      final storesResult = StoresResult(
        stores: stores,
        pagination: pagination,
        categories: [],
        totalStores: pagination.totalItems,
      );

      return Right(storesResult);
    } catch (e) {
      return Left(UnknownFailure('Erro ao processar dados das lojas: $e'));
    }
  }

  /// Parse seguro de double
  double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  /// Gera slug a partir de string
  String _generateSlug(String text) {
    return text
        .toLowerCase()
        .replaceAll(RegExp(r'[áâãàä]'), 'a')
        .replaceAll(RegExp(r'[éêë]'), 'e')
        .replaceAll(RegExp(r'[íîï]'), 'i')
        .replaceAll(RegExp(r'[óôõö]'), 'o')
        .replaceAll(RegExp(r'[úûü]'), 'u')
        .replaceAll(RegExp(r'[ç]'), 'c')
        .replaceAll(RegExp(r'[^a-z0-9\s]'), '')
        .replaceAll(RegExp(r'\s+'), '-')
        .trim();
  }
}

/// Classe para resultado de busca de lojas
class StoresResult {
  final List<Store> stores;
  final PaginationInfo pagination;
  final List<StoreCategory> categories;
  final int totalStores;

  const StoresResult({
    required this.stores,
    required this.pagination,
    required this.categories,
    required this.totalStores,
  });
}

/// Classe para informações de paginação
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

/// Classe para avaliação de loja
class StoreReview {
  final int id;
  final String userName;
  final double rating;
  final String comment;
  final DateTime createdAt;

  const StoreReview({
    required this.id,
    required this.userName,
    required this.rating,
    required this.comment,
    required this.createdAt,
  });

  factory StoreReview.fromJson(Map<String, dynamic> json) {
    return StoreReview(
      id: json['id'] as int? ?? 0,
      userName: json['nome_usuario'] ?? json['user_name'] ?? '',
      rating: _parseDouble(json['avaliacao'] ?? json['rating']),
      comment: json['comentario'] ?? json['comment'] ?? '',
      createdAt: DateTime.tryParse(json['data_criacao'] ?? json['created_at'] ?? '') ?? DateTime.now(),
    );
  }

  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}

/// Classe para resumo das avaliações
class StoreReviewsSummary {
  final double averageRating;
  final int totalReviews;
  final Map<int, int> ratingDistribution;

  const StoreReviewsSummary({
    required this.averageRating,
    required this.totalReviews,
    required this.ratingDistribution,
  });
}

/// Classe para resultado de avaliações
class StoreReviewsResult {
  final List<StoreReview> reviews;
  final PaginationInfo pagination;
  final StoreReviewsSummary summary;

  const StoreReviewsResult({
    required this.reviews,
    required this.pagination,
    required this.summary,
  });
}