import re

with open('c:/sistema/modules/documentacao/baixa_exigencias.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace("WHERE v.id = :id AND v.ativo = 1", "WHERE v.id = :id")

with open('c:/sistema/modules/documentacao/baixa_exigencias.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Fixed baixa_exigencias query.")