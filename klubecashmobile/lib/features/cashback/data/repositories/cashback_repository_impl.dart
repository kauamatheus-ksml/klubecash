// lib/features/cashback/data/repositories/cashback_repository_impl.dart
// Arquivo: Implementação do Repository de Cashback - Camada Data

import 'package:dartz/dartz.dart';

import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';
import '../../domain/entities/cashback_filter.dart';
import '../../domain/entities/cashback_transaction.dart';
import '../../domain/repositories/cashback_repository.dart';
import '../datasources/cashback_remote_datasource.dart';
import '../models/cashback_filter_model.dart';
import '../models/cashback_transaction_model.dart';

/// Implementação concreta do repositório de cashback
/// 
/// Responsável por coordenar operações entre o datasource remoto
/// e a camada de domínio, convertendo models em entities e 
/// exceptions em failures apropriadas.
class CashbackRepositoryImpl implements CashbackRepository {
  final CashbackRemoteDataSource _remoteDataSource;
  final NetworkInfo _networkInfo;

  CashbackRepositoryImpl({
    required CashbackRemoteDataSource remoteDataSource,
    required NetworkInfo networkInfo,
  })  : _remoteDataSource = remoteDataSource,
        _networkInfo = networkInfo;

  @override
  Future<Either<Failure, CashbackHistoryResult>> getCashbackHistory(
    CashbackFilter filter,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      // Converte entity para model
      final filterModel = _filterEntityToModel(filter);
      
      // Busca dados do datasource
      final result = await _remoteDataSource.getCashbackHistory(filterModel);
      
      // Parse dos dados de resposta
      final data = result['data'] as Map<String, dynamic>? ?? {};
      
      // Converte transactions
      final transactionsData = data['transactions'] as List<dynamic>? ?? [];
      final transactions = CashbackTransactionModel
          .fromJsonList(transactionsData)
          .map((model) => model as CashbackTransaction)
          .toList();
      
      // Parse da paginação
      final paginationData = data['pagination'] as Map<String, dynamic>? ?? {};
      final pagination = PaginationInfo(
        currentPage: paginationData['pagina_atual'] as int? ?? 1,
        totalPages: paginationData['total_paginas'] as int? ?? 1,
        totalItems: paginationData['total'] as int? ?? 0,
        itemsPerPage: paginationData['por_pagina'] as int? ?? 10,
        hasNextPage: (paginationData['pagina_atual'] as int? ?? 1) < (paginationData['total_paginas'] as int? ?? 1),
        hasPreviousPage: (paginationData['pagina_atual'] as int? ?? 1) > 1,
      );

      // Parse do resumo
      final summaryData = data['summary'] as Map<String, dynamic>? ?? {};
      final summary = CashbackSummary(
        totalAmount: _parseDouble(summaryData['total_transacoes']),
        totalCashback: _parseDouble(summaryData['total_cashback']),
        averageAmount: _parseDouble(summaryData['ticket_medio']),
        transactionCount: summaryData['quantidade_transacoes'] as int? ?? 0,
      );

      return Right(CashbackHistoryResult(
        transactions: transactions,
        pagination: pagination,
        summary: summary,
      ));
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar histórico: $e'));
    }
  }

  @override
  Future<Either<Failure, CashbackTransaction>> getCashbackDetails(
    String transactionId,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final transactionModel = await _remoteDataSource.getCashbackDetails(transactionId);
      return Right(transactionModel as CashbackTransaction);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar detalhes: $e'));
    }
  }

  @override
  Future<Either<Failure, CashbackStats>> getCashbackStatistics([String? storeId]) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getCashbackStatistics(storeId);
      final data = result['data'] as Map<String, dynamic>? ?? {};

      final stats = CashbackStats(
        totalBalance: _parseDouble(data['saldo_total']),
        availableBalance: _parseDouble(data['saldo_disponivel']),
        usedBalance: _parseDouble(data['saldo_usado']),
        pendingBalance: _parseDouble(data['saldo_pendente']),
        totalEarned: _parseDouble(data['total_ganho']),
        totalTransactions: data['total_transacoes'] as int? ?? 0,
        thisMonthEarned: _parseDouble(data['ganho_mes_atual']),
        lastTransactionDate: _parseDateTime(data['ultima_transacao']),
      );

      return Right(stats);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar estatísticas: $e'));
    }
  }

  @override
  Future<Either<Failure, BalanceDetails>> getBalanceDetails([String? storeId]) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getBalanceDetails(storeId);
      final data = result['data'] as Map<String, dynamic>? ?? {};

      final balanceDetails = BalanceDetails(
        storeId: storeId,
        storeName: data['nome_loja']?.toString(),
        availableBalance: _parseDouble(data['saldo_disponivel']),
        totalEarned: _parseDouble(data['total_creditado']),
        totalUsed: _parseDouble(data['total_usado']),
        transactionCount: data['total_transacoes'] as int? ?? 0,
        lastMovement: _parseDateTime(data['ultima_movimentacao']),
        canUseBalance: data['pode_usar_saldo'] as bool? ?? false,
        minimumAmount: _parseDouble(data['valor_minimo_uso']),
      );

      return Right(balanceDetails);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar detalhes do saldo: $e'));
    }
  }

  @override
  Future<Either<Failure, UseBalanceResult>> useBalance(
    String storeId,
    double amount,
    String description,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      // Validações básicas
      if (amount <= 0) {
        return const Left(ValidationFailure('Valor deve ser maior que zero'));
      }

      if (storeId.isEmpty) {
        return const Left(ValidationFailure('ID da loja é obrigatório'));
      }

      final result = await _remoteDataSource.useBalance(storeId, amount, description);
      final data = result['data'] as Map<String, dynamic>? ?? {};

      final useResult = UseBalanceResult(
        success: result['status'] == true,
        transactionId: data['transacao_id']?.toString(),
        newBalance: _parseDouble(data['novo_saldo']),
        usedAmount: amount,
        message: result['message']?.toString() ?? 'Saldo usado com sucesso',
      );

      return Right(useResult);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao usar saldo: $e'));
    }
  }

  @override
  Future<Either<Failure, BalanceSimulation>> simulateBalanceUse(
    String storeId,
    double amount,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.simulateBalanceUse(storeId, amount);
      final data = result['data'] as Map<String, dynamic>? ?? {};

      final simulation = BalanceSimulation(
        canUse: data['pode_usar'] as bool? ?? false,
        availableBalance: _parseDouble(data['saldo_disponivel']),
        requestedAmount: amount,
        remainingBalance: _parseDouble(data['saldo_restante']),
        message: data['mensagem']?.toString(),
        restrictions: List<String>.from(data['restricoes'] as List? ?? []),
      );

      return Right(simulation);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao simular uso: $e'));
    }
  }

  @override
  Future<Either<Failure, List<BalanceMovement>>> getBalanceHistory(
    String storeId, {
    int page = 1,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await _remoteDataSource.getBalanceHistory(storeId, page: page);
      final data = result['data'] as Map<String, dynamic>? ?? {};
      final movementsData = data['movimentacoes'] as List<dynamic>? ?? [];

      final movements = movementsData.map((movementData) {
        final movement = movementData as Map<String, dynamic>;
        return BalanceMovement(
          id: movement['id']?.toString() ?? '',
          type: _parseMovementType(movement['tipo_operacao']),
          amount: _parseDouble(movement['valor']),
          description: movement['descricao']?.toString() ?? '',
          date: _parseDateTime(movement['data_operacao']),
          transactionId: movement['transacao_id']?.toString(),
          balance: _parseDouble(movement['saldo_apos']),
        );
      }).toList();

      return Right(movements);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar histórico: $e'));
    }
  }

  @override
  Future<Either<Failure, List<CashbackTransaction>>> getRecentTransactions({
    int limit = 5,
  }) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final transactions = await _remoteDataSource.getRecentTransactions(limit: limit);
      return Right(transactions.map((model) => model as CashbackTransaction).toList());
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao carregar transações: $e'));
    }
  }

  @override
  Future<Either<Failure, ExportResult>> exportCashbackReport(
    CashbackFilter filter,
  ) async {
    if (!await _networkInfo.isConnected) {
      return const Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final filterModel = _filterEntityToModel(filter);
      final result = await _remoteDataSource.exportCashbackReport(filterModel);
      final data = result['data'] as Map<String, dynamic>? ?? {};

      final exportResult = ExportResult(
        downloadUrl: data['download_url']?.toString(),
        fileName: data['file_name']?.toString() ?? 'extrato_cashback.pdf',
        fileSize: data['file_size'] as int? ?? 0,
        expiresAt: _parseDateTime(data['expires_at']),
      );

      return Right(exportResult);
    } on ServerException catch (e) {
      return Left(CashbackFailure(e.message));
    } on NetworkException catch (e) {
      return Left(NetworkFailure(e.message));
    } catch (e) {
      return Left(CashbackFailure('Erro inesperado ao exportar relatório: $e'));
    }
  }

  /// Converte entity de filtro para model
  CashbackFilterModel _filterEntityToModel(CashbackFilter filter) {
    return CashbackFilterModel(
      status: filter.status?.map((s) => _mapTransactionStatus(s)).toList(),
      storeId: filter.storeId,
      storeName: filter.storeName,
      startDate: filter.startDate,
      endDate: filter.endDate,
      minAmount: filter.minAmount,
      maxAmount: filter.maxAmount,
      searchTerm: filter.searchTerm,
      userId: filter.userId,
      sortBy: filter.sortBy != null ? _mapSortBy(filter.sortBy!) : null,
      sortOrder: filter.sortOrder != null ? _mapSortOrder(filter.sortOrder!) : null,
      page: filter.page,
      perPage: filter.perPage,
      transactionCode: filter.transactionCode,
      includeBalance: filter.includeBalance,
      onlyWithCashback: filter.onlyWithCashback,
    );
  }

  /// Mapeia status da entity para model
  CashbackTransactionStatus _mapTransactionStatus(dynamic status) {
    if (status.toString() == 'pending') return CashbackTransactionStatus.pending;
    if (status.toString() == 'approved') return CashbackTransactionStatus.approved;
    if (status.toString() == 'canceled') return CashbackTransactionStatus.canceled;
    if (status.toString() == 'processing') return CashbackTransactionStatus.processing;
    if (status.toString() == 'refunded') return CashbackTransactionStatus.refunded;
    if (status.toString() == 'expired') return CashbackTransactionStatus.expired;
    if (status.toString() == 'paymentPending') return CashbackTransactionStatus.paymentPending;
    return CashbackTransactionStatus.pending;
  }

  /// Mapeia campo de ordenação da entity para model
  CashbackSortBy _mapSortBy(dynamic sortBy) {
    if (sortBy.toString() == 'date') return CashbackSortBy.date;
    if (sortBy.toString() == 'amount') return CashbackSortBy.amount;
    if (sortBy.toString() == 'cashback') return CashbackSortBy.cashback;
    if (sortBy.toString() == 'store') return CashbackSortBy.store;
    if (sortBy.toString() == 'status') return CashbackSortBy.status;
    return CashbackSortBy.date;
  }

  /// Mapeia ordem de classificação da entity para model
  SortOrder _mapSortOrder(dynamic sortOrder) {
    if (sortOrder.toString() == 'ascending') return SortOrder.ascending;
    if (sortOrder.toString() == 'descending') return SortOrder.descending;
    return SortOrder.descending;
  }

  /// Parse de tipo de movimentação de saldo
  BalanceMovementType _parseMovementType(dynamic type) {
    final typeStr = type.toString().toLowerCase();
    switch (typeStr) {
      case 'credito':
        return BalanceMovementType.credit;
      case 'uso':
        return BalanceMovementType.use;
      case 'estorno':
        return BalanceMovementType.refund;
      case 'ajuste':
        return BalanceMovementType.adjustment;
      default:
        return BalanceMovementType.credit;
    }
  }

  /// Helpers para parsing seguro
  double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) {
      final cleanValue = value.replaceAll(RegExp(r'[R$\s]'), '').replaceAll(',', '.');
      return double.tryParse(cleanValue) ?? 0.0;
    }
    return 0.0;
  }

  DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String && value.isNotEmpty) {
      try {
        return DateTime.parse(value);
      } catch (e) {
        return null;
      }
    }
    return null;
  }
}

