// lib/core/routes/route_guards.dart
// 🔒 Route Guards - Sistema de proteção de rotas para autenticação e autorização

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/domain/entities/user.dart';

/// Enum para definir os tipos de usuário que podem acessar uma rota
enum UserTypeGuard {
  /// Qualquer usuário autenticado
  authenticated,
  /// Apenas clientes
  client,
  /// Apenas administradores
  admin,
  /// Apenas lojas parceiras
  store,
}

/// Guard principal para verificar autenticação
class AuthGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    final authState = ref.read(authProviderProvider);
    
    // Se está carregando, permite continuar (splash screen irá lidar)
    if (authState.isLoading) {
      return null;
    }
    
    // Se não está autenticado, redireciona para login
    if (!authState.isAuthenticated) {
      return '/login';
    }
    
    return null;
  }
}

/// Guard para verificar se usuário NÃO está autenticado (páginas públicas)
class GuestGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    final authState = ref.read(authProviderProvider);
    
    // Se está carregando, permite continuar
    if (authState.isLoading) {
      return null;
    }
    
    // Se está autenticado, redireciona para dashboard
    if (authState.isAuthenticated) {
      final user = authState.user;
      if (user != null) {
        switch (user.type) {
          case UserType.admin:
            return '/admin/dashboard';
          case UserType.store:
            return '/store/dashboard';
          case UserType.client:
          default:
            return '/dashboard';
        }
      }
      return '/dashboard';
    }
    
    return null;
  }
}

/// Guard para verificar tipos específicos de usuário
class UserTypeGuardChecker {
  static String? redirect(
    BuildContext context, 
    GoRouterState state, 
    WidgetRef ref,
    UserTypeGuard requiredType,
  ) {
    final authState = ref.read(authProviderProvider);
    
    // Se está carregando, permite continuar
    if (authState.isLoading) {
      return null;
    }
    
    // Se não está autenticado, redireciona para login
    if (!authState.isAuthenticated) {
      return '/login';
    }
    
    final user = authState.user;
    if (user == null) {
      return '/login';
    }
    
    // Verifica se o tipo de usuário tem permissão
    switch (requiredType) {
      case UserTypeGuard.authenticated:
        // Qualquer usuário autenticado pode acessar
        return null;
        
      case UserTypeGuard.client:
        if (user.type != UserType.client) {
          return _getUnauthorizedRedirect(user.type);
        }
        break;
        
      case UserTypeGuard.admin:
        if (user.type != UserType.admin) {
          return _getUnauthorizedRedirect(user.type);
        }
        break;
        
      case UserTypeGuard.store:
        if (user.type != UserType.store) {
          return _getUnauthorizedRedirect(user.type);
        }
        break;
    }
    
    return null;
  }
  
  /// Retorna o redirecionamento apropriado baseado no tipo de usuário
  static String _getUnauthorizedRedirect(UserType userType) {
    switch (userType) {
      case UserType.admin:
        return '/admin/dashboard';
      case UserType.store:
        return '/store/dashboard';
      case UserType.client:
      default:
        return '/dashboard';
    }
  }
}

/// Guard específico para rotas de cliente
class ClientGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    return UserTypeGuardChecker.redirect(context, state, ref, UserTypeGuard.client);
  }
}

/// Guard específico para rotas de administrador
class AdminGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    return UserTypeGuardChecker.redirect(context, state, ref, UserTypeGuard.admin);
  }
}

/// Guard específico para rotas de loja
class StoreGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    return UserTypeGuardChecker.redirect(context, state, ref, UserTypeGuard.store);
  }
}

/// Guard para verificar se o perfil do usuário está completo
class ProfileCompletionGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    final authState = ref.read(authProviderProvider);
    
    // Se não está autenticado, deixa AuthGuard lidar
    if (!authState.isAuthenticated || authState.user == null) {
      return null;
    }
    
    final user = authState.user!;
    
    // Verifica se o perfil está completo
    if (!user.isProfileComplete) {
      return '/profile/complete';
    }
    
    return null;
  }
}

/// Guard para verificar se a conta foi verificada
class AccountVerificationGuard {
  static String? redirect(BuildContext context, GoRouterState state, WidgetRef ref) {
    final authState = ref.read(authProviderProvider);
    
    // Se não está autenticado, deixa AuthGuard lidar
    if (!authState.isAuthenticated || authState.user == null) {
      return null;
    }
    
    final user = authState.user!;
    
    // Verifica se a conta foi verificada
    if (!user.isVerified) {
      return '/verify-account';
    }
    
    return null;
  }
}

/// Guard combinado para verificar autenticação + tipo de usuário
class CombinedGuard {
  static String? redirect(
    BuildContext context, 
    GoRouterState state, 
    WidgetRef ref,
    {
      UserTypeGuard? userType,
      bool requireProfileComplete = false,
      bool requireVerification = false,
    }
  ) {
    // Primeiro verifica autenticação
    final authRedirect = AuthGuard.redirect(context, state, ref);
    if (authRedirect != null) {
      return authRedirect;
    }
    
    // Depois verifica tipo de usuário se especificado
    if (userType != null) {
      final typeRedirect = UserTypeGuardChecker.redirect(context, state, ref, userType);
      if (typeRedirect != null) {
        return typeRedirect;
      }
    }
    
    // Verifica se perfil precisa estar completo
    if (requireProfileComplete) {
      final profileRedirect = ProfileCompletionGuard.redirect(context, state, ref);
      if (profileRedirect != null) {
        return profileRedirect;
      }
    }
    
    // Verifica se conta precisa estar verificada
    if (requireVerification) {
      final verificationRedirect = AccountVerificationGuard.redirect(context, state, ref);
      if (verificationRedirect != null) {
        return verificationRedirect;
      }
    }
    
    return null;
  }
}

/// Extension para facilitar o uso dos guards no GoRouter
extension GoRouterAuthExtensions on GoRoute {
  /// Aplica guard de autenticação à rota
  GoRoute withAuthGuard(WidgetRef ref) {
    return GoRoute(
      path: path,
      name: name,
      builder: builder,
      redirect: (context, state) => AuthGuard.redirect(context, state, ref),
      routes: routes,
    );
  }
  
  /// Aplica guard de visitante (não autenticado) à rota
  GoRoute withGuestGuard(WidgetRef ref) {
    return GoRoute(
      path: path,
      name: name,
      builder: builder,
      redirect: (context, state) => GuestGuard.redirect(context, state, ref),
      routes: routes,
    );
  }
}