<?php
/**
 * HEADER DO SISTEMA ERP
 * 
 * Cabecalho padrao de todas as paginas
 */
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $titulo_page ?? APP_NAME; ?></title>
    
    <!-- CSS do Sistema -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css">
    
    <!-- Font Awesome para icones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header Superior -->
    <header class="header-main">
        <div class="header-left">
            <button class="btn-sidebar-toggle" onclick="toggleSidebar()" title="Menu">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?php echo APP_URL; ?>dashboard" class="logo-link" title="Dashboard">
                
                <span class="logo-texto">Sistema Amazon</span>
            </a>
        </div>
        <div class="header-right">
            <?php if (estaLogado()): ?>
            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
                    <?php echo h($_SESSION['usuario_nome']); ?>
                </span>
                <span class="user-cargo badge-<?php echo strtolower($_SESSION['usuario_cargo']); ?>">
                    <?php echo h($_SESSION['usuario_cargo'] === 'ADMIN' ? 'Administrador' : 'Vistoriador'); ?>
                </span>
                <a href="<?php echo APP_URL; ?>login?action=logout" class="btn-logout" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Mensagens -->
    <?php
    if (function_exists('getMensagem')) {
        $msg = getMensagem();
        if ($msg):
            $icones = [
                'success' => 'fa-check-circle',
                'error'   => 'fa-exclamation-circle',
                'warning' => 'fa-exclamation-triangle',
                'info'    => 'fa-info-circle'
            ];
            $icon = $icones[$msg['tipo']] ?? 'fa-info-circle';
    ?>
    <div class="message message-<?php echo h($msg['tipo']); ?>" id="mensagem" onclick="this.remove()">
        <i class="fas <?php echo $icon; ?>"></i>
        <span><?php echo h($msg['texto']); ?></span>
        <button class="close-msg" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php
        endif;
    }
    ?>

    <!-- Conteudo Principal -->
    <div class="container-main">