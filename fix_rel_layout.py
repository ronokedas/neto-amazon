import re

with open('c:/sistema/modules/vistorias/relatorio.php', 'r', encoding='utf-8') as f:
    c = f.read()

# The HTML block to move starts at "<!-- BARRA DE ETAPAS -->" and ends at "<?php\n$titulo_page"
pattern = r"(<!-- BARRA DE ETAPAS -->.*?)(<\?php\s*\$titulo_page = 'Relatorio Tecnico - ERP Sistema';\s*require_once __DIR__ \. '/\.\./\.\./includes/header\.php';\s*require_once __DIR__ \. '/\.\./\.\./includes/sidebar\.php';\s*\?>\s*<div class=\"conteudo-principal\">)"
match = re.search(pattern, c, re.DOTALL)
if match:
    block1 = match.group(1) # The barra de etapas html + closing php tag if any? No, the php closing tag is before <!-- BARRA DE ETAPAS -->
    block2 = match.group(2) # The header/sidebar includes and <div class="conteudo-principal">
    
    new_c = c[:match.start()] + block2 + "\n" + block1 + c[match.end():]
    with open('c:/sistema/modules/vistorias/relatorio.php', 'w', encoding='utf-8') as f:
        f.write(new_c)
    print("Fixed layout order.")
else:
    print("Pattern not found!")
