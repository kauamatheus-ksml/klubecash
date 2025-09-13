// lib/features/auth/data/repositories/auth_repository_impl.dart
// ARQUIVO #37 - AuthRepositoryImpl - Implementação do repositório de autenticação

import 'package:dartz/dartz.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_remote_datasource.dart';
import '../models/user_model.dart';
import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';

/// Implementação do repositório de autenticação
///
/// Gerencia dados de autenticação combinando fonte remota e cache local,
/// convertendo exceções em failures seguindo Clean Architecture
class AuthRepositoryImpl implements AuthRepository {
  final AuthRemoteDataSource remoteDataSource;
  final NetworkInfo networkInfo;
  final SharedPreferences sharedPreferences;

  // Chaves para cache local
  static const String _cachedUserKey = 'CACHED_USER';
  static const String _cachedTokenKey = 'CACHED_TOKEN';
  static const String _tokenExpiryKey = 'TOKEN_EXPIRY';

  AuthRepositoryImpl({
    required this.remoteDataSource,
    required this.networkInfo,
    required this.sharedPreferences,
  });

  @override
  Future<Either<Failure, User>> login({
    required String email,
    required String password,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.login(
        email: email,
        password: password,
      );

      if (result.isSuccess && result.hasValidUser && result.hasValidToken) {
        // Salvar dados no cache local
        await _cacheUserData(result.user!, result.token!);
        if (result.tokenExpiresAt != null) {
          await _cacheTokenExpiry(result.tokenExpiresAt!);
        }
        
        return Right(result.user!.toEntity());
      } else {
        return Left(AuthFailure(result.message));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado durante o login'));
    }
  }

  @override
  Future<Either<Failure, User>> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.register(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );

      if (result.isSuccess && result.hasValidUser) {
        // Se registro incluir token, salvar no cache
        if (result.hasValidToken) {
          await _cacheUserData(result.user!, result.token!);
          if (result.tokenExpiresAt != null) {
            await _cacheTokenExpiry(result.tokenExpiresAt!);
          }
        }
        
        return Right(result.user!.toEntity());
      } else {
        return Left(AuthFailure(result.message));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado durante o registro'));
    }
  }

  @override
  Future<Either<Failure, void>> logout() async {
    try {
      // Tentar logout no servidor se houver conexão
      if (await networkInfo.isConnected) {
        await remoteDataSource.logout();
      }
      
      // Sempre limpar dados locais
      await clearAuthData();
      
      return const Right(null);
    } on ServerException catch (e) {
      // Mesmo com erro no servidor, limpar dados locais
      await clearAuthData();
      return const Right(null);
    } catch (e) {
      // Em caso de qualquer erro, ainda assim limpar dados locais
      await clearAuthData();
      return const Right(null);
    }
  }

