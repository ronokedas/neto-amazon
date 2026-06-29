import re

def fix_clientes():
    path = r'c:\sistema\modules\clientes\actions.php'
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old_code = """            $stmt = $pdo->prepare("
                INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, criado_por)
                VALUES (UUID(), :nome, :tipo_pessoa, :cpf_cnpj, :perfil, :telefone, :email, :endereco, :criado_por)
            ");
            $stmt->execute([
                ':nome'       => $nome,
                ':tipo_pessoa' => $tipo_pessoa,
                ':cpf_cnpj'   => $cpf_cnpj ?: null,
                ':perfil'     => $perfil,
                ':telefone'   => $telefone ?: null,
                ':email'      => $email ?: null,
                ':endereco'   => $endereco ?: null,
                ':criado_por' => $_SESSION['usuario_id'],
            ]);

            // Pegar o ID do cliente inserido
            $cliente_id = $pdo->lastInsertId();"""

    new_code = """            $cliente_id = gerarUUID();
            $stmt = $pdo->prepare("
                INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, criado_por)
                VALUES (:id, :nome, :tipo_pessoa, :cpf_cnpj, :perfil, :telefone, :email, :endereco, :criado_por)
            ");
            $stmt->execute([
                ':id'         => $cliente_id,
                ':nome'       => $nome,
                ':tipo_pessoa' => $tipo_pessoa,
                ':cpf_cnpj'   => $cpf_cnpj ?: null,
                ':perfil'     => $perfil,
                ':telefone'   => $telefone ?: null,
                ':email'      => $email ?: null,
                ':endereco'   => $endereco ?: null,
                ':criado_por' => $_SESSION['usuario_id'],
            ]);"""
    
    # regex to handle line endings
    content = re.sub(re.escape(old_code).replace(r'\n', r'\r?\n'), new_code, content)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def fix_vistorias():
    path = r'c:\sistema\modules\vistorias\actions.php'
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    old_code = """                // Criar nova vistoria com numero
                $stmtV = $pdo->prepare("
                    INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, agendamento_id, data_vistoria, observacoes_tecnicas, status, criado_por)
                    VALUES (UUID(), :numero, :embarcacao_id, :pessoa_id, :agendamento_id, :data_vistoria, :obs_tecnicas, :status, :criado_por)
                ");
                $stmtV->execute([
                    ':numero'         => $numero_relatorio,
                    ':embarcacao_id'  => $ag['embarcacao_id'],
                    ':pessoa_id'      => $ag['cliente_id'],
                    ':agendamento_id' => $agendamento_id,
                    ':data_vistoria'  => $ag['data_vistoria'],
                    ':obs_tecnicas'   => $observacoes_tecnicas ?: null,
                    ':status'         => $status_vistoria,
                    ':criado_por'     => $_SESSION['usuario_id'],
                ]);
                $vistoria_id = $pdo->lastInsertId();"""

    new_code = """                // Criar nova vistoria com numero
                $vistoria_id = gerarUUID();
                $stmtV = $pdo->prepare("
                    INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, agendamento_id, data_vistoria, observacoes_tecnicas, status, criado_por)
                    VALUES (:id, :numero, :embarcacao_id, :pessoa_id, :agendamento_id, :data_vistoria, :obs_tecnicas, :status, :criado_por)
                ");
                $stmtV->execute([
                    ':id'             => $vistoria_id,
                    ':numero'         => $numero_relatorio,
                    ':embarcacao_id'  => $ag['embarcacao_id'],
                    ':pessoa_id'      => $ag['cliente_id'],
                    ':agendamento_id' => $agendamento_id,
                    ':data_vistoria'  => $ag['data_vistoria'],
                    ':obs_tecnicas'   => $observacoes_tecnicas ?: null,
                    ':status'         => $status_vistoria,
                    ':criado_por'     => $_SESSION['usuario_id'],
                ]);"""

    content = re.sub(re.escape(old_code).replace(r'\n', r'\r?\n'), new_code, content)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

fix_clientes()
fix_vistorias()
