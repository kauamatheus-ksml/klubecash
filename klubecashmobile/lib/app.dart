// lib/app.dart
// 🚀 App Principal - Configuração central do aplicativo Klube Cash

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'core/constants/app_strings.dart';
import 'core/routes/app_router.dart';
import 'core/theme/app_theme.dart';
import 'core/theme/theme_provider.dart';

/// Widget principal do aplicativo Klube Cash
/// 
/// Configura toda a estrutura base incluindo:
/// - Tema e cores
/// - Navegação com GoRouter
/// - Localização pt-BR
/// - Observadores de estado
class KlubeCashApp extends ConsumerWidget {
  const KlubeCashApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Watch dos providers necessários
    final router = ref.watch(appRouterProvider);
    final themeMode = ref.watch(themeNotifierProvider);

    return MaterialApp.router(
      // ⚙️ CONFIGURAÇÕES BÁSICAS
      title: AppStrings.appName,
      debugShowCheckedModeBanner: false,
      
      // 🎨 CONFIGURAÇÃO DE TEMA
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: themeMode,
      
      // 🛣️ CONFIGURAÇÃO DE NAVEGAÇÃO
      routerConfig: router,
      
      // 🌍 CONFIGURAÇÃO DE LOCALIZAÇÃO
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('pt', 'BR'), // Português Brasil
        Locale('en', 'US'), // Inglês (fallback)
      ],
      locale: const Locale('pt', 'BR'),
      
      // 📱 CONFIGURAÇÕES DO SISTEMA
      builder: (context, child) {
        return AnnotatedRegion<SystemUiOverlayStyle>(
          value: _getSystemUiOverlayStyle(context),
          child: MediaQuery(
            // Configuração responsiva
            data: MediaQuery.of(context).copyWith(
              textScaleFactor: MediaQuery.of(context).textScaleFactor.clamp(0.8, 1.2),
            ),
            child: child ?? const SizedBox.shrink(),
          ),
        );
      },
    );
  }

  /// Configuração da barra de status baseada no tema atual
  SystemUiOverlayStyle _getSystemUiOverlayStyle(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    
    if (brightness == Brightness.dark) {
      return const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        statusBarBrightness: Brightness.dark,
        systemNavigationBarColor: Color(0xFF121212),
        systemNavigationBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: Colors.transparent,
      );
    } else {
      return const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
        statusBarBrightness: Brightness.light,
        systemNavigationBarColor: Colors.white,
        systemNavigationBarIconBrightness: Brightness.dark,
        systemNavigationBarDividerColor: Colors.transparent,
      );
    }
  }
}

/// Provider Observer para debug e monitoramento do Riverpod
class AppProviderObserver extends ProviderObserver {
  @override
  void didAddProvider(
    ProviderBase<Object?> provider,
    Object? value,
    ProviderContainer container,
  ) {
    debugPrint('🟢 Provider adicionado: ${provider.name ?? provider.runtimeType}');
  }

  @override
  void didDisposeProvider(
    ProviderBase<Object?> provider,
    ProviderContainer container,
  ) {
    debugPrint('🔴 Provider removido: ${provider.name ?? provider.runtimeType}');
  }

  @override
  void didUpdateProvider(
    ProviderBase<Object?> provider,
    Object? previousValue,
    Object? newValue,
    ProviderContainer container,
  ) {
    debugPrint('🔄 Provider atualizado: ${provider.name ?? provider.runtimeType}');
  }

  @override
  void providerDidFail(
    ProviderBase<Object?> provider,
    Object error,
    StackTrace stackTrace,
    ProviderContainer container,
  ) {
    debugPrint('❌ Provider falhou: ${provider.name ?? provider.runtimeType}');
    debugPrint('Erro: $error');
  }
}

/// Configuração global de erro para Riverpod
class AppErrorHandler {
  static void handleError(Object error, StackTrace stackTrace) {
    debugPrint('🚨 Erro não tratado no app: $error');
    debugPrint('Stack trace: $stackTrace');
    
    // Aqui você pode adicionar integração com crash analytics
    // como Firebase Crashlytics, Sentry, etc.
  }
}

/// Extensão para facilitar acesso a providers comuns
extension AppProviders on WidgetRef {
  /// Acesso rápido ao router
  GoRouter get router => read(appRouterProvider);
  
  /// Acesso rápido ao tema
  ThemeMode get theme => watch(themeNotifierProvider);
  
  /// Verificação de autenticação
  bool get isAuthenticated => watch(isAuthenticatedProvider);
}