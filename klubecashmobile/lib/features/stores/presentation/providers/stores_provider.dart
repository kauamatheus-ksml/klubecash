// lib/features/stores/presentation/providers/stores_provider.dart
// 🏪 Stores Provider - Gerenciamento de estado das lojas parceiras do Klube Cash

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../domain/entities/store.dart';
import '../../domain/entities/store_category.dart';
import '../../domain/usecases/get_partner_stores_usecase.dart';
import '../../domain/usecases/search_stores_usecase.dart';
import '../../domain/usecases/get_store_details_usecase.dart';
import '../../data/repositories/stores_repository_impl.dart';
import '../../data/datasources/stores_remote_datasource.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/errors/failures.dart';

part 'stores_provider.g.dart';

// ==================== STATE CLASSES ====================

/// Estado das lojas parceiras contendo todas as informações necessárias
class StoresState {
  final bool isLoading;
  final bool isRefreshing;
  final bool isLoadingMore;
  final String? errorMessage;
  final List<Store> stores;
  final List<StoreCategory> categories;
  final Store? selectedStore;
  final String? searchQuery;
  final StoreCategory? selectedCategory;
  final String? sortBy;
  final bool hasMoreStores;
  final int currentPage;
  final DateTime? lastUpdate;

  const StoresState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.isLoadingMore = false,
    this.errorMessage,
    this.stores = const [],
    this.categories = const [],
    this.selectedStore,
    this.searchQuery,
    this.selectedCategory,
    this.sortBy = 'name',
    this.hasMoreStores = true,
    this.currentPage = 1,
    this.lastUpdate,
  });

  StoresState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    bool? isLoadingMore,
    String? errorMessage,
    List<Store>? stores,
    List<StoreCategory>? categories,
    Store? selectedStore,
    String? searchQuery,
    StoreCategory? selectedCategory,
    String? sortBy,
    bool? hasMoreStores,
    int? currentPage,
    DateTime? lastUpdate,
  }) {
    return StoresState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      errorMessage: errorMessage,
      stores: stores ?? this.stores,
      categories: categories ?? this.categories,
      selectedStore: selectedStore ?? this.selectedStore,
      searchQuery: searchQuery ?? this.searchQuery,
      selectedCategory: selectedCategory ?? this.selectedCategory,
      sortBy: sortBy ?? this.sortBy,
      hasMoreStores: hasMoreStores ?? this.hasMoreStores,
      currentPage: currentPage ?? this.currentPage,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }
}

// ==================== REPOSITORY PROVIDERS ====================

/// Provider para o repositório de lojas
@riverpod
StoresRepositoryImpl storesRepository(StoresRepositoryRef ref) {
  final apiClient = ref.watch(apiClientProvider);
  final dataSource = StoresRemoteDataSource(apiClient);
  return StoresRepositoryImpl(dataSource);
}

// ==================== USE CASES PROVIDERS ====================

/// Provider para use case de obter lojas parceiras
@riverpod
GetPartnerStoresUseCase getPartnerStoresUseCase(GetPartnerStoresUseCaseRef ref) {
  final repository = ref.watch(storesRepositoryProvider);
  return GetPartnerStoresUseCase(repository);
}

/// Provider para use case de buscar lojas
@riverpod
SearchStoresUseCase searchStoresUseCase(SearchStoresUseCaseRef ref) {
  final repository = ref.watch(storesRepositoryProvider);
  return SearchStoresUseCase(repository);
}

/// Provider para use case de obter detalhes da loja
@riverpod
GetStoreDetailsUseCase getStoreDetailsUseCase(GetStoreDetailsUseCaseRef ref) {
  final repository = ref.watch(storesRepositoryProvider);
  return GetStoreDetailsUseCase(repository);
}

// ==================== MAIN STORES PROVIDER ====================

/// Provider principal para gerenciamento de estado das lojas
@riverpod
class StoresNotifier extends _$StoresNotifier {
  @override
  StoresState build() {
    // Carregar dados iniciais
    loadStores();
    return const StoresState();
  }