/// Resultado da consulta de histórico de cashback
class CashbackHistoryResult {
  final List<CashbackTransaction> transactions;
  final PaginationInfo pagination;
  final CashbackSummary summary;

  const CashbackHistoryResult({
    required this.transactions,
    required this.pagination,
    required this.summary,
  });
}

/// Informações de paginação
class PaginationInfo {
  final int currentPage;
  final int totalPages;
  final int totalItems;
  final int itemsPerPage;
  final bool hasNextPage;
  final bool hasPreviousPage;

  const PaginationInfo({
    required this.currentPage,
    required this.totalPages,
    required this.totalItems,
    required this.itemsPerPage,
    required this.hasNextPage,
    required this.hasPreviousPage,
  });
}

/// Resumo de cashback
class CashbackSummary {
  final double totalAmount;
  final double totalCashback;
  final double averageAmount;
  final int transactionCount;

  const CashbackSummary({
    required this.totalAmount,
    required this.totalCashback,
    required this.averageAmount,
    required this.transactionCount,
  });
}

/// Estatísticas de cashback
class CashbackStats {
  final double totalBalance;
  final double availableBalance;
  final double usedBalance;
  final double pendingBalance;
  final double totalEarned;
  final int totalTransactions;
  final double thisMonthEarned;
  final DateTime? lastTransactionDate;

