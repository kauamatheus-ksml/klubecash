// lib/features/auth/domain/usecases/register_usecase.dart
// Use case para registro de usuário

import 'package:dartz/dartz.dart';
import '../entities/user.dart';
import '../repositories/auth_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por realizar o registro de novo usuário
/// 
/// Implementa a lógica de negócio para criação de conta,
/// incluindo validações básicas antes de chamar o repositório
class RegisterUseCase {
  final AuthRepository repository;

  const RegisterUseCase(this.repository);

  /// Executa o registro do usuário
  ///
  /// [params] - Parâmetros necessários para o registro
  /// 
  /// Retorna [User] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, User>> call(RegisterParams params) async {
    // Validações básicas
    if (params.name.isEmpty) {
      return Left(ValidationFailure('Nome é obrigatório'));
    }

    if (params.name.length < 3) {
      return Left(ValidationFailure('Nome deve ter pelo menos 3 caracteres'));
    }

    if (params.email.isEmpty) {
      return Left(ValidationFailure('Email é obrigatório'));
    }

    if (!_isValidEmail(params.email)) {
      return Left(ValidationFailure('Email inválido'));
    }

    if (params.password.isEmpty) {
      return Left(ValidationFailure('Senha é obrigatória'));
    }

    if (params.password.length < 6) {
      return Left(ValidationFailure('Senha deve ter pelo menos 6 caracteres'));
    }

    if (params.phone != null && params.phone!.isNotEmpty && !_isValidPhone(params.phone!)) {
      return Left(ValidationFailure('Telefone inválido'));
    }

    // Chamar repositório para realizar registro
    return await repository.register(
      name: params.name,
      email: params.email,
      password: params.password,
      phone: params.phone,
    );
  }

  /// Valida formato do email
  bool _isValidEmail(String email) {
    return RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(email);
  }

  /// Valida formato do telefone
  bool _isValidPhone(String phone) {
    // Remove caracteres especiais para validação
    String cleanPhone = phone.replaceAll(RegExp(r'[^\d]'), '');
    return cleanPhone.length >= 10 && cleanPhone.length <= 11;
  }
}

/// Parâmetros necessários para o registro
class RegisterParams {
  final String name;
  final String email;
  final String password;
  final String? phone;

  const RegisterParams({
    required this.name,
    required this.email,
    required this.password,
    this.phone,
  });

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    
    return other is RegisterParams &&
        other.name == name &&
        other.email == email &&
        other.password == password &&
        other.phone == phone;
  }

  @override
  int get hashCode {
    return Object.hash(name, email, password, phone);
  }

  @override
  String toString() => 'RegisterParams(name: $name, email: $email)';
}