import re

with open('c:/sistema/modules/vistorias/relatorio.php', 'r', encoding='utf-8') as f:
    c = f.read()

protection = """
    // VISTORIADOR so pode ver relatorio dos proprios agendamentos
    if ($cargo === 'VISTORIADOR' && $ag['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Este agendamento nao esta atribuido a voce.');
        redirecionar(APP_URL . 'agendamentos');
    }"""

new_protection = """
    // VISTORIADOR so pode ver relatorio dos proprios agendamentos
    if ($cargo === 'VISTORIADOR' && $ag['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Este agendamento nao esta atribuido a voce.');
        redirecionar(APP_URL . 'agendamentos');
    }
    
    // Se estiver aprovada, vistoriador não pode mais editar
    $stmtV_check = $pdo->prepare("SELECT status FROM vistorias WHERE agendamento_id = :id LIMIT 1");
    $stmtV_check->execute([':id' => $agendamento_id]);
    $vistoria_check = $stmtV_check->fetch(PDO::FETCH_ASSOC);
    if ($vistoria_check && in_array($vistoria_check['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS']) && $cargo === 'VISTORIADOR') {
        setMensagem('error', 'Este relatório já foi aprovado e não pode mais ser modificado.');
        redirecionar(APP_URL . 'agendamentos');
    }"""

c = c.replace(protection, new_protection)

with open('c:/sistema/modules/vistorias/relatorio.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Added protection.")