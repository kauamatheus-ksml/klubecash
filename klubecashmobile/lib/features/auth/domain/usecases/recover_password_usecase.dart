// lib/features/auth/domain/usecases/recover_password_usecase.dart
// Use case para recuperação de senha

import 'package:dartz/dartz.dart';
import '../repositories/auth_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por solicitar recuperação de senha
class RecoverPasswordUseCase {
  final AuthRepository repository;

  const RecoverPasswordUseCase(this.repository);

  /// Solicita recuperação de senha
  ///
  /// [params] - Parâmetros necessários para recuperação
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> call(RecoverPasswordParams params) async {
    // Validações básicas
    if (params.email.isEmpty) {
      return Left(ValidationFailure('Email é obrigatório'));
    }

    if (!_isValidEmail(params.email)) {
      return Left(ValidationFailure('Email inválido'));
    }

    // Chamar repositório para solicitar recuperação
    return await repository.recoverPassword(email: params.email);
  }

  /// Valida formato do email
  bool _isValidEmail(String email) {
    return RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email);
  }
}

/// Parâmetros necessários para recuperação de senha
class RecoverPasswordParams {
  final String email;

  const RecoverPasswordParams({required this.email});

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is RecoverPasswordParams && other.email == email;
  }

  @override
  int get hashCode => email.hashCode;

  @override
  String toString() => 'RecoverPasswordParams(email: $email)';
}