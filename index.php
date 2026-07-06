<?php
/**
 * ERP SISTEMA DE GESTAO
 * Arquivo: index.php - Roteador principal
 */

// Configuracao do sistema
require_once __DIR__ . '/config.php';

// Capturar a URI solicitada
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remover a barra final
$path = rtrim($path, '/');

// Remover o prefixo da pasta do projeto (ex: /sistema/dashboard -> /dashboard)
$app_folder = '/' . basename(__DIR__);
if (strpos($path, $app_folder) === 0) {
    $path = substr($path, strlen($app_folder));
}
$path = ltrim($path, '/');

// Mapeamento de rotas para modulos
$rotas = [
    ''              => 'modules/login/index.php',
    'login'         => 'modules/login/index.php',
    'dashboard'     => 'modules/dashboard/index.php',
    'armadores'          => 'modules/armadores/index.php',
    'armadores/form'     => 'modules/armadores/form.php',
    'armadores/actions'  => 'modules/armadores/actions.php',
    'proprietarios'          => 'modules/proprietarios/index.php',
    'proprietarios/form'     => 'modules/proprietarios/form.php',
    'proprietarios/actions'  => 'modules/proprietarios/actions.php',
    'despachantes'          => 'modules/despachantes/index.php',
    'despachantes/form'     => 'modules/despachantes/form.php',
    'despachantes/actions'  => 'modules/despachantes/actions.php',
    'embarcacoes'          => 'modules/embarcacoes/index.php',
    'embarcacoes/form'     => 'modules/embarcacoes/form.php',
    'embarcacoes/actions'  => 'modules/embarcacoes/actions.php',
    'vistorias'          => 'modules/vistorias/index.php',
    'vistorias/nova'     => 'modules/vistorias/nova.php',
    'vistorias/detalhe'  => 'modules/vistorias/detalhe.php',
    'vistorias/actions'  => 'modules/vistorias/actions.php',
    'vistorias/relatorio' => 'modules/vistorias/relatorio.php',
    'vistorias/relatorio_pdf' => 'modules/vistorias/relatorio_pdf.php',
    'vistorias/relatorio_pdf.php' => 'modules/vistorias/relatorio_pdf.php',
    'financeiro'          => 'modules/financeiro/index.php',
    'financeiro/form'     => 'modules/financeiro/form.php',
    'financeiro/actions'  => 'modules/financeiro/actions.php',
    'financeiro/relatorios' => 'modules/financeiro/relatorios.php',
    'usuarios'      => 'modules/usuarios/index.php',
    'usuarios/form' => 'modules/usuarios/form.php',
    'usuarios/actions' => 'modules/usuarios/actions.php',
    'documentacao'                      => 'modules/documentacao/index.php',
    'documentacao/certificados'         => 'modules/documentacao/certificados/index.php',
    'documentacao/certificados/form'    => 'modules/documentacao/certificados/form.php',
    'documentacao/certificados/actions' => 'modules/documentacao/certificados/actions.php',
    'documentacao/certificados/pdf'     => 'modules/documentacao/certificados/pdf.php',
    'documentacao/cnbl'                 => 'modules/documentacao/cnbl/index.php',
    'documentacao/cnbl/form'            => 'modules/documentacao/cnbl/form.php',
    'documentacao/cnbl/actions'         => 'modules/documentacao/cnbl/actions.php',
    'documentacao/cnbl/pdf'             => 'modules/documentacao/cnbl/pdf.php',
    'documentacao/cnarq'                => 'modules/documentacao/cnarq/index.php',
    'documentacao/cnarq/form'           => 'modules/documentacao/cnarq/form.php',
    'documentacao/cnarq/actions'        => 'modules/documentacao/cnarq/actions.php',
    'documentacao/cnarq/pdf'            => 'modules/documentacao/cnarq/pdf.php',
    'documentacao/lp'                   => 'modules/documentacao/lp/index.php',
    'documentacao/lp/form'              => 'modules/documentacao/lp/form.php',
    'documentacao/lp/actions'           => 'modules/documentacao/lp/actions.php',
    'documentacao/lp/pdf'               => 'modules/documentacao/lp/pdf.php',
    'documentacao/lc'                   => 'modules/documentacao/lc/index.php',
    'documentacao/lc/form'              => 'modules/documentacao/lc/form.php',
    'documentacao/lc/actions'           => 'modules/documentacao/lc/actions.php',
    'documentacao/lc/pdf'               => 'modules/documentacao/lc/pdf.php',
    'documentacao/cht'                  => 'modules/documentacao/cht/index.php',
    'documentacao/cht/form'             => 'modules/documentacao/cht/form.php',
    'documentacao/cht/actions'          => 'modules/documentacao/cht/actions.php',
    'documentacao/cht/pdf'              => 'modules/documentacao/cht/pdf.php',
    'documentacao/aprovacao_relatorios' => 'modules/documentacao/aprovacao_relatorios.php',
    'documentacao/novo_certificado'     => 'modules/documentacao/novo_certificado.php',
    'documentacao/baixa_exigencias'     => 'modules/documentacao/baixa_exigencias.php',
    'certificados'                  => 'modules/certificados/index.php',
    'certificados/wizard'           => 'modules/certificados/wizard.php',
    'certificados/wizard_step2'     => 'modules/certificados/wizard_step2.php',
    'comercial'                     => 'modules/comercial/index.php',
    'comercial/servicos'            => 'modules/comercial/servicos/index.php',
    'comercial/servicos/form'       => 'modules/comercial/servicos/form.php',
    'comercial/servicos/actions'    => 'modules/comercial/servicos/actions.php',
    'comercial/nova'                => 'modules/comercial/nova.php',
    'comercial/pdf'                 => 'modules/comercial/pdf.php',
    'comercial/propostas'           => 'modules/comercial/propostas/index.php',
    'comercial/propostas/actions'   => 'modules/comercial/propostas/actions.php',
    'contratos'                     => 'modules/contratos/index.php',
    'contratos/form'                => 'modules/contratos/form.php',
    'contratos/actions'             => 'modules/contratos/actions.php',
    'contratos/view'                => 'modules/contratos/view.php',
    'relatorios'                    => 'modules/relatorios/index.php',
    'agendamentos'          => 'modules/agendamentos/index.php',
    'agendamentos/form'     => 'modules/agendamentos/form.php',
    'agendamentos/actions'  => 'modules/agendamentos/actions.php',
    'agendamentos/os'       => 'modules/agendamentos/os.php',
    'emails'                => 'modules/emails/index.php',
    'configuracoes'             => 'modules/configuracoes/index.php',
    'configuracoes/geral'       => 'modules/configuracoes/geral.php',
    'configuracoes/basicas'     => 'modules/configuracoes/basicas.php',
    'configuracoes/backup'      => 'modules/configuracoes/backup.php',
    'configuracoes/actions'     => 'modules/configuracoes/actions.php',
    'configuracoes/backup_actions' => 'modules/configuracoes/backup_actions.php',
    'responsaveis_assinatura'         => 'modules/responsaveis_assinatura/index.php',
    'responsaveis_assinatura/form'    => 'modules/responsaveis_assinatura/form.php',
    'responsaveis_assinatura/actions' => 'modules/responsaveis_assinatura/actions.php',
    'busca-global'              => 'ajax/busca_global.php',
    'ajax/busca_cidades.php'    => 'ajax/busca_cidades.php',
    'perfil'                    => 'modules/perfil/index.php',
];

