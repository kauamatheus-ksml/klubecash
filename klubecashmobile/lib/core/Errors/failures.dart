// lib/core/errors/failures.dart
// Arquivo responsável por definir as falhas (failures) da aplicação Klube Cash

import 'package:equatable/equatable.dart';

/// Classe abstrata base para todas as falhas da aplicação
abstract class Failure extends Equatable {
  final String message;
  final String? code;

  const Failure(this.message, {this.code});

  @override
  List<Object?> get props => [message, code];
}

/// Falha genérica do servidor
class ServerFailure extends Failure {
  final int? statusCode;

  const ServerFailure(
    super.message, {
    this.statusCode,
    super.code,
  });

  @override
  List<Object?> get props => [message, code, statusCode];
}

/// Falha de cache/armazenamento local
class CacheFailure extends Failure {
  const CacheFailure(super.message, {super.code});
}

/// Falha de conectividade de rede
class NetworkFailure extends Failure {
  const NetworkFailure(super.message, {super.code});
}

/// Falha de autenticação
class AuthFailure extends Failure {
  const AuthFailure(super.message, {super.code});
}

/// Falha de validação de dados
class ValidationFailure extends Failure {
  final Map<String, List<String>>? fieldErrors;

  const ValidationFailure(
    super.message, {
    this.fieldErrors,
    super.code,
  });

  @override
  List<Object?> get props => [message, code, fieldErrors];
}

/// Falha relacionada ao sistema de cashback
class CashbackFailure extends Failure {
  const CashbackFailure(super.message, {super.code});
}

/// Falha de permissão/autorização
class PermissionFailure extends Failure {
  const PermissionFailure(super.message, {super.code});
}

/// Falha quando recurso não é encontrado
class NotFoundFailure extends Failure {
  const NotFoundFailure(super.message, {super.code});
}

/// Falha de timeout
class TimeoutFailure extends Failure {
  const TimeoutFailure(super.message, {super.code});
}

/// Falha desconhecida/não identificada
class UnknownFailure extends Failure {
  const UnknownFailure(super.message, {super.code});
}