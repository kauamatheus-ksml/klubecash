// lib/features/profile/presentation/widgets/profile_form.dart
// ARQUIVO #107 - ProfileForm - Formulário para edição de informações do perfil

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/profile.dart';
import '../providers/profile_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/theme/text_styles.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/utils/formatters.dart';

/// Tipo de formulário de perfil
enum ProfileFormType {
  personalInfo,
  address,
  password,
}

/// Widget de formulário para editar informações do perfil
class ProfileForm extends ConsumerStatefulWidget {
  /// Tipo do formulário
  final ProfileFormType formType;
  
  /// Perfil atual para preencher os campos
  final Profile? profile;
  
  /// Callback de sucesso
  final VoidCallback? onSuccess;
  
  /// Se deve mostrar botão de cancelar
  final bool showCancelButton;
  
  /// Padding personalizado
  final EdgeInsetsGeometry? padding;

  const ProfileForm({
    super.key,
    required this.formType,
    this.profile,
    this.onSuccess,
    this.showCancelButton = false,
    this.padding,
  });

  @override
  ConsumerState<ProfileForm> createState() => _ProfileFormState();
}

class _ProfileFormState extends ConsumerState<ProfileForm> {
  final _formKey = GlobalKey<FormState>();
  final _scrollController = ScrollController();
  
  // Controllers para informações pessoais
  late final TextEditingController _nameController;
  late final TextEditingController _phoneController;
  late final TextEditingController _alternativeEmailController;
  
  // Controllers para endereço
  late final TextEditingController _cepController;
  late final TextEditingController _streetController;
  late final TextEditingController _streetNumberController;
  late final TextEditingController _complementController;
  late final TextEditingController _neighborhoodController;
  late final TextEditingController _cityController;
  late final TextEditingController _stateController;
  
  // Controllers para senha
  late final TextEditingController _currentPasswordController;
  late final TextEditingController _newPasswordController;
  late final TextEditingController _confirmPasswordController;
  
  // Focus nodes
  late final FocusNode _cepFocusNode;
  
  @override
  void initState() {
    super.initState();
    _initializeControllers();
  }

  @override
  void dispose() {
    _disposeControllers();
    _scrollController.dispose();
    _cepFocusNode.dispose();
    super.dispose();
  }

  void _initializeControllers() {
    final profile = widget.profile;
    
    // Informações pessoais
    _nameController = TextEditingController(text: profile?.name ?? '');
    _phoneController = TextEditingController(text: profile?.phone ?? '');
    _alternativeEmailController = TextEditingController(text: profile?.alternativeEmail ?? '');
    
    // Endereço
    _cepController = TextEditingController(text: profile?.address?.cep ?? '');
    _streetController = TextEditingController(text: profile?.address?.street ?? '');
    _streetNumberController = TextEditingController(text: profile?.address?.streetNumber ?? '');
    _complementController = TextEditingController(text: profile?.address?.complement ?? '');
    _neighborhoodController = TextEditingController(text: profile?.address?.neighborhood ?? '');
    _cityController = TextEditingController(text: profile?.address?.city ?? '');
    _stateController = TextEditingController(text: profile?.address?.state ?? '');
    
    // Senha
    _currentPasswordController = TextEditingController();
    _newPasswordController = TextEditingController();
    _confirmPasswordController = TextEditingController();
    
    // Focus nodes
    _cepFocusNode = FocusNode();
    
    // Listener para busca automática de CEP
    _cepController.addListener(_onCepChanged);
  }

  void _disposeControllers() {
    _nameController.dispose();
    _phoneController.dispose();
    _alternativeEmailController.dispose();
    _cepController.dispose();
    _streetController.dispose();
    _streetNumberController.dispose();
    _complementController.dispose();
    _neighborhoodController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    _cepFocusNode.dispose();
  }

  void _onCepChanged() {
    final cep = _cepController.text.replaceAll(RegExp(r'[^0-9]'), '');
    if (cep.length == 8) {
      _searchCep(cep);
    }
  }