  /// Carrega lista de lojas parceiras
  Future<void> loadStores({bool refresh = false}) async {
    try {
      if (refresh) {
        state = state.copyWith(isRefreshing: true, errorMessage: null);
      } else if (state.stores.isEmpty) {
        state = state.copyWith(isLoading: true, errorMessage: null);
      }

      final getPartnerStoresUseCase = ref.read(getPartnerStoresUseCaseProvider);
      
      final params = GetPartnerStoresParams(
        page: refresh ? 1 : state.currentPage,
        searchQuery: state.searchQuery,
        categoryId: state.selectedCategory?.id,
        sortBy: state.sortBy,
      );

      final result = await getPartnerStoresUseCase.call(params);

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (storesData) {
          final newStores = refresh ? storesData.stores : [...state.stores, ...storesData.stores];
          
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            errorMessage: null,
            stores: newStores,
            categories: storesData.categories,
            hasMoreStores: storesData.hasMore,
            currentPage: refresh ? 2 : state.currentPage + 1,
            lastUpdate: DateTime.now(),
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isRefreshing: false,
        errorMessage: 'Erro inesperado ao carregar lojas',
      );
    }
  }

  /// Carrega mais lojas (paginação)
  Future<void> loadMoreStores() async {
    if (state.isLoadingMore || !state.hasMoreStores) return;

    try {
      state = state.copyWith(isLoadingMore: true);

      final getPartnerStoresUseCase = ref.read(getPartnerStoresUseCaseProvider);
      
      final params = GetPartnerStoresParams(
        page: state.currentPage,
        searchQuery: state.searchQuery,
        categoryId: state.selectedCategory?.id,
        sortBy: state.sortBy,
      );

      final result = await getPartnerStoresUseCase.call(params);

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoadingMore: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (storesData) {
          state = state.copyWith(
            isLoadingMore: false,
            errorMessage: null,
            stores: [...state.stores, ...storesData.stores],
            hasMoreStores: storesData.hasMore,
            currentPage: state.currentPage + 1,
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoadingMore: false,
        errorMessage: 'Erro ao carregar mais lojas',
      );
    }
  }

  /// Busca lojas por texto
  Future<void> searchStores(String query) async {
    try {
      state = state.copyWith(
        searchQuery: query.trim().isEmpty ? null : query.trim(),
        isLoading: true,
        errorMessage: null,
        stores: [],
        currentPage: 1,
        hasMoreStores: true,
      );

      final searchStoresUseCase = ref.read(searchStoresUseCaseProvider);
      
      final params = SearchStoresParams(
        query: query.trim(),
        categoryId: state.selectedCategory?.id,
        sortBy: state.sortBy,
      );

      final result = await searchStoresUseCase.call(params);

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (storesData) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: null,
            stores: storesData.stores,
            categories: storesData.categories,
            hasMoreStores: storesData.hasMore,
            currentPage: 2,
            lastUpdate: DateTime.now(),
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Erro inesperado ao buscar lojas',
      );
    }
  }

  /// Filtra lojas por categoria
  Future<void> filterByCategory(StoreCategory? category) async {
    state = state.copyWith(
      selectedCategory: category,
      stores: [],
      currentPage: 1,
      hasMoreStores: true,
    );
    
    await loadStores();
  }

  /// Ordena lojas
  Future<void> sortStores(String sortBy) async {
    state = state.copyWith(
      sortBy: sortBy,
      stores: [],
      currentPage: 1,
      hasMoreStores: true,
    );
    
    await loadStores();
  }

