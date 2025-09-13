// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cashback_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$getCashbackHistoryUsecaseHash() =>
    r'68c472e12a5a5de72adae9e52d7d76f319800b6c';

/// Provider para o use case de buscar histórico de cashback
///
/// Copied from [getCashbackHistoryUsecase].
@ProviderFor(getCashbackHistoryUsecase)
final getCashbackHistoryUsecaseProvider =
    AutoDisposeProvider<GetCashbackHistoryUseCase>.internal(
  getCashbackHistoryUsecase,
  name: r'getCashbackHistoryUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getCashbackHistoryUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetCashbackHistoryUsecaseRef
    = AutoDisposeProviderRef<GetCashbackHistoryUseCase>;
String _$filterCashbackUsecaseHash() =>
    r'0f5b55e4d79f8c591f824bc920804f82d88e46c5';

/// Provider para o use case de filtrar cashback
///
/// Copied from [filterCashbackUsecase].
@ProviderFor(filterCashbackUsecase)
final filterCashbackUsecaseProvider =
    AutoDisposeProvider<FilterCashbackUseCase>.internal(
  filterCashbackUsecase,
  name: r'filterCashbackUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$filterCashbackUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef FilterCashbackUsecaseRef
    = AutoDisposeProviderRef<FilterCashbackUseCase>;
String _$transactionsByStatusHash() =>
    r'98f4b632fd280a831bab9aeaa6f462408c411eee';

/// Copied from Dart SDK
class _SystemHash {
  _SystemHash._();

  static int combine(int hash, int value) {
    // ignore: parameter_assignments
    hash = 0x1fffffff & (hash + value);
    // ignore: parameter_assignments
    hash = 0x1fffffff & (hash + ((0x0007ffff & hash) << 10));
    return hash ^ (hash >> 6);
  }

  static int finish(int hash) {
    // ignore: parameter_assignments
    hash = 0x1fffffff & (hash + ((0x03ffffff & hash) << 3));
    // ignore: parameter_assignments
    hash = hash ^ (hash >> 11);
    return 0x1fffffff & (hash + ((0x00003fff & hash) << 15));
  }
}

/// Provider computado para transações filtradas por status
///
/// Copied from [transactionsByStatus].
@ProviderFor(transactionsByStatus)
const transactionsByStatusProvider = TransactionsByStatusFamily();

/// Provider computado para transações filtradas por status
///
/// Copied from [transactionsByStatus].
class TransactionsByStatusFamily extends Family<List<CashbackTransaction>> {
  /// Provider computado para transações filtradas por status
  ///
  /// Copied from [transactionsByStatus].
  const TransactionsByStatusFamily();

  /// Provider computado para transações filtradas por status
  ///
  /// Copied from [transactionsByStatus].
  TransactionsByStatusProvider call(
    CashbackTransactionStatus status,
  ) {
    return TransactionsByStatusProvider(
      status,
    );
  }

  @override
  TransactionsByStatusProvider getProviderOverride(
    covariant TransactionsByStatusProvider provider,
  ) {
    return call(
      provider.status,
    );
  }

  static const Iterable<ProviderOrFamily>? _dependencies = null;

  @override
  Iterable<ProviderOrFamily>? get dependencies => _dependencies;

  static const Iterable<ProviderOrFamily>? _allTransitiveDependencies = null;

  @override
  Iterable<ProviderOrFamily>? get allTransitiveDependencies =>
      _allTransitiveDependencies;

  @override
  String? get name => r'transactionsByStatusProvider';
}

/// Provider computado para transações filtradas por status
///
/// Copied from [transactionsByStatus].
class TransactionsByStatusProvider
    extends AutoDisposeProvider<List<CashbackTransaction>> {
  /// Provider computado para transações filtradas por status
  ///
  /// Copied from [transactionsByStatus].
  TransactionsByStatusProvider(
    CashbackTransactionStatus status,
  ) : this._internal(
          (ref) => transactionsByStatus(
            ref as TransactionsByStatusRef,
            status,
          ),
          from: transactionsByStatusProvider,
          name: r'transactionsByStatusProvider',
          debugGetCreateSourceHash:
              const bool.fromEnvironment('dart.vm.product')
                  ? null
                  : _$transactionsByStatusHash,
          dependencies: TransactionsByStatusFamily._dependencies,
          allTransitiveDependencies:
              TransactionsByStatusFamily._allTransitiveDependencies,
          status: status,
        );

  TransactionsByStatusProvider._internal(
    super._createNotifier, {
    required super.name,
    required super.dependencies,
    required super.allTransitiveDependencies,
    required super.debugGetCreateSourceHash,
    required super.from,
    required this.status,
  }) : super.internal();

  final CashbackTransactionStatus status;

  @override
  Override overrideWith(
    List<CashbackTransaction> Function(TransactionsByStatusRef provider) create,
  ) {
    return ProviderOverride(
      origin: this,
      override: TransactionsByStatusProvider._internal(
        (ref) => create(ref as TransactionsByStatusRef),
        from: from,
        name: null,
        dependencies: null,
        allTransitiveDependencies: null,
        debugGetCreateSourceHash: null,
        status: status,
      ),
    );
  }

  @override
  AutoDisposeProviderElement<List<CashbackTransaction>> createElement() {
    return _TransactionsByStatusProviderElement(this);
  }

  @override
  bool operator ==(Object other) {
    return other is TransactionsByStatusProvider && other.status == status;
  }

  @override
  int get hashCode {
    var hash = _SystemHash.combine(0, runtimeType.hashCode);
    hash = _SystemHash.combine(hash, status.hashCode);

    return _SystemHash.finish(hash);
  }
}

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
mixin TransactionsByStatusRef
    on AutoDisposeProviderRef<List<CashbackTransaction>> {
  /// The parameter `status` of this provider.
  CashbackTransactionStatus get status;
}

class _TransactionsByStatusProviderElement
    extends AutoDisposeProviderElement<List<CashbackTransaction>>
    with TransactionsByStatusRef {
  _TransactionsByStatusProviderElement(super.provider);

  @override
  CashbackTransactionStatus get status =>
      (origin as TransactionsByStatusProvider).status;
}

String _$totalCashbackAmountHash() =>
    r'984dad97af660267dad36e9b07bb84158c6beca3';

/// Provider computado para total de cashback das transações carregadas
///
/// Copied from [totalCashbackAmount].
@ProviderFor(totalCashbackAmount)
final totalCashbackAmountProvider = AutoDisposeProvider<double>.internal(
  totalCashbackAmount,
  name: r'totalCashbackAmountProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$totalCashbackAmountHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef TotalCashbackAmountRef = AutoDisposeProviderRef<double>;
String _$transactionCountByStatusHash() =>
    r'b185123dd1189f76ede1f75880c6d5d384816800';

/// Provider computado para contagem de transações por status
///
/// Copied from [transactionCountByStatus].
@ProviderFor(transactionCountByStatus)
final transactionCountByStatusProvider =
    AutoDisposeProvider<Map<CashbackTransactionStatus, int>>.internal(
  transactionCountByStatus,
  name: r'transactionCountByStatusProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$transactionCountByStatusHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef TransactionCountByStatusRef
    = AutoDisposeProviderRef<Map<CashbackTransactionStatus, int>>;
String _$hasActiveFiltersHash() => r'92af483ac9a203f4cc0726b75a57ffb1443b543f';

/// Provider computado para verificar se há filtros ativos
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
String _$activeFiltersCountHash() =>
    r'50d6ac734eccabd54e84d108a1e2c262ec01d50b';

/// Provider computado para contagem de filtros ativos
///
/// Copied from [activeFiltersCount].
@ProviderFor(activeFiltersCount)
final activeFiltersCountProvider = AutoDisposeProvider<int>.internal(
  activeFiltersCount,
  name: r'activeFiltersCountProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$activeFiltersCountHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef ActiveFiltersCountRef = AutoDisposeProviderRef<int>;
String _$averageCashbackPerTransactionHash() =>
    r'ac93f44ce7eb39d914195fdbc655c9d35a545c52';

/// Provider computado para média de cashback por transação
///
/// Copied from [averageCashbackPerTransaction].
@ProviderFor(averageCashbackPerTransaction)
final averageCashbackPerTransactionProvider =
    AutoDisposeProvider<double>.internal(
  averageCashbackPerTransaction,
  name: r'averageCashbackPerTransactionProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$averageCashbackPerTransactionHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef AverageCashbackPerTransactionRef = AutoDisposeProviderRef<double>;
String _$recentTransactionsHash() =>
    r'a042298ce4e65250c42f840c9e26a7245ed4e2fe';

/// Provider computado para transações da última semana
///
/// Copied from [recentTransactions].
@ProviderFor(recentTransactions)
final recentTransactionsProvider =
    AutoDisposeProvider<List<CashbackTransaction>>.internal(
  recentTransactions,
  name: r'recentTransactionsProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$recentTransactionsHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef RecentTransactionsRef
    = AutoDisposeProviderRef<List<CashbackTransaction>>;
String _$highestCashbackTransactionHash() =>
    r'eb9619ceff6734ad77a067fe7f84d0549b3ab8de';

/// Provider computado para maior transação de cashback
///
/// Copied from [highestCashbackTransaction].
@ProviderFor(highestCashbackTransaction)
final highestCashbackTransactionProvider =
    AutoDisposeProvider<CashbackTransaction?>.internal(
  highestCashbackTransaction,
  name: r'highestCashbackTransactionProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$highestCashbackTransactionHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef HighestCashbackTransactionRef
    = AutoDisposeProviderRef<CashbackTransaction?>;
String _$cashbackNotifierHash() => r'2ba830969c71b358ecfdc6ce9272d3b75ad28de5';

/// Provider principal para gerenciamento do estado de Cashback
///
/// Copied from [CashbackNotifier].
@ProviderFor(CashbackNotifier)
final cashbackNotifierProvider =
    AutoDisposeNotifierProvider<CashbackNotifier, CashbackState>.internal(
  CashbackNotifier.new,
  name: r'cashbackNotifierProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$cashbackNotifierHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$CashbackNotifier = AutoDisposeNotifier<CashbackState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
