// lib/core/routes/app_router.dart
// 🛣️ App Router - Configuração de navegação com Go Router e guards de autenticação

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/screens/splash_screen.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/auth/presentation/screens/recover_password_screen.dart';
import '../../features/dashboard/presentation/screens/dashboard_screen.dart';
import '../../features/cashback/presentation/screens/cashback_screen.dart';
import '../../features/cashback/presentation/screens/transaction_details_screen.dart';
import '../../features/stores/presentation/screens/stores_screen.dart';
import '../../features/stores/presentation/screens/store_details_screen.dart';
import '../../features/profile/presentation/screens/profile_screen.dart';
import '../../features/profile/presentation/screens/edit_profile_screen.dart';
import '../../features/profile/presentation/screens/change_password_screen.dart';
import '../../features/notifications/presentation/screens/notifications_screen.dart';
import 'route_guards.dart';

/// Provider do GoRouter configurado com todas as rotas e guards
final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      // Redirect global pode ser usado para lógica adicional se necessário
      return null;
    },
    routes: [
      // 🚀 SPLASH SCREEN
      GoRoute(
        path: '/',
        name: 'splash',
        builder: (context, state) => const SplashScreen(),
      ),

      // 🔐 ROTAS DE AUTENTICAÇÃO (Apenas para usuários não autenticados)
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginScreen(),
        redirect: (context, state) => GuestGuard.redirect(context, state, ref),
      ),
      
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) => const RegisterScreen(),
        redirect: (context, state) => GuestGuard.redirect(context, state, ref),
      ),
      
      GoRoute(
        path: '/recover-password',
        name: 'recover-password',
        builder: (context, state) => const RecoverPasswordScreen(),
        redirect: (context, state) => GuestGuard.redirect(context, state, ref),
      ),

      // 📱 ROTAS PRINCIPAIS DO APP (Requer autenticação)
      GoRoute(
        path: '/dashboard',
        name: 'dashboard',
        builder: (context, state) => const DashboardScreen(),
        redirect: (context, state) => CombinedGuard.redirect(
          context, 
          state, 
          ref,
          userType: UserTypeGuard.client,
        ),
        routes: [
          // Subrotas do dashboard podem ser definidas aqui se necessário
        ],
      ),

      // 💰 ROTAS DE CASHBACK
      GoRoute(
        path: '/cashback',
        name: 'cashback',
        builder: (context, state) => const CashbackScreen(),
        redirect: (context, state) => CombinedGuard.redirect(
          context, 
          state, 
          ref,
          userType: UserTypeGuard.client,
        ),
        routes: [
          GoRoute(
            path: '/transaction/:transactionId',
            name: 'transaction-details',
            builder: (context, state) {
              final transactionId = state.pathParameters['transactionId']!;
              return TransactionDetailsScreen(transactionId: transactionId);
            },
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.client,
            ),
          ),
        ],
      ),

      // 🏪 ROTAS DE LOJAS
      GoRoute(
        path: '/stores',
        name: 'stores',
        builder: (context, state) => const StoresScreen(),
        redirect: (context, state) => CombinedGuard.redirect(
          context, 
          state, 
          ref,
          userType: UserTypeGuard.client,
        ),
        routes: [
          GoRoute(
            path: '/:storeId',
            name: 'store-details',
            builder: (context, state) {
              final storeId = state.pathParameters['storeId']!;
              return StoreDetailsScreen(storeId: storeId);
            },
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.client,
            ),
          ),
        ],
      ),

      // 👤 ROTAS DE PERFIL
      GoRoute(
        path: '/profile',
        name: 'profile',
        builder: (context, state) => const ProfileScreen(),
        redirect: (context, state) => AuthGuard.redirect(context, state, ref),
        routes: [
          GoRoute(
            path: '/edit',
            name: 'edit-profile',
            builder: (context, state) => const EditProfileScreen(),
            redirect: (context, state) => AuthGuard.redirect(context, state, ref),
          ),
          GoRoute(
            path: '/change-password',
            name: 'change-password',
            builder: (context, state) => const ChangePasswordScreen(),
            redirect: (context, state) => AuthGuard.redirect(context, state, ref),
          ),
        ],
      ),

      // 🔔 ROTAS DE NOTIFICAÇÕES
      GoRoute(
        path: '/notifications',
        name: 'notifications',
        builder: (context, state) => const NotificationsScreen(),
        redirect: (context, state) => AuthGuard.redirect(context, state, ref),
      ),

      // 👨‍💼 ROTAS ADMINISTRATIVAS (Apenas para admins)
      GoRoute(
        path: '/admin',
        name: 'admin',
        redirect: (context, state) => '/admin/dashboard',
        routes: [
          GoRoute(
            path: '/dashboard',
            name: 'admin-dashboard',
            builder: (context, state) => const AdminDashboardScreen(),
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.admin,
            ),
          ),
          GoRoute(
            path: '/users',
            name: 'admin-users',
            builder: (context, state) => const AdminUsersScreen(),
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.admin,
            ),
          ),
          GoRoute(
            path: '/stores',
            name: 'admin-stores',
            builder: (context, state) => const AdminStoresScreen(),
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.admin,
            ),
          ),
        ],
      ),

      // 🏬 ROTAS PARA LOJAS PARCEIRAS (Apenas para lojas)
      GoRoute(
        path: '/store',
        name: 'store',
        redirect: (context, state) => '/store/dashboard',
        routes: [
          GoRoute(
            path: '/dashboard',
            name: 'store-dashboard',
            builder: (context, state) => const StoreDashboardScreen(),
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.store,
            ),
          ),
          GoRoute(
            path: '/transactions',
            name: 'store-transactions',
            builder: (context, state) => const StoreTransactionsScreen(),
            redirect: (context, state) => CombinedGuard.redirect(
              context, 
              state, 
              ref,
              userType: UserTypeGuard.store,
            ),
          ),
        ],
      ),

      // 🔧 ROTAS DE CONFIGURAÇÃO E OUTRAS
      GoRoute(
        path: '/settings',
        name: 'settings',
        builder: (context, state) => const SettingsScreen(),
        redirect: (context, state) => AuthGuard.redirect(context, state, ref),
      ),

      GoRoute(
        path: '/help',
        name: 'help',
        builder: (context, state) => const HelpScreen(),
        redirect: (context, state) => AuthGuard.redirect(context, state, ref),
      ),

      GoRoute(
        path: '/terms',
        name: 'terms',
        builder: (context, state) => const TermsScreen(),
      ),

      GoRoute(
        path: '/privacy',
        name: 'privacy',
        builder: (context, state) => const PrivacyScreen(),
      ),
    ],
    
    // Página de erro personalizada
    errorBuilder: (context, state) => Scaffold(
      appBar: AppBar(
        title: const Text('Erro'),
        backgroundColor: Colors.red,
        foregroundColor: Colors.white,
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              size: 64,
              color: Colors.red,
            ),
            const SizedBox(height: 16),
            Text(
              'Página não encontrada',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              'A página "${state.fullPath}" não existe.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => context.go('/dashboard'),
              child: const Text('Voltar ao Início'),
            ),
          ],
        ),
      ),
    ),
  );
});

