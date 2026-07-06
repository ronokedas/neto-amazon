<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: index.php - Painel de controle de configurações
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Garantir que a tabela configuracoes existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES 
        ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$'),
        ('backup_email', '', 'E-mail para receber backups do banco de dados')");
} catch (Exception $e) {
    // Silencia erro
}

$titulo_page = 'Configurações - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 30px;">
        <div>
            <h1><i class="fas fa-cog"></i> Configurações do Sistema</h1>
            <p>Selecione a categoria que deseja configurar.</p>
        </div>
    </div>

    <div class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        
        <!-- Configurações Gerais -->
        <a href="<?php echo APP_URL; ?>configuracoes/geral" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-sliders-h" style="font-size: 3rem; color: var(--cor-destaque); margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Geral & Comercial</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Configurações de meta mensal de faturamento e parâmetros gerais do sistema.
                    </p>
                </div>
            </div>
        </a>

        <!-- Configurações Básicas do Sistema -->
        <a href="<?php echo APP_URL; ?>configuracoes/basicas" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-users-cog" style="font-size: 3rem; color: var(--cor-destaque); margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Configurações Básicas</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Permissões e acesso ao módulo de documentação por usuário.
                    </p>
                </div>
            </div>
        </a>

        <!-- Responsáveis pela Assinatura -->
        <a href="<?php echo APP_URL; ?>responsaveis_assinatura" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-file-signature" style="font-size: 3rem; color: #17a2b8; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Responsáveis pela Assinatura</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Cadastro de responsáveis para emissão de certificados (Nome, Cargo e Registro).
                    </p>
                </div>
            </div>
        </a>

        <!-- Backup do Sistema -->
        <a href="<?php echo APP_URL; ?>configuracoes/backup" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-database" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Backup do Sistema</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Gere backups manuais do banco de dados e configure o envio automático por e-mail.
                    </p>
                </div>
            </div>
        </a>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>