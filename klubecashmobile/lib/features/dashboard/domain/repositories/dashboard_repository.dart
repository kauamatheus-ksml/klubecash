// lib/features/dashboard/domain/repositories/dashboard_repository.dart
// Interface do repositório de dashboard

import 'package:dartz/dartz.dart';
import '../entities/cashback_summary.dart';
import '../entities/transaction_summary.dart';
import '../../../../core/errors/failures.dart';

/// Interface do repositório de dashboard
///
/// Define os contratos para operações relacionadas aos dados
/// do dashboard principal do Klube Cash
abstract class DashboardRepository {
  /// Obtém o resumo de cashback do usuário
  ///
  /// Retorna [CashbackSummary] com saldos e estatísticas
  /// ou [Failure] em caso de erro
  Future<Either<Failure, CashbackSummary>> getCashbackSummary();

  /// Obtém lista de transações recentes
  ///
  /// [limit] - Quantidade máxima de transações (padrão: 5)
  /// 
  /// Retorna lista de [TransactionSummary] ou [Failure] em caso de erro
  Future<Either<Failure, List<TransactionSummary>>> getRecentTransactions({
    int limit = 5,
  });

  /// Obtém dados completos do dashboard
  ///
  /// Combina resumo de cashback e transações recentes
  /// 
  /// Retorna mapa com dados consolidados ou [Failure] em caso de erro
  Future<Either<Failure, Map<String, dynamic>>> getDashboardData();

  /// Atualiza os dados do dashboard
  ///
  /// Força uma atualização dos dados do servidor
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> refreshDashboard();

  /// Obtém estatísticas de uso de cashback
  ///
  /// [period] - Período para análise (ex: 'month', 'year')
  /// 
  /// Retorna mapa com estatísticas ou [Failure] em caso de erro
  Future<Either<Failure, Map<String, dynamic>>> getCashbackStatistics({
    String period = 'month',
  });

  /// Obtém lojas favoritas do usuário
  ///
  /// [limit] - Quantidade máxima de lojas (padrão: 3)
  /// 
  /// Retorna lista de lojas favoritas ou [Failure] em caso de erro
  Future<Either<Failure, List<Map<String, dynamic>>>> getFavoriteStores({
    int limit = 3,
  });

  /// Obtém saldos por loja
  ///
  /// Retorna lista com saldos disponíveis por loja
  /// ou [Failure] em caso de erro
  Future<Either<Failure, List<Map<String, dynamic>>>> getBalancesByStore();

  /// Obtém notificações do dashboard
  ///
  /// [limit] - Quantidade máxima de notificações (padrão: 3)
  /// 
  /// Retorna lista de notificações ou [Failure] em caso de erro
  Future<Either<Failure, List<Map<String, dynamic>>>> getDashboardNotifications({
    int limit = 3,
  });
}