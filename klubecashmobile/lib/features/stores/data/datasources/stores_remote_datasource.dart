// lib/features/stores/data/datasources/stores_remote_datasource.dart
// Arquivo: Stores Remote DataSource - Fonte de dados remota para lojas - Camada Data

import 'package:dio/dio.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/store_model.dart';
import '../models/store_category_model.dart';

/// Interface abstrata para o datasource remoto de lojas
abstract class StoresRemoteDataSource {
  /// Busca lojas parceiras com filtros
  Future<Map<String, dynamic>> getPartnerStores({
    String? category,
    String? searchTerm,
    int page = 1,
    int limit = 10,
    String? sortBy,
    String? sortOrder,
  });

  /// Busca detalhes de uma loja específica
  Future<StoreModel> getStoreDetails(int storeId);

  /// Busca lojas por categoria
  Future<Map<String, dynamic>> getStoresByCategory(
    String categorySlug, {
    int page = 1,
    int limit = 10,
  });

  /// Pesquisa lojas por termo
  Future<Map<String, dynamic>> searchStores(
    String searchTerm, {
    String? category,
    int page = 1,
    int limit = 10,
  });

  /// Obtém categorias de lojas disponíveis
  Future<List<StoreCategoryModel>> getStoreCategories();

  /// Adiciona loja aos favoritos
  Future<Map<String, dynamic>> favoriteStore(int storeId);

  /// Remove loja dos favoritos
  Future<Map<String, dynamic>> unfavoriteStore(int storeId);

  /// Obtém lista de lojas favoritas do usuário
  Future<Map<String, dynamic>> getFavoriteStores({
    int page = 1,
    int limit = 10,
  });

  /// Verifica se uma loja está nos favoritos
  Future<bool> isStoreFavorite(int storeId);

  /// Obtém estatísticas de uma loja
  Future<Map<String, dynamic>> getStoreStatistics(int storeId);

  /// Obtém avaliações de uma loja
  Future<Map<String, dynamic>> getStoreReviews(
    int storeId, {
    int page = 1,
    int limit = 10,
  });
}

/// Implementação do datasource remoto de lojas
class StoresRemoteDataSourceImpl implements StoresRemoteDataSource {
  final ApiClient _apiClient;

  const StoresRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<Map<String, dynamic>> getPartnerStores({
    String? category,
    String? searchTerm,
    int page = 1,
    int limit = 10,
    String? sortBy,
    String? sortOrder,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'limit': limit,
      };

      // Adicionar filtros opcionais
      if (category != null && category.isNotEmpty) {
        queryParams['categoria'] = category;
      }
      if (searchTerm != null && searchTerm.isNotEmpty) {
        queryParams['busca'] = searchTerm;
      }
      if (sortBy != null && sortBy.isNotEmpty) {
        queryParams['sort_by'] = sortBy;
      }
      if (sortOrder != null && sortOrder.isNotEmpty) {
        queryParams['sort_order'] = sortOrder;
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.partnerStoresEndpoint,
        queryParameters: queryParams,
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar lojas parceiras: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<StoreModel> getStoreDetails(int storeId) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.storeDetailsEndpoint}/$storeId',
      );

      final data = _validateResponse(response);
      
