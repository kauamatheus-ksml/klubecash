// lib/features/profile/domain/usecases/change_password_usecase.dart
// Use case para alterar a senha do usuário

import 'package:dartz/dartz.dart';
import '../repositories/profile_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case para alterar a senha do usuário
class ChangePasswordUseCase {
  final ProfileRepository repository;

  const ChangePasswordUseCase({required this.repository});

  /// Executa o caso de uso para alterar a senha
  ///
  /// [params] - Parâmetros contendo as senhas atual e nova
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> call(ChangePasswordParams params) async {
    return await repository.changePassword(
      userId: params.userId,
      currentPassword: params.currentPassword,
      newPassword: params.newPassword,
    );
  }
}

/// Parâmetros para alteração de senha
class ChangePasswordParams {
  final String userId;
  final String currentPassword;
  final String newPassword;

  const ChangePasswordParams({
    required this.userId,
    required this.currentPassword,
    required this.newPassword,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is ChangePasswordParams &&
        other.userId == userId &&
        other.currentPassword == currentPassword &&
        other.newPassword == newPassword;
  }

  @override
  int get hashCode => Object.hash(userId, currentPassword, newPassword);
}