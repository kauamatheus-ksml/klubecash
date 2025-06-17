// lib/features/dashboard/domain/usecases/get_recent_transactions_usecase.dart
// Use case para obter transações recentes do usuário

import 'package:dartz/dartz.dart';
import '../entities/transaction_summary.dart';
import '../repositories/dashboard_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter transações recentes do usuário
/// 
/// Implementa a lógica de negócio para recuperar as últimas
/// transações realizadas, com opção de limitar a quantidade
class GetRecentTransactionsUseCase {
  final DashboardRepository repository;

  const GetRecentTransactionsUseCase(this.repository);

  /// Executa a obtenção das transações recentes
  /// 
  /// [params] - Parâmetros opcionais para a consulta
  /// 
  /// Retorna lista de [TransactionSummary] em caso de sucesso 
  /// ou [Failure] em caso de erro
  Future<Either<Failure, List<TransactionSummary>>> call([
    GetRecentTransactionsParams? params,
  ]) async {
    final limit = params?.limit ?? 5;
    
    return await repository.getRecentTransactions(limit: limit);
  }
}

/// Parâmetros opcionais para obtenção de transações recentes
class GetRecentTransactionsParams {
  final int limit;

  const GetRecentTransactionsParams({
    this.limit = 5,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is GetRecentTransactionsParams && other.limit == limit;
  }

  @override
  int get hashCode => limit.hashCode;

  @override
  String toString() => 'GetRecentTransactionsParams(limit: $limit)';
}