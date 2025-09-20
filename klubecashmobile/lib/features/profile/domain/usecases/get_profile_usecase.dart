// lib/features/profile/domain/usecases/get_profile_usecase.dart
// Use case para obter o perfil do usuário

import 'package:dartz/dartz.dart';
import '../entities/profile.dart';
import '../repositories/profile_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case para obter o perfil completo do usuário
class GetProfileUseCase {
  final ProfileRepository repository;

  const GetProfileUseCase({required this.repository});

  /// Executa o caso de uso para obter o perfil
  ///
  /// [params] - Parâmetros contendo o ID do usuário
  /// 
  /// Retorna [Profile] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, Profile>> call(GetProfileParams params) async {
    return await repository.getProfile(userId: params.userId);
  }
}

/// Parâmetros para o caso de uso GetProfile
class GetProfileParams {
  final String userId;

  const GetProfileParams({required this.userId});

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is GetProfileParams && other.userId == userId;
  }

  @override
  int get hashCode => userId.hashCode;
}