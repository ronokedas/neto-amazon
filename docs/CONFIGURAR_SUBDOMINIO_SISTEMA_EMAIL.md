# Configurar subdominio do sistema e e-mail no VPS

Este guia mostra onde alterar o VPS para acessar o sistema por `https://sistema1.amazonnaval.com.br/` em vez de `http://IP:8082/`. Isso tambem faz os links enviados por e-mail sairem com dominio e HTTPS.

## 1. Onde ficam os arquivos no VPS

Entre no VPS por SSH e acesse a pasta do sistema:

```bash
cd /opt/sistema-amazon
```

Arquivos principais:

- `.env`: variaveis do sistema, porta publica, URL e SMTP.
- `docker-compose.yml`: configuracao dos containers e porta do app.

Para abrir o `.env`:

```bash
nano .env
```

Para abrir o `docker-compose.yml`, se precisar conferir:

```bash
nano docker-compose.yml
```

## 2. Cloudflare

No DNS da Cloudflare, crie ou edite este registro:

```text
Tipo: A
Nome: sistema1
Conteudo: 34.132.186.4
Proxy: Com proxy
TTL: Auto
```

O proxy laranja pode ficar ligado para o subdominio do sistema, porque ele usa HTTP/HTTPS.

Nao use porta no DNS. DNS aponta apenas para IP, nunca para `34.132.186.4:8082`.

## 3. Alterar o .env no VPS

Dentro de `/opt/sistema-amazon/.env`, ajuste estas linhas:

```env
APP_PORT=80
APP_URL=https://sistema1.amazonnaval.com.br/

MAIL_HOST=smtps.uhserver.com
MAIL_PORT=465
MAIL_USERNAME=contato@amazonnaval.com.br
MAIL_PASSWORD=SENHA_DO_EMAIL
MAIL_FROM_NAME=Amazon Naval
MAIL_ENCRYPTION=ssl
EMAIL_CONTATO=contato@amazonnaval.com.br
```

Salve no `nano`:

- `Ctrl + O`
- `Enter`
- `Ctrl + X`

## 4. Recriar o container do sistema

Depois de mudar o `.env`, rode:

```bash
cd /opt/sistema-amazon
docker compose up -d --force-recreate app
```

Confira se o app subiu:

```bash
docker compose ps
```

## 5. Conferir se o sistema leu o .env correto

Rode:

```bash
docker compose exec -T app php -r 'require "config.php"; echo APP_URL.PHP_EOL.MAIL_HOST.PHP_EOL.MAIL_PORT.PHP_EOL.MAIL_USERNAME.PHP_EOL.MAIL_ENCRYPTION.PHP_EOL;'
```

Resultado esperado:

```text
https://sistema1.amazonnaval.com.br/
smtps.uhserver.com
465
contato@amazonnaval.com.br
ssl
```

## 6. Testar SMTP da UOL no VPS

Rode:

```bash
docker compose exec -T app sh -lc 'curl -v --connect-timeout 15 --max-time 30 --url smtps://smtps.uhserver.com:465 --user "$MAIL_USERNAME:$MAIL_PASSWORD"'
```

Se aparecer:

```text
235 2.7.0 Authentication successful
```

o login SMTP esta funcionando. Se aparecer erro `502` depois disso, pode ignorar; o importante e a autenticacao `235`.

## 7. Cloudflare SSL/TLS

Na Cloudflare, va em:

```text
SSL/TLS -> Overview
```

Use uma destas opcoes:

- `Flexible`: mais rapido se o VPS ainda nao tem certificado SSL proprio.
- `Full`: use quando o VPS tambem tiver SSL configurado.

Para resolver rapido, use `Flexible`.

## 8. Teste final

Abra:

```text
https://sistema1.amazonnaval.com.br/
```

Depois envie um e-mail pelo sistema. Os links internos do e-mail devem sair com:

```text
https://sistema1.amazonnaval.com.br/
```

e nao mais com:

```text
http://34.132.186.4:8082/
```

## 9. Se der erro de porta 80 ocupada

Confira o que esta usando a porta 80:

```bash
sudo ss -tulpn | grep ':80'
```

Se tiver Apache/Nginx do servidor fora do Docker ocupando a porta, pare o servico antes de recriar o app:

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
```

ou:

```bash
sudo systemctl stop nginx
sudo systemctl disable nginx
```

Depois rode novamente:

```bash
docker compose up -d --force-recreate app
```
