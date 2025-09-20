// lib/features/auth/presentation/widgets/auth_header.dart
// 🎨 Auth Header - Widget reutilizável para cabeçalhos das telas de autenticação

import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_images.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';

/// Widget de cabeçalho reutilizável para telas de autenticação
/// 
/// Inclui logo, título principal, subtítulo e opções de customização
class AuthHeader extends StatelessWidget {
  /// Título principal exibido
  final String title;
  
  /// Subtítulo opcional 
  final String? subtitle;
  
  /// Se deve exibir o logo
  final bool showLogo;
  
  /// Tamanho do logo (padrão: 60)
  final double logoSize;
  
  /// Espaçamento inferior do header
  final double bottomSpacing;
  
  /// Alinhamento do texto (padrão: center)
  final TextAlign textAlign;
  
  /// Se deve aplicar animação de entrada
  final bool animated;
  
  /// Cor personalizada para o título
  final Color? titleColor;
  
  /// Cor personalizada para o subtítulo
  final Color? subtitleColor;

  const AuthHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.showLogo = true,
    this.logoSize = 60.0,
    this.bottomSpacing = AppDimensions.marginLarge,
    this.textAlign = TextAlign.center,
    this.animated = true,
    this.titleColor,
    this.subtitleColor,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    
    final headerContent = Column(
      children: [
        // Logo
        if (showLogo) ...[
          _buildLogo(),
          const SizedBox(height: AppDimensions.marginMedium),
        ],
        
        // Título Principal
        _buildTitle(theme),
        
        // Subtítulo
        if (subtitle != null) ...[
          const SizedBox(height: AppDimensions.marginSmall),
          _buildSubtitle(theme),
        ],
        
        SizedBox(height: bottomSpacing),
      ],
    );

    return animated
        ? TweenAnimationBuilder<double>(
            duration: const Duration(milliseconds: 800),
            tween: Tween(begin: 0.0, end: 1.0),
            curve: Curves.easeOutCubic,
            builder: (context, value, child) {
              return Transform.translate(
                offset: Offset(0, 20 * (1 - value)),
                child: Opacity(
                  opacity: value,
                  child: headerContent,
                ),
              );
            },
          )
        : headerContent;
  }

  /// Constrói o logo do Klube Cash
  Widget _buildLogo() {
    return Container(
      width: logoSize,
      height: logoSize,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: AppColors.primaryGradient,
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Center(
        child: Container(
          width: logoSize * 0.6,
          height: logoSize * 0.6,
          decoration: const BoxDecoration(
            shape: BoxShape.circle,
            color: AppColors.white,
          ),
          child: Center(
            child: Text(
              'C',
              style: TextStyle(
                fontSize: logoSize * 0.35,
                fontWeight: FontWeight.bold,
                color: AppColors.primary,
                fontFamily: 'Poppins',
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Constrói o título principal
  Widget _buildTitle(ThemeData theme) {
    return Text(
      title,
      textAlign: textAlign,
      style: theme.textTheme.headlineMedium?.copyWith(
        color: titleColor ?? AppColors.gray900,
        fontWeight: FontWeight.bold,
        fontFamily: 'Poppins',
        letterSpacing: -0.5,
        height: 1.2,
      ),
    );
  }

  /// Constrói o subtítulo
  Widget _buildSubtitle(ThemeData theme) {
    return Text(
      subtitle!,
      textAlign: textAlign,
      style: theme.textTheme.bodyLarge?.copyWith(
        color: subtitleColor ?? AppColors.gray600,
        fontWeight: FontWeight.w400,
        fontFamily: 'Inter',
        height: 1.4,
      ),
    );
  }
}

/// Variantes pré-configuradas do AuthHeader para diferentes telas

/// Header para tela de login
class LoginHeader extends StatelessWidget {
  const LoginHeader({super.key});

  @override
  Widget build(BuildContext context) {
    return const AuthHeader(
      title: AppStrings.welcomeBack,
      subtitle: AppStrings.loginSubtitle,
    );
  }
}

/// Header para tela de registro
class RegisterHeader extends StatelessWidget {
  const RegisterHeader({super.key});

  @override
  Widget build(BuildContext context) {
    return AuthHeader(
      title: AppStrings.createAccount,
      subtitle: AppStrings.registerSubtitle,
      titleColor: AppColors.gray900,
    );
  }
}

/// Header para tela de recuperação de senha
class RecoverPasswordHeader extends StatelessWidget {
  const RecoverPasswordHeader({super.key});

  @override
  Widget build(BuildContext context) {
    return const AuthHeader(
      title: AppStrings.recoverPassword,
      subtitle: AppStrings.recoverPasswordSubtitle,
      logoSize: 50.0,
    );
  }
}

/// Header compacto sem logo
class CompactAuthHeader extends StatelessWidget {
  final String title;
  final String? subtitle;

  const CompactAuthHeader({
    super.key,
    required this.title,
    this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return AuthHeader(
      title: title,
      subtitle: subtitle,
      showLogo: false,
      bottomSpacing: AppDimensions.marginMedium,
    );
  }
}

/// Header com destaque na palavra (usado no registro)
class HighlightedAuthHeader extends StatelessWidget {
  final String mainTitle;
  final String highlightedWord;
  final String? subtitle;

  const HighlightedAuthHeader({
    super.key,
    required this.mainTitle,
    required this.highlightedWord,
    this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    
    return Column(
      children: [
        // Logo
        const AuthHeader(
          title: '',
          showLogo: true,
          bottomSpacing: 0,
        ),
        
        const SizedBox(height: AppDimensions.marginMedium),
        
        // Título com destaque
        RichText(
          textAlign: TextAlign.center,
          text: TextSpan(
            children: [
              TextSpan(
                text: mainTitle,
                style: theme.textTheme.headlineMedium?.copyWith(
                  color: AppColors.gray900,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Poppins',
                ),
              ),
              TextSpan(
                text: ' $highlightedWord',
                style: theme.textTheme.headlineMedium?.copyWith(
                  color: AppColors.primary,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'Poppins',
                ),
              ),
            ],
          ),
        ),
        
        // Subtítulo
        if (subtitle != null) ...[
          const SizedBox(height: AppDimensions.marginSmall),
          Text(
            subtitle!,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyLarge?.copyWith(
              color: AppColors.gray600,
              fontWeight: FontWeight.w400,
              fontFamily: 'Inter',
              height: 1.4,
            ),
          ),
        ],
        
        const SizedBox(height: AppDimensions.marginLarge),
      ],
    );
  }
}