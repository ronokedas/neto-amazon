<?php
/**
 * CHT — Certificado de Homologação Técnica
 * Formulário de Criação/Edição
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
verificar_cargo('ADMIN');

$editando = false;
$cht = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;
    $stmt = $pdo->prepare("SELECT * FROM certificados_cht WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $cht = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cht) { setMensagem('error','Certificado não encontrado.'); redirecionar(APP_URL.'documentacao/cht'); }
}

$proximo_numero = '';
if (!$editando) {
    $ano = date('y'); $ano4 = date('Y');
    $s = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cht WHERE YEAR(criado_em)=:ano");
    $s->execute([':ano'=>$ano4]); $t=$s->fetch()['total'];
    $proximo_numero = "AM-REL-HT-".($t+1)."/{$ano}";
}

$titulo_page = ($editando?'Editar':'Novo').' CHT - '.APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> <?php echo $editando?'Editar':'Novo'; ?> Certificado de Homologação Técnica</h2>
        <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($editando && $cht['assinado']): ?>
    <div class="card mb-3" style="border-left:4px solid var(--cor-destaque);">
        <div class="card-body"><p style="margin:0;"><i class="fas fa-lock" style="color:var(--cor-destaque);"></i>
        <strong>Já assinado.</strong> Por: <?php echo h($cht['assinante_nome']); ?> em <?php echo formatarDataCompleta($cht['assinatura_em']); ?></p></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cht/actions">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if($editando): ?><input type="hidden" name="id" value="<?php echo h($cht['id']); ?>"><?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-id-card"></i> Identificação</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nº Relatório HT</label>
                        <input type="text" class="form-control" value="<?php echo $editando?h($cht['numero_relatorio_ht']):h($proximo_numero); ?>" readonly style="background:var(--cor-sidebar);font-weight:bold;">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php foreach(['rascunho'=>'Rascunho','emitido'=>'Emitido','cancelado'=>'Cancelado'] as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($editando?$cht['status']:'rascunho')===$v?'selected':''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="data_emissao">Data de Emissão</label>
                    <input type="date" name="data_emissao" id="data_emissao" class="form-control" required
                           value="<?php echo $editando?h($cht['data_emissao']):date('Y-m-d'); ?>">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Profissional / Empresa</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="profissional_empresa">Nome do Profissional ou Empresa *</label>
                        <input type="text" name="profissional_empresa" id="profissional_empresa" class="form-control" required
                               value="<?php echo $editando?h($cht['profissional_empresa']):''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control"
                               value="<?php echo $editando?h($cht['cpf_cnpj']):''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="atividade_homologada">Atividade Técnica Homologada *</label>
                    <textarea name="atividade_homologada" id="atividade_homologada" class="form-control" rows="3" required
                              placeholder="Ex: Vistoria de embarcações, Projeto naval, Inspeção de segurança..."><?php echo $editando?h($cht['atividade_homologada']):''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="3"
                              placeholder="Informações adicionais..."><?php echo $editando?h($cht['observacoes']):''; ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3></div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando?h($cht['assinante_nome']):''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control" placeholder="Ex: Engenheira Naval"
                               value="<?php echo $editando?h($cht['assinante_titulo']):''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control" placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando?h($cht['assinante_registro']):''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-footer" style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo $editando?'Atualizar':'Salvar'; ?> Certificado</button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>