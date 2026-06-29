<?php
/**
 * SIDEBAR DO SISTEMA ERP - NOVA ESTRUTURA
 */
$cargo = getCargo();

// Determinar pagina ativa pela URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');
$app_folder = '/' . basename(__DIR__ . '/..');
if (strpos($path, $app_folder) === 0) {
    $path = substr($path, strlen($app_folder));
}
$pagina_atual = ltrim($path, '/');
if (empty($pagina_atual)) $pagina_atual = 'dashboard';

function isActive($page, $pagina_atual) {
    return ($pagina_atual === $page) ? ' active' : '';
}

// Dados do usuario
$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$email_usuario = $_SESSION['usuario_email'] ?? 'usuario@sistema.com';
$inicial_avatar = strtoupper(substr($nome_usuario, 0, 1));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo-area">
        <a href="<?= APP_URL ?>dashboard" class="logo-title">Sistema Naval</a>
        <button class="btn-sidebar-toggle" id="sidebar-toggle">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        
        <!-- GRUPO OPERAÇÃO -->
        <div class="nav-group-label">OPERAÇÃO</div>
        <a href="<?= APP_URL ?>dashboard" class="nav-item<?= isActive('dashboard', $pagina_atual) ?>">
            <i class="fa-solid fa-grid"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="<?= APP_URL ?>vistorias" class="nav-item<?= isActive('vistorias', $pagina_atual) ?>">
            <i class="fa-solid fa-clipboard-check"></i>
            <span class="nav-text">Vistorias</span>
        </a>
        <a href="<?= APP_URL ?>agendamentos" class="nav-item<?= isActive('agendamentos', $pagina_atual) ?>">
            <i class="fa-solid fa-calendar"></i>
            <span class="nav-text">Agendamentos</span>
        </a>

        <!-- GRUPO CADASTROS -->
        <div class="nav-group-label">CADASTROS</div>
        <a href="<?= APP_URL ?>embarcacoes" class="nav-item<?= isActive('embarcacoes', $pagina_atual) ?>">
            <i class="fa-solid fa-anchor"></i>
            <span class="nav-text">Embarcações</span>
        </a>
        <a href="<?= APP_URL ?>clientes" class="nav-item<?= isActive('clientes', $pagina_atual) ?>">
            <i class="fa-solid fa-building"></i>
            <span class="nav-text">Clientes</span>
        </a>
        <a href="<?= APP_URL ?>pessoas" class="nav-item<?= isActive('pessoas', $pagina_atual) ?>">
            <i class="fa-solid fa-users"></i>
            <span class="nav-text">Pessoas</span>
        </a>

        <?php if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR'): ?>
        <!-- GRUPO COMERCIAL -->
        <div class="nav-group-label">COMERCIAL</div>
        <a href="<?= APP_URL ?>comercial" class="nav-item<?= isActive('comercial', $pagina_atual) ?>">
            <i class="fa-solid fa-file-text"></i>
            <span class="nav-text">Propostas</span>
        </a>
        <a href="#" class="nav-item nav-link disabled" title="Em breve">
            <i class="fa-solid fa-file-signature"></i>
            <span class="nav-text">Contratos</span>
            <span style="font-size:10px; color:var(--text-tertiary); margin-left:auto; background:var(--bg-surface-3); padding:1px 6px; border-radius:10px;">Em breve</span>
        </a>
        <?php endif; ?>

        <?php if ($cargo === 'ADMIN'): ?>
        <!-- GRUPO FINANCEIRO -->
        <div class="nav-group-label">FINANCEIRO</div>
        <a href="<?= APP_URL ?>financeiro" class="nav-item<?= isActive('financeiro', $pagina_atual) ?>">
            <i class="fa-solid fa-dollar-sign"></i>
            <span class="nav-text">Lançamentos</span>
        </a>
        <a href="#" class="nav-item nav-link disabled" title="Em breve">
            <i class="fa-solid fa-chart-column"></i>
            <span class="nav-text">Relatórios</span>
            <span style="font-size:10px; color:var(--text-tertiary); margin-left:auto; background:var(--bg-surface-3); padding:1px 6px; border-radius:10px;">Em breve</span>
        </a>
        <?php endif; ?>

        <!-- GRUPO WORKSPACE -->
        <div class="nav-group-label">WORKSPACE</div>
        <div class="nav-group">
            <a href="#" class="nav-item<?= (strpos($pagina_atual, 'documentacao') === 0) ? ' active' : '' ?>" onclick="this.parentElement.querySelector('.nav-submenu').classList.toggle('open'); return false;">
                <i class="fa-solid fa-book-open"></i>
                <span class="nav-text">Documentação</span>
                <i class="fa-solid fa-chevron-down nav-chevron"></i>
            </a>
            <div class="nav-submenu<?= (strpos($pagina_atual, 'documentacao') === 0) ? ' open' : '' ?>">
                <a href="<?= APP_URL ?>documentacao/certificados" class="nav-item nav-subitem<?= isActive('documentacao/certificados', $pagina_atual) ?>">Certificados (CSN)</a>
                <a href="<?= APP_URL ?>documentacao/cnbl" class="nav-item nav-subitem<?= isActive('documentacao/cnbl', $pagina_atual) ?>">CNBL</a>
                <a href="<?= APP_URL ?>documentacao/cnarq" class="nav-item nav-subitem<?= isActive('documentacao/cnarq', $pagina_atual) ?>">CNARQ</a>
                <a href="<?= APP_URL ?>documentacao/lp" class="nav-item nav-subitem<?= isActive('documentacao/lp', $pagina_atual) ?>">LP</a>
                <a href="<?= APP_URL ?>documentacao/lc" class="nav-item nav-subitem<?= isActive('documentacao/lc', $pagina_atual) ?>">LC</a>
                <a href="<?= APP_URL ?>documentacao/cht" class="nav-item nav-subitem<?= isActive('documentacao/cht', $pagina_atual) ?>">CHT</a>
            </div>
        </div>
        
        <?php if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR'): ?>
        <a href="<?= APP_URL ?>emails" class="nav-item<?= isActive('emails', $pagina_atual) ?>">
            <i class="fa-solid fa-envelope"></i>
            <span class="nav-text">E-mails</span>
        </a>
        <?php endif; ?>

        <?php if ($cargo === 'ADMIN'): ?>
        <a href="<?= APP_URL ?>usuarios" class="nav-item<?= isActive('usuarios', $pagina_atual) ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span class="nav-text">Usuários</span>
        </a>
        <a href="<?= APP_URL ?>configuracoes" class="nav-item<?= isActive('configuracoes', $pagina_atual) ?>">
            <i class="fa-solid fa-gear"></i>
            <span class="nav-text">Configurações</span>
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>login?action=logout" class="sidebar-logout" title="Sair do Sistema">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="nav-text">Sair</span>
        </a>
        <div class="user-avatar"><?= $inicial_avatar ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($nome_usuario) ?></div>
            <div class="user-email"><?= htmlspecialchars($email_usuario) ?></div>
        </div>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>