import re

with open('c:/sistema/modules/vistorias/actions.php', 'r', encoding='utf-8') as f:
    c = f.read()

reprovar_old = """        } else {
            $stmt = $pdo->prepare("UPDATE vistorias SET status = 'REPROVADA', observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
            $stmt->execute([
                ':obs' => $observacao ?: null,
                ':aprovador' => $_SESSION['usuario_id'],
                ':id' => $id
            ]);
            log_atividade('relatorio_reprovado', "Relatorio ID {$id} reprovado.");
            setMensagem('error', 'Relatorio reprovado.');
        }"""

reprovar_new = """        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE vistorias SET status = 'REPROVADA', observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
                $stmt->execute([
                    ':obs' => $observacao ?: null,
                    ':aprovador' => $_SESSION['usuario_id'],
                    ':id' => $id
                ]);
                
                if ($agendamento_id) {
                    $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')")->execute([':agendamento_id' => $agendamento_id]);
                    $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $agendamento_id]);
                }
                
                $pdo->commit();
                log_atividade('relatorio_reprovado', "Relatorio ID {$id} reprovado.");
                setMensagem('error', 'Relatorio reprovado. Agendamento concluído.');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Erro ao reprovar vistoria: ' . $e->getMessage());
                setMensagem('error', 'Erro ao reprovar relatório.');
            }
        }"""

c = c.replace(reprovar_old, reprovar_new)

with open('c:/sistema/modules/vistorias/actions.php', 'w', encoding='utf-8') as f:
    f.write(c)

print("Fixed reprovar flow.")