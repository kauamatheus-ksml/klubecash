-- Adicionar campo senat à tabela usuarios
-- Este campo será usado para determinar qual CSS carregar no dashboard de lojistas

ALTER TABLE usuarios
ADD COLUMN senat ENUM('sim', 'nao') DEFAULT 'nao' NOT NULL
AFTER tipo;

-- Comentário:
-- - senat = 'sim': usuário logado como senat, deve usar CSS com sufixo _sest.css
-- - senat = 'nao': usuário normal, deve usar CSS padrão