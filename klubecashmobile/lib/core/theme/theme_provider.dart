// lib/core/theme/theme_provider.dart
// Este arquivo contém o provider para gerenciamento de tema do app Klube Cash
// Gerencia alternância entre tema claro/escuro e persiste a preferência do usuário

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'app_theme.dart';

// ==================== ENUMS ====================

enum ThemeMode { light, dark, system }

// ==================== STATE CLASSES ====================

class ThemeState {
  final ThemeMode themeMode;
  final bool isDarkMode;
  final ThemeData currentTheme;

  const ThemeState({
    required this.themeMode,
    required this.isDarkMode,
    required this.currentTheme,
  });

  ThemeState copyWith({
    ThemeMode? themeMode,
    bool? isDarkMode,
    ThemeData? currentTheme,
  }) {
    return ThemeState(
      themeMode: themeMode ?? this.themeMode,
      isDarkMode: isDarkMode ?? this.isDarkMode,
      currentTheme: currentTheme ?? this.currentTheme,
    );
  }
}

// ==================== THEME NOTIFIER ====================

class ThemeNotifier extends StateNotifier<ThemeState> {
  static const String _themeKey = 'theme_mode';
  late SharedPreferences _prefs;

  ThemeNotifier() : super(_getInitialState()) {
    _initializePreferences();
  }

  /// Estado inicial baseado na configuração do sistema
  static ThemeState _getInitialState() {
    final brightness = SchedulerBinding.instance.platformDispatcher.platformBrightness;
    final isDark = brightness == Brightness.dark;
    
    return ThemeState(
      themeMode: ThemeMode.system,
      isDarkMode: isDark,
      currentTheme: AppTheme.getTheme(isDark),
    );
  }

  /// Inicializa SharedPreferences e carrega preferência salva
  Future<void> _initializePreferences() async {
    _prefs = await SharedPreferences.getInstance();
    await _loadSavedTheme();
  }

  /// Carrega tema salvo das preferências
  Future<void> _loadSavedTheme() async {
    final savedThemeIndex = _prefs.getInt(_themeKey);
    
    if (savedThemeIndex != null) {
      final themeMode = ThemeMode.values[savedThemeIndex];
      await setThemeMode(themeMode);
    }
  }

  /// Salva tema nas preferências
  Future<void> _saveTheme(ThemeMode themeMode) async {
    await _prefs.setInt(_themeKey, themeMode.index);
  }

  /// Define o modo de tema
  Future<void> setThemeMode(ThemeMode mode) async {
    bool isDark;
    
    switch (mode) {
      case ThemeMode.light:
        isDark = false;
        break;
      case ThemeMode.dark:
        isDark = true;
        break;
      case ThemeMode.system:
        final brightness = SchedulerBinding.instance.platformDispatcher.platformBrightness;
        isDark = brightness == Brightness.dark;
        break;
    }

    // Atualiza a barra de status
    _updateSystemUI(isDark);

    state = state.copyWith(
      themeMode: mode,
      isDarkMode: isDark,
      currentTheme: AppTheme.getTheme(isDark),
    );

    await _saveTheme(mode);
  }

  /// Alterna entre tema claro e escuro
  Future<void> toggleTheme() async {
    final newMode = state.isDarkMode ? ThemeMode.light : ThemeMode.dark;
    await setThemeMode(newMode);
  }

  /// Segue configuração do sistema
  Future<void> useSystemTheme() async {
    await setThemeMode(ThemeMode.system);
  }

  /// Atualiza configurações da UI do sistema
  void _updateSystemUI(bool isDark) {
    SystemChrome.setSystemUIOverlayStyle(
      SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: isDark ? Brightness.light : Brightness.dark,
        statusBarBrightness: isDark ? Brightness.dark : Brightness.light,
        systemNavigationBarColor: isDark ? AppTheme.darkColorScheme.surface : AppTheme.lightColorScheme.surface,
        systemNavigationBarIconBrightness: isDark ? Brightness.light : Brightness.dark,
      ),
    );
  }

  /// Responde a mudanças na configuração do sistema
  void onSystemBrightnessChanged() {
    if (state.themeMode == ThemeMode.system) {
      final brightness = SchedulerBinding.instance.platformDispatcher.platformBrightness;
      final isDark = brightness == Brightness.dark;
      
      if (isDark != state.isDarkMode) {
        _updateSystemUI(isDark);
        
        state = state.copyWith(
          isDarkMode: isDark,
          currentTheme: AppTheme.getTheme(isDark),
        );
      }
    }
  }
}

// ==================== PROVIDERS ====================

/// Provider principal do tema
final themeProvider = StateNotifierProvider<ThemeNotifier, ThemeState>((ref) {
  return ThemeNotifier();
});

/// Provider que observa apenas se está em modo escuro
final isDarkModeProvider = Provider<bool>((ref) {
  return ref.watch(themeProvider).isDarkMode;
});

/// Provider que observa apenas o tema atual
final currentThemeProvider = Provider<ThemeData>((ref) {
  return ref.watch(themeProvider).currentTheme;
});

/// Provider que observa apenas o modo de tema
final themeModeProvider = Provider<ThemeMode>((ref) {
  return ref.watch(themeProvider).themeMode;
});

/// Provider para o ColorScheme atual
final colorSchemeProvider = Provider<ColorScheme>((ref) {
  final isDark = ref.watch(isDarkModeProvider);
  return AppTheme.getColorScheme(isDark);
});

// ==================== MÉTODOS AUXILIARES ====================

/// Classe com métodos estáticos para facilitar o uso
class ThemeHelper {
  /// Verifica se está em modo escuro
  static bool isDarkMode(WidgetRef ref) {
    return ref.read(isDarkModeProvider);
  }

  /// Alterna o tema
  static Future<void> toggleTheme(WidgetRef ref) async {
    await ref.read(themeProvider.notifier).toggleTheme();
  }

  /// Define tema claro
  static Future<void> setLightTheme(WidgetRef ref) async {
    await ref.read(themeProvider.notifier).setThemeMode(ThemeMode.light);
  }

  /// Define tema escuro
  static Future<void> setDarkTheme(WidgetRef ref) async {
    await ref.read(themeProvider.notifier).setThemeMode(ThemeMode.dark);
  }

  /// Usa configuração do sistema
  static Future<void> useSystemTheme(WidgetRef ref) async {
    await ref.read(themeProvider.notifier).useSystemTheme();
  }

  /// Obtém o ColorScheme atual
  static ColorScheme getColorScheme(WidgetRef ref) {
    return ref.read(colorSchemeProvider);
  }

  /// Obtém cor primária atual
  static Color getPrimaryColor(WidgetRef ref) {
    return ref.read(colorSchemeProvider).primary;
  }

  /// Obtém cor de fundo atual
  static Color getBackgroundColor(WidgetRef ref) {
    return ref.read(colorSchemeProvider).background;
  }

  /// Obtém cor de superficie atual
  static Color getSurfaceColor(WidgetRef ref) {
    return ref.read(colorSchemeProvider).surface;
  }
}

// ==================== WIDGET LISTENER ====================

/// Widget que escuta mudanças na configuração do sistema
class SystemThemeListener extends ConsumerWidget {
  final Widget child;

  const SystemThemeListener({
    super.key,
    required this.child,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Escuta mudanças na configuração do sistema
    ref.listen<ThemeState>(themeProvider, (previous, current) {
      if (current.themeMode == ThemeMode.system) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          ref.read(themeProvider.notifier).onSystemBrightnessChanged();
        });
      }
    });

    return child;
  }
}