  Future<void> _searchCep(String cep) async {
    // Aqui você implementaria a busca de CEP via API
    // Exemplo básico de preenchimento automático
    try {
      // Simular chamada de API
      await Future.delayed(const Duration(milliseconds: 500));
      
      // Em um caso real, você faria uma chamada para a API dos Correios
      // e preencheria os campos automaticamente
      if (mounted) {
        // Exemplo de dados fictícios
        _streetController.text = 'Rua Exemplo';
        _neighborhoodController.text = 'Centro';
        _cityController.text = 'São Paulo';
        _stateController.text = 'SP';
      }
    } catch (e) {
      // Tratar erro de busca de CEP
    }
  }

  @override
  Widget build(BuildContext context) {
    final profileState = ref.watch(profileNotifierProvider);
    
    return Container(
      padding: widget.padding ?? const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          controller: _scrollController,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildFormFields(),
              
              const SizedBox(height: AppDimensions.spacingLarge),
              
              _buildActionButtons(profileState),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFormFields() {
    switch (widget.formType) {
      case ProfileFormType.personalInfo:
        return _buildPersonalInfoFields();
      case ProfileFormType.address:
        return _buildAddressFields();
      case ProfileFormType.password:
        return _buildPasswordFields();
    }
  }

  Widget _buildPersonalInfoFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Informações Pessoais',
          style: AppTextStyles.h5.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _nameController,
          label: 'Nome Completo',
          hint: 'Digite seu nome completo',
          prefixIcon: Icons.person_outline,
          validator: AppValidators.validateName,
          isRequired: true,
          textInputAction: TextInputAction.next,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _phoneController,
          label: 'Telefone',
          hint: '(00) 00000-0000',
          prefixIcon: Icons.phone_outlined,
          keyboardType: TextInputType.phone,
          inputFormatters: [AppFormatters.cellPhoneFormatter],
          validator: AppValidators.validatePhone,
          textInputAction: TextInputAction.next,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _alternativeEmailController,
          label: 'Email Alternativo',
          hint: 'seuemail@exemplo.com',
          prefixIcon: Icons.email_outlined,
          keyboardType: TextInputType.emailAddress,
          validator: (value) => AppValidators.validateEmailOptional(value),
          textInputAction: TextInputAction.done,
        ),
      ],
    );
  }

  Widget _buildAddressFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Endereço',
          style: AppTextStyles.h5.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _cepController,
          focusNode: _cepFocusNode,
          label: 'CEP',
          hint: '00000-000',
          prefixIcon: Icons.location_on_outlined,
          keyboardType: TextInputType.number,
          inputFormatters: [AppFormatters.cepFormatter],
          validator: AppValidators.validateCep,
          textInputAction: TextInputAction.next,
          helperText: 'Digite o CEP para preenchimento automático',
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        Row(
          children: [
            Expanded(
              flex: 2,
              child: CustomTextField(
                controller: _streetController,
                label: 'Logradouro',
                hint: 'Rua, Avenida, etc.',
                validator: AppValidators.validateRequired,
                textInputAction: TextInputAction.next,
                isRequired: true,
              ),
            ),
            
            const SizedBox(width: AppDimensions.spacingMedium),
            
            Expanded(
              child: CustomTextField(
                controller: _streetNumberController,
                label: 'Número',
                hint: '123',
                keyboardType: TextInputType.number,
                validator: AppValidators.validateRequired,
                textInputAction: TextInputAction.next,
                isRequired: true,
              ),
            ),
          ],
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _complementController,
          label: 'Complemento',
          hint: 'Apartamento, Bloco, etc.',
          textInputAction: TextInputAction.next,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _neighborhoodController,
          label: 'Bairro',
          hint: 'Digite o bairro',
          validator: AppValidators.validateRequired,
          textInputAction: TextInputAction.next,
          isRequired: true,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        Row(
          children: [
            Expanded(
              flex: 2,
              child: CustomTextField(
                controller: _cityController,
                label: 'Cidade',
                hint: 'Digite a cidade',
                validator: AppValidators.validateRequired,
                textInputAction: TextInputAction.next,
                isRequired: true,
              ),
            ),
            
            const SizedBox(width: AppDimensions.spacingMedium),
            
            Expanded(
              child: CustomTextField(
                controller: _stateController,
                label: 'UF',
                hint: 'SP',
                textCapitalization: TextCapitalization.characters,
                maxLength: 2,
                validator: AppValidators.validateState,
                textInputAction: TextInputAction.done,
                isRequired: true,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildPasswordFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Alteração de Senha',
          style: AppTextStyles.h5.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _currentPasswordController,
          label: 'Senha Atual',
          hint: 'Digite sua senha atual',
          prefixIcon: Icons.lock_outline,
          type: TextFieldType.password,
          validator: AppValidators.validateRequired,
          textInputAction: TextInputAction.next,
          isRequired: true,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _newPasswordController,
          label: 'Nova Senha',
          hint: 'Digite a nova senha',
          prefixIcon: Icons.lock_outline,
          type: TextFieldType.password,
          validator: AppValidators.validatePassword,
          textInputAction: TextInputAction.next,
          isRequired: true,
          helperText: 'Mínimo de 8 caracteres',
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        CustomTextField(
          controller: _confirmPasswordController,
          label: 'Confirmar Nova Senha',
          hint: 'Digite novamente a nova senha',
          prefixIcon: Icons.lock_outline,
          type: TextFieldType.password,
          validator: (value) => AppValidators.validatePasswordConfirmation(
            value,
            _newPasswordController.text,
          ),
          textInputAction: TextInputAction.done,
          isRequired: true,
        ),
      ],
    );
  }

  Widget _buildActionButtons(ProfileState profileState) {
    final isLoading = widget.formType == ProfileFormType.password
        ? profileState.isChangingPassword
        : profileState.isUpdating;

    return Column(
      children: [
        CustomButton(
          text: _getSubmitButtonText(),
          onPressed: isLoading ? null : _handleSubmit,
          isLoading: isLoading,
          type: ButtonType.primary,
        ),
        
        if (widget.showCancelButton) ...[
          const SizedBox(height: AppDimensions.spacingMedium),
          CustomButton(
            text: 'Cancelar',
            onPressed: () => Navigator.of(context).pop(),
            type: ButtonType.outline,
          ),
        ],
      ],
    );
  }

  String _getSubmitButtonText() {
    switch (widget.formType) {
      case ProfileFormType.personalInfo:
        return 'Salvar Informações';
      case ProfileFormType.address:
        return 'Salvar Endereço';
      case ProfileFormType.password:
        return 'Alterar Senha';
    }
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final notifier = ref.read(profileNotifierProvider.notifier);
    bool success = false;

    switch (widget.formType) {
      case ProfileFormType.personalInfo:
        success = await notifier.updatePersonalInfo(
          name: _nameController.text.trim(),
          phone: _phoneController.text.trim().isEmpty 
              ? null 
              : _phoneController.text.trim(),
          alternativeEmail: _alternativeEmailController.text.trim().isEmpty 
              ? null 
              : _alternativeEmailController.text.trim(),
        );
        break;
        
      case ProfileFormType.address:
        final address = ProfileAddress(
          cep: _cepController.text.trim(),
          street: _streetController.text.trim(),
          streetNumber: _streetNumberController.text.trim(),
          complement: _complementController.text.trim().isEmpty 
              ? null 
              : _complementController.text.trim(),
          neighborhood: _neighborhoodController.text.trim(),
          city: _cityController.text.trim(),
          state: _stateController.text.trim().toUpperCase(),
        );
        success = await notifier.updateAddress(address: address);
        break;
        
      case ProfileFormType.password:
        success = await notifier.changePassword(
          currentPassword: _currentPasswordController.text,
          newPassword: _newPasswordController.text,
        );
        if (success) {
          _currentPasswordController.clear();
          _newPasswordController.clear();
          _confirmPasswordController.clear();
        }
        break;
    }

    if (success && widget.onSuccess != null) {
      widget.onSuccess!();
    }
  }
}

/// Classe para representar endereço (se não existir na entidade)
class ProfileAddress {
  final String cep;
  final String street;
  final String streetNumber;
  final String? complement;
  final String neighborhood;
  final String city;
  final String state;

  const ProfileAddress({
    required this.cep,
    required this.street,
    required this.streetNumber,
    this.complement,
    required this.neighborhood,
    required this.city,
    required this.state,
  });
}