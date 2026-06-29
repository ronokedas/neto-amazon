<?php
require 'c:/sistema/config.php';
$stmt = $pdo->query('SELECT * FROM embarcacoes LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    print_r(array_keys($row));
} else {
    echo "No rows found or error.";
}
?>