// lib/features/profile/presentation/widgets/profile_header.dart
// ARQUIVO #105 - ProfileHeader - Cabeçalho do perfil do usuário com avatar e informações

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/profile.dart';
import '../providers/profile_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/theme/text_styles.dart';
import '../../../../core/utils/formatters.dart';

/// Widget de cabeçalho do perfil do usuário
/// 
/// Exibe avatar, informações básicas e completude do perfil
class ProfileHeader extends ConsumerWidget {
  /// Se deve exibir a barra de progresso
  final bool showProgress;
  
  /// Se deve permitir edição da foto
  final bool allowPhotoEdit;
  
  /// Callback para editar foto
  final VoidCallback? onEditPhoto;
  
  /// Padding personalizado
  final EdgeInsetsGeometry? padding;

  const ProfileHeader({
    super.key,
    this.showProgress = true,
    this.allowPhotoEdit = true,
    this.onEditPhoto,
    this.padding,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileState = ref.watch(profileNotifierProvider);
    final profile = profileState.profile;
    final completeness = ref.watch(profileCompletenessProvider);

    if (profile == null) {
      return _buildLoadingHeader(context);
    }

    return Container(
      width: double.infinity,
      padding: padding ?? const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: const BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(AppDimensions.borderRadiusLarge),
          bottomRight: Radius.circular(AppDimensions.borderRadiusLarge),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Avatar e informações principais
            _buildUserInfo(context, profile),
            
            if (showProgress) ...[
              const SizedBox(height: AppDimensions.marginLarge),
              _buildProgressSection(context, completeness),
            ],
          ],
        ),
      ),
    );
  }

  /// Constrói as informações do usuário com avatar
  Widget _buildUserInfo(BuildContext context, Profile profile) {
    return Row(
      children: [
        // Avatar
        _buildAvatar(context, profile),
        
        const SizedBox(width: AppDimensions.marginMedium),
        
        // Informações do usuário
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Nome
              Text(
                profile.name,
                style: AppTextStyles.h3.copyWith(
                  color: AppColors.textOnPrimary,
                  fontWeight: FontWeight.bold,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              
              const SizedBox(height: AppDimensions.spacingXSmall),
              
              // Email
              Text(
                profile.email,
                style: AppTextStyles.bodyMedium.copyWith(
                  color: AppColors.textOnPrimary.withOpacity(0.9),
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              
              // CPF se disponível
              if (profile.cpf.isNotEmpty) ...[
                const SizedBox(height: AppDimensions.spacingXSmall),
                Row(
                  children: [
                    Icon(
                      Icons.badge_outlined,
                      size: 14,
                      color: AppColors.textOnPrimary.withOpacity(0.8),
                    ),
                    const SizedBox(width: AppDimensions.spacingXSmall),
                    Text(
                      'CPF: ${AppFormatters.formatCpf(profile.cpf)}',
                      style: AppTextStyles.bodySmall.copyWith(
                        color: AppColors.textOnPrimary.withOpacity(0.8),
                      ),
                    ),
                    
                    // Indicador de verificação
                    if (profile.isCpfVerified) ...[
                      const SizedBox(width: AppDimensions.spacingXSmall),
                      Icon(
                        Icons.verified,
                        size: 16,
                        color: AppColors.success,
                      ),
                    ],
                  ],
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  /// Constrói o avatar do usuário
  Widget _buildAvatar(BuildContext context, Profile profile) {
    return Stack(
      children: [
        // Avatar principal
        Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(
              color: AppColors.textOnPrimary.withOpacity(0.3),
              width: 2,
            ),
          ),
          child: CircleAvatar(
            radius: 38,
            backgroundColor: AppColors.textOnPrimary.withOpacity(0.2),
            backgroundImage: profile.profilePictureUrl != null
                ? NetworkImage(profile.profilePictureUrl!)
                : null,
            child: profile.profilePictureUrl == null
                ? Text(
                    profile.initials,
                    style: AppTextStyles.h2.copyWith(
                      color: AppColors.textOnPrimary,
                      fontWeight: FontWeight.bold,
                    ),
                  )
                : null,
          ),
        ),
        
        // Botão de editar foto
        if (allowPhotoEdit)
          Positioned(
            bottom: 0,
            right: 0,
            child: GestureDetector(
              onTap: onEditPhoto,
              child: Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(
                  color: AppColors.textOnPrimary,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: AppColors.primary,
                    width: 2,
                  ),
                ),
                child: Icon(
                  Icons.camera_alt,
                  size: 14,
                  color: AppColors.primary,
                ),
              ),
            ),
          ),
      ],
    );
  }

  /// Constrói a seção de progresso do perfil
  Widget _buildProgressSection(BuildContext context, double completeness) {
    final progressPercent = (completeness / 100).clamp(0.0, 1.0);
    final isComplete = completeness >= 100;
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.textOnPrimary.withOpacity(0.15),
        borderRadius: BorderRadius.circular(AppDimensions.borderRadiusMedium),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header do progresso
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(
                    isComplete ? Icons.check_circle : Icons.account_circle,
                    color: isComplete ? AppColors.success : AppColors.textOnPrimary,
                    size: 20,
                  ),
                  const SizedBox(width: AppDimensions.spacingSmall),
                  Text(
                    'Completude do Perfil',
                    style: AppTextStyles.bodyMedium.copyWith(
                      color: AppColors.textOnPrimary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              Text(
                '${completeness.toInt()}%',
                style: AppTextStyles.h4.copyWith(
                  color: AppColors.textOnPrimary,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          
          const SizedBox(height: AppDimensions.spacingMedium),
          
          // Barra de progresso
          Container(
            width: double.infinity,
            height: 8,
            decoration: BoxDecoration(
              color: AppColors.textOnPrimary.withOpacity(0.3),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Stack(
              children: [
                // Progresso preenchido
                AnimatedContainer(
                  duration: const Duration(milliseconds: 800),
                  curve: Curves.easeOutCubic,
                  width: MediaQuery.of(context).size.width * progressPercent,
                  height: 8,
                  decoration: BoxDecoration(
                    color: isComplete ? AppColors.success : AppColors.textOnPrimary,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: AppDimensions.spacingSmall),
          
          // Mensagem do progresso
          Text(
            _getProgressMessage(completeness),
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textOnPrimary.withOpacity(0.9),
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói o header de carregamento
  Widget _buildLoadingHeader(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 180,
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: const BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(AppDimensions.borderRadiusLarge),
          bottomRight: Radius.circular(AppDimensions.borderRadiusLarge),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Row(
          children: [
            // Avatar skeleton
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.textOnPrimary.withOpacity(0.3),
                shape: BoxShape.circle,
              ),
            ),
            
            const SizedBox(width: AppDimensions.marginMedium),
            
            // Text skeletons
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: double.infinity,
                    height: 20,
                    decoration: BoxDecoration(
                      color: AppColors.textOnPrimary.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                  const SizedBox(height: AppDimensions.spacingSmall),
                  Container(
                    width: MediaQuery.of(context).size.width * 0.6,
                    height: 16,
                    decoration: BoxDecoration(
                      color: AppColors.textOnPrimary.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Retorna mensagem baseada na completude do perfil
  String _getProgressMessage(double completeness) {
    if (completeness >= 100) {
      return 'Parabéns! Seu perfil está completo';
    } else if (completeness >= 80) {
      return 'Quase lá! Faltam poucos detalhes';
    } else if (completeness >= 50) {
      return 'Bom progresso! Continue preenchendo';
    } else {
      return 'Complete seu perfil para melhor experiência';
    }
  }
}

/// Extensão para obter iniciais do perfil
extension ProfileInitials on Profile {
  String get initials {
    final nameParts = name.trim().split(' ');
    if (nameParts.isEmpty) return 'U';
    
    if (nameParts.length == 1) {
      return nameParts.first.substring(0, 1).toUpperCase();
    }
    
    return (nameParts.first.substring(0, 1) + 
            nameParts.last.substring(0, 1)).toUpperCase();
  }
}