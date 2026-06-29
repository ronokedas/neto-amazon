import re

with open('c:/sistema/modules/vistorias/relatorio.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = re.sub(r'\$titulo_page<\?php\s*=\s*\\\'Relatorio Tecnico - ERP Sistema\\\';', r"<?php\n$titulo_page = 'Relatorio Tecnico - ERP Sistema';", c)
c = re.sub(r'\$titulo_page<\?php\s*=\s*\'Relatorio Tecnico - ERP Sistema\';', r"<?php\n$titulo_page = 'Relatorio Tecnico - ERP Sistema';", c)

with open('c:/sistema/modules/vistorias/relatorio.php', 'w', encoding='utf-8') as f:
    f.write(c)
