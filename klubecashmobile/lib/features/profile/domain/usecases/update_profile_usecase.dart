// lib/features/profile/domain/usecases/update_profile_usecase.dart
// Use case para atualizar informações do perfil do usuário

import 'package:dartz/dartz.dart';
import '../entities/profile.dart';
import '../repositories/profile_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case para atualizar informações pessoais do perfil
class UpdateProfileUseCase {
  final ProfileRepository repository;

  const UpdateProfileUseCase({required this.repository});

  /// Executa o caso de uso para atualizar o perfil
  ///
  /// [params] - Parâmetros contendo os dados a serem atualizados
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> call(UpdateProfileParams params) async {
    return await repository.updatePersonalInfo(
      userId: params.userId,
      name: params.name,
      phone: params.phone,
      alternativeEmail: params.alternativeEmail,
    );
  }
}

/// Use case para atualizar endereço do usuário
class UpdateAddressUseCase {
  final ProfileRepository repository;

  const UpdateAddressUseCase({required this.repository});

  /// Executa o caso de uso para atualizar o endereço
  ///
  /// [params] - Parâmetros contendo os dados do endereço
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> call(UpdateAddressParams params) async {
    return await repository.updateAddress(
      userId: params.userId,
      address: params.address,
    );
  }
}

/// Use case para atualizar foto de perfil
class UpdateProfilePictureUseCase {
  final ProfileRepository repository;

  const UpdateProfilePictureUseCase({required this.repository});

  /// Executa o caso de uso para atualizar a foto de perfil
  ///
  /// [params] - Parâmetros contendo o caminho da imagem
  /// 
  /// Retorna [Profile] atualizado em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> call(UpdateProfilePictureParams params) async {
    return await repository.updateProfilePicture(
      userId: params.userId,
      imagePath: params.imagePath,
    );
  }
}

/// Parâmetros para atualização de informações pessoais
class UpdateProfileParams {
  final String userId;
  final String? name;
  final String? phone;
  final String? alternativeEmail;

  const UpdateProfileParams({
    required this.userId,
    this.name,
    this.phone,
    this.alternativeEmail,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is UpdateProfileParams &&
        other.userId == userId &&
        other.name == name &&
        other.phone == phone &&
        other.alternativeEmail == alternativeEmail;
  }

  @override
  int get hashCode => Object.hash(userId, name, phone, alternativeEmail);
}

/// Parâmetros para atualização de endereço
class UpdateAddressParams {
  final String userId;
  final ProfileAddress address;

  const UpdateAddressParams({
    required this.userId,
    required this.address,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is UpdateAddressParams &&
        other.userId == userId &&
        other.address == address;
  }

  @override
  int get hashCode => Object.hash(userId, address);
}

/// Parâmetros para atualização de foto de perfil
class UpdateProfilePictureParams {
  final String userId;
  final String imagePath;

  const UpdateProfilePictureParams({
    required this.userId,
    required this.imagePath,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is UpdateProfilePictureParams &&
        other.userId == userId &&
        other.imagePath == imagePath;
  }

  @override
  int get hashCode => Object.hash(userId, imagePath);
}