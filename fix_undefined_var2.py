import re

with open('c:/sistema/modules/vistorias/actions.php', 'r', encoding='utf-8') as f:
    c = f.read()

else_block = r"\} else \{\s*// Atualizar vistoria existente"
replacement = "} else {\n                // Obter numero existente\n                $stmtCheck = $pdo->prepare(\"SELECT numero FROM vistorias WHERE id = :id\");\n                $stmtCheck->execute([':id' => $vistoria_id]);\n                $numero_relatorio = $stmtCheck->fetchColumn() ?: '';\n\n                // Atualizar vistoria existente"

c = re.sub(else_block, replacement, c)

with open('c:/sistema/modules/vistorias/actions.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Fixed actions.php")