// Arquivo: lib/features/cashback/domain/repositories/cashback_repository.dart
// Repository interface para operações de cashback no sistema Klube Cash

import 'package:dartz/dartz.dart';

import 'package:klube_cash/core/errors/failures.dart';
import 'package:klube_cash/features/cashback/domain/entities/cashback_transaction.dart';
import 'package:klube_cash/features/cashback/domain/entities/cashback_filter.dart';

/// Modelo para resultado paginado de transações
class CashbackTransactionResult {
  final List<CashbackTransaction> transactions;
  final int total;
  final int currentPage;
  final int totalPages;
  final bool hasNextPage;

  const CashbackTransactionResult({
    required this.transactions,
    required this.total,
    required this.currentPage,
    required this.totalPages,
    required this.hasNextPage,
  });
}

/// Modelo para estatísticas de cashback
class CashbackStatistics {
  final double totalCashback;
  final double totalDisponivel;
  final double totalPendente;
  final double totalUsado;
  final int totalTransacoes;
  final int totalLojasUtilizadas;
  final double mediaTransacao;
  final double percentualUso;

  const CashbackStatistics({
    required this.totalCashback,
    required this.totalDisponivel,
    required this.totalPendente,
    required this.totalUsado,
    required this.totalTransacoes,
    required this.totalLojasUtilizadas,
    required this.mediaTransacao,
    required this.percentualUso,
  });
}

/// Modelo para saldo de cashback por loja
class CashbackBalance {
  final int lojaId;
  final String nomeLoja;
  final String? logoLoja;
  final double saldoDisponivel;
  final double totalCreditado;
  final double totalUsado;
  final DateTime? ultimaMovimentacao;
  final double percentualCashback;

  const CashbackBalance({
    required this.lojaId,
    required this.nomeLoja,
    this.logoLoja,
    required this.saldoDisponivel,
    required this.totalCreditado,
    required this.totalUsado,
    this.ultimaMovimentacao,
    required this.percentualCashback,
  });
}

/// Repository interface para operações de cashback
abstract class CashbackRepository {
  /// Obtém o histórico de transações de cashback com filtros
  Future<Either<Failure, CashbackTransactionResult>> getCashbackHistory({
    required CashbackFilter filter,
  });

  /// Obtém os detalhes de uma transação específica
  Future<Either<Failure, CashbackTransaction>> getTransactionDetails({
    required int transactionId,
  });

  /// Obtém as estatísticas gerais de cashback do usuário
  Future<Either<Failure, CashbackStatistics>> getCashbackStatistics({
    DateTime? startDate,
    DateTime? endDate,
  });

  /// Obtém os saldos de cashback por loja
  Future<Either<Failure, List<CashbackBalance>>> getCashbackBalances();

  /// Obtém o saldo de uma loja específica
  Future<Either<Failure, CashbackBalance>> getStoreBalance({
    required int storeId,
  });

  /// Obtém as transações recentes (últimas 5-10)
  Future<Either<Failure, List<CashbackTransaction>>> getRecentTransactions({
    int limit = 5,
  });

  /// Busca transações por texto (nome da loja, código, etc.)
  Future<Either<Failure, List<CashbackTransaction>>> searchTransactions({
    required String searchText,
    int limit = 20,
  });

  /// Obtém o histórico de movimentações de uma loja específica
  Future<Either<Failure, List<CashbackMovement>>> getMovementHistory({
    required int storeId,
    int page = 1,
    int limit = 50,
  });

  /// Gera relatório de cashback para um período
  Future<Either<Failure, CashbackReport>> generateReport({
    required DateTime startDate,
    required DateTime endDate,
    List<int>? storeIds,
  });

  /// Obtém as categorias de lojas com cashback
  Future<Either<Failure, List<String>>> getCashbackCategories();

  /// Atualiza o cache local de transações
  Future<Either<Failure, void>> refreshCashbackData();
}

/// Modelo para movimentação de cashback
class CashbackMovement {
  final int id;
  final CashbackMovementType tipo;
  final double valor;
  final double saldoAnterior;
  final double saldoAtual;
  final String descricao;
  final DateTime dataOperacao;
  final int? transacaoOrigemId;
  final int? transacaoUsoId;

  const CashbackMovement({
    required this.id,
    required this.tipo,
    required this.valor,
    required this.saldoAnterior,
    required this.saldoAtual,
    required this.descricao,
    required this.dataOperacao,
    this.transacaoOrigemId,
    this.transacaoUsoId,
  });
}

/// Enum para tipos de movimentação
enum CashbackMovementType {
  credito,
  uso,
  estorno,
}

/// Modelo para relatório de cashback
class CashbackReport {
  final DateTime periodo;
  final CashbackStatistics estatisticas;
  final List<CashbackTransaction> transacoes;
  final Map<String, double> cashbackPorLoja;
  final Map<String, double> cashbackPorCategoria;
  final List<Map<String, dynamic>> evolucaoMensal;

  const CashbackReport({
    required this.periodo,
    required this.estatisticas,
    required this.transacoes,
    required this.cashbackPorLoja,
    required this.cashbackPorCategoria,
    required this.evolucaoMensal,
  });
}