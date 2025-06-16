// lib/features/auth/domain/repositories/auth_repository.dart
// Interface do repositório de autenticação

import 'package:dartz/dartz.dart';
import '../entities/user.dart';
import '../../../../core/errors/failures.dart';

/// Interface do repositório de autenticação
/// 
/// Define os contratos para operações de autenticação
/// que serão implementados na camada de dados
abstract class AuthRepository {
  /// Realiza o login do usuário
  ///
  /// [email] - Email do usuário
  /// [password] - Senha do usuário
  /// 
  /// Retorna [User] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, User>> login({
    required String email,
    required String password,
  });

  /// Registra um novo usuário
  ///
  /// [name] - Nome completo do usuário
  /// [email] - Email do usuário
  /// [password] - Senha do usuário
  /// [phone] - Telefone do usuário (opcional)
  /// 
  /// Retorna [User] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, User>> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  });

  /// Realiza o logout do usuário
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> logout();

  /// Solicita recuperação de senha
  ///
  /// [email] - Email do usuário para recuperação
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> recoverPassword({
    required String email,
  });

  /// Redefine a senha com token
  ///
  /// [token] - Token de recuperação recebido por email
  /// [newPassword] - Nova senha do usuário
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> resetPassword({
    required String token,
    required String newPassword,
  });

  /// Verifica se o usuário está autenticado
  /// 
  /// Retorna [User] se autenticado ou [Failure] se não autenticado
  Future<Either<Failure, User>> getCurrentUser();

  /// Verifica se existe um token válido armazenado
  /// 
  /// Retorna [bool] indicando se há token válido
  Future<bool> isAuthenticated();

  /// Remove dados de autenticação armazenados localmente
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> clearAuthData();
}