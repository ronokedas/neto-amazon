<?php
/**
 * MÓDULO: Documentação > Licença Provisória (LP)
 * Actions: Salvar, Excluir (soft delete)
 * Numeração automática AM-LP:{n}/{ano}
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
verificar_cargo('ADMIN');

$action = $_POST['action'] ?? '';

// ============================================
// SALVAR LICENÇA
// ============================================
if ($action === 'salvar') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);

    $tipo_licenca = $_POST['tipo_licenca'] ?? 'construcao';
    $nome_embarcacao = trim($_POST['nome_embarcacao'] ?? '');
    $tipo_embarcacao = trim($_POST['tipo_embarcacao'] ?? '');
    $numero_casco = trim($_POST['numero_casco'] ?? '');
    $material_casco = trim($_POST['material_casco'] ?? '');
    $comprimento_total = $_POST['comprimento_total'] !== '' ? $_POST['comprimento_total'] : null;
    $boca_moldada = $_POST['boca_moldada'] !== '' ? $_POST['boca_moldada'] : null;
    $pontal_moldado = $_POST['pontal_moldado'] !== '' ? $_POST['pontal_moldado'] : null;
    $proprietario_nome = trim($_POST['proprietario_nome'] ?? '');
    $proprietario_cpf_cnpj = trim($_POST['proprietario_cpf_cnpj'] ?? '');
    $proprietario_endereco = trim($_POST['proprietario_endereco'] ?? '');
    $estaleiro_nome = trim($_POST['estaleiro_nome'] ?? '');
    $estaleiro_cpf_cnpj = trim($_POST['estaleiro_cpf_cnpj'] ?? '');
    $estaleiro_endereco = trim($_POST['estaleiro_endereco'] ?? '');
    $observacoes_exigencias = trim($_POST['observacoes_exigencias'] ?? '');
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $validade_dias = $_POST['validade_dias'] !== '' ? (int)$_POST['validade_dias'] : null;
    $validade_data = $_POST['validade_data'] ?: null;
    $assinante_nome = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');
    $status = $_POST['status'] ?? 'rascunho';
    if (!in_array($status, ['rascunho', 'emitido', 'cancelado'])) {
        $status = 'rascunho';
    }

    if (!in_array($tipo_licenca, ['construcao', 'alteracao', 'reclassificacao', 'lcec'])) {
        setMensagem('error', 'Tipo de licença inválido.');
        redirecionar(APP_URL . 'documentacao/lp/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($nome_embarcacao)) {
        setMensagem('error', 'O nome da embarcação é obrigatório.');
        redirecionar(APP_URL . 'documentacao/lp/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_emissao)) {
        setMensagem('error', 'A data de emissão é obrigatória.');
        redirecionar(APP_URL . 'documentacao/lp/form' . ($editando ? "?id={$id}" : ''));
    }

    try {
        if ($editando) {
            $sql = "UPDATE certificados_lp SET
                        tipo_licenca = :tipo_licenca,
                        nome_embarcacao = :nome_embarcacao,
                        tipo_embarcacao = :tipo_embarcacao,
                        numero_casco = :numero_casco,
                        material_casco = :material_casco,
                        comprimento_total = :comprimento_total,
                        boca_moldada = :boca_moldada,
                        pontal_moldado = :pontal_moldado,
                        proprietario_nome = :proprietario_nome,
                        proprietario_cpf_cnpj = :proprietario_cpf_cnpj,
                        proprietario_endereco = :proprietario_endereco,
                        estaleiro_nome = :estaleiro_nome,
                        estaleiro_cpf_cnpj = :estaleiro_cpf_cnpj,
                        estaleiro_endereco = :estaleiro_endereco,
                        observacoes_exigencias = :observacoes_exigencias,
                        data_emissao = :data_emissao,
                        validade_dias = :validade_dias,
                        validade_data = :validade_data,
                        assinante_nome = :assinante_nome,
                        assinante_titulo = :assinante_titulo,
                        assinante_registro = :assinante_registro,
                        status = :status
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo_licenca' => $tipo_licenca,
                ':nome_embarcacao' => $nome_embarcacao,
                ':tipo_embarcacao' => $tipo_embarcacao,
                ':numero_casco' => $numero_casco,
                ':material_casco' => $material_casco,
                ':comprimento_total' => $comprimento_total,
                ':boca_moldada' => $boca_moldada,
                ':pontal_moldado' => $pontal_moldado,
                ':proprietario_nome' => $proprietario_nome,
                ':proprietario_cpf_cnpj' => $proprietario_cpf_cnpj,
                ':proprietario_endereco' => $proprietario_endereco,
                ':estaleiro_nome' => $estaleiro_nome,
                ':estaleiro_cpf_cnpj' => $estaleiro_cpf_cnpj,
                ':estaleiro_endereco' => $estaleiro_endereco,
                ':observacoes_exigencias' => $observacoes_exigencias,
                ':data_emissao' => $data_emissao,
                ':validade_dias' => $validade_dias,
                ':validade_data' => $validade_data,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':id' => $id,
            ]);

            $numero_lp = $pdo->prepare("SELECT numero_lp FROM certificados_lp WHERE id = :id");
            $numero_lp->execute([':id' => $id]);
            $numero = $numero_lp->fetch()['numero_lp'];

        } else {
            $numero = gerarNumeroDocumento('LP', 'AM-LP');
            $token = bin2hex(random_bytes(32));
            $id = gerarUUID();

            $sql = "INSERT INTO certificados_lp (
                        id, numero_lp, token_assinatura,
                        tipo_licenca,
                        nome_embarcacao, tipo_embarcacao, numero_casco,
                        material_casco, comprimento_total, boca_moldada, pontal_moldado,
                        proprietario_nome, proprietario_cpf_cnpj, proprietario_endereco,
                        estaleiro_nome, estaleiro_cpf_cnpj, estaleiro_endereco,
                        observacoes_exigencias,
                        data_emissao, validade_dias, validade_data,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, criado_por
                    ) VALUES (
                        :id, :numero_lp, :token_assinatura,
                        :tipo_licenca,
                        :nome_embarcacao, :tipo_embarcacao, :numero_casco,
                        :material_casco, :comprimento_total, :boca_moldada, :pontal_moldado,
                        :proprietario_nome, :proprietario_cpf_cnpj, :proprietario_endereco,
                        :estaleiro_nome, :estaleiro_cpf_cnpj, :estaleiro_endereco,
                        :observacoes_exigencias,
                        :data_emissao, :validade_dias, :validade_data,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        :status, :criado_por
                    )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':numero_lp' => $numero,
                ':token_assinatura' => $token,
                ':tipo_licenca' => $tipo_licenca,
                ':nome_embarcacao' => $nome_embarcacao,
                ':tipo_embarcacao' => $tipo_embarcacao,
                ':numero_casco' => $numero_casco,
                ':material_casco' => $material_casco,
                ':comprimento_total' => $comprimento_total,
                ':boca_moldada' => $boca_moldada,
                ':pontal_moldado' => $pontal_moldado,
                ':proprietario_nome' => $proprietario_nome,
                ':proprietario_cpf_cnpj' => $proprietario_cpf_cnpj,
                ':proprietario_endereco' => $proprietario_endereco,
                ':estaleiro_nome' => $estaleiro_nome,
                ':estaleiro_cpf_cnpj' => $estaleiro_cpf_cnpj,
                ':estaleiro_endereco' => $estaleiro_endereco,
                ':observacoes_exigencias' => $observacoes_exigencias,
                ':data_emissao' => $data_emissao,
                ':validade_dias' => $validade_dias,
                ':validade_data' => $validade_data,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':criado_por' => $_SESSION['usuario_id'] ?? null,
            ]);
        }

        setMensagem('success', 'Licença Provisória ' . ($editando ? 'atualizada' : 'criada') . ' com sucesso.');
        redirecionar(APP_URL . 'documentacao/lp');

    } catch (Exception $e) {
        setMensagem('error', 'Erro ao salvar licença: ' . $e->getMessage());
        redirecionar(APP_URL . 'documentacao/lp/form' . ($editando ? "?id={$id}" : ''));
    }
}

// ============================================
// EXCLUIR LICENÇA (Soft Delete)
// ============================================
if ($action === 'excluir') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        setMensagem('error', 'ID da licença não informado.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    try {
        $stmt = $pdo->prepare("UPDATE certificados_lp SET ativo = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        log_atividade('licenca_lp_excluida', "Licença LP ID: {$id}");

        setMensagem('success', 'Licença excluída com sucesso.');
    } catch (Exception $e) {
        setMensagem('error', 'Erro ao excluir licença: ' . $e->getMessage());
    }

    redirecionar(APP_URL . 'documentacao/lp');
}

// ============================================
// ENVIAR LINK DE ASSINATURA POR E-MAIL
// ============================================
if ($action === 'enviar_assinatura') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID da licença não informado.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    require_once __DIR__ . '/../../../includes/enviar_assinatura.php';

    $resultado = enviarAssinaturaEmail(
        $pdo,
        $id,
        'certificados_lp',
        'LP'
    );

    if ($resultado['success']) {
        log_atividade('licenca_lp_assinatura_enviada', "Link de assinatura LP ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/lp');
}

// ============================================
// ENVIAR CERTIFICADO POR E-MAIL
// ============================================
if ($action === 'enviar_certificado') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID da licença não informado.');
        redirecionar(APP_URL . 'documentacao/lp');
    }

    require_once __DIR__ . '/../../../includes/enviar_certificado.php';

    $resultado = enviarCertificadoEmail(
        $pdo,
        $id,
        'certificados_lp',
        'LP',
        'documentacao/lp/pdf'
    );

    if ($resultado['success']) {
        log_atividade('licenca_lp_enviada_email', "Licença LP ID: {$id} enviada por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/lp');
}

// Ação inválida
setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/lp');