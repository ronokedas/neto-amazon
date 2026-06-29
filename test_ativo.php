<?php
require __DIR__ . '/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM vistorias LIKE 'ativo'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