  /// Obtém detalhes de uma loja específica
  Future<void> getStoreDetails(String storeId) async {
    try {
      state = state.copyWith(isLoading: true, errorMessage: null);

      final getStoreDetailsUseCase = ref.read(getStoreDetailsUseCaseProvider);
      final params = GetStoreDetailsParams(storeId: storeId);

      final result = await getStoreDetailsUseCase.call(params);

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (store) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: null,
            selectedStore: store,
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Erro ao carregar detalhes da loja',
      );
    }
  }

  /// Adiciona/remove loja dos favoritos
  Future<void> toggleFavorite(String storeId) async {
    try {
      // Atualizar estado local otimista
      final updatedStores = state.stores.map((store) {
        if (store.id == storeId) {
          return store.copyWith(isFavorite: !store.isFavorite);
        }
        return store;
      }).toList();

      state = state.copyWith(stores: updatedStores);

      // TODO: Implementar chamada para API para persistir favorito
      // await toggleFavoriteUseCase.call(ToggleFavoriteParams(storeId: storeId));
      
    } catch (e) {
      // Reverter mudança em caso de erro
      final revertedStores = state.stores.map((store) {
        if (store.id == storeId) {
          return store.copyWith(isFavorite: !store.isFavorite);
        }
        return store;
      }).toList();

      state = state.copyWith(
        stores: revertedStores,
        errorMessage: 'Erro ao atualizar favoritos',
      );
    }
  }

  /// Limpa filtros e busca
  Future<void> clearFilters() async {
    state = state.copyWith(
      searchQuery: null,
      selectedCategory: null,
      sortBy: 'name',
      stores: [],
      currentPage: 1,
      hasMoreStores: true,
    );
    
    await loadStores();
  }

  /// Atualiza dados (pull to refresh)
  Future<void> refresh() async {
    await loadStores(refresh: true);
  }

  /// Limpa mensagem de erro
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }

  /// Limpa loja selecionada
  void clearSelectedStore() {
    state = state.copyWith(selectedStore: null);
  }

  /// Converte failures em mensagens de erro user-friendly
  String _getFailureMessage(Failure failure) {
    if (failure is ServerFailure) {
      return failure.message.isNotEmpty
          ? failure.message
          : 'Erro no servidor. Tente novamente.';
    } else if (failure is NetworkFailure) {
      return 'Sem conexão com a internet. Verifique sua conexão.';
    } else if (failure is ValidationFailure) {
      return failure.message;
    } else if (failure is CacheFailure) {
      return 'Erro nos dados locais. Tente novamente.';
    } else {
      return 'Erro inesperado. Tente novamente.';
    }
  }
}

// ==================== COMPUTED PROVIDERS ====================

/// Provider conveniente para verificar se está carregando
@riverpod
bool isLoadingStores(IsLoadingStoresRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.isLoading;
}

/// Provider conveniente para obter lista de lojas
@riverpod
List<Store> storesList(StoresListRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.stores;
}

/// Provider conveniente para obter categorias
@riverpod
List<StoreCategory> storeCategories(StoreCategoriesRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.categories;
}

/// Provider conveniente para obter lojas favoritas
@riverpod
List<Store> favoriteStores(FavoriteStoresRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.stores.where((store) => store.isFavorite).toList();
}

/// Provider conveniente para contagem de lojas favoritas
@riverpod
int favoriteStoresCount(FavoriteStoresCountRef ref) {
  final favoriteStores = ref.watch(favoriteStoresProvider);
  return favoriteStores.length;
}

/// Provider conveniente para verificar se tem mais lojas para carregar
@riverpod
bool hasMoreStores(HasMoreStoresRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.hasMoreStores;
}

/// Provider conveniente para obter loja selecionada
@riverpod
Store? selectedStore(SelectedStoreRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.selectedStore;
}

/// Provider conveniente para verificar se está buscando
@riverpod
bool isSearching(IsSearchingRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.searchQuery != null && storesState.searchQuery!.isNotEmpty;
}

/// Provider conveniente para obter query de busca atual
@riverpod
String? currentSearchQuery(CurrentSearchQueryRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.searchQuery;
}

/// Provider conveniente para verificar se tem filtros ativos
@riverpod
bool hasActiveFilters(HasActiveFiltersRef ref) {
  final storesState = ref.watch(storesNotifierProvider);
  return storesState.selectedCategory != null || 
         (storesState.searchQuery != null && storesState.searchQuery!.isNotEmpty) ||
         storesState.sortBy != 'name';
}