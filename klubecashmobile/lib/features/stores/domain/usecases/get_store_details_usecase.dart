// lib/features/stores/domain/usecases/get_store_details_usecase.dart
// Use case para obter detalhes de uma loja específica

import 'package:dartz/dartz.dart';
import '../entities/store.dart';
import '../repositories/stores_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter detalhes completos de uma loja
/// 
/// Implementa a lógica de negócio para buscar informações detalhadas
/// de uma loja específica, incluindo validações e dados adicionais
class GetStoreDetailsUseCase {
  final StoresRepository repository;

  const GetStoreDetailsUseCase(this.repository);

  /// Executa a busca de detalhes da loja
  ///
  /// [params] - Parâmetros incluindo ID da loja e opções de carregamento
  /// 
  /// Retorna [Store] com detalhes completos ou [Failure] em caso de erro
  Future<Either<Failure, Store>> call(GetStoreDetailsParams params) async {
    // Validações básicas
    final validationResult = _validateParams(params);
    if (validationResult != null) {
      return Left(ValidationFailure(validationResult));
    }

    // Sanitizar ID da loja
    final sanitizedStoreId = _sanitizeStoreId(params.storeId);

    // Chamar repositório para obter detalhes
    final result = await repository.getStoreDetails(storeId: sanitizedStoreId);

    return result.fold(
      (failure) => Left(failure),
      (store) async {
        // Se obteu a loja com sucesso, pode carregar dados adicionais se solicitado
        if (params.includeReviews || params.includeFavoriteStatus) {
          return await _loadAdditionalData(store, params);
        }
        return Right(store);
      },
    );
  }

  /// Valida os parâmetros de entrada
  String? _validateParams(GetStoreDetailsParams params) {
    // Validar ID da loja
    if (params.storeId.isEmpty) {
      return 'ID da loja é obrigatório';
    }

    if (params.storeId.trim().isEmpty) {
      return 'ID da loja não pode ser apenas espaços';
    }

    // Validar formato do ID (assumindo que é numérico ou UUID)
    final cleanId = params.storeId.trim();
    if (cleanId.length > 50) {
      return 'ID da loja muito longo (máximo 50 caracteres)';
    }

    // Verificar se contém apenas caracteres válidos para ID
    if (!RegExp(r'^[a-zA-Z0-9\-_]+$').hasMatch(cleanId)) {
      return 'ID da loja contém caracteres inválidos';
    }

    return null; // Parâmetros válidos
  }

  /// Sanitiza o ID da loja removendo espaços e caracteres inválidos
  String _sanitizeStoreId(String storeId) {
    return storeId.trim().replaceAll(RegExp(r'[^\w\-]'), '');
  }

  /// Carrega dados adicionais se solicitado
  Future<Either<Failure, Store>> _loadAdditionalData(
    Store store,
    GetStoreDetailsParams params,
  ) async {
    Store updatedStore = store;

    try {
      // Carregar status de favorito se solicitado
      if (params.includeFavoriteStatus) {
        // Aqui poderia fazer uma chamada específica para verificar se é favorita
        // Por enquanto, mantém o status atual da loja
      }

      // Carregar avaliações se solicitado
      if (params.includeReviews) {
        final reviewsResult = await repository.getStoreReviews(
          storeId: store.id,
          limit: params.reviewsLimit,
        );
        
        // Se conseguiu carregar avaliações, pode atualizar dados da loja
        reviewsResult.fold(
          (failure) {
            // Log do erro mas não falha o carregamento principal
            // Em produção, poderia usar um logger aqui
          },
          (reviewsData) {
            // Atualizar dados de avaliação na loja se disponível
            if (reviewsData['rating'] != null) {
              updatedStore = updatedStore.copyWith(
                rating: reviewsData['rating'].toDouble(),
                reviewsCount: reviewsData['total_reviews'],
              );
            }
          },
        );
      }

      return Right(updatedStore);
    } catch (e) {
      // Se houve erro ao carregar dados adicionais, retorna a loja básica
      return Right(store);
    }
  }
}

/// Parâmetros para obter detalhes de uma loja
class GetStoreDetailsParams {
  final String storeId;
  final bool includeFavoriteStatus;
  final bool includeReviews;
  final bool includeNearbyStores;
  final int reviewsLimit;
  final int nearbyLimit;

