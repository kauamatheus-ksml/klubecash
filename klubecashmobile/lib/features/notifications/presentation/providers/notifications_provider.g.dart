// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notifications_provider.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

String _$notificationsRepositoryHash() =>
    r'b56d5042325cb724e83c1d8a68fbcd1edff3d8b0';

/// Provider para o repositório de notificações
///
/// Copied from [notificationsRepository].
@ProviderFor(notificationsRepository)
final notificationsRepositoryProvider =
    AutoDisposeProvider<NotificationsRepositoryImpl>.internal(
  notificationsRepository,
  name: r'notificationsRepositoryProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$notificationsRepositoryHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef NotificationsRepositoryRef
    = AutoDisposeProviderRef<NotificationsRepositoryImpl>;
String _$getNotificationsUsecaseHash() =>
    r'758211474763b2116011b0b55ad582ac7634d362';

/// Provider para use cases de notificações
///
/// Copied from [getNotificationsUsecase].
@ProviderFor(getNotificationsUsecase)
final getNotificationsUsecaseProvider =
    AutoDisposeProvider<GetNotificationsUsecase>.internal(
  getNotificationsUsecase,
  name: r'getNotificationsUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$getNotificationsUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef GetNotificationsUsecaseRef
    = AutoDisposeProviderRef<GetNotificationsUsecase>;
String _$markAsReadUsecaseHash() => r'7135e1f3ecdcea7248be602639b89c5f6ab96992';

/// See also [markAsReadUsecase].
@ProviderFor(markAsReadUsecase)
final markAsReadUsecaseProvider =
    AutoDisposeProvider<MarkAsReadUsecase>.internal(
  markAsReadUsecase,
  name: r'markAsReadUsecaseProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$markAsReadUsecaseHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef MarkAsReadUsecaseRef = AutoDisposeProviderRef<MarkAsReadUsecase>;
String _$unreadNotificationsCountHash() =>
    r'672a52c456b76d2d745c898fda7da8f7f5ddec51';

/// Provider para contagem de notificações não lidas
///
/// Copied from [unreadNotificationsCount].
@ProviderFor(unreadNotificationsCount)
final unreadNotificationsCountProvider = AutoDisposeProvider<int>.internal(
  unreadNotificationsCount,
  name: r'unreadNotificationsCountProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$unreadNotificationsCountHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef UnreadNotificationsCountRef = AutoDisposeProviderRef<int>;
String _$hasUnreadNotificationsHash() =>
    r'cdae6002acf67e563ee45f50d6a18553b0a96e9c';

/// Provider para verificar se há notificações não lidas
///
/// Copied from [hasUnreadNotifications].
@ProviderFor(hasUnreadNotifications)
final hasUnreadNotificationsProvider = AutoDisposeProvider<bool>.internal(
  hasUnreadNotifications,
  name: r'hasUnreadNotificationsProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$hasUnreadNotificationsHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

@Deprecated('Will be removed in 3.0. Use Ref instead')
// ignore: unused_element
typedef HasUnreadNotificationsRef = AutoDisposeProviderRef<bool>;
String _$notificationsNotifierHash() =>
    r'dc92780d2538cc4a79daf58c5da7e61eabecd263';

/// Provider principal das notificações
///
/// Copied from [NotificationsNotifier].
@ProviderFor(NotificationsNotifier)
final notificationsNotifierProvider = AutoDisposeNotifierProvider<
    NotificationsNotifier, NotificationsState>.internal(
  NotificationsNotifier.new,
  name: r'notificationsNotifierProvider',
  debugGetCreateSourceHash: const bool.fromEnvironment('dart.vm.product')
      ? null
      : _$notificationsNotifierHash,
  dependencies: null,
  allTransitiveDependencies: null,
);

typedef _$NotificationsNotifier = AutoDisposeNotifier<NotificationsState>;
// ignore_for_file: type=lint
// ignore_for_file: subtype_of_sealed_class, invalid_use_of_internal_member, invalid_use_of_visible_for_testing_member, deprecated_member_use_from_same_package
