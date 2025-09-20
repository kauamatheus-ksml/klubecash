// lib/features/dashboard/data/datasources/dashboard_remote_datasource.dart
// Fonte de dados remota para dashboard - responsável pelas chamadas à API

import 'package:dio/dio.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/cashback_summary_model.dart';
import '../models/transaction_summary_model.dart';

/// Interface abstrata para o datasource remoto do dashboard
abstract class DashboardRemoteDataSource {
  /// Busca resumo de cashback do usuário
  Future<CashbackSummaryModel> getCashbackSummary();

  /// Busca transações recentes do usuário
  Future<List<TransactionSummaryModel>> getRecentTransactions({
    int limit = 5,
  });

  /// Busca dados completos do dashboard
  Future<Map<String, dynamic>> getDashboardData();
}

/// Implementação do datasource remoto do dashboard
class DashboardRemoteDataSourceImpl implements DashboardRemoteDataSource {
  final ApiClient _apiClient;

  const DashboardRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<CashbackSummaryModel> getCashbackSummary() async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.cashbackSummaryEndpoint,
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data!;
        
        // Verificar se a API retornou status de sucesso
        if (data['status'] == true) {
          return CashbackSummaryModel.fromJson(data['data'] ?? data);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar resumo de cashback',
            statusCode: response.statusCode ?? 500,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro na resposta do servidor',
          statusCode: response.statusCode ?? 500,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar resumo de cashback: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<List<TransactionSummaryModel>> getRecentTransactions({
    int limit = 5,
  }) async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.transactionsEndpoint,
        queryParameters: {
          'limit': limit,
          'type': 'recent',
        },
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data!;
        
        // Verificar se a API retornou status de sucesso
        if (data['status'] == true) {
          final transactionsData = data['data'] as List? ?? [];
          return transactionsData
              .map((json) => TransactionSummaryModel.fromJson(json))
              .toList();
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar transações recentes',
            statusCode: response.statusCode ?? 500,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro na resposta do servidor',
          statusCode: response.statusCode ?? 500,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar transações recentes: $e',
        statusCode: 500,
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getDashboardData() async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.dashboardEndpoint,
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data!;
        
        // Verificar se a API retornou status de sucesso
        if (data['status'] == true) {
          return data['data'] ?? {};
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar dados do dashboard',
            statusCode: response.statusCode ?? 500,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro na resposta do servidor',
          statusCode: response.statusCode ?? 500,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar dados do dashboard: $e',
        statusCode: 500,
      );
    }
  }

  /// Busca saldo do usuário
  Future<Map<String, dynamic>> getUserBalance() async {
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiConstants.balanceEndpoint,
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data!;
        
        if (data['status'] == true) {
          return data['data'] ?? {};
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar saldo',
            statusCode: response.statusCode ?? 500,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro na resposta do servidor',
          statusCode: response.statusCode ?? 500,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar saldo: $e',
        statusCode: 500,
      );
    }
  }

  /// Busca extrato detalhado de transações
  Future<Map<String, dynamic>> getStatement({
    Map<String, dynamic>? filters,
    int page = 1,
    int? limit,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        if (limit != null) 'limit': limit,
        if (filters != null) ...filters,
      };

      final response = await _apiClient.get<Map<String, dynamic>>(
        '${ApiConstants.transactionsEndpoint}/statement',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data!;
        
        if (data['status'] == true) {
          return data['data'] ?? {};
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar extrato',
            statusCode: response.statusCode ?? 500,
          );
        }
      } else {
        throw ServerException(
          message: 'Erro na resposta do servidor',
          statusCode: response.statusCode ?? 500,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar extrato: $e',
        statusCode: 500,
      );
    }
  }

  /// Trata exceções do Dio e converte para exceções customizadas
  ServerException _handleDioException(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return ServerException(
          message: 'Tempo limite de conexão excedido',
          statusCode: 408,
        );
        
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode ?? 500;
        String message = 'Erro no servidor';
        
        if (e.response?.data is Map<String, dynamic>) {
          final responseData = e.response!.data as Map<String, dynamic>;
          message = responseData['message'] ?? message;
        }
        
        return ServerException(
          message: message,
          statusCode: statusCode,
        );
        
      case DioExceptionType.cancel:
        return ServerException(
          message: 'Requisição cancelada',
          statusCode: 499,
        );
        
      case DioExceptionType.connectionError:
        return ServerException(
          message: 'Erro de conexão. Verifique sua internet',
          statusCode: 503,
        );
        
      case DioExceptionType.badCertificate:
        return ServerException(
          message: 'Erro de certificado SSL',
          statusCode: 495,
        );
        
      case DioExceptionType.unknown:
      default:
        return ServerException(
          message: 'Erro desconhecido: ${e.message}',
          statusCode: 500,
        );
    }
  }
}