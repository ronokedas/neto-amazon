import re

with open('c:/sistema/modules/vistorias/index.php', 'r', encoding='utf-8') as f:
    c = f.read()

replacement = """                                case 'PENDENTE':
                                    $badgeClass = 'badge-warning';
                                    $iconClass = 'fa-clock';
                                    break;
                                case 'AGUARDANDO_APROVACAO':
                                    $badgeClass = 'badge-warning';
                                    $iconClass = 'fa-hourglass-half';
                                    break;
                                case 'APROVADA_COM_EXIGENCIAS':
                                    $badgeClass = 'badge-primary';
                                    $iconClass = 'fa-clipboard-check';
                                    break;"""

c = re.sub(r"case 'PENDENTE':\s*\$badgeClass = 'badge-warning';\s*\$iconClass = 'fa-clock';\s*break;", replacement, c)

with open('c:/sistema/modules/vistorias/index.php', 'w', encoding='utf-8') as f:
    f.write(c)

with open('c:/sistema/modules/dashboard/index.php', 'r', encoding='utf-8') as f:
    c = f.read()

replacement2 = """$status_classes = [
                                'PENDENTE' => 'badge-warning',
                                'AGUARDANDO_APROVACAO' => 'badge-warning',
                                'APROVADA_COM_EXIGENCIAS' => 'badge-primary',
                                'APROVADA' => 'badge-success',
                                'REPROVADA' => 'badge-danger',
                                'CANCELADA' => 'badge-secondary'
                            ];"""

c = re.sub(r"\$status_classes = \[\s*'PENDENTE' => 'badge-warning',\s*'APROVADA' => 'badge-success',\s*'REPROVADA' => 'badge-danger',\s*'CANCELADA' => 'badge-secondary'\s*\];", replacement2, c)

with open('c:/sistema/modules/dashboard/index.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Updated badges")