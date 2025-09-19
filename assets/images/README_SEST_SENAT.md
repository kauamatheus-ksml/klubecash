# Logo SEST SENAT

Para completar a implementação da funcionalidade SEST SENAT, é necessário adicionar o arquivo de logo:

**Arquivo necessário:**
- `sest-senat-logo.png` - Logo oficial do SEST SENAT

**Especificações recomendadas:**
- Formato: PNG com fundo transparente
- Tamanho: 40x40 pixels (para display normal)
- Resolução: 2x (80x80 pixels) para telas de alta resolução
- Cores: Azul institucional (#1E3A8A) conforme o tema personalizado

**Localização:**
O arquivo deve ser colocado em: `assets/images/sest-senat-logo.png`

**Como funciona:**
- Usuários com campo `senat = 'Sim'` na tabela `usuarios` verão este logo ao lado do logo KlubeCash
- O tema será aplicado automaticamente com as cores institucionais do SEST SENAT
- Apenas a logo muda, mantendo a funcionalidade completa do KlubeCash

**Para testar:**
1. Execute o SQL em `database/add_senat_field.sql`
2. Atualize um usuário lojista: `UPDATE usuarios SET senat = 'Sim' WHERE id = [ID_DO_USUARIO]`
3. Faça login com esse usuário para ver o tema personalizado