  const GetStoreDetailsParams({
    required this.storeId,
    this.includeFavoriteStatus = false,
    this.includeReviews = false,
    this.includeNearbyStores = false,
    this.reviewsLimit = 5,
    this.nearbyLimit = 3,
  });

  /// Construtor para busca básica apenas com ID
  const GetStoreDetailsParams.basic({
    required String storeId,
  }) : this(storeId: storeId);

  /// Construtor para busca completa com todos os dados
  const GetStoreDetailsParams.complete({
    required String storeId,
    int reviewsLimit = 10,
    int nearbyLimit = 5,
  }) : this(
          storeId: storeId,
          includeFavoriteStatus: true,
          includeReviews: true,
          includeNearbyStores: true,
          reviewsLimit: reviewsLimit,
          nearbyLimit: nearbyLimit,
        );

  /// Construtor para busca com avaliações
  const GetStoreDetailsParams.withReviews({
    required String storeId,
    int reviewsLimit = 5,
  }) : this(
          storeId: storeId,
          includeReviews: true,
          reviewsLimit: reviewsLimit,
        );

  /// Construtor para busca com status de favorito
  const GetStoreDetailsParams.withFavoriteStatus({
    required String storeId,
  }) : this(
          storeId: storeId,
          includeFavoriteStatus: true,
        );

  /// Construtor para busca com lojas próximas
  const GetStoreDetailsParams.withNearby({
    required String storeId,
    int nearbyLimit = 3,
  }) : this(
          storeId: storeId,
          includeNearbyStores: true,
          nearbyLimit: nearbyLimit,
        );

  /// Cria uma cópia dos parâmetros com campos atualizados
  GetStoreDetailsParams copyWith({
    String? storeId,
    bool? includeFavoriteStatus,
    bool? includeReviews,
    bool? includeNearbyStores,
    int? reviewsLimit,
    int? nearbyLimit,
  }) {
    return GetStoreDetailsParams(
      storeId: storeId ?? this.storeId,
      includeFavoriteStatus: includeFavoriteStatus ?? this.includeFavoriteStatus,
      includeReviews: includeReviews ?? this.includeReviews,
      includeNearbyStores: includeNearbyStores ?? this.includeNearbyStores,
      reviewsLimit: reviewsLimit ?? this.reviewsLimit,
      nearbyLimit: nearbyLimit ?? this.nearbyLimit,
    );
  }

  /// Habilita carregamento de todos os dados adicionais
  GetStoreDetailsParams enableAllData() {
    return copyWith(
      includeFavoriteStatus: true,
      includeReviews: true,
      includeNearbyStores: true,
    );
  }

  /// Desabilita carregamento de dados adicionais (apenas dados básicos)
  GetStoreDetailsParams basicDataOnly() {
    return copyWith(
      includeFavoriteStatus: false,
      includeReviews: false,
      includeNearbyStores: false,
    );
  }

  /// Verifica se há dados adicionais para carregar
  bool get hasAdditionalData =>
      includeFavoriteStatus || includeReviews || includeNearbyStores;

  /// Lista os tipos de dados adicionais solicitados
  List<String> get requestedDataTypes {
    final types = <String>[];
    if (includeFavoriteStatus) types.add('favorite_status');
    if (includeReviews) types.add('reviews');
    if (includeNearbyStores) types.add('nearby_stores');
    return types;
  }

  /// Versão simplificada do ID para exibição
  String get displayStoreId => storeId.length > 20 ? '${storeId.substring(0, 20)}...' : storeId;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is GetStoreDetailsParams &&
        other.storeId == storeId &&
        other.includeFavoriteStatus == includeFavoriteStatus &&
        other.includeReviews == includeReviews &&
        other.includeNearbyStores == includeNearbyStores &&
        other.reviewsLimit == reviewsLimit &&
        other.nearbyLimit == nearbyLimit;
  }

  @override
  int get hashCode {
    return Object.hash(
      storeId,
      includeFavoriteStatus,
      includeReviews,
      includeNearbyStores,
      reviewsLimit,
      nearbyLimit,
    );
  }

  @override
  String toString() {
    return 'GetStoreDetailsParams(storeId: "$displayStoreId", additionalData: ${requestedDataTypes.join(", ")})';
  }
}