// Se nao esta logado, sempre ir para login (exceto proprio login)
if (!isset($_SESSION['usuario_logado']) && $path !== '' && $path !== 'login') {
    // Verificar se é rota pública de assinatura
    $is_rota_publica = (strpos($path, 'assinar/') === 0);
    
    // Verificar se é rota pública de visualização de PDF via token ou ID
    if (!$is_rota_publica && (strpos($path, '/pdf') !== false || strpos($path, 'relatorio_pdf') !== false) && (!empty($_GET['token']) || !empty($_GET['id']))) {
        $is_rota_publica = true;
    }
    
    if (!$is_rota_publica) {
        $path = '';
    }
}

// Se esta logado e acessa raiz ou login, redirecionar para dashboard
// Exceto quando eh logout
if (isset($_SESSION['usuario_logado']) && ($path === '' || $path === 'login')) {
    if (!isset($_GET['action']) || $_GET['action'] !== 'logout') {
        header('Location: ' . APP_URL . 'dashboard');
        exit;
    }
}

// Verificar se a rota existe
if (isset($rotas[$path])) {
    require_once __DIR__ . '/' . $rotas[$path];
} elseif (strpos($path, 'assinar/') === 0) {
    // Rota pública de assinatura: assinar/{token_assinatura}
    $_GET['token'] = substr($path, 8); // Remover "assinar/"
    // Verificar se o token pertence a propostas
    $stmt_check_prop = $pdo->prepare("SELECT COUNT(*) as total FROM propostas WHERE token_assinatura = :token");
    $stmt_check_prop->execute([':token' => $_GET['token']]);
    $check_prop = $stmt_check_prop->fetch(PDO::FETCH_ASSOC);
    if ($check_prop && $check_prop['total'] > 0) {
        require_once __DIR__ . '/modules/comercial/propostas/assinar.php';
        exit;
    }

    // Verificar se o token pertence ao CHT, LC, LP, CNBL, CNARQ ou CSN
    $stmt_check_cht = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cht WHERE token_assinatura = :token AND ativo = 1");
    $stmt_check_cht->execute([':token' => $_GET['token']]);
    $check_cht = $stmt_check_cht->fetch(PDO::FETCH_ASSOC);
    if ($check_cht && $check_cht['total'] > 0) {
        require_once __DIR__ . '/modules/documentacao/cht/assinar.php';
    } else {
        $stmt_check_lc = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lc WHERE token_assinatura = :token AND ativo = 1");
        $stmt_check_lc->execute([':token' => $_GET['token']]);
        $check_lc = $stmt_check_lc->fetch(PDO::FETCH_ASSOC);
        if ($check_lc && $check_lc['total'] > 0) {
            require_once __DIR__ . '/modules/documentacao/lc/assinar.php';
        } else {
            $stmt_check_lp = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lp WHERE token_assinatura = :token AND ativo = 1");
            $stmt_check_lp->execute([':token' => $_GET['token']]);
            $check_lp = $stmt_check_lp->fetch(PDO::FETCH_ASSOC);
            if ($check_lp && $check_lp['total'] > 0) {
                require_once __DIR__ . '/modules/documentacao/lp/assinar.php';
            } else {
                $stmt_check_cnbl = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnbl WHERE token_assinatura = :token AND ativo = 1");
                $stmt_check_cnbl->execute([':token' => $_GET['token']]);
                $check_cnbl = $stmt_check_cnbl->fetch(PDO::FETCH_ASSOC);
                if ($check_cnbl && $check_cnbl['total'] > 0) {
                    require_once __DIR__ . '/modules/documentacao/cnbl/assinar.php';
                } else {
                    $stmt_check_cnarq = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnarq WHERE token_assinatura = :token AND ativo = 1");
                    $stmt_check_cnarq->execute([':token' => $_GET['token']]);
                    $check_cnarq = $stmt_check_cnarq->fetch(PDO::FETCH_ASSOC);
                    if ($check_cnarq && $check_cnarq['total'] > 0) {
                        require_once __DIR__ . '/modules/documentacao/cnarq/assinar.php';
                    } else {
                        require_once __DIR__ . '/modules/documentacao/certificados/assinar.php';
                    }
                }
            }
        }
    }
} else {
    // 404 - Pagina nao encontrada
    http_response_code(404);
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="error-page">
        <div class="error-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>404</h1>
            <h2>Pagina nao encontrada</h2>
            <p>A pagina que voce procura nao existe ou foi removida.</p>
            <a href="<?php echo APP_URL; ?>login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Ir para Login
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}