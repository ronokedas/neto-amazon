<?php
/**
 * AUTENTICACAO DO SISTEMA ERP
 * 
 * Funcoes de verificacao de sessao e permissoes
 */

// Verificar se esta logado
function estaLogado() {
    return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
}

// Verificar cargo do usuario logado
function getCargo() {
    return $_SESSION['usuario_cargo'] ?? null;
}

// Verificar se usuario tem permissao para acessar determinado modulo
function podeAcessar($modulo) {
    if (!estaLogado()) {
        return false;
    }
    
    $cargo = getCargo();
    
    // ADMIN tem acesso a tudo
    if ($cargo === 'ADMIN') {
        return true;
    }
    
    // VENDEDOR tem acesso similar ao ADMIN, exceto usuarios e configuracoes
    if ($cargo === 'VENDEDOR') {
        $modulosPermitidos = [
            'dashboard',
            'clientes',
            'embarcacoes',
            'pessoas',
            'vistorias',
            'agendamentos',
            'documentacao',
            'comercial',
            'emails'
        ];
        return in_array($modulo, $modulosPermitidos);
    }
    

    // VISTORIADOR tem acesso restrito
    $modulosPermitidos = [
        'dashboard',
        'login',
        'embarcacoes',
        'pessoas', 
        'vistorias'
    ];
    // Módulos de ADMIN: documentacao, financeiro, usuarios
    
    return in_array($modulo, $modulosPermitidos);
}

// Redirecionar para login se nao estiver logado
function requireLogin() {
    if (!estaLogado()) {
        header('Location: ' . APP_URL . 'login');
        exit;
    }
}

// Redirecionar se usuario nao tiver permissao
function requireCargo($cargoRequerido) {
    requireLogin();
    
    $cargo = getCargo();
    
    if (is_array($cargoRequerido)) {
        // Aceita array de cargos: ['ADMIN', 'VENDEDOR']
        if (!in_array($cargo, $cargoRequerido)) {
            header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
            exit;
        }
    } else {
        // Aceita string simples: 'ADMIN'
        if ($cargo !== $cargoRequerido) {
            header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
            exit;
        }
    }
}

// Inicializar sessao para o usuario
function login($usuario) {
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_cargo'] = $usuario['cargo'];
    $_SESSION['usuario_logado'] = true;
    $_SESSION['login_time'] = time();
}

// Encerrar sessao
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . 'login');
    exit;
}

// Verificar se a sessao expirou (30 minutos)
function verificarSessao() {
    if (!estaLogado() || (time() - ($_SESSION['login_time'] ?? 0)) > 1800) {
        logout();
    }
}

// Alias para compatibilidade com o modulo
function verificar_sessao() {
    verificarSessao();
    requireLogin();
}

// Verificar se o usuario logado possui o cargo especificado
function verificar_cargo($cargoRequerido) {
    requireCargo($cargoRequerido);
}

// Verificar se o usuario logado e VENDEDOR
function is_vendedor() {
    return getCargo() === 'VENDEDOR';
}

// Obter usuario logado por ID
function getUsuarioLogado() {
    global $pdo;
    if (!estaLogado()) return null;
    $stmt = $pdo->prepare("SELECT id, nome, email, cargo, ativo FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
