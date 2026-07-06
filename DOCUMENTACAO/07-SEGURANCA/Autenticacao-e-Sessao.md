# Autenticação e Sessão

## Como o login funciona
O login é processado pelos arquivos dentro de `modules/login/`. O POST do formulário é recebido, e uma query PDO busca o email na tabela `usuarios`. A senha enviada no form é verificada contra o hash armazenado no banco. Se for compatível, os dados essenciais são injetados na sessão global `$_SESSION`.

## Dados armazenados na sessão
- `$_SESSION['usuario_logado']`: Armazena o ID interno e o perfil/cargo do usuário (ex: ADMIN, VENDEDOR).

## Como o logout funciona
Ao chamar `?action=logout` no `index.php`, o sistema limpa os dados (`session_destroy()` ou `unset`) e faz o redirect via `header('Location: login')`.

## Proteção de páginas
O arquivo `index.php` atua como roteador e contém uma cláusula global que verifica `if (!isset($_SESSION['usuario_logado']))`. Se o usuário não estiver logado e não for a rota de login ou de assinatura via token (`/assinar`), ele é bloqueado e redirecionado.

## Armazenamento de senhas
As senhas estão cacheadas utilizando funções nativas de hash do PHP (`password_hash()`, provavelmente utilizando Bcrypt, a depender da versão). Estão bem implementadas nesse quesito comparado a sistemas legados puros usando MD5.

## Tempo de sessão
Não identificado no código atual um controle explícito de expiração de sessão por tempo de inatividade. O PHP segue a duração default do `session.gc_maxlifetime` do `php.ini`.

## Problemas encontrados
Falta de proteção contra Brute Force (bloqueio após X tentativas).

## O que está correto
Verificação centralizada de `$_SESSION` no `index.php` evitando páginas esquecidas sem proteção (Zero Trust).