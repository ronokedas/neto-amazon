<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: backup.php - Configurações de Backup
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Buscar configuracao backup_email
try {
    $stmt = $pdo->query("SELECT chave, valor, descricao FROM configuracoes WHERE chave IN ('backup_email') ORDER BY chave ASC");
    $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $configMap = [];
    foreach ($configuracoes as $c) {
        $configMap[$c['chave']] = $c;
    }
} catch (Exception $e) {
    $configMap = [];
}

// Verifica se o diretório de backups existe e lista os últimos backups gerados
$backupDir = BASE_PATH . '/storage/backups';
$arquivosBackup = [];
if (is_dir($backupDir)) {
    $arquivos = glob($backupDir . '/*.sql.gz');
    if ($arquivos) {
        // Ordena por data de modificação (mais recente primeiro)
        array_multisort(array_map('filemtime', $arquivos), SORT_DESC, $arquivos);
        // Pega os 5 mais recentes para exibir
        $arquivosBackup = array_slice($arquivos, 0, 5);
    }
}

$titulo_page = 'Backup do Sistema - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div>
                <h1><i class="fas fa-database"></i> Backup do Sistema</h1>
                <p>Configure o envio de backups e gere cópias manuais do banco de dados.</p>
            </div>
            <a href="<?php echo APP_URL; ?>configuracoes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- Bloco de Configuração de Email -->
        <div class="card">
            <div class="card-header">
                <h3 style="color: var(--cor-destaque);"><i class="fas fa-envelope"></i> Configuração de Envio</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo APP_URL; ?>configuracoes/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                    <input type="hidden" name="action" value="salvar">
                    <input type="hidden" name="redirect_to" value="configuracoes/backup">

                    <div class="form-group" style="margin-bottom: 22px;">
                        <label for="cfg_backup_email">E-mail para recebimento de backups</label>
                        <input type="email" id="cfg_backup_email" name="cfg[backup_email]" 
                               value="<?php echo h($configMap['backup_email']['valor'] ?? ''); ?>" 
                               placeholder="exemplo@suaempresa.com.br"
                               style="width: 100%; padding: 10px 14px;">
                        <small style="display: block; color: var(--cor-texto-secundario); margin-top: 4px;">
                            <i class="fas fa-info-circle"></i> 
                            Deixe em branco se não quiser enviar o backup por e-mail automaticamente.
                        </small>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar E-mail
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bloco de Ações Manuais e Histórico -->
        <div class="card">
            <div class="card-header">
                <h3 style="color: var(--cor-destaque);"><i class="fas fa-download"></i> Ações Manuais</h3>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 20px; color: var(--cor-texto-secundario);">
                    Use os botões abaixo para gerar um backup do banco de dados agora ou enviar o backup mais recente para o e-mail configurado.
                </p>

                <div style="display: flex; gap: 10px; margin-bottom: 30px;">
                    <form method="POST" action="<?php echo APP_URL; ?>configuracoes/backup_actions">
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                        <input type="hidden" name="action" value="gerar_backup">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Isso iniciará o processo de backup. Pode levar alguns segundos. Deseja continuar?');">
                            <i class="fas fa-database"></i> Gerar Backup Agora
                        </button>
                    </form>

                    <form method="POST" action="<?php echo APP_URL; ?>configuracoes/backup_actions">
                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                        <input type="hidden" name="action" value="enviar_backup">
                        <button type="submit" class="btn btn-info" <?php echo empty($configMap['backup_email']['valor']) || empty($arquivosBackup) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i> Enviar Último Backup
                        </button>
                    </form>
                </div>

                <h4 style="margin-bottom: 10px; border-bottom: 1px solid var(--cor-borda); padding-bottom: 5px;">Últimos Backups Gerados</h4>
                <?php if (empty($arquivosBackup)): ?>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.9rem;">Nenhum backup encontrado na pasta storage/backups.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($arquivosBackup as $arq): ?>
                            <li style="padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem; display: flex; justify-content: space-between;">
                                <span><i class="far fa-file-archive" style="color: #666; margin-right: 5px;"></i> <?php echo basename($arq); ?></span>
                                <span style="color: #888;"><?php echo date('d/m/Y H:i', filemtime($arq)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>