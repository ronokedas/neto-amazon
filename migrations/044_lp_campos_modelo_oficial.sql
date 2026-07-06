-- Migration 044: campo auxiliar do modelo oficial da Licença Provisória (LP)

ALTER TABLE `certificados_lp`
  ADD COLUMN `data_requerimento` date DEFAULT NULL AFTER `validade_data`;
