// Arquivo: lib/features/cashback/domain/usecases/filter_cashback_usecase.dart
// Use case para aplicação de filtros em transações de cashback

import 'package:dartz/dartz.dart';
import '../repositories/cashback_repository.dart';
import '../entities/cashback_filter.dart';
import '../entities/cashback_transaction.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por filtrar transações de cashback
/// 
/// Aplica filtros específicos e retorna transações que atendem aos critérios
class FilterCashbackUseCase {
  final CashbackRepository repository;

  const FilterCashbackUseCase(this.repository);

  /// Executa a filtragem de transações de cashback
  ///
  /// [params] - Parâmetros de filtro para aplicar
  /// 
  /// Retorna [List<CashbackTransaction>] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, List<CashbackTransaction>>> call(FilterCashbackParams params) async {
    final filter = params.filter;

    // Validações específicas para filtros
    if (filter.textoBusca != null && filter.textoBusca!.isEmpty) {
      return Left(ValidationFailure('Texto de busca não pode estar vazio'));
    }

    if (filter.textoBusca != null && filter.textoBusca!.length < 2) {
      return Left(ValidationFailure('Texto de busca deve ter pelo menos 2 caracteres'));
    }

    // Validar se há pelo menos um filtro ativo
    if (!filter.hasActiveFilters) {
      return Left(ValidationFailure('Pelo menos um filtro deve ser aplicado'));
    }

    // Para filtros de busca por texto, usar método específico
    if (filter.textoBusca != null && filter.textoBusca!.isNotEmpty) {
      return await repository.searchTransactions(
        searchText: filter.textoBusca!,
        limit: params.limit,
      );
    }

    // Para outros filtros, usar histórico completo
    final result = await repository.getCashbackHistory(filter: filter);
    
    return result.fold(
      (failure) => Left(failure),
      (transactionResult) => Right(transactionResult.transactions),
    );
  }
}

/// Parâmetros para filtrar transações de cashback
class FilterCashbackParams {
  final CashbackFilter filter;
  final int limit;

  const FilterCashbackParams({
    required this.filter,
    this.limit = 50,
  });

  /// Construtor para filtro por status específico
  FilterCashbackParams.byStatus({
    required CashbackTransactionStatus status,
    this.limit = 50,
  }) : filter = CashbackFilter(status: [status]);

  /// Construtor para filtro por múltiplos status
  FilterCashbackParams.byMultipleStatus({
    required List<CashbackTransactionStatus> status,
    this.limit = 50,
  }) : filter = CashbackFilter(status: status);

  /// Construtor para filtro por loja específica
  FilterCashbackParams.byStore({
    required int storeId,
    this.limit = 50,
  }) : filter = CashbackFilter(lojaIds: [storeId]);

  /// Construtor para filtro por categoria
  FilterCashbackParams.byCategory({
    required String category,
    this.limit = 50,
  }) : filter = CashbackFilter(categoria: category);

  /// Construtor para filtro por período
  FilterCashbackParams.byDateRange({
    required DateTime startDate,
    required DateTime endDate,
    this.limit = 50,
  }) : filter = CashbackFilter(
    dataInicio: startDate,
    dataFim: endDate,
  );

  /// Construtor para filtro por valor de cashback
  FilterCashbackParams.byCashbackValue({
    double? minValue,
    double? maxValue,
    this.limit = 50,
  }) : filter = CashbackFilter(
    cashbackMinimo: minValue,
    cashbackMaximo: maxValue,
  );

  /// Construtor para busca por texto
  FilterCashbackParams.bySearch({
    required String searchText,
    this.limit = 20,
  }) : filter = CashbackFilter(textoBusca: searchText);

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    
    return other is FilterCashbackParams &&
        other.filter == filter &&
        other.limit == limit;
  }

  @override
  int get hashCode => Object.hash(filter, limit);

  @override
  String toString() => 'FilterCashbackParams(filter: $filter, limit: $limit)';
}