// lib/features/profile/data/repositories/profile_repository_impl.dart
// ARQUIVO #103 - ProfileRepositoryImpl - Implementação do repositório de perfil

import 'dart:convert';
import 'dart:io';
import 'package:dartz/dartz.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../domain/entities/profile.dart';
import '../../domain/repositories/profile_repository.dart';
import '../datasources/profile_remote_datasource.dart';
import '../models/profile_model.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';

/// Implementação do repositório de perfil
///
/// Gerencia dados de perfil combinando fonte remota e cache local,
/// convertendo exceções em failures seguindo Clean Architecture
class ProfileRepositoryImpl implements ProfileRepository {
  final ProfileRemoteDataSource remoteDataSource;
  final NetworkInfo networkInfo;
  final SharedPreferences sharedPreferences;

  // Chaves para cache local
  static const String _cachedProfileKey = 'CACHED_PROFILE';
  static const String _profileCacheTimeKey = 'PROFILE_CACHE_TIME';
  static const int _cacheValidityHours = 24;

  ProfileRepositoryImpl({
    required this.remoteDataSource,
    required this.networkInfo,
    required this.sharedPreferences,
  });

  @override
  Future<Either<Failure, Profile>> getProfile() async {
    if (await networkInfo.isConnected) {
      try {
        final result = await remoteDataSource.getProfile();
        
        // Cache do perfil atualizado
        await _cacheProfileData(result);
        
        return Right(result.toEntity());
      } on ServerException catch (e) {
        return Left(_mapServerExceptionToFailure(e));
      } catch (e) {
        return Left(ServerFailure('Erro inesperado ao buscar perfil'));
      }
    } else {
      // Tentar buscar dados do cache quando offline
      try {
        final cachedProfile = await _getCachedProfile();
        if (cachedProfile != null) {
          return Right(cachedProfile.toEntity());
        } else {
          return Left(NetworkFailure('Sem conexão e sem dados em cache'));
        }
      } catch (e) {
        return Left(CacheFailure('Erro ao acessar dados offline'));
      }
    }
  }

  @override
  Future<Either<Failure, Profile>> updateProfile({
    required String name,
    required String email,
    String? phone,
    String? alternativeEmail,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.updateProfile(
        name: name,
        email: email,
        phone: phone,
        alternativeEmail: alternativeEmail,
      );

      // Atualizar cache com dados atualizados
      await _cacheProfileData(result);

      return Right(result.toEntity());
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao atualizar perfil'));
    }
  }

  @override
  Future<Either<Failure, Profile>> updateAddress({
    required String cep,
    required String street,
    required String streetNumber,
    String? complement,
    required String neighborhood,
    required String city,
    required String state,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.updateAddress(
        cep: cep,
        street: street,
        streetNumber: streetNumber,
        complement: complement,
        neighborhood: neighborhood,
        city: city,
        state: state,
      );

      // Atualizar cache com dados atualizados
      await _cacheProfileData(result);

      return Right(result.toEntity());
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao atualizar endereço'));
    }
  }

