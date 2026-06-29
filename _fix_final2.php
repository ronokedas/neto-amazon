<?php
$path = 'c:\sistema\modules\agendamentos\form.php';
$c = file_get_contents($path);

// Fix 1: load vistoriadores for VENDEDOR too
$c = str_replace(
    "if (\$cargo === 'ADMIN') {",
    "if (\$cargo === 'ADMIN' || \$cargo === 'VENDEDOR') {",
    $c
);

// Fix 2: add hidden vendedor_id for VENDEDOR
$c = str_replace(
    '<?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->',
    '<?php endif; ?>
                    <?php if ($cargo === \'VENDEDOR\'): ?>
                        <input type="hidden" name="vendedor_id" value="<?php echo h($_SESSION[\'usuario_id\']); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->',
    $c
);

file_put_contents($path, $c);
echo "form.php updated\n";
