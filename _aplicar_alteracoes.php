<?php
echo "Iniciando alteracoes...\n";

// --- actions.php ---
$path = 'c:\sistema\modules\agendamentos\actions.php';
$c = file_get_contents($path);

// 1. Permissao de acesso
$c = str_replace(
    "if (!in_array(\$cargo, ['ADMIN', 'VISTORIADOR'])) {",
    "if (!in_array(\$cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {",
    $c
);

// 2. Adicionar $vendedor_id na captura
$c = str_replace(
    "\$vistoriador_id  = \$_POST['vistoriador_id'] ?? null;",
    "\$vistoriador_id  = \$_POST['vistoriador_id'] ?? null;
            \$vendedor_id     = \$_POST['vendedor_id'] ?? null;",
    $c
);

// 3. Auto-assign vendedor_id para VENDEDOR
$c = str_replace(
    "// Se VISTORIADOR, atribuir automaticamente a si mesmo
            if (\$cargo === 'VISTORIADOR') {
                \$vistoriador_id = \$_SESSION['usuario_id'];
            }",
    "// Se VISTORIADOR, atribuir automaticamente a si mesmo
            if (\$cargo === 'VISTORIADOR') {
                \$vistoriador_id = \$_SESSION['usuario_id'];
            }

            // Se VENDEDOR, atribuir vendedor_id automaticamente
            if (\$cargo === 'VENDEDOR') {
                \$vendedor_id = \$_SESSION['usuario_id'];
            }",
    $c
);

// 4. INSERT com vendedor_id
$c = str_replace(
    "INSERT INTO agendamentos (
                    id, proposta_id, embarcacao_id, cliente_id, vistoriador_id,
                    tipo_vistoria, data_vistoria, hora_vistoria, local,
                    contato_nome, contato_telefone, status, observacoes, criado_por
                ) VALUES (
                    UUID(), :proposta_id, :embarcacao_id, :cliente_id, :vistoriador_id,
                    :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                    :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                )",
    "INSERT INTO agendamentos (
                    id, proposta_id, embarcacao_id, cliente_id, vistoriador_id, vendedor_id,
                    tipo_vistoria, data_vistoria, hora_vistoria, local,
                    contato_nome, contato_telefone, status, observacoes, criado_por
                ) VALUES (
                    UUID(), :proposta_id, :embarcacao_id, :cliente_id, :vistoriador_id, :vendedor_id,
                    :tipo_vistoria, :data_vistoria, :hora_vistoria, :local,
                    :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por
                )",
    $c
);

// 5. Parametros com vendedor_id
$c = str_replace(
    "':vistoriador_id'  => \$vistoriador_id ?: null,",
    "':vistoriador_id'  => \$vistoriador_id ?: null,
                ':vendedor_id'     => \$vendedor_id ?: null,",
    $c
);

file_put_contents($path, $c);
echo "OK: actions.php\n";

// --- index.php ---
$path = 'c:\sistema\modules\agendamentos\index.php';
$c = file_get_contents($path);

$c = str_replace(
    "if (!in_array(\$cargo, ['ADMIN', 'VISTORIADOR'])) {",
    "if (!in_array(\$cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {",
    $c
);

$c = str_replace(
    "<?php if (\$a['status'] === 'pendente' && \$cargo === 'ADMIN'): ?>",
    "<?php if (\$a['status'] === 'pendente' && (\$cargo === 'ADMIN' || \$cargo === 'VENDEDOR')): ?>",
    $c
);

file_put_contents($path, $c);
echo "OK: index.php\n";

// --- form.php ---
$path = 'c:\sistema\modules\agendamentos\form.php';
$c = file_get_contents($path);

$c = str_replace(
    "if (!in_array(\$cargo, ['ADMIN', 'VISTORIADOR'])) {",
    "if (!in_array(\$cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {",
    $c
);

$c = str_replace(
    "if (\$cargo === 'ADMIN') {\n        \$vistoriadores = \$pdo->query(",
    "if (\$cargo === 'ADMIN' || \$cargo === 'VENDEDOR') {\n        \$vistoriadores = \$pdo->query(",
    $c
);

$c = str_replace(
    "<?php if (\$cargo === 'ADMIN'): ?>",
    "<?php if (\$cargo === 'ADMIN' || \$cargo === 'VENDEDOR'): ?>",
    $c
);

// Adicionar hidden vendedor_id para VENDEDOR
$c = str_replace(
    "<?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->",
    "<?php endif; ?>
                    <?php if (\$cargo === 'VENDEDOR'): ?>
                        <input type=\"hidden\" name=\"vendedor_id\" value=\"<?php echo h(\$_SESSION['usuario_id']); ?>\">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->",
    $c
);

file_put_contents($path, $c);
echo "OK: form.php\n";

echo "\nTodas as alteracoes foram aplicadas com sucesso!\n";
