# Deploy do Sistema Amazon no Ubuntu 24.04 com Docker

Este manual instala o sistema em um VPS Linux Ubuntu 24.04 usando Docker Compose.

## 1. Atualizar o servidor

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl gnupg git unzip nano ufw
sudo reboot
```

Depois do reboot, conecte novamente no servidor.

## 2. Instalar Docker e Docker Compose

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER
```

Saia do SSH e entre novamente para o grupo `docker` valer.

Teste:

```bash
docker --version
docker compose version
```

## 3. Liberar portas no firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 8082/tcp
sudo ufw allow 8083/tcp
sudo ufw allow 9002/tcp
sudo ufw allow 9003/tcp
sudo ufw enable
sudo ufw status
```

Portas:

- `8082`: sistema ERP
- `8083`: phpMyAdmin
- `9002`: MinIO API
- `9003`: MinIO Console

## 4. Baixar o sistema do GitHub

```bash
cd /opt
sudo git clone https://github.com/ronokedas/neto-amazon.git sistema-amazon
sudo chown -R $USER:$USER /opt/sistema-amazon
cd /opt/sistema-amazon
```

Como o repositório é privado, o GitHub pode pedir usuário e senha. Use seu usuário GitHub e um Personal Access Token como senha.

## 5. Configurar variáveis de ambiente

```bash
cp .env.example .env
nano .env
```

Altere principalmente:

```env
APP_URL=http://SEU_IP_OU_DOMINIO:8082/
DB_PASS=uma_senha_forte
MYSQL_ROOT_PASSWORD=outra_senha_forte
MINIO_ROOT_PASSWORD=outra_senha_forte
```

Se futuramente usar domínio com HTTPS, altere:

```env
APP_URL=https://seudominio.com/
```

## 6. Subir os containers

```bash
docker compose up -d --build
```

Verifique:

```bash
docker compose ps
docker compose logs -f app
```

Acesse:

- Sistema: `http://SEU_IP:8082`
- phpMyAdmin: `http://SEU_IP:8083`
- MinIO Console: `http://SEU_IP:9003`

## 7. Banco de dados inicial

Na primeira subida, o MySQL executa automaticamente estes arquivos:

- `docker/init-custom.sql`
- `docker/02-erp_sistema_mysql.sql`
- `docker/03-add_minio_columns.sql`

Se quiser importar um dump completo, coloque o arquivo no servidor e rode:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < db.sql
```

O terminal vai pedir a senha definida em `MYSQL_ROOT_PASSWORD`.

## 8. Permissões das pastas de runtime

Normalmente o Dockerfile já prepara as permissões. Se precisar corrigir:

```bash
docker compose exec app mkdir -p uploads logs storage/backups temp_pdf
docker compose exec app chown -R www-data:www-data uploads logs storage temp_pdf
docker compose exec app chmod -R 775 uploads logs storage temp_pdf
```

## 9. Criar bucket no MinIO

Acesse `http://SEU_IP:9003` com:

- usuário: valor de `MINIO_ROOT_USER`
- senha: valor de `MINIO_ROOT_PASSWORD`

Crie o bucket:

```text
erp-storage
```

Ou use o nome configurado em `MINIO_BUCKET`.

## 10. Comandos úteis

Status:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f
docker compose logs -f app
docker compose logs -f db
```

Reiniciar:

```bash
docker compose restart
```

Parar:

```bash
docker compose down
```

Subir:

```bash
docker compose up -d
```

Rebuild:

```bash
docker compose up -d --build
```

## 11. Atualizar o sistema no VPS

```bash
cd /opt/sistema-amazon
git pull
docker compose up -d --build
```

Se houver nova migration SQL manual, aplique uma por vez:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < migrations/048_pre_agendamentos_sem_data.sql
```

## 12. Backup do banco

Criar backup:

```bash
mkdir -p ~/backups-amazon
docker compose exec -T db mysqldump -u root -p erp_sistema > ~/backups-amazon/erp_sistema_$(date +%F_%H-%M).sql
```

Restaurar backup:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < ~/backups-amazon/arquivo.sql
```

## 13. Atualizar Ubuntu e imagens Docker

```bash
sudo apt update
sudo apt upgrade -y
cd /opt/sistema-amazon
docker compose pull
docker compose up -d --build
```

## 14. Segurança recomendada

- Troque todas as senhas do `.env`.
- Não envie `.env` para o GitHub.
- Evite deixar phpMyAdmin aberto publicamente em produção.
- Use HTTPS com Nginx Proxy Manager, Caddy ou Nginx quando apontar domínio.
- Faça backup antes de atualizar.
- Guarde os backups fora do VPS também.
