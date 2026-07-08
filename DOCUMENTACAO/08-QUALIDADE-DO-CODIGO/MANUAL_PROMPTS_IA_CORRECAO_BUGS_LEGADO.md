# Manual de prompts para IA corrigir bugs no legado PHP

Este manual ensina uma IA sem navegador web a corrigir problemas no sistema usando leitura de arquivos, PowerShell, PHP CLI, Docker, cURL e logs. Ele foi baseado na correção real do bug de edição de agendamento em que o campo "Armador responsável" deveria sumir da tela e o botão "Atualizar agendamento" precisava voltar para `/agendamentos`.

## Prompt mestre

Use este prompt para orientar outra IA antes de ela mexer no código:

```text
Voce esta trabalhando em um sistema PHP legado em C:\sistema. Nao use navegador web. Use apenas terminal PowerShell, leitura de arquivos, cURL, PHP CLI, Docker e logs.

Objetivo:
1. Entender o fluxo real no codigo antes de editar.
2. Fazer alteracoes pequenas e localizadas.
3. Nao reverter arquivos que ja estavam modificados por outra pessoa.
4. Validar sintaxe com php -l.
5. Validar o comportamento por requisicoes HTTP com curl.exe, usando login, cookies, CSRF e headers.
6. Se um redirect esperado nao acontecer, verificar BOM/saida antes de header, logs do container e resposta HTTP bruta.
7. Ao final, relatar arquivos alterados, comandos de validacao e resultado observado.

Sempre que um formulario POST deveria redirecionar, valide a resposta com curl -i. O resultado esperado e HTTP 302 com Location correto, nao uma pagina vazia 200.
```

## Prompt para investigar o problema

```text
Investigue o modulo relacionado ao problema. Procure textos visiveis, nomes de campos, actions, redirects e rotas.

Use comandos como:
rg -n "Armador|armador|responsavel|responsável|agendamentos|Location|redirect|header\(" modules -S
Get-Content modules\agendamentos\form.php | Select-Object -Index @(130..205)
Get-Content modules\agendamentos\actions.php | Select-Object -Index @(270..395)

Depois explique:
- qual arquivo renderiza a tela;
- qual arquivo processa o POST;
- qual campo precisa sair da interface;
- qual rota deve receber o submit;
- qual redirect deve acontecer no sucesso.
```

## Prompt para remover campo visual sem quebrar dados

```text
Remova o campo visivel "Armador responsável" somente da edicao do agendamento. Preserve o valor em input hidden para que a atualizacao nao apague o armador existente.

Regras:
- Se estiver editando, nao renderizar select#armador_id.
- Se estiver editando e a origem nao estiver travada, enviar armador_id como hidden.
- Se a origem estiver travada, manter os hiddens existentes.
- No cadastro novo, manter o campo como esta, salvo se o pedido disser explicitamente para remover tambem do cadastro.
- Nao alterar regras de negocio desnecessariamente.
```

Exemplo da ideia aplicada:

```php
<?php if ($editando && !$origemTravada): ?>
    <input type="hidden" name="armador_id" value="<?php echo h($agendamento['armador_id']); ?>">
<?php endif; ?>

<?php if (!$editando): ?>
    <!-- select visivel de armador_id fica apenas no cadastro novo -->
<?php endif; ?>
```

## Prompt para validar sintaxe

```text
Depois das alteracoes, valide sintaxe PHP nos arquivos tocados:

php -l modules\agendamentos\form.php
php -l modules\agendamentos\actions.php

Se houver erro, corrija antes de qualquer teste HTTP.
```

## Prompt para testar sem navegador

Use cURL com cookies. Primeiro faça login, depois abra o formulário para capturar o CSRF.

```powershell
$cookie = Join-Path $env:TEMP 'erp_cookies.txt'
Remove-Item $cookie -ErrorAction SilentlyContinue

curl.exe -s -c $cookie -b $cookie `
  -d "email=teste%40teste.com&senha=teste123" `
  -X POST http://localhost:8082/login `
  -o NUL `
  -w "login:%{http_code} %{redirect_url}`n"

$html = curl.exe -s -b $cookie -c $cookie `
  "http://localhost:8082/agendamentos/form?id=ID_DO_AGENDAMENTO"

$csrf = ($html | Select-String -Pattern 'name="csrf_token" value="([^"]+)"').Matches.Groups[1].Value
$action = ($html | Select-String -Pattern 'name="action" value="([^"]+)"').Matches.Groups[1].Value

"csrf=$csrf"
"action=$action"
```

Confirme no HTML que o campo visual sumiu:

