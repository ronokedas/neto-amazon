-- Migration 018: Corrigir ENUM corrompido de certificados_lp
-- Problema: acentos exibidos como ????
-- Solução: alterar ENUM para valores sem acento

-- Primeiro, atualizar dados existentes
UPDATE certificados_lp SET tipo_licenca = 'lcec' WHERE tipo_licenca = 'lcec';

-- Alterar ENUM para valores sem acento
ALTER TABLE certificados_lp 
    MODIFY COLUMN tipo_licenca ENUM('construcao','alteracao','reclassificacao','lcec') NOT NULL DEFAULT 'construcao';