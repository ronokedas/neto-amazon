<?php
/**
 * SIDEBAR DO SISTEMA ERP
 * 
 * Menu lateral com navegacao entre modulos
 * Controle de acesso por cargo (ADMIN / VISTORIADOR)
 */

// Verificar permissoes do usuario logado
$cargo = getCargo();
$modulos = [];

if ($cargo === 'ADMIN') {
    $modulos = [
        // Dashboard
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'page' => 'dashboard'],
        
        // Cadastros
        ['icon' => 'fa-user-tie', 'label' => 'Clientes', 'page' => 'clientes'],
        ['icon' => 'fa-ship', 'label' => 'Embarcacoes', 'page' => 'embarcacoes'],
        ['icon' => 'fa-users', 'label' => 'Pessoas', 'page' => 'pessoas'],
        
        // Operacional
        ['icon' => 'fa-clipboard-check', 'label' => 'Vistorias', 'page' => 'vistorias'],
        ['icon' => 'fa-calendar-check', 'label' => 'Agendamentos', 'page' => 'agendamentos'],
        
        // Comercial
        [
            'icon' => 'fa-chart-line',
            'label' => 'Comercial',
            'page' => 'comercial',
            'submenu' => [
                ['icon' => 'fa-file-invoice', 'label' => 'Propostas', 'page' => 'comercial'],
                ['icon' => 'fa-cogs', 'label' => 'Serviços', 'page' => 'comercial/servicos'],
            ]
        ],
        
        // Financeiro
        ['icon' => 'fa-dollar-sign', 'label' => 'Financeiro', 'page' => 'financeiro'],
        
        // Documentacao
        [
            'icon' => 'fa-file-alt',
            'label' => 'Documentação',
            'page' => 'documentacao',
            'submenu' => [
                ['icon' => 'fa-file-shield', 'label' => 'Certificados CSN', 'page' => 'documentacao/certificados'],
                ['icon' => 'fa-file-contract', 'label' => 'Certificados CNBL', 'page' => 'documentacao/cnbl'],
                ['icon' => 'fa-file-signature', 'label' => 'Certificados CNARQ', 'page' => 'documentacao/cnarq'],
                ['icon' => 'fa-file-pen', 'label' => 'Licenças Provisórias LP', 'page' => 'documentacao/lp'],
                ['icon' => 'fa-file-contract', 'label' => 'Licenças Construção LC', 'page' => 'documentacao/lc'],
                ['icon' => 'fa-file-check', 'label' => 'Homologação Técnica CHT', 'page' => 'documentacao/cht'],
            ]
        ],
        
        // Administracao
        ['icon' => 'fa-user-cog', 'label' => 'Usuarios', 'page' => 'usuarios'],
        ['icon' => 'fa-envelope', 'label' => 'E-mails', 'page' => 'emails'],
        ['icon' => 'fa-cog', 'label' => 'Configurações', 'page' => 'configuracoes'],
    ];
} else {
    // VISTORIADOR — acesso restrito a modulos operacionais
    $modulos = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'page' => 'dashboard'],
        ['icon' => 'fa-ship', 'label' => 'Embarcacoes', 'page' => 'embarcacoes'],
        ['icon' => 'fa-users', 'label' => 'Pessoas', 'page' => 'pessoas'],
        ['icon' => 'fa-clipboard-check', 'label' => 'Vistorias', 'page' => 'vistorias'],
        ['icon' => 'fa-calendar-check', 'label' => 'Agendamentos', 'page' => 'agendamentos'],
    ];
}

// Determinar pagina ativa pela URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');
$app_folder = '/' . basename(__DIR__ . '/..');
if (strpos($path, $app_folder) === 0) {
    $path = substr($path, strlen($app_folder));
}
$pagina_atual = ltrim($path, '/');
if (empty($pagina_atual)) $pagina_atual = 'login';
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php foreach ($modulos as $modulo): ?>
            <?php if (isset($modulo['submenu'])): ?>
                <?php 
                $parent_active = strpos($pagina_atual, $modulo['page']) === 0;
                ?>
                <div class="nav-group">
                    <a href="javascript:void(0);" class="nav-item<?php echo $parent_active ? ' active' : ''; ?>" onclick="toggleSubmenu(this)">
                        <i class="fas <?php echo $modulo['icon']; ?>"></i>
                        <span><?php echo $modulo['label']; ?></span>
                        <i class="fas fa-chevron-down nav-chevron"></i>
                    </a>
                    <div class="nav-submenu<?php echo $parent_active ? ' open' : ''; ?>">
                        <?php foreach ($modulo['submenu'] as $sub): ?>
                            <?php 
                            $sub_active = ($pagina_atual === $sub['page']) ? ' active' : '';
                            ?>
                            <a href="<?php echo APP_URL . $sub['page']; ?>" class="nav-item nav-subitem<?php echo $sub_active; ?>">
                                <i class="fas <?php echo $sub['icon']; ?>"></i>
                                <span><?php echo $sub['label']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php 
                $is_active = ($pagina_atual === $modulo['page']) ? ' active' : '';
                $url = APP_URL . $modulo['page'];
                ?>
                <a href="<?php echo $url; ?>" class="nav-item<?php echo $is_active; ?>">
                    <i class="fas <?php echo $modulo['icon']; ?>"></i>
                    <span><?php echo $modulo['label']; ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div class="nav-divider"></div>
        
        <a href="<?php echo APP_URL; ?>login?action=logout" class="nav-item nav-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>