/// Extension para navegação mais fácil
extension AppRouterExtensions on GoRouter {
  /// Navega para o dashboard baseado no tipo de usuário
  void goToDashboard(UserType userType) {
    switch (userType) {
      case UserType.admin:
        go('/admin/dashboard');
        break;
      case UserType.store:
        go('/store/dashboard');
        break;
      case UserType.client:
      default:
        go('/dashboard');
        break;
    }
  }
  
  /// Navega para a tela de login
  void goToLogin() {
    go('/login');
  }
  
  /// Navega para a tela de perfil
  void goToProfile() {
    go('/profile');
  }
}

/// Provider para acessar o router atual
final currentRouterProvider = Provider<GoRouter>((ref) {
  return ref.watch(appRouterProvider);
});

// Screens que ainda precisam ser implementadas (placeholders)
class AdminDashboardScreen extends StatelessWidget {
  const AdminDashboardScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Admin Dashboard')));
}

class AdminUsersScreen extends StatelessWidget {
  const AdminUsersScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Admin Users')));
}

class AdminStoresScreen extends StatelessWidget {
  const AdminStoresScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Admin Stores')));
}

class StoreDashboardScreen extends StatelessWidget {
  const StoreDashboardScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Store Dashboard')));
}

class StoreTransactionsScreen extends StatelessWidget {
  const StoreTransactionsScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Store Transactions')));
}

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Settings')));
}

class HelpScreen extends StatelessWidget {
  const HelpScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Help')));
}

class TermsScreen extends StatelessWidget {
  const TermsScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Terms')));
}

class PrivacyScreen extends StatelessWidget {
  const PrivacyScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(body: Center(child: Text('Privacy')));
}