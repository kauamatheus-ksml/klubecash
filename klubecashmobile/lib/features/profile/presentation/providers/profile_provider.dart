// lib/features/profile/presentation/providers/profile_provider.dart
// ARQUIVO #104 - ProfileProvider - Gerenciamento de estado do perfil do usuário usando Riverpod

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../domain/entities/profile.dart';
import '../../domain/usecases/get_profile_usecase.dart';
import '../../domain/usecases/update_profile_usecase.dart';
import '../../domain/usecases/change_password_usecase.dart';
import '../../data/repositories/profile_repository_impl.dart';
import '../../data/datasources/profile_remote_datasource.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/network/network_info.dart';
import '../../../../core/errors/failures.dart';

part 'profile_provider.g.dart';

/// Estado do perfil do usuário
class ProfileState {
  final bool isLoading;
  final bool isUpdating;
  final bool isChangingPassword;
  final Profile? profile;
  final String? errorMessage;
  final String? successMessage;

  const ProfileState({
    this.isLoading = false,
    this.isUpdating = false,
    this.isChangingPassword = false,
    this.profile,
    this.errorMessage,
    this.successMessage,
  });

  ProfileState copyWith({
    bool? isLoading,
    bool? isUpdating,
    bool? isChangingPassword,
    Profile? profile,
    String? errorMessage,
    String? successMessage,
  }) {
    return ProfileState(
      isLoading: isLoading ?? this.isLoading,
      isUpdating: isUpdating ?? this.isUpdating,
      isChangingPassword: isChangingPassword ?? this.isChangingPassword,
      profile: profile ?? this.profile,
      errorMessage: errorMessage,
      successMessage: successMessage,
    );
  }

  /// Verifica se há dados do perfil carregados
  bool get hasProfile => profile != null;

  /// Verifica se há erro
  bool get hasError => errorMessage != null;

  /// Verifica se há mensagem de sucesso
  bool get hasSuccess => successMessage != null;

  /// Verifica se alguma operação está em andamento
  bool get isBusy => isLoading || isUpdating || isChangingPassword;
}

// ==================== DEPENDENCY PROVIDERS ====================

/// Provider para o repositório de perfil
@riverpod
ProfileRepositoryImpl profileRepository(ProfileRepositoryRef ref) {
  final apiClient = ref.watch(apiClientProvider);
  final networkInfo = ref.watch(networkInfoProvider);
  final dataSource = ProfileRemoteDataSource(apiClient);
  return ProfileRepositoryImpl(
    remoteDataSource: dataSource,
    networkInfo: networkInfo,
  );
}

/// Provider para use cases de perfil
@riverpod
GetProfileUsecase getProfileUsecase(GetProfileUsecaseRef ref) {
  final repository = ref.watch(profileRepositoryProvider);
  return GetProfileUsecase(repository);
}

@riverpod
UpdateProfileUsecase updateProfileUsecase(UpdateProfileUsecaseRef ref) {
  final repository = ref.watch(profileRepositoryProvider);
  return UpdateProfileUsecase(repository);
}

@riverpod
ChangePasswordUsecase changePasswordUsecase(ChangePasswordUsecaseRef ref) {
  final repository = ref.watch(profileRepositoryProvider);
  return ChangePasswordUsecase(repository);
}

// ==================== MAIN PROVIDER ====================

/// Provider principal do perfil
@riverpod
class ProfileNotifier extends _$ProfileNotifier {
  @override
  ProfileState build() {
    return const ProfileState();
  }

  /// Carrega o perfil do usuário
  Future<void> loadProfile() async {
    if (state.isLoading) return;

    state = state.copyWith(
      isLoading: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final getProfileUsecase = ref.read(getProfileUsecaseProvider);
      final result = await getProfileUsecase.call(NoParams());

      result.fold(
        (failure) {
          state = state.copyWith(
            isLoading: false,
            errorMessage: _getFailureMessage(failure),
          );
        },
        (profile) {
          state = state.copyWith(
            isLoading: false,
            profile: profile,
            errorMessage: null,
          );
        },
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Erro inesperado ao carregar perfil',
      );
    }
  }

