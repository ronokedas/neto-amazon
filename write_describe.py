import os
with open('c:/sistema/describe.php', 'w') as f:
    f.write("<?php\nrequire 'c:/sistema/config.php';\n")
    f.write("foreach($pdo->query('DESCRIBE agendamentos') as $row) echo $row[0] . ' - ' . $row[1] . \"\\n\";\n")
    f.write("echo \"\\nVISTORIAS:\\n\";\n")
    f.write("foreach($pdo->query('DESCRIBE vistorias') as $row) echo $row[0] . ' - ' . $row[1] . \"\\n\";\n")
