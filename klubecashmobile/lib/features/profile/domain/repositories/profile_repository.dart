// lib/features/profile/domain/repositories/profile_repository.dart
// Interface do repositório de perfil do usuário

import 'package:dartz/dartz.dart';
import '../entities/profile.dart';
import '../../../../core/errors/failures.dart';

/// Interface do repositório de perfil
/// 
/// Define os contratos para operações de perfil do usuário
/// que serão implementados na camada de dados
abstract class ProfileRepository {
  /// Obtém o perfil completo do usuário
  ///
  /// [userId] - ID do usuário
  /// 
  /// Retorna [Profile] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> getProfile({
    required String userId,
  });

  /// Atualiza as informações pessoais do perfil
  ///
  /// [userId] - ID do usuário
  /// [name] - Nome completo (opcional)
  /// [phone] - Telefone (opcional)
  /// [alternativeEmail] - Email alternativo (opcional)
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> updatePersonalInfo({
    required String userId,
    String? name,
    String? phone,
    String? alternativeEmail,
  });

  /// Atualiza o endereço do usuário
  ///
  /// [userId] - ID do usuário
  /// [address] - Dados do endereço
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> updateAddress({
    required String userId,
    required ProfileAddress address,
  });

  /// Atualiza a foto de perfil do usuário
  ///
  /// [userId] - ID do usuário
  /// [imagePath] - Caminho local da imagem
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> updateProfilePicture({
    required String userId,
    required String imagePath,
  });

  /// Altera a senha do usuário
  ///
  /// [userId] - ID do usuário
  /// [currentPassword] - Senha atual
  /// [newPassword] - Nova senha
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> changePassword({
    required String userId,
    required String currentPassword,
    required String newPassword,
  });

  /// Remove a foto de perfil do usuário
  ///
  /// [userId] - ID do usuário
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> removeProfilePicture({
    required String userId,
  });

  /// Solicita verificação de email alternativo
  ///
  /// [userId] - ID do usuário
  /// [email] - Email para verificação
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> requestEmailVerification({
    required String userId,
    required String email,
  });

  /// Calcula a porcentagem de completude do perfil
  ///
  /// [profile] - Dados do perfil
  /// 
  /// Retorna a porcentagem de completude (0.0 a 100.0)
  double calculateProfileCompleteness(Profile profile);
}