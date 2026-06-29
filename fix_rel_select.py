import re

with open('c:/sistema/modules/vistorias/relatorio.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace the <select id="status_vistoria"> options based on cargo
old_select = """                        <option value="PENDENTE" <?php echo ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>Pendente (relatorio em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO" <?php echo ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <option value="APROVADA" <?php echo ($vistoria['status'] ?? '') === 'APROVADA' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="APROVADA_COM_EXIGENCIAS" <?php echo ($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS' ? 'selected' : ''; ?>>Aprovada c/ Exigências</option>
                        <option value="REPROVADA" <?php echo ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                        <option value="CANCELADA" <?php echo ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>"""

new_select = """                        <option value="PENDENTE" <?php echo ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>Pendente (relatorio em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO" <?php echo ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <?php if (getCargo() === 'ADMIN'): ?>
                        <option value="APROVADA" <?php echo ($vistoria['status'] ?? '') === 'APROVADA' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="APROVADA_COM_EXIGENCIAS" <?php echo ($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS' ? 'selected' : ''; ?>>Aprovada c/ Exigências</option>
                        <option value="REPROVADA" <?php echo ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                        <option value="CANCELADA" <?php echo ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        <?php endif; ?>"""

c = c.replace(old_select, new_select)

with open('c:/sistema/modules/vistorias/relatorio.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Updated relatorio.php select options.")