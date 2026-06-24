<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: index.php - Gerenciar configurações do sistema
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Garantir que a tabela configuracoes existe e tem o registro padrao
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$')");
} catch (Exception $e) {
    // Silencia erro - pode acontecer se a conexao falhar
}

// Buscar configuracoes
try {
    $stmt = $pdo->query("SELECT chave, valor, descricao FROM configuracoes ORDER BY chave ASC");
    $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $configMap = [];
    foreach ($configuracoes as $c) {
        $configMap[$c['chave']] = $c;
    }
} catch (Exception $e) {
    $configMap = [];
}

$titulo_page = 'Configurações - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-cog"></i> Configurações do Sistema</h1>
            <p>Gerencie as configurações e parâmetros do sistema.</p>
        </div>
    </div>

    <?php if (empty($configMap)): ?>
        <div class="tabela-vazia">
            <i class="fas fa-cogs"></i>
            <h3>Nenhuma configuração encontrada</h3>
            <p>Não foi possível carregar as configurações do sistema.</p>
        </div>
    <?php else: ?>
        <div class="card" style="max-width: 700px;">
            <div class="card-header">
                <h3 style="color: var(--cor-destaque);"><i class="fas fa-sliders-h"></i> Parâmetros</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo APP_URL; ?>configuracoes/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                    <input type="hidden" name="action" value="salvar">

                    <?php foreach ($configMap as $chave => $cfg): ?>
                        <div class="form-group" style="margin-bottom: 22px;">
                            <label for="cfg_<?php echo h($chave); ?>">
                                <?php
                                    $labels = [
                                        'meta_mensal' => 'Meta Mensal (R$)',
                                    ];
                                    echo $labels[$chave] ?? h($cfg['descricao'] ?: $chave);
                                ?>
                            </label>
                            <?php if ($chave === 'meta_mensal'): ?>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span style="font-size: 1.1rem; font-weight: 600; color: var(--cor-destaque);">R$</span>
                                    <input type="number" step="0.01" min="0" id="cfg_<?php echo h($chave); ?>"
                                           name="cfg[<?php echo h($chave); ?>]"
                                           value="<?php echo h($cfg['valor']); ?>"
                                           style="flex: 1; padding: 10px 14px; font-size: 1.1rem; font-weight: 600;">
                                </div>
                                <small style="display: block; color: var(--cor-texto-secundario); margin-top: 4px;">
                                    <i class="fas fa-info-circle"></i> 
                                    Valor utilizado para calcular o percentual de meta atingida no Dashboard.
                                </small>
                            <?php else: ?>
                                <input type="text" id="cfg_<?php echo h($chave); ?>"
                                       name="cfg[<?php echo h($chave); ?>]"
                                       value="<?php echo h($cfg['valor']); ?>"
                                       style="width: 100%; padding: 10px 14px;">
                                <?php if (!empty($cfg['descricao'])): ?>
                                    <small style="display: block; color: var(--cor-texto-secundario); margin-top: 4px;">
                                        <?php echo h($cfg['descricao']); ?>
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>