// lib/features/cashback/presentation/providers/cashback_provider.dart
// Provider principal para gerenciamento de estado da feature Cashback

import 'package:riverpod_annotation/riverpod_annotation.dart';
import '../../domain/entities/cashback_transaction.dart';
import '../../domain/entities/cashback_filter.dart';
import '../../domain/usecases/get_cashback_history_usecase.dart';
import '../../domain/usecases/filter_cashback_usecase.dart';
import '../../../../core/errors/failures.dart';

part 'cashback_provider.g.dart';

// ==================== ESTADO DO CASHBACK ====================

/// Estado da feature Cashback
class CashbackState {
  /// Indica se está carregando dados iniciais
  final bool isLoading;
  
  /// Indica se está realizando refresh (pull-to-refresh)
  final bool isRefreshing;
  
  /// Indica se está carregando mais itens (paginação)
  final bool isLoadingMore;
  
  /// Mensagem de erro, se houver
  final String? errorMessage;
  
  /// Lista de transações de cashback
  final List<CashbackTransaction> transactions;
  
  /// Filtro atualmente aplicado
  final CashbackFilter currentFilter;
  
  /// Informações de paginação
  final PaginationInfo? pagination;
  
  /// Resumo estatístico das transações
  final CashbackSummary? summary;
  
  /// Transação selecionada para visualização de detalhes
  final CashbackTransaction? selectedTransaction;
  
  /// Indica se há mais páginas para carregar
  final bool hasMorePages;
  
  /// Timestamp da última atualização
  final DateTime? lastUpdate;

  const CashbackState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.isLoadingMore = false,
    this.errorMessage,
    this.transactions = const [],
    this.currentFilter = const CashbackFilter(),
    this.pagination,
    this.summary,
    this.selectedTransaction,
    this.hasMorePages = true,
    this.lastUpdate,
  });

  /// Cria uma cópia do estado com valores modificados
  CashbackState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    bool? isLoadingMore,
    String? errorMessage,
    List<CashbackTransaction>? transactions,
    CashbackFilter? currentFilter,
    PaginationInfo? pagination,
    CashbackSummary? summary,
    CashbackTransaction? selectedTransaction,
    bool? hasMorePages,
    DateTime? lastUpdate,
  }) {
    return CashbackState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      errorMessage: errorMessage,
      transactions: transactions ?? this.transactions,
      currentFilter: currentFilter ?? this.currentFilter,
      pagination: pagination ?? this.pagination,
      summary: summary ?? this.summary,
      selectedTransaction: selectedTransaction ?? this.selectedTransaction,
      hasMorePages: hasMorePages ?? this.hasMorePages,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }

  /// Verifica se há dados carregados
  bool get hasData => transactions.isNotEmpty;

  /// Verifica se há erro
  bool get hasError => errorMessage != null;

  /// Verifica se está em estado inicial (sem dados e sem loading)
  bool get isInitial => !isLoading && !hasData && !hasError;

  /// Verifica se algum filtro está ativo
  bool get hasActiveFilters => currentFilter.hasActiveFilters;
}

// ==================== PROVIDERS DE USE CASES ====================

/// Provider para o use case de buscar histórico de cashback
@riverpod
GetCashbackHistoryUseCase getCashbackHistoryUsecase(
  GetCashbackHistoryUsecaseRef ref,
) {
  throw UnimplementedError('Deve ser implementado na injeção de dependência');
}

/// Provider para o use case de filtrar cashback
@riverpod
FilterCashbackUseCase filterCashbackUsecase(
  FilterCashbackUsecaseRef ref,
) {
  throw UnimplementedError('Deve ser implementado na injeção de dependência');
}

// ==================== PROVIDER PRINCIPAL ====================

/// Provider principal para gerenciamento do estado de Cashback
@riverpod
class CashbackNotifier extends _$CashbackNotifier {
  @override
  CashbackState build() {
    // Carrega dados iniciais
    loadCashbackHistory();
    return const CashbackState(isLoading: true);
  }

