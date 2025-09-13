-- Script para adicionar configurações personalizadas de cashback por loja
-- Execução: mysql -u username -p database_name < add_cashback_config.sql

-- Adicionar novos campos na tabela lojas
ALTER TABLE lojas 
ADD COLUMN porcentagem_cliente DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Percentual de cashback para o cliente (%)',
ADD COLUMN porcentagem_admin DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Percentual de comissão para o admin/plataforma (%)',
ADD COLUMN cashback_ativo TINYINT(1) DEFAULT 1 COMMENT 'Se a loja oferece cashback (0=inativo, 1=ativo)',
ADD COLUMN data_config_cashback TIMESTAMP NULL DEFAULT NULL COMMENT 'Data da última configuração de cashback';

-- Atualizar registros existentes baseando-se no campo porcentagem_cashback atual
UPDATE lojas 
SET 
    porcentagem_cliente = CASE 
        WHEN porcentagem_cashback > 0 THEN porcentagem_cashback / 2 
        ELSE 5.00 
    END,
    porcentagem_admin = CASE 
        WHEN porcentagem_cashback > 0 THEN porcentagem_cashback / 2 
        ELSE 5.00 
    END,
    cashback_ativo = CASE 
        WHEN porcentagem_cashback > 0 THEN 1 
        ELSE 1 
    END,
    data_config_cashback = NOW()
WHERE id > 0;

-- Criar índice para consultas por configuração de cashback
CREATE INDEX idx_lojas_cashback_config ON lojas (cashback_ativo, porcentagem_cliente, porcentagem_admin);

-- Comentários sobre o campo antigo (manter por compatibilidade)
ALTER TABLE lojas MODIFY COLUMN porcentagem_cashback DECIMAL(5,2) COMMENT 'Campo legado - usar porcentagem_cliente + porcentagem_admin';-