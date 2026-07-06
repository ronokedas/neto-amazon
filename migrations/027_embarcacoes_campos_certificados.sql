-- Migration: 027_embarcacoes_campos_certificados
-- Adicionando campos tecnicos dos certificados diretamente na embarcacao

ALTER TABLE `embarcacoes`
ADD COLUMN `possui_propulsao` TINYINT(1) DEFAULT NULL AFTER `tipo_embarcacao`,
ADD COLUMN `fabricante_motor` VARCHAR(300) DEFAULT NULL AFTER `possui_propulsao`,
ADD COLUMN `potencia_kw` VARCHAR(50) DEFAULT NULL AFTER `fabricante_motor`,
ADD COLUMN `autorizado_carga` TINYINT(1) DEFAULT NULL,
ADD COLUMN `obs_passageiros` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `acessibilidade` TINYINT(1) DEFAULT NULL,
ADD COLUMN `local_construcao` VARCHAR(200) DEFAULT NULL,
ADD COLUMN `arqueacao_liquida` DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN `metodo_arqueacao` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `borda_livre_mm` INT DEFAULT NULL,
ADD COLUMN `borda_livre_tipo` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `calado_maximo_m` DECIMAL(8,2) DEFAULT NULL,
ADD COLUMN `aresta_superior_linha_conves` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `centro_disco_situado` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `dist_linha_conves_bico_proa` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `dist_linha_conves_abaixo_disco` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `marca_linha_carga_area1` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `marca_linha_carga_area2` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `acrescimo_agua_salgada` VARCHAR(50) DEFAULT NULL;