  @override
  Future<Either<Failure, void>> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      await remoteDataSource.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
      );

      return Right(null);
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao alterar senha'));
    }
  }

  @override
  Future<Either<Failure, Profile>> uploadProfilePicture(File imageFile) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.uploadProfilePicture(imageFile);

      // Atualizar cache com foto atualizada
      await _cacheProfileData(result);

      return Right(result.toEntity());
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao fazer upload da foto'));
    }
  }

  @override
  Future<Either<Failure, String>> requestEmailVerification() async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.requestEmailVerification();
      final message = result['message'] ?? 'Código enviado com sucesso';
      return Right(message);
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao solicitar verificação'));
    }
  }

  @override
  Future<Either<Failure, String>> verifyEmail(String verificationCode) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.verifyEmail(verificationCode);
      final message = result['message'] ?? 'Email verificado com sucesso';
      
      // Invalidar cache para forçar atualização dos dados
      await _clearProfileCache();
      
      return Right(message);
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao verificar email'));
    }
  }

  @override
  Future<Either<Failure, Map<String, String>>> getAddressByCep(String cep) async {
    if (!await networkInfo.isConnected) {
      return Left(NetworkFailure('Sem conexão com a internet'));
    }

    try {
      final result = await remoteDataSource.getAddressByCep(cep);
      
      if (result['status'] == true && result['data'] != null) {
        final addressData = result['data'] as Map<String, dynamic>;
        
        // Converter para Map<String, String>
        final addressMap = <String, String>{};
        addressData.forEach((key, value) {
          if (value != null) {
            addressMap[key] = value.toString();
          }
        });
        
        return Right(addressMap);
      } else {
        return Left(ServerFailure(
          result['message'] ?? 'CEP não encontrado'
        ));
      }
    } on ServerException catch (e) {
      return Left(_mapServerExceptionToFailure(e));
    } catch (e) {
      return Left(ServerFailure('Erro inesperado ao buscar CEP'));
    }
  }

  @override
  Future<Either<Failure, void>> clearProfileCache() async {
    try {
      await _clearProfileCache();
      return Right(null);
    } catch (e) {
      return Left(CacheFailure('Erro ao limpar cache do perfil'));
    }
  }

  /// Salva dados do perfil no cache local
  Future<void> _cacheProfileData(ProfileModel profile) async {
    try {
      final profileJson = jsonEncode(profile.toJson(includeId: true));
      await sharedPreferences.setString(_cachedProfileKey, profileJson);
      await sharedPreferences.setInt(
        _profileCacheTimeKey,
        DateTime.now().millisecondsSinceEpoch,
      );
    } catch (e) {
      // Log error mas não falha a operação principal
      print('Erro ao salvar perfil no cache: $e');
    }
  }

  /// Busca dados do perfil do cache local
  Future<ProfileModel?> _getCachedProfile() async {
    try {
      final profileJson = sharedPreferences.getString(_cachedProfileKey);
      final cacheTime = sharedPreferences.getInt(_profileCacheTimeKey);

      if (profileJson != null && cacheTime != null) {
        // Verificar se o cache ainda é válido
        final cacheDate = DateTime.fromMillisecondsSinceEpoch(cacheTime);
        final now = DateTime.now();
        final difference = now.difference(cacheDate);

        if (difference.inHours < _cacheValidityHours) {
          final profileMap = jsonDecode(profileJson) as Map<String, dynamic>;
          return ProfileModel.fromJson(profileMap);
        }
      }

      return null;
    } catch (e) {
      print('Erro ao buscar perfil do cache: $e');
      return null;
    }
  }

  /// Remove dados do perfil do cache
  Future<void> _clearProfileCache() async {
    await sharedPreferences.remove(_cachedProfileKey);
    await sharedPreferences.remove(_profileCacheTimeKey);
  }

  /// Verifica se o cache do perfil é válido
  Future<bool> _isCacheValid() async {
    final cacheTime = sharedPreferences.getInt(_profileCacheTimeKey);
    
    if (cacheTime == null) return false;

    final cacheDate = DateTime.fromMillisecondsSinceEpoch(cacheTime);
    final now = DateTime.now();
    final difference = now.difference(cacheDate);

    return difference.inHours < _cacheValidityHours;
  }

  /// Converte ServerException em Failure apropriado
  Failure _mapServerExceptionToFailure(ServerException exception) {
    switch (exception.statusCode) {
      case 400:
        return ValidationFailure(exception.message);
      case 401:
        return AuthFailure('Sessão expirada. Faça login novamente.');
      case 403:
        return AuthFailure('Acesso negado');
      case 404:
        return ServerFailure('Recurso não encontrado');
      case 422:
        return ValidationFailure(exception.message);
      case 500:
        return ServerFailure('Erro interno do servidor');
      default:
        return ServerFailure(
          exception.message.isNotEmpty 
            ? exception.message 
            : 'Erro no servidor'
        );
    }
  }
}