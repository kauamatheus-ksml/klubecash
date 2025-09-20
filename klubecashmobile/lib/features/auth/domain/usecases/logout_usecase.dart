// lib/features/auth/domain/usecases/logout_usecase.dart
// Use case para logout do usuário

import 'package:dartz/dartz.dart';
import '../repositories/auth_repository.dart';
import '../../../../core/errors/failures.dart';

/// Use case responsável por realizar o logout do usuário
/// 
/// Implementa a lógica de negócio para desautenticação,
/// limpando dados locais e da sessão no servidor
class LogoutUseCase {
  final AuthRepository repository;

  const LogoutUseCase(this.repository);

  /// Executa o logout do usuário
  /// 
  /// Retorna [void] em caso de sucesso ou [Failure] em caso de erro
  Future<Either<Failure, void>> call() async {
    return await repository.logout();
  }
}