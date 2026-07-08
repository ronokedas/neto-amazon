<?php
/**
 * Autenticacao e consultas do Portal do Cliente.
 */

function clienteEstaLogado(): bool
{
    return isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true;
}

function clientePortalId(): ?string
{
    return $_SESSION['cliente_id'] ?? null;
}

function clientePortalNome(): string
{
    return $_SESSION['cliente_nome'] ?? 'Cliente';
}

function clientePortalForcarTrocaSenha(): bool
{
    return !empty($_SESSION['cliente_forcar_troca_senha']);
}

function loginCliente(array $cliente, array $acesso): void
{
    session_regenerate_id(true);
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'];
    $_SESSION['cliente_email'] = $cliente['email'];
    $_SESSION['cliente_logado'] = true;
    $_SESSION['cliente_login_time'] = time();
    $_SESSION['cliente_forcar_troca_senha'] = (int)($acesso['forcar_troca_senha'] ?? 0) === 1;
}

function logoutCliente(): void
{
    unset(
        $_SESSION['cliente_id'],
        $_SESSION['cliente_nome'],
        $_SESSION['cliente_email'],
        $_SESSION['cliente_logado'],
        $_SESSION['cliente_login_time'],
        $_SESSION['cliente_forcar_troca_senha']
    );
    header('Location: ' . APP_URL . 'portal/login');
    exit;
}

function verificarSessaoCliente(): void
{
    if (!clienteEstaLogado() || (time() - ($_SESSION['cliente_login_time'] ?? 0)) > 3600) {
        logoutCliente();
    }

    $_SESSION['cliente_login_time'] = time();
}

function requireClienteLogin(): void
{
    if (!clienteEstaLogado()) {
        header('Location: ' . APP_URL . 'portal/login');
        exit;
    }

    verificarSessaoCliente();
}

function requireClienteSenhaDefinitiva(): void
{
    requireClienteLogin();
    if (clientePortalForcarTrocaSenha()) {
        header('Location: ' . APP_URL . 'portal/trocar-senha');
        exit;
    }
}

