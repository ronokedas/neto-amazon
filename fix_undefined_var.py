import re

with open('c:/sistema/modules/vistorias/actions.php', 'r', encoding='utf-8') as f:
    c = f.read()

else_block_old = """            } else {
                // Atualizar vistoria existente
                $stmtV = $pdo->prepare("
                    UPDATE vistorias 
                    SET observacoes_tecnicas = :obs_tecnicas, status = :status
                    WHERE id = :id
                ");
                $stmtV->execute([
                    ':obs_tecnicas' => $observacoes_tecnicas ?: null,
                    ':status'       => $status_vistoria,
                    ':id'           => $vistoria_id,
                ]);

                // Remover exigencias antigas para reinserir
                $stmtDel = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                $stmtDel->execute([':vistoria_id' => $vistoria_id]);
            }"""

else_block_new = """            } else {
                // Obter numero existente
                $stmtCheck = $pdo->prepare("SELECT numero FROM vistorias WHERE id = :id");
                $stmtCheck->execute([':id' => $vistoria_id]);
                $numero_relatorio = $stmtCheck->fetchColumn() ?: '';

                // Atualizar vistoria existente
                $stmtV = $pdo->prepare("
                    UPDATE vistorias 
                    SET observacoes_tecnicas = :obs_tecnicas, status = :status
                    WHERE id = :id
                ");
                $stmtV->execute([
                    ':obs_tecnicas' => $observacoes_tecnicas ?: null,
                    ':status'       => $status_vistoria,
                    ':id'           => $vistoria_id,
                ]);

                // Remover exigencias antigas para reinserir
                $stmtDel = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                $stmtDel->execute([':vistoria_id' => $vistoria_id]);
            }"""

c = c.replace(else_block_old, else_block_new)

with open('c:/sistema/modules/vistorias/actions.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Fixed actions.php")