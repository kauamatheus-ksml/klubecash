// loading_indicator.dart - Widget de indicador de carregamento reutilizável para o app Klube Cash
// Arquivo: lib/core/widgets/loading_indicator.dart

import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';

/// Widget de indicador de carregamento customizável
/// 
/// Usado para exibir estados de carregamento em toda a aplicação.
/// Suporta diferentes tamanhos, cores e tipos de exibição.
class LoadingIndicator extends StatelessWidget {
  /// Tamanho do indicador de carregamento
  final double size;
  
  /// Cor do indicador (padrão: cor primária do app)
  final Color? color;
  
  /// Espessura da linha do indicador
  final double strokeWidth;
  
  /// Texto opcional para exibir abaixo do indicador
  final String? message;
  
  /// Estilo do texto da mensagem
  final TextStyle? messageStyle;
  
  /// Se deve exibir um fundo semi-transparente
  final bool showBackground;
  
  /// Cor do fundo (usado quando showBackground = true)
  final Color? backgroundColor;
  
  /// Tipo do indicador de carregamento
  final LoadingIndicatorType type;

  const LoadingIndicator({
    super.key,
    this.size = 40.0,
    this.color,
    this.strokeWidth = 4.0,
    this.message,
    this.messageStyle,
    this.showBackground = false,
    this.backgroundColor,
    this.type = LoadingIndicatorType.circular,
  });

  /// Construtor para indicador pequeno (usado em botões)
  const LoadingIndicator.small({
    super.key,
    this.color,
    this.message,
    this.messageStyle,
    this.showBackground = false,
    this.backgroundColor,
    this.type = LoadingIndicatorType.circular,
  })  : size = 20.0,
        strokeWidth = 2.0;

  /// Construtor para indicador médio (padrão)
  const LoadingIndicator.medium({
    super.key,
    this.color,
    this.message,
    this.messageStyle,
    this.showBackground = false,
    this.backgroundColor,
    this.type = LoadingIndicatorType.circular,
  })  : size = 40.0,
        strokeWidth = 4.0;

  /// Construtor para indicador grande (tela cheia)
  const LoadingIndicator.large({
    super.key,
    this.color,
    this.message,
    this.messageStyle,
    this.showBackground = true,
    this.backgroundColor,
    this.type = LoadingIndicatorType.circular,
  })  : size = 60.0,
        strokeWidth = 6.0;

  /// Construtor para overlay de carregamento
  const LoadingIndicator.overlay({
    super.key,
    this.message = 'Carregando...',
    this.messageStyle,
    this.color,
    this.type = LoadingIndicatorType.circular,
  })  : size = 50.0,
        strokeWidth = 4.0,
        showBackground = true,
        backgroundColor = AppColors.backgroundOverlay;

  @override
  Widget build(BuildContext context) {
    final Widget indicator = _buildIndicator(context);
    
    if (showBackground) {
      return Container(
        color: backgroundColor ?? AppColors.backgroundOverlay,
        child: Center(
          child: _buildContent(context, indicator),
        ),
      );
    }
    
    return _buildContent(context, indicator);
  }

  Widget _buildIndicator(BuildContext context) {
    final indicatorColor = color ?? AppColors.primary;
    
    switch (type) {
      case LoadingIndicatorType.circular:
        return SizedBox(
          width: size,
          height: size,
          child: CircularProgressIndicator(
            strokeWidth: strokeWidth,
            color: indicatorColor,
          ),
        );
        
      case LoadingIndicatorType.linear:
        return SizedBox(
          width: size * 2,
          child: LinearProgressIndicator(
            color: indicatorColor,
            backgroundColor: indicatorColor.withOpacity(0.2),
          ),
        );
        
      case LoadingIndicatorType.dots:
        return _buildDotsIndicator(indicatorColor);
        
      case LoadingIndicatorType.pulse:
        return _buildPulseIndicator(indicatorColor);
    }
  }

  Widget _buildContent(BuildContext context, Widget indicator) {
    if (message == null) {
      return indicator;
    }
    
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        indicator,
        const SizedBox(height: AppDimensions.marginMedium),
        Text(
          message!,
          style: messageStyle ??
              Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: color ?? AppColors.textSecondary,
                fontWeight: FontWeight.w500,
              ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildDotsIndicator(Color indicatorColor) {
    return SizedBox(
      width: size,
      height: size / 3,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: List.generate(3, (index) {
          return AnimatedContainer(
            duration: Duration(milliseconds: 600 + (index * 200)),
            width: size / 6,
            height: size / 6,
            decoration: BoxDecoration(
              color: indicatorColor,
              shape: BoxShape.circle,
            ),
          );
        }),
      ),
    );
  }

  Widget _buildPulseIndicator(Color indicatorColor) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: indicatorColor.withOpacity(0.3),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Container(
          width: size * 0.6,
          height: size * 0.6,
          decoration: BoxDecoration(
            color: indicatorColor,
            shape: BoxShape.circle,
          ),
        ),
      ),
    );
  }
}

/// Tipos de indicador de carregamento disponíveis
enum LoadingIndicatorType {
  /// Indicador circular padrão
  circular,
  
  /// Indicador linear/barra de progresso
  linear,
  
  /// Indicador com pontos animados
  dots,
  
  /// Indicador com efeito pulse
  pulse,
}

/// Widget para sobreposição de carregamento
class LoadingOverlay extends StatelessWidget {
  /// Widget filho que será sobreposto
  final Widget child;
  
  /// Se está carregando
  final bool isLoading;
  
  /// Mensagem de carregamento
  final String? message;
  
  /// Cor do indicador
  final Color? color;

  const LoadingOverlay({
    super.key,
    required this.child,
    required this.isLoading,
    this.message,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        child,
        if (isLoading)
          LoadingIndicator.overlay(
            message: message,
            color: color,
          ),
      ],
    );
  }
}

/// Extensão para facilitar o uso em qualquer widget
extension LoadingExtension on Widget {
  /// Adiciona overlay de carregamento ao widget
  Widget withLoading({
    required bool isLoading,
    String? message,
    Color? color,
  }) {
    return LoadingOverlay(
      isLoading: isLoading,
      message: message,
      color: color,
      child: this,
    );
  }
}