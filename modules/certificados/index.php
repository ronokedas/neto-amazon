<?php
/**
 * MODULO: CERTIFICADOS
 * Arquivo: index.php - Central de emissão de certificados
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$modelos = [
    [
        'codigo' => 'CSN',
        'nome' => 'Certificado de Segurança da Navegação',
        'descricao' => 'Emissão a partir de relatório aprovado, com controle de tipo, validade e responsável técnico.',
        'icone' => 'fa-ship',
        'status' => 'Fluxo completo',
        'status_classe' => 'ready',
    ],
    [
        'codigo' => 'CNBL',
        'nome' => 'Certificado Nacional de Borda Livre',
        'descricao' => 'Documento técnico para borda livre e dados complementares da embarcação.',
        'icone' => 'fa-water',
        'status' => 'Assistente ativo',
        'status_classe' => 'ready',
    ],
    [
        'codigo' => 'CNARQ',
        'nome' => 'Certificado Nacional de Arqueação',
        'descricao' => 'Registro de arqueação, dimensões e identificação técnica da embarcação.',
        'icone' => 'fa-ruler-combined',
        'status' => 'Assistente ativo',
        'status_classe' => 'ready',
    ],
    [
        'codigo' => 'LP',
        'nome' => 'Licença Provisória',
        'descricao' => 'Licença temporária emitida a partir da documentação e vistoria vinculada.',
        'icone' => 'fa-clipboard-list',
        'status' => 'Assistente ativo',
        'status_classe' => 'ready',
    ],
    [
        'codigo' => 'LC',
        'nome' => 'Licença de Construção',
        'descricao' => 'Etapa documental para construção, regularização e acompanhamento técnico.',
        'icone' => 'fa-building-user',
        'status' => 'Formulário dedicado',
        'status_classe' => 'soon',
    ],
    [
        'codigo' => 'CHT',
        'nome' => 'Certificado de Homologação Técnica',
        'descricao' => 'Homologação de empresa ou profissional prestador de serviços técnicos.',
        'icone' => 'fa-user-check',
        'status' => 'Assistente ativo',
        'status_classe' => 'ready',
    ],
];

$titulo_page = 'Certificados - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <section class="cert-hero">
        <div class="cert-hero-main">
            <span class="flow-eyebrow"><i class="fas fa-file-signature"></i> Etapa final do fluxo operacional</span>
            <h1>Emissão de Certificados</h1>
            <p>Escolha o modelo do documento e siga um fluxo guiado até validar os dados, escolher o responsável e gerar o certificado.</p>
        </div>
        <div class="cert-hero-panel">
            <span>Próxima ação</span>
            <strong>Selecione o modelo</strong>
            <small>Depois disso o assistente mostra o tipo, relatório aprovado e dados de emissão.</small>
        </div>
    </section>

    <div class="cert-workspace">
        <aside class="cert-flow-sidebar">
            <div class="cert-flow-title">
                <i class="fas fa-route"></i>
                <div>
                    <strong>Fluxo até finalizar</strong>
                    <span>Visão rápida das etapas</span>
                </div>
            </div>

            <ol class="cert-step-list">
                <li class="is-active">
                    <span>01</span>
                    <div>
                        <strong>Modelo</strong>
                        <small>Escolher CSN, CNBL, CNARQ, LP, LC ou CHT.</small>
                    </div>
                </li>
                <li>
                    <span>02</span>
                    <div>
                        <strong>Tipo</strong>
                        <small>Definir se será provisório, condicional ou definitivo.</small>
                    </div>
                </li>
                <li>
                    <span>03</span>
                    <div>
                        <strong>Relatório e dados</strong>
                        <small>Usar relatório aprovado e conferir dados da embarcação.</small>
                    </div>
                </li>
                <li>
                    <span>04</span>
                    <div>
                        <strong>Gerar certificado</strong>
                        <small>Escolher assinatura, validade e local de emissão.</small>
                    </div>
                </li>
            </ol>
        </aside>

        <section class="cert-main-panel">
            <div class="cert-panel-header">
                <div>
                    <h2>Qual certificado será emitido?</h2>
                    <p>Os modelos ficam agrupados em cartões para facilitar a escolha sem confundir siglas parecidas.</p>
                </div>
            </div>

            <div class="cert-model-grid">
                <?php foreach ($modelos as $modelo): ?>
                    <a href="<?= APP_URL ?>certificados/wizard?modelo=<?= urlencode($modelo['codigo']) ?>" class="cert-model-card">
                        <div class="cert-model-top">
                            <span class="cert-model-icon"><i class="fa-solid <?= h($modelo['icone']) ?>"></i></span>
                            <span class="cert-model-status <?= h($modelo['status_classe']) ?>"><?= h($modelo['status']) ?></span>
                        </div>
                        <div>
                            <strong><?= h($modelo['codigo']) ?></strong>
                            <h3><?= h($modelo['nome']) ?></h3>
                            <p><?= h($modelo['descricao']) ?></p>
                        </div>
                        <span class="cert-model-action">Iniciar emissão <i class="fas fa-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