```powershell
$html -match 'Armador responsável'
$html -match 'select id="armador_id"'
$html -match 'input type="hidden" name="armador_id"'
```

Resultado esperado na edição:

```text
Armador responsável: False
select id="armador_id": False
hidden armador_id: True
```

## Prompt para testar o POST e redirect

```text
Monte um POST com os campos obrigatorios do formulario. Use o csrf capturado e envie para /agendamentos/actions. Leia os headers com curl -i.

O resultado correto deve ser:
HTTP/1.1 302 Found
Location: http://localhost:8082/agendamentos
Content-Length: 0
```

Exemplo:

```powershell
$body = "csrf_token=$csrf&action=editar&id=ID_DO_AGENDAMENTO&proposta_id=ID_PROPOSTA&armador_id=&cliente_id=ID_CLIENTE&embarcacao_id=ID_EMBARCACAO&tipo_vistoria=Vistoria&vistoriador_id=ID_VISTORIADOR&data_vistoria=2026-07-08&hora_vistoria=18%3A00&local=belem&contato_nome=Contato&contato_telefone=91999999999&observacoes=Teste"

curl.exe -i -s -b $cookie -c $cookie `
  -X POST `
  -H "Content-Type: application/x-www-form-urlencoded" `
  --data $body `
  http://localhost:8082/agendamentos/actions
```

## Prompt para diagnosticar pagina branca em actions

```text
Se o POST para actions retornar HTTP 200 com corpo vazio em vez de 302, investigue nesta ordem:

1. Verifique logs do container:
docker logs --tail 120 erp_app 2>&1 | Select-String -Pattern "PHP|Warning|Fatal|headers|Cannot|agendamentos/actions|Erro" -Context 1,2

2. Veja a resposta bruta:
curl.exe -i -s ...

3. Se aparecer Content-Length pequeno, como 3, e corpo invisivel, suspeite de BOM UTF-8 antes de <?php.

4. Confirme os primeiros bytes do arquivo:
$bytes = [System.IO.File]::ReadAllBytes((Resolve-Path 'modules\agendamentos\actions.php'))
($bytes[0..7] | ForEach-Object { $_.ToString('X2') }) -join ' '

Se comecar com EF BB BF, existe BOM. O correto e comecar com:
3C 3F 70 68 70
que corresponde a <?php.
```

## Prompt para remover BOM sem destruir acentos

```text
Remova BOM dos arquivos PHP que usam header('Location') ou redirecionar(). Preserve o texto em UTF-8.

Use PowerShell:
```

```powershell
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
foreach ($path in @('modules\agendamentos\form.php','modules\agendamentos\actions.php')) {
    $full = Resolve-Path $path
    $text = [System.IO.File]::ReadAllText($full, [System.Text.Encoding]::UTF8)
    [System.IO.File]::WriteAllText($full, $text, $utf8NoBom)
}
```

Depois confirme:

```powershell
$bytes = [System.IO.File]::ReadAllBytes((Resolve-Path 'modules\agendamentos\actions.php'))
($bytes[0..7] | ForEach-Object { $_.ToString('X2') }) -join ' '

php -l modules\agendamentos\form.php
php -l modules\agendamentos\actions.php
```

## Prompt para validar sucesso completo

```text
Repita o POST depois de remover BOM.

Aceite como corrigido apenas se:
- o HTML do formulario de edicao nao contem "Armador responsável";
- nao existe select#armador_id visivel na edicao;
- existe input hidden name="armador_id" quando necessario;
- php -l nao mostra erros;
- POST /agendamentos/actions retorna HTTP 302;
- o header Location e http://localhost:8082/agendamentos.
```

## Checklist final para a IA

```text
Antes de encerrar, responda:
1. Quais arquivos foram alterados?
2. O que foi removido da interface?
3. Como o dado foi preservado?
4. Qual era a causa do redirect quebrado?
5. Quais comandos provaram que funcionou?
6. Algum arquivo ja estava modificado antes e nao foi tocado/revertido?
```

## Resumo da correcao real aplicada

- Arquivo da tela: `modules/agendamentos/form.php`.
- Arquivo do POST: `modules/agendamentos/actions.php`.
- Campo removido da edicao: select visivel `armador_id` com label "Armador responsável".
- Preservacao do dado: `input type="hidden" name="armador_id"`.
- Redirect esperado: `APP_URL . 'agendamentos'`.
- Causa da pagina branca: BOM UTF-8 no inicio de arquivo PHP, gerando saida antes de `header('Location: ...')`.
- Correcao do redirect: salvar PHP como UTF-8 sem BOM.
- Validacao final esperada: `HTTP/1.1 302 Found` com `Location: http://localhost:8082/agendamentos`.