  const CashbackStats({
    required this.totalBalance,
    required this.availableBalance,
    required this.usedBalance,
    required this.pendingBalance,
    required this.totalEarned,
    required this.totalTransactions,
    required this.thisMonthEarned,
    this.lastTransactionDate,
  });
}

/// Detalhes do saldo por loja
class BalanceDetails {
  final String? storeId;
  final String? storeName;
  final double availableBalance;
  final double totalEarned;
  final double totalUsed;
  final int transactionCount;
  final DateTime? lastMovement;
  final bool canUseBalance;
  final double minimumAmount;

  const BalanceDetails({
    this.storeId,
    this.storeName,
    required this.availableBalance,
    required this.totalEarned,
    required this.totalUsed,
    required this.transactionCount,
    this.lastMovement,
    required this.canUseBalance,
    required this.minimumAmount,
  });
}

/// Resultado do uso de saldo
class UseBalanceResult {
  final bool success;
  final String? transactionId;
  final double newBalance;
  final double usedAmount;
  final String message;

  const UseBalanceResult({
    required this.success,
    this.transactionId,
    required this.newBalance,
    required this.usedAmount,
    required this.message,
  });
}

/// Simulação de uso de saldo
class BalanceSimulation {
  final bool canUse;
  final double availableBalance;
  final double requestedAmount;
  final double remainingBalance;
  final String? message;
  final List<String> restrictions;

