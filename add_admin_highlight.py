import re

with open('c:/sistema/modules/dashboard/index.php', 'r', encoding='utf-8') as f:
    c = f.read()

admin_block = """    <!-- RELATORIOS AGUARDANDO APROVACAO (apenas para ADMIN) -->
<?php if ($cargo === 'ADMIN'):
    try {
        $stmtRel = $pdo->prepare("
            SELECT v.*, e.nome AS embarcacao_nome, u.nome AS vistoriador_nome
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id
            LEFT JOIN usuarios u ON a.vistoriador_id = u.id
            WHERE v.status = 'AGUARDANDO_APROVACAO'
            ORDER BY v.atualizado_em ASC
            LIMIT 5
        ");
        $stmtRel->execute();
        $relatorios_pendentes = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $relatorios_pendentes = [];
    }
    if (!empty($relatorios_pendentes)):
?>
<div class="card mb-4" style="border-left: 4px solid #e74c3c; border-radius: 8px; background: linear-gradient(135deg, rgba(231,76,60,0.08) 0%, rgba(231,76,60,0.02) 100%);">
    <div class="card-header" style="border-bottom: 1px solid rgba(231,76,60,0.3);">
        <h4 style="margin: 0; color: #e74c3c;">
            <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> Relatórios Aguardando Aprovação
            <span class="badge" style="background: #e74c3c; color: #fff; font-size: 14px; margin-left: 10px;">
                <?php echo count($relatorios_pendentes); ?> pendente(s)
            </span>
        </h4>
    </div>
    <div class="card-body" style="padding: 16px;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(231,76,60,0.3);">
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Data Vistoria</th>
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Vistoriador</th>
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Embarcação</th>
                        <th style="padding: 10px 8px; text-align: center; color: #e74c3c;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relatorios_pendentes as $rel): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px 8px;"><?php echo formatarData($rel['data_vistoria']); ?></td>
                            <td style="padding: 12px 8px;"><?php echo h($rel['vistoriador_nome'] ?? '-'); ?></td>
                            <td style="padding: 12px 8px;"><?php echo h($rel['embarcacao_nome'] ?? '-'); ?></td>
                            <td style="padding: 12px 8px; text-align: center;">
                                <a href="<?php echo APP_URL; ?>documentacao/aprovacao_relatorios" class="btn btn-sm btn-danger">
                                    <i class="fas fa-gavel"></i> Analisar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; endif; ?>

"""

c = c.replace("    <!-- AGENDAMENTOS EM DESTAQUE (apenas para VISTORIADOR) -->", admin_block + "    <!-- AGENDAMENTOS EM DESTAQUE (apenas para VISTORIADOR) -->")

with open('c:/sistema/modules/dashboard/index.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Added ADMIN reports highlight.")