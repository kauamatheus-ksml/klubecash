// lib/features/cashback/data/datasources/cashback_remote_datasource.dart
// Arquivo: Data Source Remoto de Cashback - Camada Data

import 'package:dio/dio.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/cashback_filter_model.dart';
import '../models/cashback_transaction_model.dart';

/// Data source remoto para operações de cashback
/// 
/// Responsável por comunicar-se com a API do backend PHP
/// para operações relacionadas ao cashback e transações.
abstract class CashbackRemoteDataSource {
  /// Obtém o histórico de transações de cashback com filtros
  Future<Map<String, dynamic>> getCashbackHistory(CashbackFilterModel filter);
  
  /// Obtém estatísticas de cashback do usuário
  Future<Map<String, dynamic>> getCashbackStatistics([String? storeId]);
  
  /// Obtém detalhes de uma transação específica
  Future<CashbackTransactionModel> getCashbackDetails(String transactionId);
  
  /// Obtém detalhes do saldo por loja
  Future<Map<String, dynamic>> getBalanceDetails([String? storeId]);
  
  /// Usa saldo de cashback em uma compra
  Future<Map<String, dynamic>> useBalance(String storeId, double amount, String description);
  
  /// Simula uso de saldo para verificar disponibilidade
  Future<Map<String, dynamic>> simulateBalanceUse(String storeId, double amount);
  
  /// Obtém histórico de movimentações de saldo
  Future<Map<String, dynamic>> getBalanceHistory(String storeId, {int page = 1});
  
  /// Gera relatório de cashback exportável
  Future<Map<String, dynamic>> exportCashbackReport(CashbackFilterModel filter);
}

/// Implementação concreta do data source remoto de cashback
class CashbackRemoteDataSourceImpl implements CashbackRemoteDataSource {
  final ApiClient _apiClient;

  CashbackRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<Map<String, dynamic>> getCashbackHistory(CashbackFilterModel filter) async {
    try {
      final queryParams = filter.toQueryParams();
      
      final response = await _apiClient.get(
        '/cliente/actions',
        queryParameters: {
          'action': 'history',
          ...queryParams,
        },
      );

      final data = _validateResponse(response);
      
      // Parse das transações usando o modelo
      if (data['data'] != null && data['data']['transacoes'] != null) {
        final transactions = CashbackTransactionModel.fromJsonList(
          data['data']['transacoes'] as List<dynamic>
        );
        
        return {
          'status': true,
          'data': {
            'transactions': transactions.map((t) => t.toJson()).toList(),
            'pagination': data['data']['paginacao'] ?? {},
            'summary': data['data']['resumo'] ?? {},
          }
        };
      }

      return data;
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar histórico de cashback: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> getCashbackStatistics([String? storeId]) async {
    try {
      final queryParams = <String, dynamic>{
        'action': 'balance',
      };
      
      if (storeId != null) {
        queryParams['loja_id'] = storeId;
      }

      final response = await _apiClient.get(
        '/cliente/actions',
        queryParameters: queryParams,
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar estatísticas de cashback: $e');
    }
  }

  @override
  Future<CashbackTransactionModel> getCashbackDetails(String transactionId) async {
    try {
      final response = await _apiClient.post(
        '/cliente/actions',
        data: {
          'action': 'transaction_details',
          'transaction_id': transactionId,
        },
      );

      final data = _validateResponse(response);
      
      if (data['data'] != null && data['data']['transacao'] != null) {
        return CashbackTransactionModel.fromJson(
          data['data']['transacao'] as Map<String, dynamic>
        );
      }

      throw const ServerException('Dados da transação não encontrados');
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar detalhes da transação: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> getBalanceDetails([String? storeId]) async {
    try {
      final queryParams = <String, dynamic>{
        'action': 'store_balance_details',
      };
      
      if (storeId != null) {
        queryParams['loja_id'] = storeId;
      }

      final response = await _apiClient.get(
        '/cliente/actions',
        queryParameters: queryParams,
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar detalhes do saldo: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> useBalance(String storeId, double amount, String description) async {
    try {
      final response = await _apiClient.post(
        '/api/balance',
        data: {
          'action': 'use_balance',
          'store_id': storeId,
          'amount': amount,
          'description': description.isNotEmpty ? description : 'Uso de saldo cashback',
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao usar saldo: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> simulateBalanceUse(String storeId, double amount) async {
    try {
      final response = await _apiClient.post(
        '/cliente/actions',
        data: {
          'action': 'simulate_balance_use',
          'loja_id': storeId,
          'valor': amount,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao simular uso de saldo: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> getBalanceHistory(String storeId, {int page = 1}) async {
    try {
      final response = await _apiClient.get(
        '/api/balance',
        queryParameters: {
          'action': 'movement_history',
          'store_id': storeId,
          'page': page.toString(),
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar histórico de movimentações: $e');
    }
  }

  @override
  Future<Map<String, dynamic>> exportCashbackReport(CashbackFilterModel filter) async {
    try {
      final queryParams = filter.toQueryParams();
      
      final response = await _apiClient.post(
        '/cliente/actions',
        data: {
          'action': 'report',
          'filters': queryParams,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao gerar relatório: $e');
    }
  }

  /// Método auxiliar para buscar saldo específico por loja
  Future<Map<String, dynamic>> getStoreBalance(String storeId) async {
    try {
      final response = await _apiClient.get(
        '/api/balance',
        queryParameters: {
          'action': 'store_balance',
          'store_id': storeId,
        },
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar saldo da loja: $e');
    }
  }

  /// Método auxiliar para obter resumo geral de cashback
  Future<Map<String, dynamic>> getCashbackSummary() async {
    try {
      final response = await _apiClient.get(
        ApiConstants.cashbackSummaryEndpoint,
      );

      return _validateResponse(response);
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException('Erro ao carregar resumo de cashback: $e');
    }
  }

  /// Método auxiliar para buscar transações recentes
  Future<List<CashbackTransactionModel>> getRecentTransactions({int limit = 5}) async {
    try {
      final filter = CashbackFilterModel(
        perPage: limit,
        sortBy: CashbackSortBy.date,
        sortOrder: SortOrder.descending,
      );

      final result = await getCashbackHistory(filter);
      
      if (result['data'] != null && result['data']['transactions'] != null) {
        return CashbackTransactionModel.fromJsonList(
          result['data']['transactions'] as List<dynamic>
        );
      }

      return [];
    } catch (e) {
      throw ServerException('Erro ao carregar transações recentes: $e');
    }
  }

  /// Método auxiliar para buscar transações por status
  Future<Map<String, dynamic>> getTransactionsByStatus(CashbackTransactionStatus status) async {
    try {
      final filter = CashbackFilterModel(
        status: [status],
        sortBy: CashbackSortBy.date,
        sortOrder: SortOrder.descending,
      );

      return await getCashbackHistory(filter);
    } catch (e) {
      throw ServerException('Erro ao carregar transações por status: $e');
    }
  }

  /// Valida a resposta da API e retorna os dados ou lança exceção
  Map<String, dynamic> _validateResponse(Response response) {
    final data = response.data;
    
    if (data is! Map<String, dynamic>) {
      throw const ServerException('Formato de resposta inválido');
    }

    final status = data['status'];
    if (status != true && status != 'true') {
      final message = data['message']?.toString() ?? 'Erro desconhecido';
      throw ServerException(message);
    }

    return data;
  }

  /// Converte DioException em exceções da aplicação
  Exception _handleDioException(DioException dioException) {
    switch (dioException.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        throw const NetworkException('Timeout na conexão com o servidor');
      
      case DioExceptionType.badResponse:
        final statusCode = dioException.response?.statusCode;
        final responseData = dioException.response?.data;
        
        String message = 'Erro no servidor';
        if (responseData is Map<String, dynamic>) {
          message = responseData['message']?.toString() ?? message;
        }
        
        switch (statusCode) {
          case 401:
            throw const ServerException('Sessão expirada. Faça login novamente');
          case 403:
            throw const ServerException('Acesso negado');
          case 404:
            throw const ServerException('Recurso não encontrado');
          case 422:
            throw ServerException('Dados inválidos: $message');
          case 500:
            throw const ServerException('Erro interno do servidor');
          default:
            throw ServerException(message);
        }
      
      case DioExceptionType.cancel:
        throw const NetworkException('Operação cancelada');
      
      case DioExceptionType.connectionError:
        throw const NetworkException('Erro de conexão. Verifique sua internet');
      
      case DioExceptionType.badCertificate:
        throw const NetworkException('Erro de certificado SSL');
      
      case DioExceptionType.unknown:
        throw NetworkException(
          'Erro de rede: ${dioException.message ?? "Erro desconhecido"}'
        );
    }
  }
}

/// Classe auxiliar para resposta paginada de transações
class PaginatedCashbackResponse {
  final List<CashbackTransactionModel> transactions;
  final int currentPage;
  final int totalPages;
  final int totalItems;
  final int itemsPerPage;
  final bool hasNextPage;
  final bool hasPreviousPage;

  const PaginatedCashbackResponse({
    required this.transactions,
    required this.currentPage,
    required this.totalPages,
    required this.totalItems,
    required this.itemsPerPage,
    required this.hasNextPage,
    required this.hasPreviousPage,
  });

  factory PaginatedCashbackResponse.fromJson(Map<String, dynamic> json) {
    final transactionsData = json['transactions'] as List<dynamic>? ?? [];
    final paginationData = json['pagination'] as Map<String, dynamic>? ?? {};

    final transactions = CashbackTransactionModel.fromJsonList(transactionsData);
    final currentPage = paginationData['pagina_atual'] as int? ?? 1;
    final totalPages = paginationData['total_paginas'] as int? ?? 1;
    final totalItems = paginationData['total'] as int? ?? 0;
    final itemsPerPage = paginationData['por_pagina'] as int? ?? 10;

    return PaginatedCashbackResponse(
      transactions: transactions,
      currentPage: currentPage,
      totalPages: totalPages,
      totalItems: totalItems,
      itemsPerPage: itemsPerPage,
      hasNextPage: currentPage < totalPages,
      hasPreviousPage: currentPage > 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'transactions': transactions.map((t) => t.toJson()).toList(),
      'pagination': {
        'pagina_atual': currentPage,
        'total_paginas': totalPages,
        'total': totalItems,
        'por_pagina': itemsPerPage,
        'tem_proxima': hasNextPage,
        'tem_anterior': hasPreviousPage,
      },
    };
  }
}

/// Enum reutilizado dos modelos
enum CashbackTransactionStatus {
  pending('pending'),
  approved('approved'),
  canceled('canceled'),
  processing('processing'),
  refunded('refunded'),
  expired('expired'),
  paymentPending('payment_pending');

  const CashbackTransactionStatus(this.value);
  final String value;
}

enum CashbackSortBy {
  date('date'),
  amount('amount'),
  cashback('cashback'),
  store('store'),
  status('status');

  const CashbackSortBy(this.value);
  final String value;
}

enum SortOrder {
  ascending('asc'),
  descending('desc');

  const SortOrder(this.value);
  final String value;
}