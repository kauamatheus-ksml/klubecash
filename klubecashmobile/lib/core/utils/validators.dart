// lib/core/utils/validators.dart
// Arquivo de validações para formulários do Klube Cash
// Contém validações para CPF, email, senha, telefone e outros campos comuns

class Validators {
  /// Valida endereço de email
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateEmail(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Email é obrigatório';
    }
    
    final emailRegExp = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    
    if (!emailRegExp.hasMatch(value.trim())) {
      return 'Email inválido';
    }
    
    return null;
  }

  /// Valida CPF brasileiro
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateCPF(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'CPF é obrigatório';
    }
    
    // Remove caracteres não numéricos
    final cpf = value.replaceAll(RegExp(r'\D'), '');
    
    // Verifica se tem 11 dígitos
    if (cpf.length != 11) {
      return 'CPF deve ter 11 dígitos';
    }
    
    // Verifica se todos os dígitos são iguais (CPF inválido)
    if (RegExp(r'^(\d)\1{10}$').hasMatch(cpf)) {
      return 'CPF inválido';
    }
    
    // Validação do primeiro dígito verificador
    int soma = 0;
    for (int i = 0; i < 9; i++) {
      soma += int.parse(cpf[i]) * (10 - i);
    }
    int resto = soma % 11;
    int digito1 = resto < 2 ? 0 : 11 - resto;
    
    if (int.parse(cpf[9]) != digito1) {
      return 'CPF inválido';
    }
    
    // Validação do segundo dígito verificador
    soma = 0;
    for (int i = 0; i < 10; i++) {
      soma += int.parse(cpf[i]) * (11 - i);
    }
    resto = soma % 11;
    int digito2 = resto < 2 ? 0 : 11 - resto;
    
    if (int.parse(cpf[10]) != digito2) {
      return 'CPF inválido';
    }
    
    return null;
  }

  /// Valida senha
  /// Retorna null se válida, mensagem de erro se inválida
  static String? validatePassword(String? value, {int minLength = 8}) {
    if (value == null || value.isEmpty) {
      return 'Senha é obrigatória';
    }
    
    if (value.length < minLength) {
      return 'Senha deve ter pelo menos $minLength caracteres';
    }
    
    return null;
  }

  /// Valida confirmação de senha
  /// Retorna null se válida, mensagem de erro se inválida
  static String? validatePasswordConfirmation(
    String? value,
    String? originalPassword,
  ) {
    if (value == null || value.isEmpty) {
      return 'Confirmação de senha é obrigatória';
    }
    
    if (value != originalPassword) {
      return 'Senhas não coincidem';
    }
    
    return null;
  }

  /// Valida número de telefone brasileiro
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validatePhone(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Telefone é obrigatório';
    }
    
    // Remove caracteres não numéricos
    final phone = value.replaceAll(RegExp(r'\D'), '');
    
    // Verifica se tem pelo menos 10 dígitos (DDD + número)
    if (phone.length < 10 || phone.length > 11) {
      return 'Telefone inválido';
    }
    
    // Verifica se o DDD é válido (começando com números válidos)
    final ddd = phone.substring(0, 2);
    final validDDDs = [
      '11', '12', '13', '14', '15', '16', '17', '18', '19', // SP
      '21', '22', '24', // RJ
      '27', '28', // ES
      '31', '32', '33', '34', '35', '37', '38', // MG
      '41', '42', '43', '44', '45', '46', // PR
      '47', '48', '49', // SC
      '51', '53', '54', '55', // RS
      '61', // DF
      '62', '64', // GO
      '63', // TO
      '65', '66', // MT
      '67', // MS
      '68', // AC
      '69', // RO
      '71', '73', '74', '75', '77', // BA
      '79', // SE
      '81', '87', // PE
      '82', // AL
      '83', // PB
      '84', // RN
      '85', '88', // CE
      '86', '89', // PI
      '91', '93', '94', // PA
      '92', '97', // AM
      '95', // RR
      '96', // AP
      '98', '99', // MA
    ];
    
    if (!validDDDs.contains(ddd)) {
      return 'DDD inválido';
    }
    
    return null;
  }

  /// Valida nome completo
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateName(String? value, {int minLength = 3}) {
    if (value == null || value.trim().isEmpty) {
      return 'Nome é obrigatório';
    }
    
    final name = value.trim();
    
    if (name.length < minLength) {
      return 'Nome deve ter pelo menos $minLength caracteres';
    }
    
    // Verifica se contém pelo menos um espaço (nome e sobrenome)
    if (!name.contains(' ')) {
      return 'Informe nome e sobrenome';
    }
    
    return null;
  }

  /// Valida campo obrigatório genérico
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateRequired(String? value, String fieldName) {
    if (value == null || value.trim().isEmpty) {
      return '$fieldName é obrigatório';
    }
    return null;
  }

  /// Valida valor monetário
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateAmount(String? value, {double minValue = 0.01}) {
    if (value == null || value.trim().isEmpty) {
      return 'Valor é obrigatório';
    }
    
    // Remove formatação monetária
    final cleanValue = value.replaceAll(RegExp(r'[^\d,.]'), '');
    final doubleValue = double.tryParse(cleanValue.replaceAll(',', '.'));
    
    if (doubleValue == null) {
      return 'Valor inválido';
    }
    
    if (doubleValue < minValue) {
      return 'Valor mínimo: R\$ ${minValue.toStringAsFixed(2)}';
    }
    
    return null;
  }

  /// Verifica força da senha
  /// Retorna um score de 0 a 4 e uma descrição
  static Map<String, dynamic> checkPasswordStrength(String password) {
    int score = 0;
    List<String> feedback = [];
    
    if (password.length >= 8) {
      score++;
    } else {
      feedback.add('Mínimo 8 caracteres');
    }
    
    if (password.contains(RegExp(r'[a-z]'))) {
      score++;
    } else {
      feedback.add('Adicione letras minúsculas');
    }
    
    if (password.contains(RegExp(r'[A-Z]'))) {
      score++;
    } else {
      feedback.add('Adicione letras maiúsculas');
    }
    
    if (password.contains(RegExp(r'[0-9]'))) {
      score++;
    } else {
      feedback.add('Adicione números');
    }
    
    if (password.contains(RegExp(r'[!@#$%^&*(),.?":{}|<>]'))) {
      score++;
    } else {
      feedback.add('Adicione símbolos');
    }
    
    String strength;
    switch (score) {
      case 0:
      case 1:
        strength = 'Muito fraca';
        break;
      case 2:
        strength = 'Fraca';
        break;
      case 3:
        strength = 'Média';
        break;
      case 4:
        strength = 'Forte';
        break;
      case 5:
        strength = 'Muito forte';
        break;
      default:
        strength = 'Desconhecida';
    }
    
    return {
      'score': score,
      'strength': strength,
      'feedback': feedback,
    };
  }

  /// Valida CEP brasileiro
  /// Retorna null se válido, mensagem de erro se inválido
  static String? validateCEP(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'CEP é obrigatório';
    }
    
    // Remove caracteres não numéricos
    final cep = value.replaceAll(RegExp(r'\D'), '');
    
    if (cep.length != 8) {
      return 'CEP deve ter 8 dígitos';
    }
    
    return null;
  }

  /// Combina múltiplos validadores
  /// Retorna a primeira mensagem de erro encontrada ou null se tudo válido
  static String? combineValidators(List<String? Function()> validators) {
    for (final validator in validators) {
      final result = validator();
      if (result != null) return result;
    }
    return null;
  }
}