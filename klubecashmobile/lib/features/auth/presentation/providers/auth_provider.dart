// lib/features/auth/presentation/providers/auth_provider.dart
// 🔐 Auth Provider - Gerenciamento de estado de autenticação usando Riverpod

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../domain/entities/user.dart';
import '../../domain/usecases/login_usecase.dart';
import '../../domain/usecases/register_usecase.dart';
import '../../domain/usecases/logout_usecase.dart';
import '../../domain/usecases/recover_password_usecase.dart';
import '../../data/repositories/auth_repository_impl.dart';
import '../../data/datasources/auth_remote_datasource.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/errors/failures.dart';

part 'auth_provider.g.dart';

/// Estado de autenticação do usuário
class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final User? user;
  final String? errorMessage;

  const AuthState({
    this.isLoading = false,
    this.isAuthenticated = false,
    this.user,
    this.errorMessage,
  });

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    User? user,
    String? errorMessage,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }
}

/// Provider para o repositório de autenticação
// Substitua o provider antigo por este
@riverpod
AuthRepositoryImpl authRepository(AuthRepositoryRef ref) {
  final apiClient = ref.watch(apiClientProvider);
  // Assumindo que você tenha um provider para SharedPreferences
  // Se não tiver, adicione: final sharedPreferences = await SharedPreferences.getInstance();
  // e mude o provider para um FutureProvider. Por simplicidade, vamos assumir que ele existe.
  final networkInfo = ref.watch(networkInfoProvider); 
  final secureStorage = ref.watch(flutterSecureStorageProvider); // Supondo que você crie este provider

  final dataSource = AuthRemoteDataSourceImpl(apiClient: apiClient);
  
  return AuthRepositoryImpl(
    remoteDataSource: dataSource,
    networkInfo: networkInfo,
    // Você precisa passar o SharedPreferences também.
    // Supondo que você tenha um provider para ele.
    // sharedPreferences: ref.watch(sharedPreferencesProvider), 
  );
}

/// Provider para use cases de autenticação
@riverpod
LoginUseCase loginUseCase(LoginUseCaseRef ref) {
  final repository = ref.watch(authRepositoryProvider);
  return LoginUseCase(repository);
}

@riverpod
RegisterUseCase registerUseCase(RegisterUseCaseRef ref) {
  final repository = ref.watch(authRepositoryProvider);
  return RegisterUseCase(repository);
}

@riverpod
LogoutUseCase logoutUseCase(LogoutUseCaseRef ref) {
  final repository = ref.watch(authRepositoryProvider);
  return LogoutUseCase(repository);
}

@riverpod
RecoverPasswordUseCase recoverPasswordUseCase(RecoverPasswordUseCaseRef ref) {
  final repository = ref.watch(authRepositoryProvider);
  return RecoverPasswordUseCase(repository);
}

/// Provider principal de autenticação
@riverpod
class AuthProvider extends _$AuthProvider {
  @override
  AuthState build() {
    // Inicializar verificando se o usuário já está autenticado
    _checkAuthStatus();
    return const AuthState();
  }

  /// Verifica se o usuário já está autenticado ao iniciar o app
  Future<void> _checkAuthStatus() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    
    try {
      final repository = ref.read(authRepositoryProvider);
      final result = await repository.getCurrentUser();
      
      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: false,
            user: null,
          );
        },
        (user) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: true,
            user: user,
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: false,
        errorMessage: 'Erro ao verificar autenticação',
      );
    }
  }

  /// Realiza login do usuário
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    
    try {
      final loginUseCase = ref.read(loginUseCaseProvider);
      final params = LoginParams(email: email, password: password);
      final result = await loginUseCase.call(params);
      
      return result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (user) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: true,
            user: user,
            errorMessage: null,
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: false,
        errorMessage: 'Erro inesperado durante o login',
      );
      return false;
    }
  }

  /// Registra novo usuário
  Future<bool> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    
    try {
      final registerUseCase = ref.read(registerUseCaseProvider);
      final params = RegisterParams(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );
      final result = await registerUseCase.call(params);
      
      return result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (user) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: true,
            user: user,
            errorMessage: null,
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: false,
        errorMessage: 'Erro inesperado durante o cadastro',
      );
      return false;
    }
  }

  /// Realiza logout do usuário
  Future<bool> logout() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    
    try {
      final logoutUseCase = ref.read(logoutUseCaseProvider);
      final result = await logoutUseCase.call();
      
      return result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (_) {
          state = state.copyWith(
            isLoading: false,
            isAuthenticated: false,
            user: null,
            errorMessage: null,
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Erro inesperado durante o logout',
      );
      return false;
    }
  }

  /// Solicita recuperação de senha
  Future<bool> recoverPassword({required String email}) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    
    try {
      final recoverUseCase = ref.read(recoverPasswordUseCaseProvider);
      final params = RecoverPasswordParams(email: email);
      final result = await recoverUseCase.call(params);
      
      return result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (_) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: null,
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Erro inesperado ao solicitar recuperação',
      );
      return false;
    }
  }

  /// Limpa mensagem de erro
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }

  /// Força logout sem chamada ao servidor (para casos de erro)
  void forceLogout() {
    state = state.copyWith(
      isLoading: false,
      isAuthenticated: false,
      user: null,
      errorMessage: null,
    );
  }

  /// Converte failures em mensagens de erro user-friendly
  String _getFailureMessage(Failure failure) {
    if (failure is ServerFailure) {
      return failure.message.isNotEmpty
          ? failure.message
          : 'Erro no servidor. Tente novamente.';
    } else if (failure is NetworkFailure) {
      return 'Sem conexão com a internet. Verifique sua conexão.';
    } else if (failure is ValidationFailure) {
      return failure.message;
    } else if (failure is CacheFailure) {
      return 'Erro nos dados locais. Tente fazer login novamente.';
    } else {
      return 'Erro inesperado. Tente novamente.';
    }
  }
}

/// Provider conveniente para acessar apenas o estado de autenticação
@riverpod
bool isAuthenticated(IsAuthenticatedRef ref) {
  final authState = ref.watch(authProviderProvider);
  return authState.isAuthenticated;
}

/// Provider conveniente para acessar apenas o usuário atual
@riverpod
User? currentUser(CurrentUserRef ref) {
  final authState = ref.watch(authProviderProvider);
  return authState.user;
}

/// Provider conveniente para verificar se está carregando
@riverpod
bool isLoading(IsLoadingRef ref) {
  final authState = ref.watch(authProviderProvider);
  return authState.isLoading;
}