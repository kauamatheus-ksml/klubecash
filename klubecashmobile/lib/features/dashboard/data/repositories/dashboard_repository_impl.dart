// lib/features/dashboard/data/repositories/dashboard_repository_impl.dart
// Implementação do repositório de dashboard - camada de dados

import 'package:dartz/dartz.dart';
import '../../domain/entities/cashback_summary.dart';
import '../../domain/entities/transaction_summary.dart';
import '../../domain/repositories/dashboard_repository.dart';
import '../datasources/dashboard_remote_datasource.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';

/// Implementação concreta do repositório de dashboard
/// 
/// Responsável por coordenar dados entre datasources e aplicar
/// regras de negócio da camada de dados
class DashboardRepositoryImpl implements DashboardRepository {
  final DashboardRemoteDataSource remoteDataSource;
  final NetworkInfo networkInfo;

  const DashboardRepositoryImpl({
    required this.remoteDataSource,
    required this.networkInfo,
  });

  @override
  Future<Either<Failure, CashbackSummary>> getCashbackSummary() async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getCashbackSummary();
        return Right(result);
      } on ServerException catch (e) {
        return Left(ServerFailure(
          e.message,
          statusCode: e.statusCode,
          code: e.code,
        ));
      } on CacheException catch (e) {
        return Left(CacheFailure(e.message, code: e.code));
      } catch (e) {
        return Left(UnknownFailure('Erro inesperado: $e'));
      }
    } else {
      return const Left(NetworkFailure(
        'Sem conexão com a internet. Verifique sua rede.',
      ));
    }
  }

  @override
  Future<Either<Failure, List<TransactionSummary>>> getRecentTransactions({
    int limit = 5,
  }) async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getRecentTransactions(
          limit: limit,
        );
        return Right(result);
      } on ServerException catch (e) {
        return Left(ServerFailure(
          e.message,
          statusCode: e.statusCode,
          code: e.code,
        ));
      } on CacheException catch (e) {
        return Left(CacheFailure(e.message, code: e.code));
      } catch (e) {
        return Left(UnknownFailure('Erro inesperado: $e'));
      }
    } else {
      return const Left(NetworkFailure(
        'Sem conexão com a internet. Verifique sua rede.',
      ));
    }
  }

  @override
  Future<Either<Failure, Map<String, dynamic>>> getDashboardData() async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getDashboardData();
        return Right(result);
      } on ServerException catch (e) {
        return Left(ServerFailure(
          e.message,
          statusCode: e.statusCode,
          code: e.code,
        ));
      } on CacheException catch (e) {
        return Left(CacheFailure(e.message, code: e.code));
      } catch (e) {
        return Left(UnknownFailure('Erro inesperado: $e'));
      }
    } else {
      return const Left(NetworkFailure(
        'Sem conexão com a internet. Verifique sua rede.',
      ));
    }
  }

  @override
  Future<Either<Failure, Map<String, dynamic>>> getUserBalance() async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getUserBalance();
        return Right(result);
      } on ServerException catch (e) {
        return Left(ServerFailure(
          e.message,
          statusCode: e.statusCode,
          code: e.code,
        ));
      } on CacheException catch (e) {
        return Left(CacheFailure(e.message, code: e.code));
      } catch (e) {
        return Left(UnknownFailure('Erro inesperado: $e'));
      }
    } else {
      return const Left(NetworkFailure(
        'Sem conexão com a internet. Verifique sua rede.',
      ));
    }
  }

  @override
  Future<Either<Failure, Map<String, dynamic>>> getStatement({
    Map<String, dynamic>? filters,
    int page = 1,
    int? limit,
  }) async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getStatement(
          filters: filters,
          page: page,
          limit: limit,
        );
        return Right(result);
      } on ServerException catch (e) {
        return Left(ServerFailure(
          e.message,
          statusCode: e.statusCode,
          code: e.code,
        ));
      } on CacheException catch (e) {
        return Left(CacheFailure(e.message, code: e.code));
      } catch (e) {
        return Left(UnknownFailure('Erro inesperado: $e'));
      }
    } else {
      return const Left(NetworkFailure(
        'Sem conexão com a internet. Verifique sua rede.',
      ));
    }
  }

  @override
  Future<Either<Failure, CashbackSummary>> refreshCashbackSummary() async {
    // Para refresh, sempre tenta buscar dados atualizados
    return await getCashbackSummary();
  }

  @override
  Future<Either<Failure, List<TransactionSummary>>> refreshRecentTransactions({
    int limit = 5,
  }) async {
    // Para refresh, sempre tenta buscar dados atualizados
    return await getRecentTransactions(limit: limit);
  }
}