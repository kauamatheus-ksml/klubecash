// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$authRepositoryHash() => r'5a6cc52e11da90d5386b5b01c892892235cb1ddc';

/// Provider para o repositório de autenticação
///
/// Copied from [authRepository].
@ProviderFor(authRepository)
final authRepositoryProvider = AutoDisposeProvider<AuthRepositoryImpl>.internal(
  authRepository,
  name: r'authRepositoryProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$authRepositoryHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef AuthRepositoryRef = AutoDisposeProviderRef<AuthRepositoryImpl>;
String _$loginUseCaseHash() => r'0e7afee1ae08672bd0eef20379802273225802d1';

/// Provider para use cases de autenticação
///
/// Copied from [loginUseCase].
@ProviderFor(loginUseCase)
final loginUseCaseProvider = AutoDisposeProvider<LoginUseCase>.internal(
  loginUseCase,
  name: r'loginUseCaseProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$loginUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef LoginUseCaseRef = AutoDisposeProviderRef<LoginUseCase>;
String _$registerUseCaseHash() => r'84c2c6ba2489fecb8ef86cb1083513cbbc2a400a';

/// See also [registerUseCase].
@ProviderFor(registerUseCase)
final registerUseCaseProvider = AutoDisposeProvider<RegisterUseCase>.internal(
  registerUseCase,
  name: r'registerUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$registerUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef RegisterUseCaseRef = AutoDisposeProviderRef<RegisterUseCase>;
String _$logoutUseCaseHash() => r'38998edfb7c9086eff09011ab46b35cac779581a';

/// See also [logoutUseCase].
@ProviderFor(logoutUseCase)
final logoutUseCaseProvider = AutoDisposeProvider<LogoutUseCase>.internal(
  logoutUseCase,
  name: r'logoutUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$logoutUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef LogoutUseCaseRef = AutoDisposeProviderRef<LogoutUseCase>;
String _$recoverPasswordUseCaseHash() =>
    r'b729c50977147d5b0f68660217b9b9f02203602e';

/// See also [recoverPasswordUseCase].
@ProviderFor(recoverPasswordUseCase)
final recoverPasswordUseCaseProvider =
    AutoDisposeProvider<RecoverPasswordUseCase>.internal(
  recoverPasswordUseCase,
  name: r'recoverPasswordUseCaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$recoverPasswordUseCaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef RecoverPasswordUseCaseRef
    = AutoDisposeProviderRef<RecoverPasswordUseCase>;
String _$isAuthenticatedHash() => r'd70b9eb2d0c47afdee83a0904cc00017f74dadd4';

/// Provider conveniente para acessar apenas o estado de autenticação
///
/// Copied from [isAuthenticated].
@ProviderFor(isAuthenticated)
final isAuthenticatedProvider = AutoDisposeProvider<bool>.internal(
  isAuthenticated,
  name: r'isAuthenticatedProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$isAuthenticatedHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsAuthenticatedRef = AutoDisposeProviderRef<bool>;
String _$currentUserHash() => r'634005fd3bc945c45f809428bbe29fddc9f83874';

/// Provider conveniente para acessar apenas o usuário atual
///
/// Copied from [currentUser].
@ProviderFor(currentUser)
final currentUserProvider = AutoDisposeProvider<User?>.internal(
  currentUser,
  name: r'currentUserProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$currentUserHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef CurrentUserRef = AutoDisposeProviderRef<User?>;
String _$isLoadingHash() => r'b303ce4d6380e60e7c6ac83be4fb1af613a7825f';

/// Provider conveniente para verificar se está carregando
///
/// Copied from [isLoading].
@ProviderFor(isLoading)
final isLoadingProvider = AutoDisposeProvider<bool>.internal(
  isLoading,
  name: r'isLoadingProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$isLoadingHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsLoadingRef = AutoDisposeProviderRef<bool>;
String _$authProviderHash() => r'0b6637ea7348d4fa61e13892b0947bbc78d5c01c';

/// Provider principal de autenticação
///
/// Copied from [AuthProvider].
@ProviderFor(AuthProvider)
final authProviderProvider =
    AutoDisposeNotifierProvider<AuthProvider, AuthState>.internal(
  AuthProvider.new,
  name: r'authProviderProvider',
  debugGetCreateSourceHash:
      const bool.fromEnvironment('dart.vm.product') ? null : _$authProviderHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$AuthProvider = AutoDisposeNotifier<AuthState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
