// lib/features/dashboard/domain/usecases/get_cashback_summary_usecase.dart
// Use case para obter resumo de cashback do usuário

import 'package:dartz/dartz.dart';
import '../entities/cashback_summary.dart';
import '../repositories/dashboard_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter o resumo de cashback do usuário
/// 
/// Implementa a lógica de negócio para recuperar dados consolidados
/// de saldo, estatísticas e informações do cashback
class GetCashbackSummaryUseCase {
  final DashboardRepository repository;

  const GetCashbackSummaryUseCase(this.repository);

  /// Executa a obtenção do resumo de cashback
  /// 
  /// Retorna [CashbackSummary] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, CashbackSummary>> call() async {
    return await repository.getCashbackSummary();
  }
}