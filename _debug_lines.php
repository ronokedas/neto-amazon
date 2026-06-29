<?php
$f = file_get_contents('c:\sistema\modules\agendamentos\index.php');
$lines = explode("\n", $f);
for($i=219; $i<=247; $i++) {
    $line = $lines[$i-1];
    echo "Line $i: " . bin2hex($line) . "\n";
    echo "  -> " . $line . "\n\n";
}
