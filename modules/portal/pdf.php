<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$tipo = strtolower(trim($_GET['tipo'] ?? ''));
$id = trim($_GET['id'] ?? '');
$configs = clientePortalConfigDocumentos();

if (!isset($configs[$tipo])) {
    http_response_code(404);
    die('Tipo de documento inválido.');
}

$doc = clientePortalDocumento($pdo, clientePortalId(), $tipo, $id);
if (!$doc) {
    http_response_code(403);
    die('Documento não encontrado ou sem permissão de acesso.');
}

$pdfRelPath = $configs[$tipo]['pdf'];
$pdfScript = __DIR__ . '/../' . $pdfRelPath . '.php';
if (!is_file($pdfScript)) {
    http_response_code(500);
    die('Gerador de PDF não encontrado.');
}

$_GET = ['id' => $id];
require $pdfScript;