  const BalanceSimulation({
    required this.canUse,
    required this.availableBalance,
    required this.requestedAmount,
    required this.remainingBalance,
    this.message,
    required this.restrictions,
  });
}

/// Movimentação de saldo
class BalanceMovement {
  final String id;
  final BalanceMovementType type;
  final double amount;
  final String description;
  final DateTime date;
  final String? transactionId;
  final double balance;

  const BalanceMovement({
    required this.id,
    required this.type,
    required this.amount,
    required this.description,
    required this.date,
    this.transactionId,
    required this.balance,
  });
}

/// Tipo de movimentação de saldo
enum BalanceMovementType {
  credit,
  use,
  refund,
  adjustment,
}

/// Resultado de exportação
class ExportResult {
  final String? downloadUrl;
  final String fileName;
  final int fileSize;
  final DateTime? expiresAt;

  const ExportResult({
    this.downloadUrl,
    required this.fileName,
    required this.fileSize,
    this.expiresAt,
  });
}

/// Enums reutilizados dos models (compatibilidade)
enum CashbackTransactionStatus {
  pending,
  approved,
  canceled,
  processing,
  refunded,
  expired,
  paymentPending,
}

enum CashbackSortBy {
  date,
  amount,
  cashback,
  store,
  status,
}

enum SortOrder {
  ascending,
  descending,
}