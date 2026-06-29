<?php
$path = 'c:\sistema\modules\agendamentos\form.php';
$c = file_get_contents($path);

// Insert hidden vendedor_id between endif and /div
$old = '<?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->';

$new = '<?php endif; ?>
                    <?php if ($cargo === \'VENDEDOR\'): ?>
                        <input type="hidden" name="vendedor_id" value="<?php echo h($_SESSION[\'usuario_id\']); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->';

$c = str_replace($old, $new, $c);
file_put_contents($path, $c);
echo "done\n";
