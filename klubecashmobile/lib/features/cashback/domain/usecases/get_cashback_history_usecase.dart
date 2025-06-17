// Arquivo: lib/features/cashback/domain/usecases/get_cashback_history_usecase.dart
// Use case para obtenção do histórico de transações de cashback

import 'package:dartz/dartz.dart';
import '../repositories/cashback_repository.dart';
import '../entities/cashback_filter.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por obter o histórico de transações de cashback
/// 
/// Implementa a lógica de negócio para buscar transações com filtros,
/// incluindo validações e transformações necessárias
class GetCashbackHistoryUseCase {
  final CashbackRepository repository;

  const GetCashbackHistoryUseCase(this.repository);

  /// Executa a busca do histórico de cashback
  ///
  /// [params] - Parâmetros de filtro para a consulta
  /// 
  /// Retorna [CashbackTransactionResult] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, CashbackTransactionResult>> call(GetCashbackHistoryParams params) async {
    // Validações básicas do filtro
    final filter = params.filter;

    // Validar período se informado
    if (filter.dataInicio != null && filter.dataFim != null) {
      if (filter.dataInicio!.isAfter(filter.dataFim!)) {
        return Left(ValidationFailure('Data inicial deve ser anterior à data final'));
      }

      // Verificar se o período não é muito extenso (máximo 2 anos)
      final diferenca = filter.dataFim!.difference(filter.dataInicio!);
      if (diferenca.inDays > 730) {
        return Left(ValidationFailure('Período máximo de consulta é de 2 anos'));
      }
    }

    // Validar valores mínimos e máximos
    if (filter.cashbackMinimo != null && filter.cashbackMaximo != null) {
      if (filter.cashbackMinimo! > filter.cashbackMaximo!) {
        return Left(ValidationFailure('Valor mínimo de cashback deve ser menor que o máximo'));
      }
    }

    if (filter.valorMinimoTransacao != null && filter.valorMaximoTransacao != null) {
      if (filter.valorMinimoTransacao! > filter.valorMaximoTransacao!) {
        return Left(ValidationFailure('Valor mínimo da transação deve ser menor que o máximo'));
      }
    }

    // Validar valores negativos
    if (filter.cashbackMinimo != null && filter.cashbackMinimo! < 0) {
      return Left(ValidationFailure('Valor mínimo de cashback deve ser positivo'));
    }

    if (filter.valorMinimoTransacao != null && filter.valorMinimoTransacao! < 0) {
      return Left(ValidationFailure('Valor mínimo da transação deve ser positivo'));
    }

    // Validar paginação
    if (filter.pagina != null && filter.pagina! < 1) {
      return Left(ValidationFailure('Número da página deve ser maior que zero'));
    }

    if (filter.itensPorPagina != null) {
      if (filter.itensPorPagina! < 1 || filter.itensPorPagina! > 100) {
        return Left(ValidationFailure('Itens por página deve estar entre 1 e 100'));
      }
    }

    // Validar texto de busca
    if (filter.textoBusca != null && filter.textoBusca!.length > 100) {
      return Left(ValidationFailure('Texto de busca deve ter no máximo 100 caracteres'));
    }

    // Chamar repositório para buscar histórico
    return await repository.getCashbackHistory(filter: filter);
  }
}

/// Parâmetros necessários para buscar o histórico de cashback
class GetCashbackHistoryParams {
  final CashbackFilter filter;

  const GetCashbackHistoryParams({
    required this.filter,
  });

  /// Construtor conveniente para filtro padrão (sem filtros ativos)
  GetCashbackHistoryParams.defaultFilter() 
    : filter = const CashbackFilter();

  /// Construtor conveniente para filtro por período
  GetCashbackHistoryParams.byPeriod({
    required DateTime startDate,
    required DateTime endDate,
  }) : filter = CashbackFilter(
    dataInicio: startDate,
    dataFim: endDate,
  );

  /// Construtor conveniente para filtro por status
  GetCashbackHistoryParams.byStatus({
    required List<CashbackTransactionStatus> status,
  }) : filter = CashbackFilter(
    status: status,
  );

  /// Construtor conveniente para filtro por loja
  GetCashbackHistoryParams.byStores({
    required List<int> storeIds,
  }) : filter = CashbackFilter(
    lojaIds: storeIds,
  );

  /// Construtor conveniente para busca por texto
  GetCashbackHistoryParams.byText({
    required String searchText,
  }) : filter = CashbackFilter(
    textoBusca: searchText,
  );

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    
    return other is GetCashbackHistoryParams &&
        other.filter == filter;
  }

  @override
  int get hashCode => filter.hashCode;

  @override
  String toString() => 'GetCashbackHistoryParams(filter: $filter)';
}