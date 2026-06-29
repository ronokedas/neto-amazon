import re

with open('c:/sistema/modules/vistorias/actions.php', 'r', encoding='utf-8') as f:
    c = f.read()

validation = """        $statusValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'];
        if (!in_array($status_vistoria, $statusValidos)) {
            setMensagem('error', 'Status de vistoria invalido.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }"""

new_validation = """        $statusValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'];
        if (!in_array($status_vistoria, $statusValidos)) {
            setMensagem('error', 'Status de vistoria invalido.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        
        if (getCargo() === 'VISTORIADOR' && in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'])) {
            setMensagem('error', 'Vistoriadores só podem salvar relatórios como Pendente ou Aguardando Aprovação.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }"""

c = c.replace(validation, new_validation)

with open('c:/sistema/modules/vistorias/actions.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Updated validation.")