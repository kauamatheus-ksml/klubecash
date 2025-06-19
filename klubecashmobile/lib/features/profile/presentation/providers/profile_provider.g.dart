// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$profileRepositoryHash() => r'4c1d79743476657cb68110dced6c04f062738337';

/// Provider para o repositório de perfil
///
/// Copied from [profileRepository].
@ProviderFor(profileRepository)
final profileRepositoryProvider =
    AutoDisposeProvider<ProfileRepositoryImpl>.internal(
  profileRepository,
  name: r'profileRepositoryProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$profileRepositoryHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef ProfileRepositoryRef = AutoDisposeProviderRef<ProfileRepositoryImpl>;
String _$getProfileUsecaseHash() => r'7d7109096b3ff7e598af0b2271f5ff38807e51ba';

/// Provider para use cases de perfil
///
/// Copied from [getProfileUsecase].
@ProviderFor(getProfileUsecase)
final getProfileUsecaseProvider =
    AutoDisposeProvider<GetProfileUsecase>.internal(
  getProfileUsecase,
  name: r'getProfileUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getProfileUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetProfileUsecaseRef = AutoDisposeProviderRef<GetProfileUsecase>;
String _$updateProfileUsecaseHash() =>
    r'99157ed25b4f325f0406332046c933d90bcddd10';

/// See also [updateProfileUsecase].
@ProviderFor(updateProfileUsecase)
final updateProfileUsecaseProvider =
    AutoDisposeProvider<UpdateProfileUsecase>.internal(
  updateProfileUsecase,
  name: r'updateProfileUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$updateProfileUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef UpdateProfileUsecaseRef = AutoDisposeProviderRef<UpdateProfileUsecase>;
String _$changePasswordUsecaseHash() =>
    r'd19ec7ee3625a86f517406f93f4d6d8eac3abb09';

/// See also [changePasswordUsecase].
@ProviderFor(changePasswordUsecase)
final changePasswordUsecaseProvider =
    AutoDisposeProvider<ChangePasswordUsecase>.internal(
  changePasswordUsecase,
  name: r'changePasswordUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$changePasswordUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef ChangePasswordUsecaseRef
    = AutoDisposeProviderRef<ChangePasswordUsecase>;
String _$currentProfileHash() => r'3c6ca25fbe7191e7921db7c64be36502233a2c4c';

/// Provider conveniente para acessar apenas o perfil atual
///
/// Copied from [currentProfile].
@ProviderFor(currentProfile)
final currentProfileProvider = AutoDisposeProvider<Profile?>.internal(
  currentProfile,
  name: r'currentProfileProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$currentProfileHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef CurrentProfileRef = AutoDisposeProviderRef<Profile?>;
String _$isProfileLoadingHash() => r'429a7b140b07b0133a55305afc03f3cda0cec82e';

/// Provider conveniente para verificar se está carregando
///
/// Copied from [isProfileLoading].
@ProviderFor(isProfileLoading)
final isProfileLoadingProvider = AutoDisposeProvider<bool>.internal(
  isProfileLoading,
  name: r'isProfileLoadingProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$isProfileLoadingHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsProfileLoadingRef = AutoDisposeProviderRef<bool>;
String _$isProfileUpdatingHash() => r'0e6ae3bf1017ee3992dbafe92cfa301b515d0a61';

/// Provider conveniente para verificar se está atualizando
///
/// Copied from [isProfileUpdating].
@ProviderFor(isProfileUpdating)
final isProfileUpdatingProvider = AutoDisposeProvider<bool>.internal(
  isProfileUpdating,
  name: r'isProfileUpdatingProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$isProfileUpdatingHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsProfileUpdatingRef = AutoDisposeProviderRef<bool>;
String _$profileCompletenessHash() =>
    r'c57748737c869b5691bcccb96e41e83640872604';

/// Provider conveniente para acessar a porcentagem de completude do perfil
///
/// Copied from [profileCompleteness].
@ProviderFor(profileCompleteness)
final profileCompletenessProvider = AutoDisposeProvider<double>.internal(
  profileCompleteness,
  name: r'profileCompletenessProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$profileCompletenessHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef ProfileCompletenessRef = AutoDisposeProviderRef<double>;
String _$isProfileCompleteHash() => r'24756cf7b689eef69000297f107d61903527703a';

/// Provider para verificar se o perfil está completo
///
/// Copied from [isProfileComplete].
@ProviderFor(isProfileComplete)
final isProfileCompleteProvider = AutoDisposeProvider<bool>.internal(
  isProfileComplete,
  name: r'isProfileCompleteProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$isProfileCompleteHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef IsProfileCompleteRef = AutoDisposeProviderRef<bool>;
String _$profileNotifierHash() => r'401f394dc4492cc98f188ff220186117bf496181';

/// Provider principal do perfil
///
/// Copied from [ProfileNotifier].
@ProviderFor(ProfileNotifier)
final profileNotifierProvider =
    AutoDisposeNotifierProvider<ProfileNotifier, ProfileState>.internal(
  ProfileNotifier.new,
  name: r'profileNotifierProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$profileNotifierHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$ProfileNotifier = AutoDisposeNotifier<ProfileState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
