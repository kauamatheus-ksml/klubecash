// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'dashboard_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$getCashbackSummaryUsecaseHash() =>
    r'2f9cb99e6bb02cc21f6a95eb54f2a86e3763cb80';

/// Provider para o use case de buscar resumo de cashback
///
/// Copied from [getCashbackSummaryUsecase].
@ProviderFor(getCashbackSummaryUsecase)
final getCashbackSummaryUsecaseProvider =
    AutoDisposeProvider<GetCashbackSummaryUsecase>.internal(
  getCashbackSummaryUsecase,
  name: r'getCashbackSummaryUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getCashbackSummaryUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetCashbackSummaryUsecaseRef
    = AutoDisposeProviderRef<GetCashbackSummaryUsecase>;
String _$getRecentTransactionsUsecaseHash() =>
    r'feb8f685b0cbc89fbafec9628967f3d847fb6b34';

/// Provider para o use case de buscar transações recentes
///
/// Copied from [getRecentTransactionsUsecase].
@ProviderFor(getRecentTransactionsUsecase)
final getRecentTransactionsUsecaseProvider =
    AutoDisposeProvider<GetRecentTransactionsUsecase>.internal(
  getRecentTransactionsUsecase,
  name: r'getRecentTransactionsUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getRecentTransactionsUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetRecentTransactionsUsecaseRef
    = AutoDisposeProviderRef<GetRecentTransactionsUsecase>;
String _$availableBalanceHash() => r'e3c8f1aabd7a09b7c55d1400464d8bd2bcf1a8d6';

/// Provider computado para saldo disponível
///
/// Copied from [availableBalance].
@ProviderFor(availableBalance)
final availableBalanceProvider = AutoDisposeProvider<double?>.internal(
  availableBalance,
  name: r'availableBalanceProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$availableBalanceHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef AvailableBalanceRef = AutoDisposeProviderRef<double?>;
String _$pendingBalanceHash() => r'cdac32e97c56d0ca3e98d870420bd19224daa70b';

/// Provider computado para saldo pendente
///
/// Copied from [pendingBalance].
@ProviderFor(pendingBalance)
final pendingBalanceProvider = AutoDisposeProvider<double?>.internal(
  pendingBalance,
  name: r'pendingBalanceProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$pendingBalanceHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef PendingBalanceRef = AutoDisposeProviderRef<double?>;
String _$totalSavedHash() => r'095dc79ad065261c2abcd9aea23685126336627e';

/// Provider computado para total economizado
///
/// Copied from [totalSaved].
@ProviderFor(totalSaved)
final totalSavedProvider = AutoDisposeProvider<double?>.internal(
  totalSaved,
  name: r'totalSavedProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$totalSavedHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef TotalSavedRef = AutoDisposeProviderRef<double?>;
String _$hasRecentTransactionsHash() =>
    r'67464b22f5f56aeeacf9b5c96666b4d26f75c507';

/// Provider computado para verificar se tem transações recentes
///
/// Copied from [hasRecentTransactions].
@ProviderFor(hasRecentTransactions)
final hasRecentTransactionsProvider = AutoDisposeProvider<bool>.internal(
  hasRecentTransactions,
  name: r'hasRecentTransactionsProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$hasRecentTransactionsHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef HasRecentTransactionsRef = AutoDisposeProviderRef<bool>;
String _$pendingTransactionsCountHash() =>
    r'13a8112de4e54c7ffe63eaff70e1d7e78bc4c829';

/// Provider computado para contagem de transações pendentes
///
/// Copied from [pendingTransactionsCount].
@ProviderFor(pendingTransactionsCount)
final pendingTransactionsCountProvider = AutoDisposeProvider<int>.internal(
  pendingTransactionsCount,
  name: r'pendingTransactionsCountProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$pendingTransactionsCountHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef PendingTransactionsCountRef = AutoDisposeProviderRef<int>;
String _$dashboardNotifierHash() => r'087e419ff734609f51799b6af93586e8dc05539b';

/// Provider principal do dashboard
///
/// Copied from [DashboardNotifier].
@ProviderFor(DashboardNotifier)
final dashboardNotifierProvider =
    AutoDisposeNotifierProvider<DashboardNotifier, DashboardState>.internal(
  DashboardNotifier.new,
  name: r'dashboardNotifierProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$dashboardNotifierHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$DashboardNotifier = AutoDisposeNotifier<DashboardState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
