<?php
/**
 * MODULO: EXIGENCIAS_CATALOGO
 * Arquivo: actions.php - Processar acoes (salvar, desativar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN)
verificar_sessao();
// Utilizando a verificação de cargo padrão do sistema
if (getCargo() !== 'ADMIN') {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'exigencias_catalogo');
        }

        // Verificar CSRF se a funcao estiver disponivel e foi enviada
        $csrf = $_POST['csrf_token'] ?? '';
        if (function_exists('verificarCSRF') && !empty($csrf)) {
            if (!verificarCSRF($csrf)) {
                setMensagem('error', 'Token de seguranca invalido.');
                redirecionar(APP_URL . 'exigencias_catalogo');
            }
        }

        $id                = trim($_POST['id'] ?? '');
        $codigo_interno    = trim($_POST['codigo_interno'] ?? '');
        $descricao         = trim($_POST['descricao'] ?? '');
        $item_normam       = trim($_POST['item_normam'] ?? '');
        $tipo_vistoria     = trim($_POST['tipo_vistoria'] ?? '');
        $prazo_padrao_dias = isset($_POST['prazo_padrao_dias']) && $_POST['prazo_padrao_dias'] !== '' ? (int)$_POST['prazo_padrao_dias'] : null;
        $ativo             = isset($_POST['ativo']) ? 1 : 0;

        // Validacoes
        $erros = [];

        if (empty($descricao)) {
            $erros[] = 'A descricao e obrigatoria.';
        }

        $tipos_permitidos = ['seco', 'flutuando', 'borda_livre', 'arqueacao'];
        if (!empty($tipo_vistoria) && !in_array($tipo_vistoria, $tipos_permitidos)) {
            $erros[] = 'Tipo de vistoria invalido.';
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            $url = APP_URL . 'exigencias_catalogo/form';
            if (!empty($id)) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            $isEdicao = !empty($id);

            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET codigo_interno = :codigo_interno, descricao = :descricao, item_normam = :item_normam, tipo_vistoria = :tipo_vistoria, prazo_padrao_dias = :prazo_padrao_dias, ativo = :ativo WHERE id = :id");
                $stmt->execute([
                    ':codigo_interno'    => $codigo_interno,
                    ':descricao'         => $descricao,
                    ':item_normam'       => $item_normam,
                    ':tipo_vistoria'     => empty($tipo_vistoria) ? null : $tipo_vistoria,
                    ':prazo_padrao_dias' => $prazo_padrao_dias,
                    ':ativo'             => $ativo,
                    ':id'                => $id
                ]);
                setMensagem('success', 'Exigencia atualizada com sucesso!');
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO exigencias_catalogo (id, codigo_interno, descricao, item_normam, tipo_vistoria, prazo_padrao_dias, ativo) VALUES (:id, :codigo_interno, :descricao, :item_normam, :tipo_vistoria, :prazo_padrao_dias, :ativo)");
                $stmt->execute([
                    ':id'                => gerarUUID(),
                    ':codigo_interno'    => $codigo_interno,
                    ':descricao'         => $descricao,
                    ':item_normam'       => $item_normam,
                    ':tipo_vistoria'     => empty($tipo_vistoria) ? null : $tipo_vistoria,
                    ':prazo_padrao_dias' => $prazo_padrao_dias,
                    ':ativo'             => $ativo
                ]);
                setMensagem('success', 'Exigencia criada com sucesso!');
            }
        } catch (Exception $e) {
            error_log('Erro ao salvar exigencia: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar exigencia. Tente novamente.');
        }

        redirecionar(APP_URL . 'exigencias_catalogo');
        break;

    // ==============================
    // DESATIVAR / ATIVAR
    // ==============================
    case 'alternar_status':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'exigencias_catalogo');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, ativo FROM exigencias_catalogo WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                setMensagem('error', 'Exigencia nao encontrada.');
                redirecionar(APP_URL . 'exigencias_catalogo');
            }

            $novoStatus = $item['ativo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE exigencias_catalogo SET ativo = :ativo WHERE id = :id");
            $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);

            $msgStatus = $novoStatus ? 'ativada' : 'desativada';
            setMensagem('success', "Exigencia {$msgStatus} com sucesso!");
        } catch (Exception $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status da exigencia.');
        }

        redirecionar(APP_URL . 'exigencias_catalogo');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'exigencias_catalogo');
}