  /// Carrega o histórico de transações de cashback
  Future<void> loadCashbackHistory({bool refresh = false}) async {
    if (state.isRefreshing && refresh) return;
    if (state.isLoadingMore && !refresh) return;

    state = state.copyWith(
      isLoading: state.isInitial && !refresh,
      isRefreshing: refresh,
      errorMessage: null,
    );

    try {
      final usecase = ref.read(getCashbackHistoryUsecaseProvider);
      final params = GetCashbackHistoryParams(
        filter: refresh ? state.currentFilter.copyWith(pagina: 1) : state.currentFilter,
      );

      final result = await usecase(params);

      result.fold(
        (failure) => throw failure,
        (historyResult) {
          if (refresh || state.currentFilter.pagina == null || state.currentFilter.pagina == 1) {
            // Primeira página ou refresh - substitui a lista
            state = state.copyWith(
              isLoading: false,
              isRefreshing: false,
              transactions: historyResult.transactions,
              pagination: historyResult.pagination,
              summary: historyResult.summary,
              hasMorePages: historyResult.pagination.hasNextPage,
              lastUpdate: DateTime.now(),
            );
          } else {
            // Páginas seguintes - adiciona à lista existente
            state = state.copyWith(
              isLoading: false,
              isLoadingMore: false,
              transactions: [...state.transactions, ...historyResult.transactions],
              pagination: historyResult.pagination,
              hasMorePages: historyResult.pagination.hasNextPage,
              lastUpdate: DateTime.now(),
            );
          }
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isRefreshing: false,
        isLoadingMore: false,
        errorMessage: _getErrorMessage(e),
      );
    }
  }

  /// Carrega mais transações (paginação)
  Future<void> loadMoreTransactions() async {
    if (!state.hasMorePages || state.isLoadingMore) return;

    final nextPage = (state.pagination?.currentPage ?? 0) + 1;
    final newFilter = state.currentFilter.copyWith(pagina: nextPage);
    
    state = state.copyWith(
      isLoadingMore: true,
      currentFilter: newFilter,
    );

    await loadCashbackHistory();
  }

  /// Aplica filtros às transações
  Future<void> applyFilter(CashbackFilter filter) async {
    // Reset da paginação ao aplicar novo filtro
    final filterWithFirstPage = filter.copyWith(pagina: 1);
    
    state = state.copyWith(
      currentFilter: filterWithFirstPage,
      isLoading: true,
      errorMessage: null,
    );

    await loadCashbackHistory();
  }

  /// Limpa todos os filtros aplicados
  Future<void> clearFilters() async {
    final defaultFilter = const CashbackFilter().copyWith(pagina: 1);
    await applyFilter(defaultFilter);
  }

  /// Busca transações por texto
  Future<void> searchTransactions(String searchText) async {
    final filter = state.currentFilter.copyWith(
      textoBusca: searchText.isNotEmpty ? searchText : null,
      pagina: 1,
    );
    
    await applyFilter(filter);
  }

  /// Atualiza o filtro de período
  Future<void> updatePeriodFilter({
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    final filter = state.currentFilter.copyWith(
      dataInicio: startDate,
      dataFim: endDate,
      pagina: 1,
    );
    
    await applyFilter(filter);
  }

  /// Atualiza o filtro de status
  Future<void> updateStatusFilter(List<CashbackTransactionStatus> statusList) async {
    final filter = state.currentFilter.copyWith(
      status: statusList.isNotEmpty ? statusList : null,
      pagina: 1,
    );
    
    await applyFilter(filter);
  }

  /// Atualiza o filtro de lojas
  Future<void> updateStoreFilter(List<int> storeIds) async {
    final filter = state.currentFilter.copyWith(
      lojaIds: storeIds.isNotEmpty ? storeIds : null,
      pagina: 1,
    );
    
    await applyFilter(filter);
  }

  /// Atualiza a ordenação
  Future<void> updateSorting({
    CashbackSortOption? sortBy,
    SortDirection? direction,
  }) async {
    final filter = state.currentFilter.copyWith(
      orderBy: sortBy,
      sortDirection: direction,
      pagina: 1,
    );
    
    await applyFilter(filter);
  }

  /// Seleciona uma transação para visualização de detalhes
  void selectTransaction(CashbackTransaction transaction) {
    state = state.copyWith(selectedTransaction: transaction);
  }

  /// Limpa a seleção de transação
  void clearSelectedTransaction() {
    state = state.copyWith(selectedTransaction: null);
  }

  /// Recarrega os dados (pull-to-refresh)
  Future<void> refreshData() async {
    await loadCashbackHistory(refresh: true);
  }

  /// Converte exceções em mensagens de erro amigáveis
  String _getErrorMessage(Object error) {
    if (error is Failure) {
      switch (error.runtimeType) {
        case ServerFailure:
          return 'Erro no servidor. Tente novamente.';
        case NetworkFailure:
          return 'Sem conexão com a internet.';
        case CacheFailure:
          return 'Erro ao carregar dados locais.';
        case ValidationFailure:
          return error.message;
        default:
          return 'Ocorreu um erro inesperado.';
      }
    }
    return 'Erro desconhecido.';
  }

  /// Limpa o estado de erro
  void clearError() {
    if (state.hasError) {
      state = state.copyWith(errorMessage: null);
    }
  }
}

// ==================== COMPUTED PROVIDERS ====================

/// Provider computado para transações filtradas por status
@riverpod
List<CashbackTransaction> transactionsByStatus(
  TransactionsByStatusRef ref,
  CashbackTransactionStatus status,
) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  return cashbackState.transactions.where((t) => t.status == status).toList();
}

/// Provider computado para total de cashback das transações carregadas
@riverpod
double totalCashbackAmount(TotalCashbackAmountRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  return cashbackState.transactions
      .fold(0.0, (total, transaction) => total + transaction.valorCashback);
}

/// Provider computado para contagem de transações por status
@riverpod
Map<CashbackTransactionStatus, int> transactionCountByStatus(
  TransactionCountByStatusRef ref,
) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  final Map<CashbackTransactionStatus, int> count = {};
  
