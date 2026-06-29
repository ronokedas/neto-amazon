<?php
require __DIR__ . '/config.php';
try {
    $pdo->exec("ALTER TABLE vistorias MODIFY COLUMN status ENUM('PENDENTE','AGUARDANDO_APROVACAO','APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA','CANCELADA') DEFAULT 'PENDENTE'");
    echo "Enum atualizado na tabela vistorias com sucesso!\n";
} catch (Exception $e) {
    echo "Erro vistorias: " . $e->getMessage() . "\n";
}
