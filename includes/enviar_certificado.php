<?php
/**
 * ENVIO DE CERTIFICADO POR E-MAIL
 * Função compartilhada para CSN, CNBL e CNARQ
 * 
 * Uso:
 *   require_once __DIR__ . '/../../includes/enviar_certificado.php';
 *   enviarCertificadoEmail($pdo, $certificado_id, $tabela, $tipo_label, $pdf_relative_path);
 */

require_once __DIR__ . '/mailer.php';

/**
 * Envia certificado por e-mail, anexando o PDF e registrando no email_logs
 *
 * @param PDO    $pdo               Conexão PDO
 * @param string $certificado_id    UUID do certificado
 * @param string $tabela            Nome da tabela (certificados_csn, certificados_cnbl, certificados_cnarq)
 * @param string $tipo_label        Label do tipo (ex: "CSN", "CNBL", "CNARQ")
 * @param string $pdf_rel_path      Caminho relativo ao PDF (ex: "modules/documentacao/certificados/pdf.php" ou "modules/documentacao/cnbl/pdf.php" ou "modules/documentacao/cnarq/pdf.php")
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function enviarCertificadoEmail(PDO $pdo, string $certificado_id, string $tabela, string $tipo_label, string $pdf_rel_path): array
{
    try {
        $mapas = [
            'certificados_csn' => 'id, numero, token_assinatura, nome_embarcacao, data_emissao, data_validade, status, NULL AS email_destinatario',
            'certificados_cnbl' => 'id, numero, token_assinatura, nome_embarcacao, data_emissao, data_validade, status, NULL AS email_destinatario',
            'certificados_cnarq' => 'id, numero, token_assinatura, nome_embarcacao, data_emissao, data_validade, status, NULL AS email_destinatario',
            'certificados_lc' => 'id, numero_lc AS numero, token_assinatura, nome_embarcacao, data_emissao, data_validade, status, NULL AS email_destinatario',
            'certificados_lp' => 'id, numero_lp AS numero, token_assinatura, nome_embarcacao, data_emissao, validade_data AS data_validade, status, NULL AS email_destinatario',
            'certificados_cht' => 'id, COALESCE(numero_certificado, numero_relatorio_ht) AS numero, token_assinatura, profissional_empresa AS nome_embarcacao, data_emissao, data_validade, status, email_destinatario',
        ];
        if (!isset($mapas[$tabela])) {
            return ['success' => false, 'message' => 'Tipo de documento não suportado para envio.'];
        }

        $campos_comuns = $mapas[$tabela];
        $stmt = $pdo->prepare("SELECT {$campos_comuns} FROM {$tabela} WHERE id = :id AND ativo = 1");
        $stmt->execute([':id' => $certificado_id]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            return ['success' => false, 'message' => 'Certificado não encontrado.'];
        }

        if ($tabela === 'certificados_cht') {
            $cliente = [
                'cliente_nome' => $cert['nome_embarcacao'],
                'cliente_email' => $cert['email_destinatario'],
            ];
        } else {
            $stmtCli = $pdo->prepare("
                SELECT c.nome AS cliente_nome, c.email AS cliente_email
                FROM clientes c
                INNER JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id
                INNER JOIN embarcacoes e ON e.id = ce.embarcacao_id
                WHERE e.nome = :emb_nome AND c.status = 'ATIVO'
                LIMIT 1
            ");
            $stmtCli->execute([':emb_nome' => $cert['nome_embarcacao']]);
            $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);
        }

        if (!$cliente || empty($cliente['cliente_email'])) {
            return ['success' => false, 'message' => 'Cliente não encontrado ou sem e-mail cadastrado para esta embarcação.'];
        }

        // Carregar template
        $templatePath = __DIR__ . '/../templates/email/certificado.html';
        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => 'Template de e-mail não encontrado.'];
        }
        $htmlBody = file_get_contents($templatePath);

        // Link de assinatura
        $link_assinatura = APP_URL . 'assinar/' . $cert['token_assinatura'];

        // Substituir placeholders
        $replacements = [
            '{{TIPO_CERTIFICADO}}'    => "Certificado {$tipo_label}",
            '{{NOME_CLIENTE}}'        => h($cliente['cliente_nome']),
            '{{EMBARCACAO_NOME}}'     => h($cert['nome_embarcacao']),
            '{{NUMERO_CERTIFICADO}}'  => h($cert['numero']),
            '{{DATA_EMISSAO}}'        => date('d/m/Y', strtotime($cert['data_emissao'])),
            '{{DATA_VALIDADE}}'       => !empty($cert['data_validade']) ? date('d/m/Y', strtotime($cert['data_validade'])) : 'Não se aplica',
            '{{LINK_ASSINATURA}}'     => $link_assinatura,
            '{{EMAIL_CONTATO}}'       => EMAIL_CONTATO,
            '{{TELEFONE_CONTATO}}'    => TELEFONE_CONTATO,
        ];
        $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

        // Gerar PDF via requisição HTTP interna com cookie de sessão
        $pdfDir  = __DIR__ . '/../temp_pdf/';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        $pdfFile = $pdfDir . $tabela . '_' . $certificado_id . '.pdf';

        $pdfUrl = APP_URL . $pdf_rel_path . '?id=' . urlencode($certificado_id);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Cookie: " . session_name() . "=" . session_id() . "\r\n",
                'timeout' => 30,
            ]
        ];
        $context = stream_context_create($opts);
        $pdfContent = @file_get_contents($pdfUrl, false, $context);

        if (!$pdfContent || strlen($pdfContent) < 200) {
            return ['success' => false, 'message' => 'Não foi possível gerar o PDF do certificado.'];
        }
        file_put_contents($pdfFile, $pdfContent);

        // Incluir mailer e enviar
        $resultado = enviarEmail(
            $cliente['cliente_email'],
            $cliente['cliente_nome'],
            "{$tipo_label} - {$cert['numero']}",
            $htmlBody,
            [$pdfFile]
        );

        // Registrar no log de e-mails
        $stmtLog = $pdo->prepare("
            INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
            VALUES (UUID(), :destinatario, :assunto, 'certificado', :referencia_tipo, :referencia_id, :status, :mensagem_erro, :enviado_por)
        ");
        $stmtLog->execute([
            ':destinatario'    => $cliente['cliente_email'],
            ':assunto'         => "{$tipo_label} - {$cert['numero']}",
            ':referencia_tipo' => $tabela,
            ':referencia_id'   => $certificado_id,
            ':status'          => $resultado['success'] ? 'enviado' : 'erro',
            ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
            ':enviado_por'     => $_SESSION['usuario_id'] ?? null,
        ]);

        // Limpar PDF temporário
        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }

        return $resultado;

    } catch (Exception $e) {
        error_log('Erro ao enviar certificado por e-mail: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}
