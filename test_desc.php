<?php
require 'config.php';
$sql = "ALTER TABLE certificados_cnbl 
    ADD COLUMN aresta_superior_linha_conves VARCHAR(50) DEFAULT '0 mm',
    ADD COLUMN centro_disco_situado VARCHAR(50) DEFAULT '0 mm',
    ADD COLUMN dist_linha_conves_bico_proa VARCHAR(50) DEFAULT '',
    ADD COLUMN dist_linha_conves_abaixo_disco VARCHAR(50) DEFAULT '',
    ADD COLUMN marca_linha_carga_area1 VARCHAR(50) DEFAULT '0 mm',
    ADD COLUMN marca_linha_carga_area2 VARCHAR(50) DEFAULT '0 mm',
    ADD COLUMN acrescimo_agua_salgada VARCHAR(50) DEFAULT '0 mm';";
$pdo->exec($sql);
echo "Colunas adicionadas com sucesso!\n";
