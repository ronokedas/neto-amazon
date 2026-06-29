import sys
import re

def fix(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_block = """        if ($decisao === 'aprovar') {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM vistoria_exigencias WHERE vistoria_id = :id AND conforme = 'nao'");
                $stmt->execute([':id' => $id]);
                $nao_conformes = (int)$stmt->fetchColumn();

                $novo_status = ($nao_conformes > 0) ? 'APROVADA_COM_EXIGENCIAS' : 'APROVADA';
                $stmt = $pdo->prepare("UPDATE vistorias SET status = :status, observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
                $stmt->execute([
                    ':status' => $novo_status,
                    ':obs' => $observacao ?: null,
                    ':aprovador' => $_SESSION['usuario_id'],
                    ':id' => $id
                ]);

                if ($agendamento_id) {
                    $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')")->execute([':agendamento_id' => $agendamento_id]);
                    $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $agendamento_id]);
                }

                $pdo->commit();

                log_atividade('relatorio_aprovado', "Relatorio ID {$id} aprovado. Status: {$novo_status}.");
                setMensagem('success', 'Relatorio aprovado com sucesso.');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Erro ao aprovar vistoria: ' . $e->getMessage());
                setMensagem('error', 'Erro ao processar aprovação. Tente novamente.');
            }
        } else {
            $stmt = $pdo->prepare("UPDATE vistorias SET status = 'REPROVADA', observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
            $stmt->execute([
                ':obs' => $observacao ?: null,
                ':aprovador' => $_SESSION['usuario_id'],
                ':id' => $id
            ]);
            log_atividade('relatorio_reprovado', "Relatorio ID {$id} reprovado.");
            setMensagem('error', 'Relatorio reprovado.');
        }"""

    # We use a regex that is very broad to find the if-else block
    # and replace it with our new, correct block.
    # This regex looks for 'if ($decisao === \'aprovar\') {' followed by anything, 
    # then '} else {' followed by anything, then the closing '}'
    pattern = r'if\s*\(\$decisao\s*===\s*\'aprovar\'\)\s*\{.*?\}\s*else\s*\{.*?\}'
    
    new_content = re.sub(pattern, new_block, content, flags=re.DOTALL)
    
    if new_content == content:
        print("Failed to replace block with regex.")
    else:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Successfully patched {file_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        fix(sys.argv[1])
