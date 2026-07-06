<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

$page_title = "Responsáveis pela Assinatura";
require_once __DIR__ . '/../../includes/header.php';

$stmt = $pdo->query("SELECT * FROM responsaveis_assinatura ORDER BY nome_completo ASC");
$responsaveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Responsáveis pela Assinatura</h1>
        <a href="<?= APP_URL ?>responsaveis_assinatura/form" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Novo Responsável
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome Completo</th>
                            <th>Cargo/Título</th>
                            <th>Registro Profissional</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responsaveis as $resp): ?>
                            <tr>
                                <td><?= htmlspecialchars($resp['nome_completo']) ?></td>
                                <td><?= htmlspecialchars($resp['cargo_titulo']) ?></td>
                                <td><?= htmlspecialchars($resp['registro_profissional'] ?? '-') ?></td>
                                <td>
                                    <?php if ($resp['ativo']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= APP_URL ?>responsaveis_assinatura/form?id=<?= $resp['id'] ?>" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
