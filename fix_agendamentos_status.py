import re

with open('c:/sistema/modules/agendamentos/index.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Add v.status AS vistoria_status to the query
sql_old = """    $sql = "
        SELECT a.*, 
               c.nome AS cliente_nome,
               e.nome AS embarcacao_nome,
               u.nome AS vistoriador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status
        FROM agendamentos a"""
sql_new = """    $sql = "
        SELECT a.*, 
               c.nome AS cliente_nome,
               e.nome AS embarcacao_nome,
               u.nome AS vistoriador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status,
               v.status AS vistoria_status
        FROM agendamentos a
        LEFT JOIN vistorias v ON v.agendamento_id = a.id"""

c = c.replace(sql_old, sql_new)

# Update badge logic
badge_old = """                            $status_info = $status_labels[$a['status']] ?? ['label' => $a['status'], 'class' => 'badge-secondary'];"""
badge_new = """                            $status_info = $status_labels[$a['status']] ?? ['label' => $a['status'], 'class' => 'badge-secondary'];
                            if ($a['status'] === 'concluido' && ($a['vistoria_status'] ?? '') === 'APROVADA_COM_EXIGENCIAS') {
                                $status_info['label'] = 'Concluído c/ Exigências';
                                $status_info['class'] = 'badge-warning';
                            }
                            if ($a['status'] === 'em_andamento' && ($a['vistoria_status'] ?? '') === 'AGUARDANDO_APROVACAO') {
                                $status_info['label'] = 'Aguardando Aprovação';
                                $status_info['class'] = 'badge-warning';
                            }"""

c = c.replace(badge_old, badge_new)

with open('c:/sistema/modules/agendamentos/index.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Updated agendamentos index.")