  /// Atualiza as informações pessoais do perfil
  Future<bool> updatePersonalInfo({
    required String name,
    String? phone,
    String? alternativeEmail,
  }) async {
    if (state.isUpdating) return false;

    state = state.copyWith(
      isUpdating: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final updateUsecase = ref.read(updateProfileUsecaseProvider);
      final params = UpdateProfileParams(
        name: name,
        phone: phone,
        alternativeEmail: alternativeEmail,
      );

      final result = await updateUsecase.call(params);

      return result.fold(
        (failure) {
          state = state.copyWith(
            isUpdating: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (updatedProfile) {
          state = state.copyWith(
            isUpdating: false,
            profile: updatedProfile,
            successMessage: 'Perfil atualizado com sucesso!',
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isUpdating: false,
        errorMessage: 'Erro inesperado ao atualizar perfil',
      );
      return false;
    }
  }

  /// Atualiza o endereço do usuário
  Future<bool> updateAddress({
    required ProfileAddress address,
  }) async {
    if (state.isUpdating) return false;

    state = state.copyWith(
      isUpdating: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final updateUsecase = ref.read(updateProfileUsecaseProvider);
      final params = UpdateAddressParams(address: address);

      final result = await updateUsecase.call(params);

      return result.fold(
        (failure) {
          state = state.copyWith(
            isUpdating: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (updatedProfile) {
          state = state.copyWith(
            isUpdating: false,
            profile: updatedProfile,
            successMessage: 'Endereço atualizado com sucesso!',
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isUpdating: false,
        errorMessage: 'Erro inesperado ao atualizar endereço',
      );
      return false;
    }
  }

  /// Atualiza a foto de perfil
  Future<bool> updateProfilePicture(String imagePath) async {
    if (state.isUpdating) return false;

    state = state.copyWith(
      isUpdating: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final updateUsecase = ref.read(updateProfileUsecaseProvider);
      final params = UpdateProfilePictureParams(imagePath: imagePath);

      final result = await updateUsecase.call(params);

      return result.fold(
        (failure) {
          state = state.copyWith(
            isUpdating: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (updatedProfile) {
          state = state.copyWith(
            isUpdating: false,
            profile: updatedProfile,
            successMessage: 'Foto de perfil atualizada!',
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isUpdating: false,
        errorMessage: 'Erro inesperado ao atualizar foto',
      );
      return false;
    }
  }

  /// Remove a foto de perfil
  Future<bool> removeProfilePicture() async {
    if (state.isUpdating) return false;

    state = state.copyWith(
      isUpdating: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final updateUsecase = ref.read(updateProfileUsecaseProvider);
      final params = RemoveProfilePictureParams();

      final result = await updateUsecase.call(params);

      return result.fold(
        (failure) {
          state = state.copyWith(
            isUpdating: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (updatedProfile) {
          state = state.copyWith(
            isUpdating: false,
            profile: updatedProfile,
            successMessage: 'Foto removida com sucesso!',
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isUpdating: false,
        errorMessage: 'Erro inesperado ao remover foto',
      );
      return false;
    }
  }

  /// Altera a senha do usuário
  Future<bool> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    if (state.isChangingPassword) return false;

    state = state.copyWith(
      isChangingPassword: true,
      errorMessage: null,
      successMessage: null,
    );

    try {
      final changePasswordUsecase = ref.read(changePasswordUsecaseProvider);
      final params = ChangePasswordParams(
        currentPassword: currentPassword,
        newPassword: newPassword,
      );

      final result = await changePasswordUsecase.call(params);

      return result.fold(
        (failure) {
          state = state.copyWith(
            isChangingPassword: false,
            errorMessage: _getFailureMessage(failure),
          );
          return false;
        },
        (_) {
          state = state.copyWith(
            isChangingPassword: false,
            successMessage: 'Senha alterada com sucesso!',
          );
          return true;
        },
      );
    } catch (e) {
      state = state.copyWith(
        isChangingPassword: false,
        errorMessage: 'Erro inesperado ao alterar senha',
      );
      return false;
    }
  }

  /// Força atualização do perfil (pull-to-refresh)
  Future<void> refreshProfile() async {
    await loadProfile();
  }

  /// Limpa mensagens de erro e sucesso
  void clearMessages() {
    state = state.copyWith(
      errorMessage: null,
      successMessage: null,
    );
  }

  /// Limpa apenas a mensagem de erro
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }

  /// Limpa apenas a mensagem de sucesso
  void clearSuccess() {
    state = state.copyWith(successMessage: null);
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
      return 'Erro nos dados locais. Tente novamente.';
    } else {
      return 'Erro inesperado. Tente novamente.';
    }
  }
}

// ==================== CONVENIENCE PROVIDERS ====================

/// Provider conveniente para acessar apenas o perfil atual
@riverpod
Profile? currentProfile(CurrentProfileRef ref) {
  final profileState = ref.watch(profileNotifierProvider);
  return profileState.profile;
}

/// Provider conveniente para verificar se está carregando
@riverpod
bool isProfileLoading(IsProfileLoadingRef ref) {
  final profileState = ref.watch(profileNotifierProvider);
  return profileState.isLoading;
}

/// Provider conveniente para verificar se está atualizando
@riverpod
bool isProfileUpdating(IsProfileUpdatingRef ref) {
  final profileState = ref.watch(profileNotifierProvider);
  return profileState.isUpdating;
}

/// Provider conveniente para acessar a porcentagem de completude do perfil
@riverpod
double profileCompleteness(ProfileCompletenessRef ref) {
  final profile = ref.watch(currentProfileProvider);
  if (profile == null) return 0.0;
  
  final repository = ref.watch(profileRepositoryProvider);
  return repository.calculateProfileCompleteness(profile);
}

/// Provider para verificar se o perfil está completo
@riverpod
bool isProfileComplete(IsProfileCompleteRef ref) {
  final completeness = ref.watch(profileCompletenessProvider);
  return completeness >= 100.0;
}