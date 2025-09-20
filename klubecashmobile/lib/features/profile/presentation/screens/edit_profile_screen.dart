// lib/features/profile/presentation/screens/edit_profile_screen.dart
// ARQUIVO #110 - EditProfileScreen - Tela de edição das informações pessoais

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/profile_provider.dart';
import '../widgets/profile_form.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/loading_indicator.dart';

/// Tela de edição das informações pessoais do perfil
class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  @override
  void initState() {
    super.initState();
    // Limpa mensagens ao entrar na tela
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(profileNotifierProvider.notifier).clearMessages();
    });
  }

  @override
  Widget build(BuildContext context) {
    final profileState = ref.watch(profileNotifierProvider);

    // Escuta mudanças de sucesso para mostrar feedback
    ref.listen<ProfileState>(profileNotifierProvider, (previous, current) {
      if (current.hasSuccess && !current.isUpdating) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(current.successMessage!),
            backgroundColor: AppColors.success,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(profileNotifierProvider.notifier).clearSuccess();
      }

      if (current.hasError && !current.isUpdating) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(current.errorMessage!),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(profileNotifierProvider.notifier).clearError();
      }
    });

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: CustomAppBar(
        title: 'Editar Perfil',
        centerTitle: true,
        leading: IconButton(
          icon: Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: profileState.isLoading && !profileState.hasProfile
          ? const LoadingIndicator()
          : SingleChildScrollView(
              padding: const EdgeInsets.all(AppDimensions.paddingMedium),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Informações da seção
                  _buildSectionHeader(),
                  
                  const SizedBox(height: AppDimensions.spacingLarge),
                  
                  // Formulário de informações pessoais
                  Container(
                    decoration: BoxDecoration(
                      color: AppColors.backgroundSecondary,
                      borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
                    ),
                    child: ProfileForm(
                      formType: ProfileFormType.personalInfo,
                      profile: profileState.profile,
                      onSuccess: () => _handleSuccess(),
                      showCancelButton: true,
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildSectionHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Informações Pessoais',
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        
        const SizedBox(height: AppDimensions.spacingSmall),
        
        Text(
          'Atualize suas informações de contato e dados pessoais. Essas informações são importantes para melhorar sua experiência no app.',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }

  void _handleSuccess() {
    // O feedback de sucesso é tratado pelo listener
    // Aqui podemos adicionar ações adicionais se necessário
  }
}