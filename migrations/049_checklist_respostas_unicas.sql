-- Migration 049: uma resposta de checklist por vistoria e item de catalogo
-- Mantem a resposta mais recente quando houver duplicidade historica.

DELETE r_old
FROM vistoria_checklist_respostas r_old
INNER JOIN vistoria_checklist_respostas r_new
  ON r_new.vistoria_id = r_old.vistoria_id
 AND r_new.catalogo_id = r_old.catalogo_id
 AND (
      COALESCE(r_new.atualizado_em, r_new.criado_em, '1000-01-01') > COALESCE(r_old.atualizado_em, r_old.criado_em, '1000-01-01')
      OR (
          COALESCE(r_new.atualizado_em, r_new.criado_em, '1000-01-01') = COALESCE(r_old.atualizado_em, r_old.criado_em, '1000-01-01')
          AND r_new.id > r_old.id
      )
 )
WHERE r_old.vistoria_id IS NOT NULL
  AND r_old.catalogo_id IS NOT NULL;

ALTER TABLE vistoria_checklist_respostas
  ADD UNIQUE KEY uniq_vistoria_catalogo (vistoria_id, catalogo_id);
