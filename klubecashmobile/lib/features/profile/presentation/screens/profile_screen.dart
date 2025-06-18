// lib/features/profile/presentation/screens/profile_screen.dart
// ARQUIVO #109 - ProfileScreen - Tela principal do perfil do usuário

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/profile_provider.dart';
import '../widgets/profile_header.dart';
import '../widgets/profile_menu_item.dart';
import '../widgets/logout_dialog.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/widgets/loading_indicator.dart';

/// Tela principal do perfil do usuário
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    // Carrega o perfil ao inicializar a tela
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(profileNotifierProvider.notifier).loadProfile();
    });
  }

  @override
  Widget build(BuildContext context) {
    final profileState = ref.watch(profileNotifierProvider);

    return Scaffold(
      backgroundColor: AppColors.backgroundSecondary,
      appBar: CustomAppBar(
        title: 'Meu Perfil',
        actions: [
          IconButton(
            icon: Icon(Icons.refresh),
            onPressed: profileState.isLoading 
                ? null 
                : () => ref.read(profileNotifierProvider.notifier).refreshProfile(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(profileNotifierProvider.notifier).refreshProfile(),
        child: _buildBody(profileState),
      ),
    );
  }

  Widget _buildBody(ProfileState profileState) {
    if (profileState.isLoading && !profileState.hasProfile) {
      return const LoadingIndicator();
    }

    if (profileState.hasError && !profileState.hasProfile) {
      return CustomErrorWidget(
        message: profileState.errorMessage!,
        onRetry: () => ref.read(profileNotifierProvider.notifier).loadProfile(),
      );
    }

    return CustomScrollView(
      slivers: [
        // Header do perfil
        SliverToBoxAdapter(
          child: ProfileHeader(
            onEditPhoto: _handleEditPhoto,
          ),
        ),

        // Menu principal
        SliverToBoxAdapter(
          child: _buildMainMenu(),
        ),

        // Menu de configurações
        SliverToBoxAdapter(
          child: _buildSettingsMenu(),
        ),

        // Menu de suporte
        SliverToBoxAdapter(
          child: _buildSupportMenu(),
        ),

        // Botão de logout
        SliverToBoxAdapter(
          child: _buildLogoutSection(),
        ),

        // Espaçamento inferior
        const SliverToBoxAdapter(
          child: SizedBox(height: AppDimensions.spacingLarge),
        ),
      ],
    );
  }

  Widget _buildMainMenu() {
    return ProfileMenuSection(
      title: 'Conta',
      children: [
        ProfileMenuItem(
          icon: Icons.person_outline,
          title: 'Editar Perfil',
          subtitle: 'Nome, telefone e email alternativo',
          onTap: () => context.push('/profile/edit'),
        ),
        
        ProfileMenuItem(
          icon: Icons.location_on_outlined,
          title: 'Endereço',
          subtitle: 'Gerenciar endereços de entrega',
          onTap: () => context.push('/profile/address'),
        ),
        
        ProfileMenuItem(
          icon: Icons.lock_outline,
          title: 'Alterar Senha',
          subtitle: 'Segurança da sua conta',
          onTap: () => context.push('/profile/change-password'),
        ),
        
        ProfileMenuItem(
          icon: Icons.verified_user_outlined,
          title: 'Verificação',
          subtitle: 'Status de verificação CPF e email',
          onTap: () => _showVerificationDialog(),
          showDivider: false,
        ),
      ],
    );
  }

  Widget _buildSettingsMenu() {
    return ProfileMenuSection(
      title: 'Preferências',
      children: [
        ProfileMenuItem(
          icon: Icons.notifications_outlined,
          title: 'Notificações',
          subtitle: 'Configurar alertas e lembretes',
          onTap: () => context.push('/profile/notifications'),
        ),
        
        ProfileMenuSwitchItem(
          icon: Icons.dark_mode_outlined,
          title: 'Tema Escuro',
          subtitle: 'Alternar entre tema claro e escuro',
          value: false, // Conectar com theme provider
          onChanged: (value) => _handleThemeChange(value),
        ),
        
        ProfileMenuItem(
          icon: Icons.language_outlined,
          title: 'Idioma',
          subtitle: 'Português (Brasil)',
          onTap: () => _showLanguageDialog(),
        ),
        
        ProfileMenuItem(
          icon: Icons.security_outlined,
          title: 'Privacidade e Segurança',
          subtitle: 'Controle seus dados',
          onTap: () => context.push('/profile/privacy'),
          showDivider: false,
        ),
      ],
    );
  }

  Widget _buildSupportMenu() {
    return ProfileMenuSection(
      title: 'Suporte',
      children: [
        ProfileMenuItem(
          icon: Icons.help_outline,
          title: 'Central de Ajuda',
          subtitle: 'FAQ e tutoriais',
          onTap: () => context.push('/help'),
        ),
        
        ProfileMenuItem(
          icon: Icons.chat_bubble_outline,
          title: 'Fale Conosco',
          subtitle: 'Entre em contato com nosso suporte',
          onTap: () => context.push('/contact'),
        ),
        
        ProfileMenuItem(
          icon: Icons.description_outlined,
          title: 'Termos de Uso',
          subtitle: 'Políticas e termos',
          onTap: () => context.push('/terms'),
        ),
        
        ProfileMenuInfoItem(
          icon: Icons.info_outline,
          title: 'Versão do App',
          info: '1.0.0',
          showDivider: false,
        ),
      ],
    );
  }

  Widget _buildLogoutSection() {
    return Padding(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: ProfileMenuItem(
        icon: Icons.logout,
        title: 'Sair da Conta',
        subtitle: 'Fazer logout do aplicativo',
        onTap: () => LogoutDialog.show(context),
        isDangerous: true,
        showDivider: false,
        backgroundColor: Colors.transparent,
      ),
    );
  }

  void _handleEditPhoto() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(AppDimensions.radiusMedium),
        ),
      ),
      builder: (context) => _buildPhotoOptions(),
    );
  }

  Widget _buildPhotoOptions() {
    return SafeArea(
      child: Wrap(
        children: [
          ListTile(
            leading: Icon(Icons.camera_alt, color: AppColors.primary),
            title: Text('Tirar Foto'),
            onTap: () {
              Navigator.pop(context);
              _pickImageFromCamera();
            },
          ),
          ListTile(
            leading: Icon(Icons.photo_library, color: AppColors.primary),
            title: Text('Escolher da Galeria'),
            onTap: () {
              Navigator.pop(context);
              _pickImageFromGallery();
            },
          ),
          ListTile(
            leading: Icon(Icons.delete, color: AppColors.error),
            title: Text('Remover Foto'),
            onTap: () {
              Navigator.pop(context);
              _removePhoto();
            },
          ),
        ],
      ),
    );
  }

  void _pickImageFromCamera() {
    // Implementar captura de foto
  }

  void _pickImageFromGallery() {
    // Implementar seleção da galeria
  }

  void _removePhoto() {
    ref.read(profileNotifierProvider.notifier).removeProfilePicture();
  }

  void _handleThemeChange(bool isDark) {
    // Implementar mudança de tema
  }

  void _showVerificationDialog() {
    final profile = ref.read(currentProfileProvider);
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Status de Verificação'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildVerificationItem(
              'CPF', 
              profile?.isCpfVerified ?? false,
            ),
            _buildVerificationItem(
              'Email', 
              profile?.isEmailVerified ?? false,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Fechar'),
          ),
        ],
      ),
    );
  }

  Widget _buildVerificationItem(String title, bool isVerified) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        children: [
          Icon(
            isVerified ? Icons.check_circle : Icons.cancel,
            color: isVerified ? AppColors.success : AppColors.error,
          ),
          const SizedBox(width: 12),
          Text(title),
          const Spacer(),
          Text(
            isVerified ? 'Verificado' : 'Pendente',
            style: TextStyle(
              color: isVerified ? AppColors.success : AppColors.error,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  void _showLanguageDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Selecionar Idioma'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: Text('Português (Brasil)'),
              value: 'pt_BR',
              groupValue: 'pt_BR',
              onChanged: (value) => Navigator.pop(context),
            ),
            RadioListTile<String>(
              title: Text('Inglês'),
              value: 'en_US',
              groupValue: 'pt_BR',
              onChanged: (value) => Navigator.pop(context),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Cancelar'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Salvar'),
          ),
        ],
      ),
    );
  }
}