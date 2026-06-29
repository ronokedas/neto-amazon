<?php
/**
 * FUNÇÕES UTILITÁRIAS DO SISTEMA ERP
 */

// Sanitizar input
function sanitizar($dados) {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
    return $dados;
}

// Alias para compatibilidade
if (!function_exists('h')) {
    function h($dados) {
        return sanitizar($dados);
    }
}

if (!function_exists('sanitize')) {
    function sanitize($dados) {
        return sanitizar($dados);
    }
}


// Gerar CSRF token
function gerarCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar CSRF token
function verificarCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirecionar
function redirecionar($url) {
    header('Location: ' . $url);
    exit;
}

// Definir mensagem de sucesso/erro
function setMensagem($tipo, $mensagem) {
    $_SESSION['mensagem'] = [
        'tipo' => $tipo, // success, error, warning, info
        'texto' => $mensagem
    ];
}

// Obter e limpar mensagem
function getMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $msg = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return $msg;
    }
    return null;
}

// Formatar moeda
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Formatador de CPF
function formatarCPF($cpf) {
    if (empty($cpf)) return '';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Validar CPF
function validarCPF($cpf) {
    if (empty($cpf)) return false;
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $i = 0; $i < $t; $i++) {
            $d += $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$t] != $d) return false;
    }
    return true;
}

/**
 * Validar CNPJ
 */
function validarCNPJ($cnpj) {
    if (empty($cnpj)) return false;
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

    // Validar dígitos verificadores
    for ($t = 12; $t < 14; $t++) {
        $d = 0;
        $m = 5;
        for ($i = 0; $i < $t; $i++) {
            $d += $cnpj[$i] * $m;
            $m = ($m == 2) ? 9 : $m - 1;
        }
        $d = (($d % 11) < 2) ? 0 : (11 - ($d % 11));
        if ($cnpj[$t] != $d) return false;
    }
    return true;
}

// Validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Gerar UUID
function gerarUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Upload de arquivo
function uploadArquivo($arquivo, $pasta = 'uploads/') {
    if (!isset($arquivo['tmp_name']) || empty($arquivo['tmp_name'])) {
        return ['success' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
    }
    
    $nomeArquivo = uniqid() . '_' . basename($arquivo['name']);
    $caminho = $pasta . $nomeArquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        return ['success' => true, 'caminho' => $caminho];
    }
    
    return ['success' => false, 'mensagem' => 'Erro ao fazer upload.'];
}

// Data em português
function formatarData($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y');
}

// Data completa em português
function formatarDataCompleta($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y - H:i');
}

// Hook para eventos do sistema
function hook($nome, $dados = []) {
    global $hooks;
    if (isset($hooks[$nome])) {
        foreach ($hooks[$nome] as $callback) {
            $callback($dados);
        }
    }
}

// Register hook callback
function addHook($nome, $callback) {
    global $hooks;
    $hooks[$nome][] = $callback;
}

// H Função auxiliar para echo seguro
function h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Obter total de registros por mês
function getTotalPorMes($tabela, $campo_data, $ano) {
    global $pdo;
    $sql = "SELECT COUNT(*) as total FROM {$tabela} 
            WHERE YEAR({$campo_data}) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ano]);
    return $stmt->fetch()['total'];
}

// Log de atividades do sistema (arquivo)
function log_atividade($acao, $descricao = '', $usuario_id = null) {
    global $pdo;
    $usuario_id = $usuario_id ?? ($_SESSION['usuario_id'] ?? 'sistema');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $data = date('Y-m-d H:i:s');
    
    // Tentar salvar no banco se a tabela existir
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, descricao, ip, criado_em) 
                               VALUES (:usuario, :acao, :descricao, :ip, :data)");
        $stmt->execute([
            ':usuario'  => $usuario_id,
            ':acao'     => $acao,
            ':descricao' => $descricao,
            ':ip'       => $ip,
            ':data'     => $data,
        ]);
    } catch (Exception $e) {
        // Se a tabela não existe, salvar em arquivo
        $logs_dir = __DIR__ . '/../logs/';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
        $arquivo = $logs_dir . 'atividades_' . date('Y-m-d') . '.log';
        $linha = "[{$data}] [{$ip}] [{$usuario_id}] {$acao}: {$descricao}" . PHP_EOL;
        @file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Gera um número sequencial para documentos do sistema.
 * Usa SELECT FOR UPDATE dentro de transação para garantir atomicidade
 * e evitar números duplicados em acesso simultâneo.
 * 
 * @param string $tipo    Tipo do documento (ex: 'CSN')
 * @param string $prefixo Prefixo do número (ex: 'AM-CSN')
 * @param int    $ano     Ano do documento (padrão: ano atual)
 * @return string         Número formatado (ex: 'AM-CSN-7/26')
 */
function gerarNumeroDocumento($tipo, $prefixo, $ano = null) {
    global $pdo;

    if ($ano === null) {
        $ano = (int) date('Y');
    }

    $ano_curto = substr($ano, -2);

    try {
        // Trava a linha com FOR UPDATE para evitar race condition
        // (requer que a transação seja gerenciada pelo código que chama esta função)
        $sql = "SELECT ultimo_numero FROM sequenciais_documentos 
                WHERE tipo_documento = :tipo AND ano = :ano 
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo' => $tipo,
            ':ano'  => $ano,
        ]);
        $row = $stmt->fetch();

        if ($row) {
            // Já existe: incrementa
            $numero = (int)$row['ultimo_numero'] + 1;
            $sqlUpdate = "UPDATE sequenciais_documentos 
                          SET ultimo_numero = :numero 
                          WHERE tipo_documento = :tipo AND ano = :ano";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':numero' => $numero,
                ':tipo'   => $tipo,
                ':ano'    => $ano,
            ]);
        } else {
            // Não existe: insere começando em 1
            $numero = 1;
            $sqlInsert = "INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero) 
                          VALUES (:tipo, :ano, 1)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':tipo' => $tipo,
                ':ano'  => $ano,
            ]);
        }

        // Formata: PREFIXO-NUMERO/ANO (ex: AM-CSN-7/26)
        return $prefixo . '-' . $numero . '/' . $ano_curto;

    } catch (Exception $e) {
        // Log do erro
        log_atividade('erro_sequencial', 'Erro ao gerar número documento: ' . $e->getMessage());
        
        // Fallback: gera número baseado no timestamp para não quebrar o fluxo
        return $prefixo . '-' . date('mdHis') . '/' . $ano_curto;
    }
}

// Paginação simples
function paginar($tabela, $por_pagina, $pagina_atual) {
    global $pdo;
    
    $total_registros = $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetch()[0];
    $total_paginas = ceil($total_registros / $por_pagina);
    $inicio = ($pagina_atual - 1) * $por_pagina;
    
    return [
        'total' => $total_registros,
        'paginas' => $total_paginas,
        'atual' => $pagina_atual,
        'inicio' => $inicio,
        'por_pagina' => $por_pagina
    ];
}