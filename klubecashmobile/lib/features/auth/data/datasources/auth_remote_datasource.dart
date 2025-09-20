// lib/features/auth/data/datasources/auth_remote_datasource.dart
// ARQUIVO #36 - AuthRemoteDataSource - Fonte de dados remota para autenticação

import 'dart:convert';
import 'package:dio/dio.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/auth_response_model.dart';
import '../models/user_model.dart';

/// Interface do datasource remoto de autenticação
abstract class AuthRemoteDataSource {
  /// Realiza login na API
  Future<AuthResponseModel> login({
    required String email,
    required String password,
  });

  /// Registra novo usuário na API
  Future<AuthResponseModel> register({
    required String name,
    required String email,
    required String password,
    String? phone,
    String? cpf,
  });

  /// Solicita recuperação de senha
  Future<AuthResponseModel> recoverPassword({
    required String email,
  });

  /// Redefine senha com token
  Future<AuthResponseModel> resetPassword({
    required String token,
    required String newPassword,
  });

  /// Obtém dados do usuário atual
  Future<UserModel> getCurrentUser();

  /// Valida token JWT
  Future<bool> validateToken(String token);

  /// Faz logout (invalidação do token no servidor)
  Future<AuthResponseModel> logout();
}

/// Implementação do datasource remoto de autenticação
class AuthRemoteDataSourceImpl implements AuthRemoteDataSource {
  final ApiClient _apiClient;

  AuthRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<AuthResponseModel> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiConstants.loginEndpoint,
        data: {
          'email': email,
          'senha': password, // API espera 'senha' em português
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final authResponse = AuthResponseModel.fromJson(response.data);
        
        // Se login foi bem-sucedido e há token, salvar no interceptor
        if (authResponse.isSuccess && authResponse.hasValidToken) {
          _apiClient.setAuthToken(authResponse.token!);
        }
        
        return authResponse;
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado durante o login: $e',
      );
    }
  }

  @override
  Future<AuthResponseModel> register({
    required String name,
    required String email,
    required String password,
    String? phone,
    String? cpf,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiConstants.registerEndpoint,
        data: {
          'nome': name, // API espera 'nome' em português
          'email': email,
          'senha': password, // API espera 'senha' em português
          'telefone': phone ?? '',
          'cpf': cpf ?? '',
          'tipo': 'cliente', // Tipo padrão para registro público
        },
        queryParameters: {
          'public': 'true', // Indica registro público (não requer admin)
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final authResponse = AuthResponseModel.fromJson(response.data);
        
        // Se registro foi bem-sucedido e há token, salvar no interceptor
        if (authResponse.isSuccess && authResponse.hasValidToken) {
          _apiClient.setAuthToken(authResponse.token!);
        }
        
        return authResponse;
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado durante o registro: $e',
      );
    }
  }

  @override
  Future<AuthResponseModel> recoverPassword({
    required String email,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiConstants.recoverPasswordEndpoint,
        data: {
          'email': email,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        return AuthResponseModel.fromJson(response.data);
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado durante recuperação de senha: $e',
      );
    }
  }

  @override
  Future<AuthResponseModel> resetPassword({
    required String token,
    required String newPassword,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiConstants.resetPasswordEndpoint,
        data: {
          'token': token,
          'nova_senha': newPassword, // API espera 'nova_senha' em português
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        return AuthResponseModel.fromJson(response.data);
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado durante redefinição de senha: $e',
      );
    }
  }

  @override
  Future<UserModel> getCurrentUser() async {
    try {
      final response = await _apiClient.get(
        ApiConstants.userProfileEndpoint,
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = response.data;
        
        // A API pode retornar o usuário direto ou dentro de 'data' ou 'user'
        Map<String, dynamic> userData;
        if (data['user'] != null) {
          userData = data['user'];
        } else if (data['data']?['usuario'] != null) {
          userData = data['data']['usuario'];
        } else if (data['data'] != null) {
          userData = data['data'];
        } else {
          userData = data;
        }
        
        return UserModel.fromJson(userData);
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao obter dados do usuário: $e',
      );
    }
  }

  @override
  Future<bool> validateToken(String token) async {
    try {
      // Fazer uma requisição simples para validar o token
      final response = await _apiClient.get(
        ApiConstants.validateTokenEndpoint,
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Content-Type': 'application/json',
          },
        ),
      );

      return response.statusCode == 200 && 
             response.data['status'] == true;
    } on DioException catch (e) {
      // Token inválido ou expirado retorna erro 401
      if (e.response?.statusCode == 401) {
        return false;
      }
      throw _handleDioException(e);
    } catch (e) {
      return false;
    }
  }

  @override
  Future<AuthResponseModel> logout() async {
    try {
      final response = await _apiClient.post(
        ApiConstants.logoutEndpoint,
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      // Independente da resposta, limpar token local
      _apiClient.clearAuthToken();

      if (response.statusCode == 200) {
        return AuthResponseModel.fromJson(response.data);
      } else {
        // Mesmo se o servidor retornar erro, considerar logout local bem-sucedido
        return AuthResponseModel.logoutSuccess();
      }
    } on DioException catch (e) {
      // Mesmo com erro de rede, limpar token local
      _apiClient.clearAuthToken();
      
      // Se for erro de rede, considerar logout bem-sucedido localmente
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.connectionError) {
        return AuthResponseModel.logoutSuccess(
          message: 'Logout realizado localmente (sem conexão)',
        );
      }
      
      throw _handleDioException(e);
    } catch (e) {
      // Limpar token em caso de qualquer erro
      _apiClient.clearAuthToken();
      return AuthResponseModel.logoutSuccess();
    }
  }

  /// Trata exceções do Dio e converte para exceções do domínio
  ServerException _handleDioException(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
        return ServerException(
          message: 'Tempo limite de conexão excedido',
          statusCode: 408,
        );
      
      case DioExceptionType.sendTimeout:
        return ServerException(
          message: 'Tempo limite para envio de dados excedido',
          statusCode: 408,
        );
      
      case DioExceptionType.receiveTimeout:
        return ServerException(
          message: 'Tempo limite para recebimento de dados excedido',
          statusCode: 408,
        );
      
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode ?? 500;
        String message = 'Erro no servidor';
        
        // Tentar extrair mensagem de erro da resposta
        if (e.response?.data != null) {
          if (e.response!.data is Map<String, dynamic>) {
            final responseData = e.response!.data as Map<String, dynamic>;
            message = responseData['message'] ?? 
                     responseData['msg'] ?? 
                     responseData['error'] ?? 
                     message;
          } else if (e.response!.data is String) {
            message = e.response!.data;
          }
        }
        
        return ServerException(
          message: message,
          statusCode: statusCode,
        );
      
      case DioExceptionType.connectionError:
        return ServerException(
          message: 'Erro de conexão com o servidor',
          statusCode: 0,
        );
      
      case DioExceptionType.badCertificate:
        return ServerException(
          message: 'Erro de certificado SSL',
          statusCode: 0,
        );
      
      case DioExceptionType.cancel:
        return ServerException(
          message: 'Requisição cancelada',
          statusCode: 0,
        );
      
      case DioExceptionType.unknown:
        return ServerException(
          message: 'Erro desconhecido: ${e.message}',
          statusCode: 0,
        );
    }
  }

  /// Método auxiliar para login social (Google, Facebook, etc.)
  Future<AuthResponseModel> socialLogin({
    required String provider,
    required String accessToken,
  }) async {
    try {
      final response = await _apiClient.post(
        '${ApiConstants.baseUrl}/auth/social',
        data: {
          'provider': provider,
          'access_token': accessToken,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final authResponse = AuthResponseModel.fromJson(response.data);
        
        // Se login social foi bem-sucedido e há token, salvar
        if (authResponse.isSuccess && authResponse.hasValidToken) {
          _apiClient.setAuthToken(authResponse.token!);
        }
        
        return authResponse;
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado durante login social: $e',
      );
    }
  }

  /// Método auxiliar para atualizar dados do usuário
  Future<UserModel> updateUserProfile({
    required String userId,
    required Map<String, dynamic> userData,
  }) async {
    try {
      final response = await _apiClient.put(
        '${ApiConstants.usersEndpoint}/$userId',
        data: userData,
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = response.data;
        
        // Extrair dados do usuário da resposta
        Map<String, dynamic> userResponse;
        if (data['user'] != null) {
          userResponse = data['user'];
        } else if (data['data']?['usuario'] != null) {
          userResponse = data['data']['usuario'];
        } else {
          userResponse = data;
        }
        
        return UserModel.fromJson(userResponse);
      } else {
        throw ServerException(
          message: 'Erro no servidor: ${response.statusCode}',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao atualizar perfil: $e',
      );
    }
  }
}