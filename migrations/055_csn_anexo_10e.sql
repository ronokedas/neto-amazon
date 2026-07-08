-- Campos oficiais do CSN conforme Anexo 10-E (NORMAM-201/DPC).
-- Todos nascem opcionais para preservar certificados e embarcacoes antigas.

ALTER TABLE certificados_csn
  ADD COLUMN emitente varchar(200) NULL AFTER token_assinatura,
  ADD COLUMN normam_aplicavel varchar(30) NULL AFTER local_vistoria,
  ADD COLUMN tipo_vistoria_certificado varchar(50) NULL AFTER normam_aplicavel,
  ADD COLUMN observacoes_verso text NULL AFTER obs_passageiros;

ALTER TABLE csn_distribuicao_passageiros
  ADD COLUMN item_codigo varchar(80) NULL AFTER certificado_id,
  ADD COLUMN conves_principal varchar(50) NULL AFTER quantidade,
  ADD COLUMN conves_superior varchar(50) NULL AFTER conves_principal,
  ADD COLUMN area_lazer varchar(50) NULL AFTER conves_superior,
  ADD COLUMN unidade varchar(20) NULL AFTER area_lazer;
