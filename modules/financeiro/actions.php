<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: actions.php - Processar acoes (salvar, excluir lancamentos)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function normalizarArquivosUpload($campo) {
    $arquivos = $_FILES[$campo] ?? null;
    if (!$arquivos || empty($arquivos['name'])) {
        return [];
    }

    if (!is_array($arquivos['name'])) {
        return [$arquivos];
    }

    $normalizados = [];
    foreach ($arquivos['name'] as $idx => $nome) {
        $normalizados[] = [
            'name' => $nome,
            'type' => $arquivos['type'][$idx] ?? '',
            'tmp_name' => $arquivos['tmp_name'][$idx] ?? '',
            'error' => $arquivos['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
            'size' => $arquivos['size'][$idx] ?? 0,
        ];
    }

    return $normalizados;
}

function salvarComprovantesFinanceiros(PDO $pdo, string $lancamentoId, ?string $usuarioId): array {
    $arquivos = normalizarArquivosUpload('comprovantes');
    if (empty($arquivos)) {
        return [];
    }

    $permitidos = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $maxBytes = 10 * 1024 * 1024;
    $pastaRelativa = 'uploads/financeiro/comprovantes/';
    $pastaFisica = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'financeiro' . DIRECTORY_SEPARATOR . 'comprovantes' . DIRECTORY_SEPARATOR;
    $enviados = [];

    if (!is_dir($pastaFisica) && !mkdir($pastaFisica, 0755, true)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de comprovantes.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO financeiro_comprovantes
            (id, lancamento_id, nome_original, nome_arquivo, caminho, mime_type, tamanho, criado_por)
        VALUES
            (:id, :lancamento_id, :nome_original, :nome_arquivo, :caminho, :mime_type, :tamanho, :criado_por)
    ");

    foreach ($arquivos as $arquivo) {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erro ao enviar o comprovante "' . ($arquivo['name'] ?? '') . '".');
        }

        if (($arquivo['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('O comprovante "' . ($arquivo['name'] ?? '') . '" ultrapassa o limite de 10MB.');
        }

        $nomeOriginal = basename((string)$arquivo['name']);
        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if (!isset($permitidos[$extensao])) {
            throw new RuntimeException('Tipo de comprovante nao permitido. Envie PDF, JPG, PNG ou WEBP.');
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $arquivo['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
        }
        if ($mime === '') {
            $mime = $arquivo['type'] ?? $permitidos[$extensao];
        }

        if ($mime !== $permitidos[$extensao]) {
            throw new RuntimeException('O arquivo "' . $nomeOriginal . '" nao parece ser um ' . strtoupper($extensao) . ' valido.');
        }

        $id = gerarUUID();
        $nomeArquivo = $lancamentoId . '_' . $id . '.' . $extensao;
        $destino = $pastaFisica . $nomeArquivo;
        $caminhoRelativo = $pastaRelativa . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException('Nao foi possivel salvar o comprovante "' . $nomeOriginal . '".');
        }

        $stmt->execute([
            ':id' => $id,
            ':lancamento_id' => $lancamentoId,
            ':nome_original' => $nomeOriginal,
            ':nome_arquivo' => $nomeArquivo,
            ':caminho' => $caminhoRelativo,
            ':mime_type' => $mime,
            ':tamanho' => (int)$arquivo['size'],
            ':criado_por' => $usuarioId,
        ]);

        $enviados[] = $nomeOriginal;
    }

    return $enviados;
}

function removerArquivoComprovanteFinanceiro(string $caminhoRelativo): void {
    $baseUploads = realpath(rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads');
    if ($baseUploads === false || $caminhoRelativo === '') {
        return;
    }

    $arquivo = realpath(rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoRelativo));
    if ($arquivo === false || strpos($arquivo, $baseUploads . DIRECTORY_SEPARATOR) !== 0 || !is_file($arquivo)) {
        return;
    }

    unlink($arquivo);
}

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'financeiro');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        $id              = trim($_POST['id'] ?? '');
        $tipo            = $_POST['tipo'] ?? '';
        $frequencia      = $_POST['frequencia'] ?? 'unica';
        $status          = $_POST['status'] ?? 'PAGO';
        $data_vencimento = trim($_POST['data_vencimento'] ?? '');
        $cliente_id      = trim($_POST['cliente_id'] ?? '');
        $data            = trim($_POST['data'] ?? '');
        $descricao       = trim($_POST['descricao'] ?? '');
        $valor           = trim($_POST['valor'] ?? '');
        $categoria       = trim($_POST['categoria'] ?? '');
        $observacoes     = trim($_POST['observacoes'] ?? '');

        if (empty($data)) $data = null;

        // Validacoes
        $erros = [];
        $errosCampos = [];

        if (!in_array($tipo, ['RECEITA', 'DESPESA'])) {
            $erros[] = 'Tipo invalido. Selecione RECEITA ou DESPESA.';
            $errosCampos['tipo'] = 'Selecione Receita ou Despesa.';
        }

        if (empty($descricao)) {
            $erros[] = 'A descricao e obrigatoria.';
            $errosCampos['descricao'] = 'Informe a descricao do lancamento.';
        } elseif (strlen($descricao) < 3) {
            $erros[] = 'A descricao deve ter pelo menos 3 caracteres.';
            $errosCampos['descricao'] = 'Use pelo menos 3 caracteres.';
        }

        // Validar e converter valor (aceitar formato brasileiro 1.234,56)
        $valorLimpo = str_replace(['.', ','], ['', '.'], $valor);
        if (!is_numeric($valorLimpo) || floatval($valorLimpo) <= 0) {
            $erros[] = 'O valor deve ser um numero positivo.';
            $errosCampos['valor'] = 'Informe um valor maior que zero.';
        } else {
            $valorLimpo = number_format(floatval($valorLimpo), 2, '.', '');
        }

        if (empty($data_vencimento)) {
            $erros[] = 'A data de vencimento e obrigatoria.';
            $errosCampos['data_vencimento'] = 'Informe a data de vencimento.';
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros), $errosCampos);
            $url = APP_URL . 'financeiro/form';
            if (!empty($id)) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            $isEdicao = !empty($id);

            $pdo->beginTransaction();
            $lancamentoId = $id;

            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE financeiro_lancamentos SET tipo = :tipo, frequencia = :frequencia, status = :status, data_vencimento = :data_vencimento, cliente_id = :cliente_id, descricao = :descricao, valor = :valor, data = :data, categoria = :categoria, observacoes = :observacoes WHERE id = :id");
                $stmt->execute([
                    ':tipo'            => $tipo,
                    ':frequencia'      => $frequencia,
                    ':status'          => $status,
                    ':data_vencimento' => $data_vencimento,
                    ':cliente_id'      => $cliente_id ?: null,
                    ':descricao'       => $descricao,
                    ':valor'           => $valorLimpo,
                    ':data'            => $data,
                    ':categoria'       => $categoria,
                    ':observacoes'     => $observacoes,
                    ':id'              => $id
                ]);

                // Lógica de recorrência ao pagar
                if ($status === 'PAGO' && $frequencia !== 'unica') {
                    // Verifica se já foi gerado (evitar duplicação).
                    // Para simplificar, verificamos se existe algum lançamento com mesma descricao e data_vencimento maior que a deste
                    $stmtClone = $pdo->prepare("SELECT COUNT(*) as qtd FROM financeiro_lancamentos WHERE descricao = :descricao AND data_vencimento > :dv_atual");
                    $stmtClone->execute([':descricao' => $descricao, ':dv_atual' => $data_vencimento]);
                    $ja_existe = $stmtClone->fetch()['qtd'] > 0;

                    if (!$ja_existe) {
                        // Calcula nova data
                        $meses = 1;
                        if ($frequencia === 'trimestral') $meses = 3;
                        if ($frequencia === 'anual') $meses = 12;

                        $nova_data_vencimento = date('Y-m-d', strtotime("+{$meses} months", strtotime($data_vencimento)));

                        $stmtInsert = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                        $stmtInsert->execute([
                            ':id'              => gerarUUID(),
                            ':tipo'            => $tipo,
                            ':frequencia'      => $frequencia,
                            ':status'          => 'PENDENTE',
                            ':data_vencimento' => $nova_data_vencimento,
                            ':cliente_id'      => $cliente_id ?: null,
                            ':descricao'       => $descricao,
                            ':valor'           => $valorLimpo,
                            ':data'            => null,
                            ':categoria'       => $categoria,
                            ':observacoes'     => $observacoes,
                            ':criado_por'      => $_SESSION['usuario_id'] ?? null
                        ]);
                    }
                }
                $comprovantesEnviados = salvarComprovantesFinanceiros($pdo, $lancamentoId, $_SESSION['usuario_id'] ?? null);

                $pdo->commit();
                $msg = 'Lancamento atualizado com sucesso!';
                if (!empty($comprovantesEnviados)) {
                    $msg .= ' Comprovante(s) anexado(s): ' . implode(', ', $comprovantesEnviados) . '.';
                }
                setMensagem('success', $msg);
            } else {
                // Criar
                $lancamentoId = gerarUUID();
                $stmt = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                $stmt->execute([
                    ':id'              => $lancamentoId,
                    ':tipo'            => $tipo,
                    ':frequencia'      => $frequencia,
                    ':status'          => $status,
                    ':data_vencimento' => $data_vencimento,
                    ':cliente_id'      => $cliente_id ?: null,
                    ':descricao'       => $descricao,
                    ':valor'           => $valorLimpo,
                    ':data'            => $data,
                    ':categoria'       => $categoria,
                    ':observacoes'     => $observacoes,
                    ':criado_por'      => $_SESSION['usuario_id'] ?? null
                ]);

                // Lógica de recorrência se for criado já como PAGO
                if ($status === 'PAGO' && $frequencia !== 'unica') {
                    $meses = 1;
                    if ($frequencia === 'trimestral') $meses = 3;
                    if ($frequencia === 'anual') $meses = 12;

                    $nova_data_vencimento = date('Y-m-d', strtotime("+{$meses} months", strtotime($data_vencimento)));

                    $stmtInsert = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                    $stmtInsert->execute([
                        ':id'              => gerarUUID(),
                        ':tipo'            => $tipo,
                        ':frequencia'      => $frequencia,
                        ':status'          => 'PENDENTE',
                        ':data_vencimento' => $nova_data_vencimento,
                        ':cliente_id'      => $cliente_id ?: null,
                        ':descricao'       => $descricao,
                        ':valor'           => $valorLimpo,
                        ':data'            => null,
                        ':categoria'       => $categoria,
                        ':observacoes'     => $observacoes,
                        ':criado_por'      => $_SESSION['usuario_id'] ?? null
                    ]);
                }
                $comprovantesEnviados = salvarComprovantesFinanceiros($pdo, $lancamentoId, $_SESSION['usuario_id'] ?? null);

                $pdo->commit();
                $msg = 'Lancamento criado com sucesso!';
                if (!empty($comprovantesEnviados)) {
                    $msg .= ' Comprovante(s) anexado(s): ' . implode(', ', $comprovantesEnviados) . '.';
                }
                setMensagem('success', $msg);
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao salvar lancamento: ' . $e->getMessage());
            $mensagemErro = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Erro ao salvar lancamento. Tente novamente.';
            setMensagem('error', $mensagemErro);
        }

        redirecionar(APP_URL . 'financeiro');
        break;

    // ==============================
    // EXCLUIR COMPROVANTE
    // ==============================
    case 'excluir_comprovante':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'financeiro');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        $comprovanteId = trim($_POST['comprovante_id'] ?? '');
        $lancamentoId = trim($_POST['id'] ?? '');
        if ($comprovanteId === '' || $lancamentoId === '') {
            setMensagem('error', 'Comprovante invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        try {
            $stmt = $pdo->prepare("
                SELECT id, lancamento_id, caminho
                FROM financeiro_comprovantes
                WHERE id = :id AND lancamento_id = :lancamento_id
                LIMIT 1
            ");
            $stmt->execute([
                ':id' => $comprovanteId,
                ':lancamento_id' => $lancamentoId,
            ]);
            $comprovante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comprovante) {
                setMensagem('error', 'Comprovante nao encontrado.');
                redirecionar(APP_URL . 'financeiro/form?id=' . urlencode($lancamentoId));
            }

            removerArquivoComprovanteFinanceiro((string)$comprovante['caminho']);

            $stmtDelete = $pdo->prepare("DELETE FROM financeiro_comprovantes WHERE id = :id");
            $stmtDelete->execute([':id' => $comprovanteId]);

            setMensagem('success', 'Comprovante excluido com sucesso.');
        } catch (Exception $e) {
            error_log('Erro ao excluir comprovante financeiro: ' . $e->getMessage());
            setMensagem('error', 'Erro ao excluir comprovante.');
        }

        redirecionar(APP_URL . 'financeiro/form?id=' . urlencode($lancamentoId));
        break;

    // ==============================
    // EXCLUIR
    // ==============================
    case 'excluir':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        try {
            $stmt = $pdo->prepare("UPDATE financeiro_lancamentos SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setMensagem('success', 'Lancamento excluido com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao excluir lancamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao excluir lancamento.');
        }

        redirecionar(APP_URL . 'financeiro');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'financeiro');
}
