// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'stores_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$storesRepositoryHash() => r'715b7acbaa5066ee718a5eb0d2d1786efeb4e814';

/// Provider para o repositório de lojas
///
/// Copied from [storesRepository].
@ProviderFor(storesRepository)
final storesRepositoryProvider =
    AutoDisposeProvider<StoresRepositoryImpl>.internal(
  storesRepository,
  name: r'storesRepositoryProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$storesRepositoryHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef StoresRepositoryRef = AutoDisposeProviderRef<StoresRepositoryImpl>;
String _$getPartnerStoresUseCaseHash() =>
    r'dac5fa3058766b42db33ed7b7ce72e64215f72e3';

/// Provider para use case de obter lojas parceiras
///
/// Copied from [getPartnerStoresUseCase].
@ProviderFor(getPartnerStoresUseCase)
final getPartnerStoresUseCaseProvider =
    AutoDisposeProvider<GetPartnerStoresUseCase>.internal(
  getPartnerStoresUseCase,
  name: r'getPartnerStoresUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getPartnerStoresUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetPartnerStoresUseCaseRef
    = AutoDisposeProviderRef<GetPartnerStoresUseCase>;
String _$searchStoresUseCaseHash() =>
    r'6950bd2101d9f8cd026e26212842c8d7f36940c1';

/// Provider para use case de buscar lojas
///
/// Copied from [searchStoresUseCase].
@ProviderFor(searchStoresUseCase)
final searchStoresUseCaseProvider =
    AutoDisposeProvider<SearchStoresUseCase>.internal(
  searchStoresUseCase,
  name: r'searchStoresUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$searchStoresUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef SearchStoresUseCaseRef = AutoDisposeProviderRef<SearchStoresUseCase>;
String _$getStoreDetailsUseCaseHash() =>
    r'8424fe94b3914909b2796a3efe7b77ec78933238';

/// Provider para use case de obter detalhes da loja
///
/// Copied from [getStoreDetailsUseCase].
@ProviderFor(getStoreDetailsUseCase)
final getStoreDetailsUseCaseProvider =
    AutoDisposeProvider<GetStoreDetailsUseCase>.internal(
  getStoreDetailsUseCase,
  name: r'getStoreDetailsUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getStoreDetailsUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetStoreDetailsUseCaseRef
    = AutoDisposeProviderRef<GetStoreDetailsUseCase>;
String _$isLoadingStoresHash() => r'45a019029ba204ab18c92f3f7ab01489283343ce';

/// Provider conveniente para verificar se está carregando
///
/// Copied from [isLoadingStores].
@ProviderFor(isLoadingStores)
final isLoadingStoresProvider = AutoDisposeProvider<bool>.internal(
  isLoadingStores,
  name: r'isLoadingStoresProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$isLoadingStoresHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsLoadingStoresRef = AutoDisposeProviderRef<bool>;
String _$storesListHash() => r'3a5952f5e3c9257ee3c5ad2ab6e8d105eae8a7cd';

/// Provider conveniente para obter lista de lojas
///
/// Copied from [storesList].
@ProviderFor(storesList)
final storesListProvider = AutoDisposeProvider<List<Store>>.internal(
  storesList,
  name: r'storesListProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$storesListHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef StoresListRef = AutoDisposeProviderRef<List<Store>>;
String _$storeCategoriesHash() => r'48b2ea19cfd8610ec4d2825ec933b59f6a24365b';

/// Provider conveniente para obter categorias
///
/// Copied from [storeCategories].
@ProviderFor(storeCategories)
final storeCategoriesProvider =
    AutoDisposeProvider<List<StoreCategory>>.internal(
  storeCategories,
  name: r'storeCategoriesProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$storeCategoriesHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef StoreCategoriesRef = AutoDisposeProviderRef<List<StoreCategory>>;
String _$favoriteStoresHash() => r'39309f362b785208bf5159332479c9dcadfb1b8e';

/// Provider conveniente para obter lojas favoritas
///
/// Copied from [favoriteStores].
@ProviderFor(favoriteStores)
final favoriteStoresProvider = AutoDisposeProvider<List<Store>>.internal(
  favoriteStores,
  name: r'favoriteStoresProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$favoriteStoresHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef FavoriteStoresRef = AutoDisposeProviderRef<List<Store>>;
String _$favoriteStoresCountHash() =>
    r'0a7449f1a73082a77a22ce8cd514269028f41d50';

/// Provider conveniente para contagem de lojas favoritas
///
/// Copied from [favoriteStoresCount].
@ProviderFor(favoriteStoresCount)
final favoriteStoresCountProvider = AutoDisposeProvider<int>.internal(
  favoriteStoresCount,
  name: r'favoriteStoresCountProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$favoriteStoresCountHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef FavoriteStoresCountRef = AutoDisposeProviderRef<int>;
String _$hasMoreStoresHash() => r'a42e4d1101a9d47968da69598b20d2f083d3f30a';

/// Provider conveniente para verificar se tem mais lojas para carregar
///
/// Copied from [hasMoreStores].
@ProviderFor(hasMoreStores)
final hasMoreStoresProvider = AutoDisposeProvider<bool>.internal(
  hasMoreStores,
  name: r'hasMoreStoresProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$hasMoreStoresHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef HasMoreStoresRef = AutoDisposeProviderRef<bool>;
String _$selectedStoreHash() => r'7212cf70dd42ee34ef9b62c20b9b0af3c7ed361d';

/// Provider conveniente para obter loja selecionada
///
/// Copied from [selectedStore].
@ProviderFor(selectedStore)
final selectedStoreProvider = AutoDisposeProvider<Store?>.internal(
  selectedStore,
  name: r'selectedStoreProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$selectedStoreHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef SelectedStoreRef = AutoDisposeProviderRef<Store?>;
String _$isSearchingHash() => r'a9182c1d5fe5a5ae368b7d0b47367d9fb310f8b5';

/// Provider conveniente para verificar se está buscando
///
/// Copied from [isSearching].
@ProviderFor(isSearching)
final isSearchingProvider = AutoDisposeProvider<bool>.internal(
  isSearching,
  name: r'isSearchingProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$isSearchingHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsSearchingRef = AutoDisposeProviderRef<bool>;
String _$currentSearchQueryHash() =>
    r'b88cf2d91987fa18c872beadd60111435b8eb15b';

/// Provider conveniente para obter query de busca atual
///
/// Copied from [currentSearchQuery].
@ProviderFor(currentSearchQuery)
final currentSearchQueryProvider = AutoDisposeProvider<String?>.internal(
  currentSearchQuery,
  name: r'currentSearchQueryProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$currentSearchQueryHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef CurrentSearchQueryRef = AutoDisposeProviderRef<String?>;
String _$hasActiveFiltersHash() => r'9660b87b31dc29b1fe770a4cbb13032fdde8c963';

/// Provider conveniente para verificar se tem filtros ativos
///
/// Copied from [hasActiveFilters].
@ProviderFor(hasActiveFilters)
final hasActiveFiltersProvider = AutoDisposeProvider<bool>.internal(
  hasActiveFilters,
  name: r'hasActiveFiltersProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$hasActiveFiltersHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef HasActiveFiltersRef = AutoDisposeProviderRef<bool>;
String _$storesNotifierHash() => r'a6c7471b28b49f79a8b86eb52b362dc4b5185f5f';

/// Provider principal para gerenciamento de estado das lojas
///
/// Copied from [StoresNotifier].
@ProviderFor(StoresNotifier)
final storesNotifierProvider =
    AutoDisposeNotifierProvider<StoresNotifier, StoresState>.internal(
  StoresNotifier.new,
  name: r'storesNotifierProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$storesNotifierHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$StoresNotifier = AutoDisposeNotifier<StoresState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
