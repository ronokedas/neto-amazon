<?php
/**
 * Gera PDFs dos documentos em arquivo temporario sem depender de APP_URL.
 */

function gerarDocumentoPdfTemporario(string $documentoId, string $pdfRelPath, string $prefixo, string $diretorio): string
{
    global $pdo;

    if (trim($documentoId) === '') {
        throw new InvalidArgumentException('ID do documento nao informado.');
    }

    $pdfRelPath = trim($pdfRelPath, '/');
    if (!preg_match('/^documentacao\/[a-z0-9_\/-]+\/pdf$/i', $pdfRelPath)) {
        throw new InvalidArgumentException('Caminho de PDF invalido.');
    }

    $pdfScript = __DIR__ . '/../modules/' . $pdfRelPath . '.php';
    if (!is_file($pdfScript)) {
        throw new RuntimeException('Gerador de PDF nao encontrado.');
    }

    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    $salvar_pdf_caminho = rtrim($diretorio, '/\\') . DIRECTORY_SEPARATOR . $prefixo . '_' . $documentoId . '.pdf';
    $oldGet = $_GET;
    $_GET = ['id' => $documentoId];

    ob_start();
    require $pdfScript;
    $unexpectedOutput = ob_get_clean();

    $_GET = $oldGet;

    if (!is_file($salvar_pdf_caminho) || filesize($salvar_pdf_caminho) < 200) {
        throw new RuntimeException('Nao foi possivel gerar o PDF do documento.');
    }

    if ($unexpectedOutput !== '') {
        error_log('Saida inesperada ao gerar PDF do documento: ' . substr($unexpectedOutput, 0, 300));
    }

    return $salvar_pdf_caminho;
}
