-- Adicionar campo Senat na tabela usuarios
-- Este campo identificará usuários especiais SEST SENAT

ALTER TABLE usuarios
ADD COLUMN senat ENUM('Sim', 'Não') DEFAULT 'Não' AFTER tipo;

-- Criar índice para otimizar consultas por usuários SEST SENAT
CREATE INDEX idx_usuarios_senat ON usuarios(senat);

-- Comentário para documentação
-- Campo senat: 'Sim' para usuários SEST SENAT com tema personalizado, 'Não' para usuários normais