function clientePortalConfigDocumentos(): array
{
    return [
        'csn' => [
            'label' => 'CSN',
            'table' => 'certificados_csn',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/certificados/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'cnbl' => [
            'label' => 'CNBL',
            'table' => 'certificados_cnbl',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/cnbl/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'cnarq' => [
            'label' => 'CNARQ',
            'table' => 'certificados_cnarq',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/cnarq/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'lc' => [
            'label' => 'LC',
            'table' => 'certificados_lc',
            'numero' => 'numero_lc',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/lc/pdf',
            'has_embarcacao_id' => true,
            'has_numero_inscricao' => false,
        ],
        'lp' => [
            'label' => 'LP',
            'table' => 'certificados_lp',
            'numero' => 'numero_lp',
            'validade' => 'validade_data',
            'pdf' => 'documentacao/lp/pdf',
            'has_embarcacao_id' => true,
            'has_numero_inscricao' => false,
        ],
    ];
}

function clientePortalEmbarcacoes(PDO $pdo, string $clienteId): array
{
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.id, e.nome, e.registro, e.numero_inscricao, e.tipo_embarcacao
        FROM embarcacoes e
        LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.cliente_id = :cliente_ce
        WHERE e.ativo = 1
          AND (
            e.proprietario_id = :cliente_prop
            OR e.cliente_id = :cliente_cad
            OR ce.cliente_id IS NOT NULL
          )
        ORDER BY e.nome ASC
    ");
    $stmt->execute([
        ':cliente_ce' => $clienteId,
        ':cliente_prop' => $clienteId,
        ':cliente_cad' => $clienteId,
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clientePortalEmbarcacaoIds(PDO $pdo, string $clienteId): array
{
    return array_values(array_unique(array_column(clientePortalEmbarcacoes($pdo, $clienteId), 'id')));
}

function clientePortalSqlIn(array $ids, string $prefixo, array &$params): string
{
    $placeholders = [];
    foreach ($ids as $index => $id) {
        $key = ':' . $prefixo . $index;
        $placeholders[] = $key;
        $params[$key] = $id;
    }
    return implode(',', $placeholders);
}

function clientePortalSelectDocumentos(PDO $pdo, string $clienteId, array $filtros = []): array
{
    $embarcacaoIds = clientePortalEmbarcacaoIds($pdo, $clienteId);
    if (empty($embarcacaoIds)) {
        return [];
    }

    $configs = clientePortalConfigDocumentos();
    $tipos = !empty($filtros['tipo']) && isset($configs[$filtros['tipo']])
        ? [$filtros['tipo'] => $configs[$filtros['tipo']]]
        : $configs;

    $documentos = [];
    foreach ($tipos as $tipo => $cfg) {
        $params = [];
        $in = clientePortalSqlIn($embarcacaoIds, 'emb_', $params);
        $embJoin = $cfg['has_embarcacao_id']
            ? "LEFT JOIN embarcacoes ed ON ed.id = c.embarcacao_id AND ed.ativo = 1"
            : "LEFT JOIN embarcacoes ed ON 1 = 0";
        $fallbackInscricao = $cfg['has_numero_inscricao']
            ? "AND (
                    c.numero_inscricao IS NULL
                    OR c.numero_inscricao = ''
                    OR en.numero_inscricao = c.numero_inscricao
                    OR en.registro = c.numero_inscricao
                )"
            : "";
        $numeroCampo = $cfg['numero'];
        $validadeCampo = $cfg['validade'];

        $sql = "
            SELECT
                c.id,
                '{$tipo}' AS tipo,
                '{$cfg['label']}' AS tipo_label,
                c.{$numeroCampo} AS numero,
                c.nome_embarcacao,
                c.data_emissao,
                c.{$validadeCampo} AS data_validade,
                c.status,
                c.assinado,
                c.criado_em,
                COALESCE(ed.id, ev.id, en.id) AS embarcacao_id,
                COALESCE(ed.nome, ev.nome, en.nome, c.nome_embarcacao) AS embarcacao_nome
            FROM {$cfg['table']} c
            {$embJoin}
            LEFT JOIN vistorias v ON v.id = c.vistoria_id
            LEFT JOIN embarcacoes ev ON ev.id = v.embarcacao_id AND ev.ativo = 1
            LEFT JOIN embarcacoes en ON en.ativo = 1
                AND en.nome = c.nome_embarcacao
                {$fallbackInscricao}
            WHERE c.ativo = 1
              AND c.status IN ('emitido', 'assinado')
              AND COALESCE(ed.id, ev.id, en.id) IN ({$in})
        ";

        if (!empty($filtros['embarcacao_id'])) {
            $sql .= " AND COALESCE(ed.id, ev.id, en.id) = :filtro_embarcacao";
            $params[':filtro_embarcacao'] = $filtros['embarcacao_id'];
        }

        if (!empty($filtros['status']) && in_array($filtros['status'], ['emitido', 'assinado'], true)) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['busca'])) {
            $sql .= " AND (c.{$numeroCampo} LIKE :busca_numero OR c.nome_embarcacao LIKE :busca_embarcacao)";
            $params[':busca_numero'] = '%' . $filtros['busca'] . '%';
            $params[':busca_embarcacao'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['vencendo_dias'])) {
            $dias = max(1, (int)$filtros['vencendo_dias']);
            $sql .= " AND c.{$validadeCampo} IS NOT NULL AND c.{$validadeCampo} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $documentos = array_merge($documentos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    usort($documentos, function ($a, $b) {
        return strcmp((string)($b['data_validade'] ?? ''), (string)($a['data_validade'] ?? ''));
    });

    return $documentos;
}

function clientePortalDocumento(PDO $pdo, string $clienteId, string $tipo, string $documentoId): ?array
{
    if ($documentoId === '') {
        return null;
    }

    $docs = clientePortalSelectDocumentos($pdo, $clienteId, ['tipo' => $tipo]);
    foreach ($docs as $doc) {
        if (hash_equals((string)$doc['id'], $documentoId)) {
            return $doc;
        }
    }
    return null;
}

function clientePortalTemplate(string $nome, array $replacements): string
{
    $path = __DIR__ . '/../templates/email/' . $nome . '.html';
    if (!is_file($path)) {
        throw new RuntimeException('Template de e-mail nao encontrado.');
    }

    $html = file_get_contents($path);
    return str_replace(array_keys($replacements), array_values($replacements), $html);
}

function clientePortalGerarSenhaFacil(int $tamanho = 8): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $senha;
}