  for (final transaction in cashbackState.transactions) {
    count[transaction.status] = (count[transaction.status] ?? 0) + 1;
  }
  
  return count;
}

/// Provider computado para verificar se há filtros ativos
@riverpod
bool hasActiveFilters(HasActiveFiltersRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  return cashbackState.hasActiveFilters;
}

/// Provider computado para contagem de filtros ativos
@riverpod
int activeFiltersCount(ActiveFiltersCountRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  return cashbackState.currentFilter.activeFiltersCount;
}

/// Provider computado para média de cashback por transação
@riverpod
double averageCashbackPerTransaction(AverageCashbackPerTransactionRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  final transactions = cashbackState.transactions;
  
  if (transactions.isEmpty) return 0.0;
  
  final total = transactions.fold(0.0, (sum, t) => sum + t.valorCashback);
  return total / transactions.length;
}

/// Provider computado para transações da última semana
@riverpod
List<CashbackTransaction> recentTransactions(RecentTransactionsRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  final weekAgo = DateTime.now().subtract(const Duration(days: 7));
  
  return cashbackState.transactions
      .where((t) => t.dataTransacao.isAfter(weekAgo))
      .toList();
}

/// Provider computado para maior transação de cashback
@riverpod
CashbackTransaction? highestCashbackTransaction(HighestCashbackTransactionRef ref) {
  final cashbackState = ref.watch(cashbackNotifierProvider);
  
  if (cashbackState.transactions.isEmpty) return null;
  
  return cashbackState.transactions.reduce(
    (current, next) => current.valorCashback > next.valorCashback ? current : next,
  );
}