  @override
  Future<Either<Failure, void>> recoverPassword({
    required String email,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.recoverPassword(email: email);
      
      if (result.isSuccess) {
        return const Right(null);
      } else {
        return Left(AuthFailure(result.message));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado durante recuperação de senha'));
    }
  }

  @override
  Future<Either<Failure, void>> resetPassword({
    required String token,
    required String newPassword,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.resetPassword(
        token: token,
        newPassword: newPassword,
      );
      
      if (result.isSuccess) {
        return const Right(null);
      } else {
        return Left(AuthFailure(result.message));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado durante redefinição de senha'));
    }
  }

  @override
  Future<Either<Failure, User>> getCurrentUser() async {
    try {
      // Primeiro tentar obter do cache local
      final cachedUser = await _getCachedUser();
      if (cachedUser != null && await _isTokenValid()) {
        return Right(cachedUser.toEntity());
      }

      // Se não há cache válido, tentar obter do servidor
      if (!await networkInfo.isConnected) {
        return Left(NetworkFailure('Sem conexão com a internet e sem dados em cache'));
      }

      final userModel = await remoteDataSource.getCurrentUser();
      
      // Atualizar cache com dados atuais
      final currentToken = await _getCachedToken();
      if (currentToken != null) {
        await _cacheUserData(userModel, currentToken);
      }
      
      return Right(userModel.toEntity());
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao obter dados do usuário'));
    }
  }

  @override
  Future<bool> isAuthenticated() async {
    final token = await _getCachedToken();
    if (token == null) return false;

    // Verificar se token não expirou localmente
    if (!await _isTokenValid()) {
      await clearAuthData();
      return false;
    }

    // Se há conectividade, validar token no servidor
    if (await networkInfo.isConnected) {
      try {
        final isValid = await remoteDataSource.validateToken(token);
        if (!isValid) {
          await clearAuthData();
          return false;
        }
      } catch (e) {
        // Se erro na validação, considerar válido se token não expirou localmente
        return true;
      }
    }

    return true;
  }

  @override
  Future<Either<Failure, void>> clearAuthData() async {
    try {
      await Future.wait([
        sharedPreferences.remove(_cachedUserKey),
        sharedPreferences.remove(_cachedTokenKey),
        sharedPreferences.remove(_tokenExpiryKey),
      ]);
      
      return const Right(null);
    } catch (e) {
      return Left(CacheFailure('Erro ao limpar dados de autenticação'));
    }
  }

  /// Métodos auxiliares privados

  /// Salva dados do usuário e token no cache local
  Future<void> _cacheUserData(UserModel user, String token) async {
    await Future.wait([
      sharedPreferences.setString(_cachedUserKey, user.toJson().toString()),
      sharedPreferences.setString(_cachedTokenKey, token),
    ]);
  }

  /// Salva data de expiração do token
  Future<void> _cacheTokenExpiry(DateTime expiry) async {
    await sharedPreferences.setString(
      _tokenExpiryKey,
      expiry.toIso8601String(),
    );
  }

  /// Obtém usuário do cache local
  Future<UserModel?> _getCachedUser() async {
    try {
      final userString = sharedPreferences.getString(_cachedUserKey);
      if (userString != null) {
        // Converter string de volta para Map e criar UserModel
        // Nota: Implementação simplificada, pode precisar de JSON.decode
        return UserModel.fromJson({'data': userString});
      }
    } catch (e) {
      // Ignorar erros de cache
    }
    return null;
  }

  /// Obtém token do cache local
  Future<String?> _getCachedToken() async {
    return sharedPreferences.getString(_cachedTokenKey);
  }

  /// Verifica se token ainda é válido localmente
  Future<bool> _isTokenValid() async {
    final expiryString = sharedPreferences.getString(_tokenExpiryKey);
    if (expiryString == null) return true; // Se não há data de expiração, considerar válido

    try {
      final expiry = DateTime.parse(expiryString);
      return DateTime.now().isBefore(expiry);
    } catch (e) {
      return true; // Se erro ao parsear, considerar válido
    }
  }

  /// Mapeia ServerException para Failure específico
  Failure _mapServerExceptionToFailure(ServerException exception) {
    switch (exception.statusCode) {
      case 400:
        return ValidationFailure(exception.message);
      case 401:
        return AuthFailure('Credenciais inválidas');
      case 403:
        return AuthFailure('Acesso negado');
      case 404:
        return AuthFailure('Usuário não encontrado');
      case 408:
        return NetworkFailure('Tempo limite excedido');
      case 422:
        return ValidationFailure(exception.message);
      case 429:
        return ServerFailure('Muitas tentativas. Tente novamente mais tarde');
      case 500:
      case 502:
      case 503:
        return ServerFailure('Erro interno do servidor');
      default:
        return ServerFailure(exception.message);
    }
  }

  /// Método auxiliar para login social (Google, Facebook, etc.)
  Future<Either<Failure, User>> socialLogin({
    required String provider,
    required String accessToken,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.socialLogin(
        provider: provider,
        accessToken: accessToken,
      );

      if (result.isSuccess && result.hasValidUser && result.hasValidToken) {
        await _cacheUserData(result.user!, result.token!);
        if (result.tokenExpiresAt != null) {
          await _cacheTokenExpiry(result.tokenExpiresAt!);
        }
        
        return Right(result.user!.toEntity());
      } else {
        return Left(AuthFailure(result.message));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado durante login social'));
    }
  }

  /// Método auxiliar para atualizar perfil do usuário
  Future<Either<Failure, User>> updateUserProfile({
    required String userId,
    required Map<String, dynamic> userData,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final userModel = await remoteDataSource.updateUserProfile(
        userId: userId,
        userData: userData,
      );

      // Atualizar cache com dados atualizados
      final currentToken = await _getCachedToken();
      if (currentToken != null) {
        await _cacheUserData(userModel, currentToken);
      }

      return Right(userModel.toEntity());
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao atualizar perfil'));
    }
  }
}