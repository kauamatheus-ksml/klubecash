// lib/features/auth/data/models/auth_response_model.dart
// ARQUIVO #35 - AuthResponseModel - Modelo de resposta da autenticação da API

import 'user_model.dart';

/// Modelo de resposta da autenticação para comunicação com a API
///
/// Representa a estrutura padrão de resposta dos endpoints de autenticação
/// incluindo login, registro, recuperação de senha e outras operações auth
class AuthResponseModel {
  final bool status;
  final String message;
  final UserModel? user;
  final String? token;
  final DateTime? tokenExpiresAt;
  final Map<String, dynamic>? additionalData;

  const AuthResponseModel({
    required this.status,
    required this.message,
    this.user,
    this.token,
    this.tokenExpiresAt,
    this.additionalData,
  });

  /// Cria um AuthResponseModel a partir de um Map (resposta da API)
  ///
  /// [json] - Map contendo a resposta da API de autenticação
  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    return AuthResponseModel(
      status: json['status'] ?? false,
      message: json['message'] ?? json['msg'] ?? '',
      user: json['user'] != null ? UserModel.fromJson(json['user']) : null,
      token: json['token'],
      tokenExpiresAt: json['token_expires_at'] != null
          ? DateTime.parse(json['token_expires_at'])
          : json['exp'] != null
              ? DateTime.fromMillisecondsSinceEpoch(json['exp'] * 1000)
              : null,
      additionalData: json['data'] is Map<String, dynamic> 
          ? json['data'] as Map<String, dynamic>
          : null,
    );
  }

  /// Converte o AuthResponseModel para Map (para cache ou debug)
  Map<String, dynamic> toJson() {
    return {
      'status': status,
      'message': message,
      'user': user?.toJson(),
      'token': token,
      'token_expires_at': tokenExpiresAt?.toIso8601String(),
      'data': additionalData,
    };
  }

  /// Cria uma cópia do AuthResponseModel com campos atualizados
  AuthResponseModel copyWith({
    bool? status,
    String? message,
    UserModel? user,
    String? token,
    DateTime? tokenExpiresAt,
    Map<String, dynamic>? additionalData,
  }) {
    return AuthResponseModel(
      status: status ?? this.status,
      message: message ?? this.message,
      user: user ?? this.user,
      token: token ?? this.token,
      tokenExpiresAt: tokenExpiresAt ?? this.tokenExpiresAt,
      additionalData: additionalData ?? this.additionalData,
    );
  }

  /// Verifica se a resposta indica sucesso
  bool get isSuccess => status == true;

  /// Verifica se a resposta indica falha
  bool get isFailure => status == false;

  /// Verifica se há dados de usuário válidos
  bool get hasValidUser => user != null && user!.isValid;

  /// Verifica se há token válido
  bool get hasValidToken => token != null && token!.isNotEmpty;

  /// Verifica se a autenticação foi bem-sucedida completamente
  bool get isAuthenticationSuccess => isSuccess && hasValidUser && hasValidToken;

  /// Verifica se o token está expirado
  bool get isTokenExpired {
    if (tokenExpiresAt == null) return false;
    return DateTime.now().isAfter(tokenExpiresAt!);
  }

  /// Retorna o tempo restante até a expiração do token
  Duration? get timeUntilExpiration {
    if (tokenExpiresAt == null) return null;
    final now = DateTime.now();
    if (now.isAfter(tokenExpiresAt!)) return Duration.zero;
    return tokenExpiresAt!.difference(now);
  }

  /// Factory para criar resposta de sucesso de login
  factory AuthResponseModel.loginSuccess({
    required UserModel user,
    required String token,
    DateTime? tokenExpiresAt,
    String? message,
    Map<String, dynamic>? additionalData,
  }) {
    return AuthResponseModel(
      status: true,
      message: message ?? 'Login realizado com sucesso',
      user: user,
      token: token,
      tokenExpiresAt: tokenExpiresAt,
      additionalData: additionalData,
    );
  }

  /// Factory para criar resposta de sucesso de registro
  factory AuthResponseModel.registerSuccess({
    required UserModel user,
    String? token,
    DateTime? tokenExpiresAt,
    String? message,
    Map<String, dynamic>? additionalData,
  }) {
    return AuthResponseModel(
      status: true,
      message: message ?? 'Cadastro realizado com sucesso',
      user: user,
      token: token,
      tokenExpiresAt: tokenExpiresAt,
      additionalData: additionalData,
    );
  }

  /// Factory para criar resposta de sucesso de recuperação de senha
  factory AuthResponseModel.passwordRecoverySuccess({
    String? message,
    Map<String, dynamic>? additionalData,
  }) {
    return AuthResponseModel(
      status: true,
      message: message ?? 'Instruções de recuperação enviadas por email',
      additionalData: additionalData,
    );
  }

  /// Factory para criar resposta de sucesso de logout
  factory AuthResponseModel.logoutSuccess({
    String? message,
  }) {
    return AuthResponseModel(
      status: true,
      message: message ?? 'Logout realizado com sucesso',
    );
  }

  /// Factory para criar resposta de erro
  factory AuthResponseModel.error({
    required String message,
    Map<String, dynamic>? additionalData,
  }) {
    return AuthResponseModel(
      status: false,
      message: message,
      additionalData: additionalData,
    );
  }

  /// Factory para criar resposta de erro de credenciais inválidas
  factory AuthResponseModel.invalidCredentials({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Email ou senha incorretos',
    );
  }

  /// Factory para criar resposta de erro de usuário não encontrado
  factory AuthResponseModel.userNotFound({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Usuário não encontrado',
    );
  }

  /// Factory para criar resposta de erro de email já cadastrado
  factory AuthResponseModel.emailAlreadyExists({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Este email já está cadastrado',
    );
  }

  /// Factory para criar resposta de erro de token inválido
  factory AuthResponseModel.invalidToken({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Token de autenticação inválido ou expirado',
    );
  }

  /// Factory para criar resposta de erro de servidor
  factory AuthResponseModel.serverError({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Erro interno do servidor',
    );
  }

  /// Factory para criar resposta de erro de conexão
  factory AuthResponseModel.connectionError({
    String? message,
  }) {
    return AuthResponseModel(
      status: false,
      message: message ?? 'Erro de conexão com o servidor',
    );
  }

  /// Retorna informações sobre o tipo de resposta para debug
  String get responseType {
    if (isAuthenticationSuccess) return 'AUTHENTICATION_SUCCESS';
    if (hasValidUser && !hasValidToken) return 'USER_ONLY';
    if (!hasValidUser && hasValidToken) return 'TOKEN_ONLY';
    if (isSuccess) return 'SUCCESS';
    return 'ERROR';
  }

  /// Extrai dados específicos do additionalData
  T? getAdditionalData<T>(String key) {
    if (additionalData == null) return null;
    return additionalData![key] as T?;
  }

  /// Verifica se contém dados adicionais específicos
  bool hasAdditionalData(String key) {
    return additionalData?.containsKey(key) ?? false;
  }

  @override
  String toString() {
    return 'AuthResponseModel('
        'status: $status, '
        'message: $message, '
        'hasUser: ${user != null}, '
        'hasToken: ${token != null}, '
        'responseType: $responseType'
        ')';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is AuthResponseModel &&
        other.status == status &&
        other.message == message &&
        other.user == user &&
        other.token == token &&
        other.tokenExpiresAt == tokenExpiresAt;
  }

  @override
  int get hashCode {
    return Object.hash(
      status,
      message,
      user,
      token,
      tokenExpiresAt,
    );
  }
}

/// Extensão para facilitar o tratamento de respostas de autenticação
extension AuthResponseModelExtension on AuthResponseModel {
  /// Executa callback apenas se a resposta for de sucesso
  void ifSuccess(Function(AuthResponseModel response) callback) {
    if (isSuccess) callback(this);
  }

  /// Executa callback apenas se a resposta for de erro
  void ifError(Function(String message) callback) {
    if (isFailure) callback(message);
  }

  /// Executa callback apenas se houver autenticação completa
  void ifAuthenticated(Function(UserModel user, String token) callback) {
    if (isAuthenticationSuccess) callback(user!, token!);
  }
}