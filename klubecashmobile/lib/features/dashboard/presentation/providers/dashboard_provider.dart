// lib/features/dashboard/presentation/providers/dashboard_provider.dart
// 📊 Dashboard Provider - Gerenciamento de estado do dashboard do Klube Cash

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../domain/entities/cashback_summary.dart';
import '../../domain/entities/transaction_summary.dart';
import '../../domain/usecases/get_cashback_summary_usecase.dart';
import '../../domain/usecases/get_recent_transactions_usecase.dart';
import '../../../../core/errors/failures.dart';

part 'dashboard_provider.g.dart';

// ==================== STATE CLASSES ====================

/// Estado do dashboard contendo todas as informações necessárias
class DashboardState {
  final bool isLoading;
  final bool isRefreshing;
  final String? errorMessage;
  final CashbackSummary? cashbackSummary;
  final List<TransactionSummary> recentTransactions;
  final DateTime? lastUpdate;

  const DashboardState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.errorMessage,
    this.cashbackSummary,
    this.recentTransactions = const [],
    this.lastUpdate,
  });

  DashboardState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    String? errorMessage,
    CashbackSummary? cashbackSummary,
    List<TransactionSummary>? recentTransactions,
    DateTime? lastUpdate,
  }) {
    return DashbackState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      errorMessage: errorMessage,
      cashbackSummary: cashbackSummary ?? this.cashbackSummary,
      recentTransactions: recentTransactions ?? this.recentTransactions,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }

  /// Verifica se há dados carregados
  bool get hasData => cashbackSummary != null;

  /// Verifica se há erro
  bool get hasError => errorMessage != null;

  /// Verifica se está em estado inicial (sem dados e sem loading)
  bool get isInitial => !isLoading && !hasData && !hasError;
}

// ==================== PROVIDERS ====================

/// Provider para o use case de buscar resumo de cashback
@riverpod
GetCashbackSummaryUsecase getCashbackSummaryUsecase(
  GetCashbackSummaryUsecaseRef ref,
) {
  throw UnimplementedError('Deve ser implementado na injeção de dependência');
}

/// Provider para o use case de buscar transações recentes
@riverpod
GetRecentTransactionsUsecase getRecentTransactionsUsecase(
  GetRecentTransactionsUsecaseRef ref,
) {
  throw UnimplementedError('Deve ser implementado na injeção de dependência');
}

/// Provider principal do dashboard
@riverpod
class DashboardNotifier extends _$DashboardNotifier {
  @override
  DashboardState build() {
    // Carrega os dados iniciais
    loadDashboardData();
    return const DashboardState(isLoading: true);
  }

  /// Carrega todos os dados do dashboard
  Future<void> loadDashboardData() async {
    if (state.isRefreshing) return;

    state = state.copyWith(
      isLoading: state.isInitial,
      isRefreshing: !state.isInitial,
      errorMessage: null,
    );

    try {
      // Executa ambas as operações em paralelo
      final results = await Future.wait([
        _loadCashbackSummary(),
        _loadRecentTransactions(),
      ]);

      final cashbackSummary = results[0] as CashbackSummary?;
      final recentTransactions = results[1] as List<TransactionSummary>;

      state = state.copyWith(
        isLoading: false,
        isRefreshing: false,
        cashbackSummary: cashbackSummary,
        recentTransactions: recentTransactions,
        lastUpdate: DateTime.now(),
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isRefreshing: false,
        errorMessage: _getErrorMessage(e),
      );
    }
  }

  /// Recarrega os dados do dashboard (pull-to-refresh)
  Future<void> refreshDashboard() async {
    await loadDashboardData();
  }

  /// Carrega o resumo de cashback
  Future<CashbackSummary?> _loadCashbackSummary() async {
    final usecase = ref.read(getCashbackSummaryUsecaseProvider);
    final result = await usecase();

    return result.fold(
      (failure) => throw failure,
      (summary) => summary,
    );
  }

  /// Carrega as transações recentes
  Future<List<TransactionSummary>> _loadRecentTransactions() async {
    final usecase = ref.read(getRecentTransactionsUsecaseProvider);
    final result = await usecase(limit: 5); // Últimas 5 transações

    return result.fold(
      (failure) => throw failure,
      (transactions) => transactions,
    );
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

  /// Simula uma atualização do saldo (para uso em outras features)
  void updateCashbackSummary(CashbackSummary newSummary) {
    state = state.copyWith(
      cashbackSummary: newSummary,
      lastUpdate: DateTime.now(),
    );
  }
}

// ==================== COMPUTED PROVIDERS ====================

/// Provider computado para saldo disponível
@riverpod
double? availableBalance(AvailableBalanceRef ref) {
  final dashboardState = ref.watch(dashboardNotifierProvider);
  return dashboardState.cashbackSummary?.availableBalance;
}

/// Provider computado para saldo pendente
@riverpod
double? pendingBalance(PendingBalanceRef ref) {
  final dashboardState = ref.watch(dashboardNotifierProvider);
  return dashboardState.cashbackSummary?.pendingBalance;
}

/// Provider computado para total economizado
@riverpod
double? totalSaved(TotalSavedRef ref) {
  final dashboardState = ref.watch(dashboardNotifierProvider);
  return dashboardState.cashbackSummary?.totalBalance;
}

/// Provider computado para verificar se tem transações recentes
@riverpod
bool hasRecentTransactions(HasRecentTransactionsRef ref) {
  final dashboardState = ref.watch(dashboardNotifierProvider);
  return dashboardState.recentTransactions.isNotEmpty;
}

/// Provider computado para contagem de transações pendentes
@riverpod
int pendingTransactionsCount(PendingTransactionsCountRef ref) {
  final dashboardState = ref.watch(dashboardNotifierProvider);
  return dashboardState.recentTransactions
      .where((transaction) => transaction.isPending)
      .length;
}