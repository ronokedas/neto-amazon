<?php
$content = file_get_contents('c:\\sistema\\modules\\agendamentos\\actions.php');
echo bin2hex(substr($content, 0, 200));
