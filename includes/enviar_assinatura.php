<?php
/**
 * ENVIO DE LINK DE ASSINATURA POR E-MAIL
 * Função compartilhada para CSN, CNBL e CNARQ
 *
 * Uso:
 *   require_once __DIR__ . '/../../includes/enviar_assinatura.php';
 *   enviarAssinaturaEmail($pdo, $certificado_id, $tabela, $tipo_label);
 */

require_once __DIR__ . '/mailer.php';

/**
 * Envia link de assinatura digital por e-mail, registrando no email_logs
 *
 * @param PDO    $pdo               Conexão PDO
 * @param string $certificado_id    UUID do certificado
 * @param string $tabela            Nome da tabela (certificados_csn, certificados_cnbl, certificados_cnarq)
 * @param string $tipo_label        Label do tipo (ex: "CSN", "CNBL", "CNARQ")
 *
 * @return array ['success' => bool, 'message' => string]
 */
function enviarAssinaturaEmail(PDO $pdo, string $certificado_id, string $tabela, string $tipo_label): array
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

        $stmt = $pdo->prepare("SELECT {$mapas[$tabela]} FROM {$tabela} WHERE id = :id AND ativo = 1");
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
        $templatePath = __DIR__ . '/../templates/email/assinatura.html';
        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => 'Template de e-mail não encontrado.'];
        }
        $htmlBody = file_get_contents($templatePath);

        $link_assinatura = APP_URL . 'assinar/' . $cert['token_assinatura'];

        $replacements = [
            '{{TIPO_CERTIFICADO}}'   => "Certificado {$tipo_label}",
            '{{NOME_CLIENTE}}'       => h($cliente['cliente_nome']),
            '{{EMBARCACAO_NOME}}'    => h($cert['nome_embarcacao']),
            '{{NUMERO_CERTIFICADO}}' => h($cert['numero']),
            '{{DATA_EMISSAO}}'       => date('d/m/Y', strtotime($cert['data_emissao'])),
            '{{DATA_VALIDADE}}'      => !empty($cert['data_validade']) ? date('d/m/Y', strtotime($cert['data_validade'])) : 'Não se aplica',
            '{{LINK_ASSINATURA}}'    => $link_assinatura,
            '{{EMAIL_CONTATO}}'      => EMAIL_CONTATO,
            '{{TELEFONE_CONTATO}}'   => TELEFONE_CONTATO,
        ];
        $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

        // Enviar via mailer central
        require_once __DIR__ . '/mailer.php';

        $resultado = enviarEmail(
            $cliente['cliente_email'],
            $cliente['cliente_nome'],
            "Assinatura Digital - {$tipo_label} {$cert['numero']}",
            $htmlBody
        );

        // Registrar no log de e-mails
        $stmtLog = $pdo->prepare("
            INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
            VALUES (UUID(), :destinatario, :assunto, 'assinatura', :referencia_tipo, :referencia_id, :status, :mensagem_erro, :enviado_por)
        ");
        $stmtLog->execute([
            ':destinatario'    => $cliente['cliente_email'],
            ':assunto'         => "Assinatura Digital - {$tipo_label} {$cert['numero']}",
            ':referencia_tipo' => $tabela,
            ':referencia_id'   => $certificado_id,
            ':status'          => $resultado['success'] ? 'enviado' : 'erro',
            ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
            ':enviado_por'     => $_SESSION['usuario_id'] ?? null,
        ]);

        return $resultado;

    } catch (Exception $e) {
        error_log('Erro ao enviar link de assinatura: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}
