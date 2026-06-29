import os
with open('c:/sistema/modules/vistorias/actions.php', 'r', encoding='utf-8') as f:
    text = f.read()

text = text.replace('} reprovado.");\n            setMensagem(\'error\', \'Relatorio reprovado.\');\n        }', '}')

with open('c:/sistema/modules/vistorias/actions.php', 'w', encoding='utf-8') as f:
    f.write(text)