      if (data['data'] != null) {
        return StoreModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw ServerException(
          message: 'Dados da loja não encontrados',
          statusCode: 404,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar detalhes da loja: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getStoresByCategory(
    String categorySlug, {
    int page = 1,
    int limit = 10,
  }) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.partnerStoresEndpoint,
        queryParameters: {
          'categoria': categorySlug,
          'page': page,
          'limit': limit,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar lojas por categoria: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> searchStores(
    String searchTerm, {
    String? category,
    int page = 1,
    int limit = 10,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'busca': searchTerm,
        'page': page,
        'limit': limit,
      };

      if (category != null && category.isNotEmpty) {
        queryParams['categoria'] = category;
      }

      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.partnerStoresEndpoint,
        queryParameters: queryParams,
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao pesquisar lojas: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<List<StoreCategoryModel>> getStoreCategories() async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.storesEndpoint,
        queryParameters: {'action': 'categories'},
      );

      final data = _validateResponse(response);
      
      if (data['data'] != null) {
        final categoriesData = data['data'] as List<dynamic>;
        return categoriesData
            .map((category) => StoreCategoryModel.fromJson({
                  'id': categoriesData.indexOf(category) + 1,
                  'nome': category.toString(),
                  'slug': _generateSlug(category.toString()),
                }))
            .toList();
      } else {
        // Retornar categorias padrão se não houver dados da API
        return StoreCategoryModel.getDefaultCategories();
      }
    } on DioException catch (e) {
      // Em caso de erro, retornar categorias padrão
      return StoreCategoryModel.getDefaultCategories();
    } catch (e) {
      // Em caso de erro, retornar categorias padrão
      return StoreCategoryModel.getDefaultCategories();
    }
  }

  @override
  Future<Map<String, dynamic>> favoriteStore(int storeId) async {
    try {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '/api/favorites',
        data: {
          'store_id': storeId,
          'action': 'add',
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao favoritar loja: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> unfavoriteStore(int storeId) async {
    try {
      final response = await _apiClient.post<Map<String, dynamic>>(
        '/api/favorites',
        data: {
          'store_id': storeId,
          'action': 'remove',
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao remover loja dos favoritos: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getFavoriteStores({
    int page = 1,
    int limit = 10,
  }) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/api/favorites',
        queryParameters: {
          'type': 'stores',
          'page': page,
          'limit': limit,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar lojas favoritas: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<bool> isStoreFavorite(int storeId) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '/api/favorites/check',
        queryParameters: {
          'store_id': storeId,
        },
      );

      final data = _validateResponse(response);
      return data['is_favorite'] == true;
    } on DioException catch (e) {
      // Se der erro, considerar como não favoritado
      return false;
    } catch (e) {
      // Se der erro, considerar como não favoritado
      return false;
    }
  }

  @override
  Future<Map<String, dynamic>> getStoreStatistics(int storeId) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.storeDetailsEndpoint}/$storeId/statistics',
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar estatísticas da loja: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getStoreReviews(
    int storeId, {
    int page = 1,
    int limit = 10,
  }) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.storeDetailsEndpoint}/$storeId/reviews',
        queryParameters: {
          'page': page,
          'limit': limit,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar avaliações da loja: $e',
        statusCode: 500,
      );
    }
  }

  /// Valida a resposta da API
  Map<String, dynamic> _validateResponse(Response<Map<String, dynamic>> response) {
    if (response.statusCode == 200 && response.data != null) {
      final data = response.data!;
      
      // Verificar se a API retornou status de sucesso
      if (data['status'] == true) {
        return data;
      } else {
        throw ServerException(
          message: data['message'] ?? 'Erro na resposta da API',
          statusCode: response.statusCode ?? 500,
        );
      }
    } else {
      throw ServerException(
        message: 'Erro na resposta do servidor',
        statusCode: response.statusCode ?? 500,
      );
    }
  }

  /// Trata exceções do Dio
  ServerException _handleDioException(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const ServerException(
          message: 'Tempo limite de conexão excedido',
          statusCode: 408,
        );
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode ?? 500;
        final message = e.response?.data?['message'] ?? 'Erro no servidor';
        return ServerException(
          message: message,
          statusCode: statusCode,
        );
      case DioExceptionType.cancel:
        return const ServerException(
          message: 'Requisição cancelada',
          statusCode: 499,
        );
      case DioExceptionType.connectionError:
        return const ServerException(
          message: 'Erro de conexão com o servidor',
          statusCode: 503,
        );
      default:
        return ServerException(
          message: 'Erro inesperado: ${e.message}',
          statusCode: 500,
        );
    }
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