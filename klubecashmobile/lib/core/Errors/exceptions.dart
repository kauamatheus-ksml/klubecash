// lib/core/errors/exceptions.dart
// Arquivo responsável por definir as exceções customizadas da aplicação Klube Cash

/// Classe base para todas as exceções customizadas da aplicação
abstract class AppException implements Exception {
  final String message;
  
  final String? code;
  final dynamic originalError;

  const AppException(
    this.message, {
    this.code,
    this.originalError,
  });

  @override
  String toString() => 'AppException: $message';
}

/// Exceção lançada quando há falha na comunicação com o servidor
class ServerException extends AppException {
  final int? statusCode;

  const ServerException(
    super.message, {
    this.statusCode,
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'ServerException($statusCode): $message';
}

/// Exceção lançada quando há problemas com o cache local
class CacheException extends AppException {
  const CacheException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'CacheException: $message';
}

/// Exceção lançada quando há problemas de conectividade de rede
class NetworkException extends AppException {
  const NetworkException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'NetworkException: $message';
}

/// Exceção lançada quando há falhas de autenticação
class AuthException extends AppException {
  const AuthException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'AuthException: $message';
}

/// Exceção lançada quando há falhas de validação de dados
class ValidationException extends AppException {
  final Map<String, List<String>>? fieldErrors;

  const ValidationException(
    super.message, {
    this.fieldErrors,
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'ValidationException: $message';
}

/// Exceção lançada para erros relacionados ao sistema de cashback
class CashbackException extends AppException {
  const CashbackException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'CashbackException: $message';
}

/// Exceção lançada quando o usuário não tem permissão para executar uma ação
class PermissionException extends AppException {
  const PermissionException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'PermissionException: $message';
}

/// Exceção lançada quando um recurso solicitado não é encontrado
class NotFoundException extends AppException {
  const NotFoundException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'NotFoundException: $message';
}

/// Exceção lançada quando há timeout em operações
class TimeoutException extends AppException {
  final Duration? timeout;

  const TimeoutException(
    super.message, {
    this.timeout,
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'TimeoutException: $message';
}

/// Exceção lançada para erros não identificados ou inesperados
class UnknownException extends AppException {
  const UnknownException(
    super.message, {
    super.code,
    super.originalError,
  });

  @override
  String toString() => 'UnknownException: $message';
}