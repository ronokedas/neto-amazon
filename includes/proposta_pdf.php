<?php
/**
 * Helpers para gerar PDF de proposta sem depender de chamada HTTP interna.
 */

function gerarPropostaPdfString(string $propostaId): string
{
    global $pdo;

    if (trim($propostaId) === '') {
        throw new InvalidArgumentException('ID da proposta nao informado.');
    }

    $oldId = $GLOBALS['PROPOSTA_PDF_ID'] ?? null;
    $oldReturn = $GLOBALS['PROPOSTA_PDF_RETURN_STRING'] ?? null;

    $GLOBALS['PROPOSTA_PDF_ID'] = $propostaId;
    $GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = true;

    ob_start();
    $pdfContent = require __DIR__ . '/../modules/comercial/pdf.php';
    $unexpectedOutput = ob_get_clean();

    if ($oldId === null) {
        unset($GLOBALS['PROPOSTA_PDF_ID']);
    } else {
        $GLOBALS['PROPOSTA_PDF_ID'] = $oldId;
    }

    if ($oldReturn === null) {
        unset($GLOBALS['PROPOSTA_PDF_RETURN_STRING']);
    } else {
        $GLOBALS['PROPOSTA_PDF_RETURN_STRING'] = $oldReturn;
    }

    if (!is_string($pdfContent) || strlen($pdfContent) < 200) {
        throw new RuntimeException('Nao foi possivel gerar o PDF da proposta para anexar.');
    }

    if ($unexpectedOutput !== '') {
        error_log('Saida inesperada ao gerar PDF da proposta: ' . substr($unexpectedOutput, 0, 300));
    }

    return $pdfContent;
}

function salvarPropostaPdfTemporario(string $propostaId, string $diretorio): string
{
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    $arquivo = rtrim($diretorio, '/\\') . DIRECTORY_SEPARATOR . 'proposta_' . $propostaId . '.pdf';
    file_put_contents($arquivo, gerarPropostaPdfString($propostaId));

    return $arquivo;
}
