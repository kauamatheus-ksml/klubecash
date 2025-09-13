// lib/features/profile/data/datasources/profile_remote_datasource.dart
// ARQUIVO #102 - ProfileRemoteDataSource - Fonte de dados remota para perfil do usuário

import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:http_parser/http_parser.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/network/api_client.dart';
import '../models/profile_model.dart';

/// Interface do datasource remoto de perfil
abstract class ProfileRemoteDataSource {
  /// Obtém dados do perfil atual
  Future<ProfileModel> getProfile();

  /// Atualiza dados pessoais do perfil
  Future<ProfileModel> updateProfile({
    required String name,
    required String email,
    String? phone,
    String? alternativeEmail,
  });

  /// Atualiza endereço do usuário
  Future<ProfileModel> updateAddress({
    required String cep,
    required String street,
    required String streetNumber,
    String? complement,
    required String neighborhood,
    required String city,
    required String state,
  });

  /// Altera senha do usuário
  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
  });

  /// Faz upload da foto de perfil
  Future<ProfileModel> uploadProfilePicture(File imageFile);

  /// Solicita verificação de email
  Future<Map<String, dynamic>> requestEmailVerification();

  /// Confirma verificação de email com código
  Future<Map<String, dynamic>> verifyEmail(String verificationCode);

  /// Obtém dados de endereço por CEP
  Future<Map<String, dynamic>> getAddressByCep(String cep);
}

/// Implementação do datasource remoto de perfil
class ProfileRemoteDataSourceImpl implements ProfileRemoteDataSource {
  final ApiClient _apiClient;

  ProfileRemoteDataSourceImpl({
    required ApiClient apiClient,
  }) : _apiClient = apiClient;

  @override
  Future<ProfileModel> getProfile() async {
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
        final data = _validateResponse(response.data);
        
        if (data['status'] == true && data['data'] != null) {
          return ProfileModel.fromJson(data['data']);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao buscar dados do perfil',
            statusCode: response.statusCode,
          );
        }
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
        message: 'Erro inesperado ao buscar perfil: $e',
      );
    }
  }

  @override
  Future<ProfileModel> updateProfile({
    required String name,
    required String email,
    String? phone,
    String? alternativeEmail,
  }) async {
    try {
      final response = await _apiClient.put(
        ApiConstants.userProfileEndpoint,
        data: {
          'nome': name,
          'email': email,
          'telefone': phone,
          'email_alternativo': alternativeEmail,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true && data['data'] != null) {
          return ProfileModel.fromJson(data['data']);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao atualizar perfil',
            statusCode: response.statusCode,
          );
        }
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

  @override
  Future<ProfileModel> updateAddress({
    required String cep,
    required String street,
    required String streetNumber,
    String? complement,
    required String neighborhood,
    required String city,
    required String state,
  }) async {
    try {
      final response = await _apiClient.put(
        '${ApiConstants.userProfileEndpoint}/endereco',
        data: {
          'cep': cep,
          'logradouro': street,
          'numero': streetNumber,
          'complemento': complement,
          'bairro': neighborhood,
          'cidade': city,
          'estado': state,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true && data['data'] != null) {
          return ProfileModel.fromJson(data['data']);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao atualizar endereço',
            statusCode: response.statusCode,
          );
        }
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
        message: 'Erro inesperado ao atualizar endereço: $e',
      );
    }
  }

  @override
  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      final response = await _apiClient.put(
        '${ApiConstants.userProfileEndpoint}/senha',
        data: {
          'senha_atual': currentPassword,
          'nova_senha': newPassword,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        return data;
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
        message: 'Erro inesperado ao alterar senha: $e',
      );
    }
  }

  @override
  Future<ProfileModel> uploadProfilePicture(File imageFile) async {
    try {
      // Preparar arquivo para upload
      final fileName = imageFile.path.split('/').last;
      final formData = FormData.fromMap({
        'foto_perfil': await MultipartFile.fromFile(
          imageFile.path,
          filename: fileName,
          contentType: MediaType('image', 'jpeg'),
        ),
      });

      final response = await _apiClient.post(
        '${ApiConstants.userProfileEndpoint}/foto',
        data: formData,
        options: Options(
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        
        if (data['status'] == true && data['data'] != null) {
          return ProfileModel.fromJson(data['data']);
        } else {
          throw ServerException(
            message: data['message'] ?? 'Erro ao fazer upload da foto',
            statusCode: response.statusCode,
          );
        }
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
        message: 'Erro inesperado ao fazer upload da foto: $e',
      );
    }
  }

  @override
  Future<Map<String, dynamic>> requestEmailVerification() async {
    try {
      final response = await _apiClient.post(
        '${ApiConstants.userProfileEndpoint}/verificar-email',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        return data;
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
        message: 'Erro inesperado ao solicitar verificação: $e',
      );
    }
  }

  @override
  Future<Map<String, dynamic>> verifyEmail(String verificationCode) async {
    try {
      final response = await _apiClient.post(
        '${ApiConstants.userProfileEndpoint}/confirmar-email',
        data: {
          'codigo_verificacao': verificationCode,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        return data;
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
        message: 'Erro inesperado ao verificar email: $e',
      );
    }
  }

  @override
  Future<Map<String, dynamic>> getAddressByCep(String cep) async {
    try {
      // Remove formatação do CEP
      final cleanCep = cep.replaceAll(RegExp(r'[^0-9]'), '');
      
      final response = await _apiClient.get(
        '/endereco/cep/$cleanCep',
        options: Options(
          headers: {
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = _validateResponse(response.data);
        return data;
      } else {
        throw ServerException(
          message: 'CEP não encontrado',
          statusCode: response.statusCode,
        );
      }
    } on DioException catch (e) {
      throw _handleDioException(e);
    } catch (e) {
      throw ServerException(
        message: 'Erro inesperado ao buscar CEP: $e',
      );
    }
  }

  /// Valida se a resposta da API está no formato esperado
  Map<String, dynamic> _validateResponse(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      return responseData;
    } else {
      throw ServerException(
        message: 'Formato de resposta inválido',
        statusCode: 500,
      );
    }
  }

  /// Converte DioException em ServerException
  ServerException _handleDioException(DioException e) {
    String message = 'Erro de conexão';
    int statusCode = 500;

    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        message = 'Timeout na conexão';
        statusCode = 408;
        break;
      case DioExceptionType.badResponse:
        statusCode = e.response?.statusCode ?? 500;
        final responseData = e.response?.data;
        
        if (responseData is Map<String, dynamic> && 
            responseData['message'] != null) {
          message = responseData['message'];
        } else {
          message = 'Erro no servidor (${statusCode})';
        }
        break;
      case DioExceptionType.cancel:
        message = 'Requisição cancelada';
        statusCode = 499;
        break;
      case DioExceptionType.connectionError:
        message = 'Erro de conexão com o servidor';
        statusCode = 503;
        break;
      default:
        message = 'Erro desconhecido';
        statusCode = 500;
    }

    return ServerException(
      message: message,
      statusCode: statusCode,
    );
  }
}