// lib/main.dart
// 🚀 Main - Ponto de entrada principal do aplicativo Klube Cash

import 'dart:async';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';

/// 🎯 Função principal do aplicativo
/// 
/// Responsável por:
/// - Configurar Flutter binding
/// - Inicializar dependências
/// - Configurar orientação da tela
/// - Configurar barra de status
/// - Tratar erros globais
/// - Executar o app com Riverpod
void main() async {
  // Garante inicialização dos bindings do Flutter
  WidgetsFlutterBinding.ensureInitialized();

  // Configura tratamento de erros global
  await _setupErrorHandling();

  // Configura orientação da tela
  await _setupScreenOrientation();

  // Configura interface do sistema
  await _setupSystemUI();

  // Inicializa dependências do app
  await _initializeApp();

  // Executa o aplicativo com Riverpod
  runApp(
    ProviderScope(
      observers: [
        // Observer para debug em modo desenvolvimento
        if (kDebugMode) AppProviderObserver(),
      ],
      child: const KlubeCashApp(),
    ),
  );
}

/// ⚠️ Configuração de tratamento de erros global
Future<void> _setupErrorHandling() async {
  // Trata erros do Flutter framework
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    
    if (kDebugMode) {
      debugPrint('🚨 Flutter Error: ${details.exception}');
      debugPrint('Stack: ${details.stack}');
    }
    
    // Em produção, aqui você enviaria para crash analytics
    // FirebaseCrashlytics.instance.recordFlutterError(details);
  };

  // Trata erros assíncronos não capturados
  PlatformDispatcher.instance.onError = (error, stack) {
    if (kDebugMode) {
      debugPrint('🚨 Platform Error: $error');
      debugPrint('Stack: $stack');
    }
    
    // Em produção, aqui você enviaria para crash analytics
    // FirebaseCrashlytics.instance.recordError(error, stack);
    
    return true;
  };
}

/// 📱 Configuração de orientação da tela
Future<void> _setupScreenOrientation() async {
  // Define orientações permitidas
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
}

/// 🎨 Configuração da interface do sistema
Future<void> _setupSystemUI() async {
  // Configura barra de status transparente
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      statusBarBrightness: Brightness.light,
      systemNavigationBarColor: Colors.white,
      systemNavigationBarIconBrightness: Brightness.dark,
      systemNavigationBarDividerColor: Colors.transparent,
    ),
  );

  // Habilita edge-to-edge no Android
  if (Platform.isAndroid) {
    SystemChrome.setEnabledSystemUIMode(
      SystemUiMode.edgeToEdge,
    );
  }
}

/// 🔧 Inicialização de dependências do aplicativo
Future<void> _initializeApp() async {
  try {
    if (kDebugMode) {
      debugPrint('🚀 Inicializando Klube Cash...');
    }

    // Aqui você pode inicializar:
    // - Firebase
    // - Shared Preferences
    // - Secure Storage
    // - Notifications
    // - Crash Analytics
    // - etc.

    // Exemplo de inicializações:
    // await Firebase.initializeApp();
    // await NotificationService.initialize();
    // await SecureStorageService.initialize();

    if (kDebugMode) {
      debugPrint('✅ Klube Cash inicializado com sucesso!');
    }
  } catch (error, stackTrace) {
    if (kDebugMode) {
      debugPrint('❌ Erro na inicialização: $error');
      debugPrint('Stack: $stackTrace');
    }
    
    // Em caso de erro crítico, você pode mostrar uma tela de erro
    // ou tentar recuperação
    rethrow;
  }
}

/// 🔍 Observer para debug e monitoramento do Riverpod
class AppProviderObserver extends ProviderObserver {
  @override
  void didAddProvider(
    ProviderBase<Object?> provider,
    Object? value,
    ProviderContainer container,
  ) {
    if (kDebugMode) {
      debugPrint('🟢 Provider adicionado: ${provider.name ?? provider.runtimeType}');
    }
  }

  @override
  void didDisposeProvider(
    ProviderBase<Object?> provider,
    ProviderContainer container,
  ) {
    if (kDebugMode) {
      debugPrint('🔴 Provider removido: ${provider.name ?? provider.runtimeType}');
    }
  }

  @override
  void didUpdateProvider(
    ProviderBase<Object?> provider,
    Object? previousValue,
    Object? newValue,
    ProviderContainer container,
  ) {
    if (kDebugMode) {
      debugPrint('🔄 Provider atualizado: ${provider.name ?? provider.runtimeType}');
    }
  }

  @override
  void providerDidFail(
    ProviderBase<Object?> provider,
    Object error,
    StackTrace stackTrace,
    ProviderContainer container,
  ) {
    if (kDebugMode) {
      debugPrint('❌ Provider falhou: ${provider.name ?? provider.runtimeType}');
      debugPrint('Erro: $error');
      debugPrint('Stack: $stackTrace');
    }
    
    // Em produção, envie para crash analytics
    // FirebaseCrashlytics.instance.recordError(error, stackTrace);
  }
}