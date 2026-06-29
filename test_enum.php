<?php
require 'c:/sistema/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM agendamentos LIKE 'status'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SHOW COLUMNS FROM vistorias LIKE 'status'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
