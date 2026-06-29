# Instalação no VPS - Sistema Amazon Moderno

Este guia contém todas as instruções para instalar o sistema em um VPS Linux.

## 📋 Pré-requisitos

- VPS com Ubuntu 20.04/22.04 ou Debian 11/12
- Acesso SSH com privilégios sudo
- Domínio apontando para o IP do VPS (opcional)
- Portas 80 e 443 liberadas no firewall

## 🗄️ Banco de Dados

### 1. Instalar MySQL/MariaDB

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y mysql-server

# Iniciar e habilitar o serviço
sudo systemctl start mysql
sudo systemctl enable mysql
```

### 2. Configurar Banco de Dados

```bash
# Acessar MySQL
sudo mysql

# No prompt do MySQL, executar:
CREATE DATABASE sistema_amazon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sistema_user'@'localhost' IDENTIFIED BY 'SenhaSegura123!';
GRANT ALL PRIVILEGES ON sistema_amazon.* TO 'sistema_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Importar Estrutura e Dados

```bash
# Navegar até o diretório do projeto
cd /var/www/sistema-amazon

# Importar estrutura principal
mysql -u sistema_user -p sistema_amazon < erp_sistema.sql

# Importar migrations (em ordem)
mysql -u sistema_user -p sistema_amazon < migrations/001_sequenciais_documentos.sql
mysql -u sistema_user -p sistema_amazon < migrations/002_alter_embarcacoes.sql
mysql -u sistema_user -p sistema_amazon < migrations/003_clientes.sql
mysql -u sistema_user -p sistema_amazon < migrations/004_seed_sequenciais.sql
mysql -u sistema_user -p sistema_amazon < migrations/005_servicos.sql
mysql -u sistema_user -p sistema_amazon < migrations/006_propostas.sql
mysql -u sistema_user -p sistema_amazon < migrations/007_alter_propostas.sql
mysql -u sistema_user -p sistema_amazon < migrations/008_agendamentos_ordens_servico.sql
mysql -u sistema_user -p sistema_amazon < migrations/009_vistorias_relatorio.sql
mysql -u sistema_user -p sistema_amazon < migrations/010_certificados_cnbl.sql
mysql -u sistema_user -p sistema_amazon < migrations/011_certificados_cnarq.sql
mysql -u sistema_user -p sistema_amazon < migrations/012_email_logs.sql
mysql -u sistema_user -p sistema_amazon < migrations/013_financeiro_soft_delete.sql
mysql -u sistema_user -p sistema_amazon < migrations/014_configuracoes.sql
mysql -u sistema_user -p sistema_amazon < migrations/015_certificados_lp.sql
mysql -u sistema_user -p sistema_amazon < migrations/016_certificados_lc.sql
mysql -u sistema_user -p sistema_amazon < migrations/017_certificados_cht.sql
mysql -u sistema_user -p sistema_amazon < migrations/018_corrigir_enum_lp.sql
mysql -u sistema_user -p sistema_amazon < migrations/019_cargo_vendedor.sql
```

### 4. (Opcional) Popular com Dados de Teste

```bash
mysql -u sistema_user -p sistema_amazon < seed_dados_ficticios_v3.sql
```

## 🌐 Servidor Web (Apache + PHP)

### 1. Instalar Dependências

```bash
sudo apt update
sudo apt install -y apache2 php php-mysql php-mbstring php-xml php-curl php-gd php-zip php-bcmath php-json php-opcache unzip git curl
```

### 2. Configurar Apache

```bash
# Criar arquivo de configuração do site
sudo nano /etc/apache2/sites-available/sistema-amazon.conf
```

Conteúdo do arquivo:
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com.br
    ServerAlias www.seu-dominio.com.br
    DocumentRoot /var/www/sistema-amazon
    
    <Directory /var/www/sistema-amazon>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sistema-amazon-error.log
    CustomLog ${APACHE_LOG_DIR}/sistema-amazon-access.log combined
</VirtualHost>
```

```bash
# Habilitar o site e módulos necessários
sudo a2ensite sistema-amazon.conf
sudo a2enmod rewrite
sudo a2enmod ssl  # Se for usar HTTPS
sudo systemctl reload apache2
```

### 3. Clonar o Repositório

```bash
# Navegar até o diretório web
cd /var/www

# Remover diretório padrão se existir
sudo rm -rf html

# Clonar o repositório
sudo git clone https://github.com/ronokedas/sistema-amazon-moderno.git sistema-amazon

# Ajustar permissões
sudo chown -R www-data:www-data sistema-amazon
sudo chmod -R 755 sistema-amazon
sudo chmod -R 775 sistema-amazon/assets
sudo chmod -R 775 sistema-amazon/uploads  # Criar se não existir
sudo chmod -R 775 sistema-amazon/templates
```

### 4. Configurar Conexão com Banco de Dados

```bash
# Editar arquivo de configuração
sudo nano /var/www/sistema-amazon/config.php
```

Atualizar as credenciais do banco de dados:
```php
// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_amazon');
define('DB_USER', 'sistema_user');
define('DB_PASS', 'SenhaSegura123!');
```

### 5. Configurar URL Base

```bash
sudo nano /var/www/sistema-amazon/includes/config.php
```

Atualizar a URL base:
```php
define('BASE_URL', 'https://seu-dominio.com.br');
```

## 🔒 HTTPS (SSL) - Let's Encrypt

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-apache

# Obter certificado SSL
sudo certbot --apache -d seu-dominio.com.br -d www.seu-dominio.com.br

# O Certbot configura automaticamente o Apache para redirecionar HTTP para HTTPS
```

## 📧 Configuração de E-mail

Edite o arquivo de configuração de e-mail:
```bash
sudo nano /var/www/sistema-amazon/includes/mailer.php
```

Configure com suas credenciais SMTP:
```php
define('SMTP_HOST', 'smtp.seudominio.com.br');
define('SMTP_PORT', 587);
define('SMTP_USER', 'email@seudominio.com.br');
define('SMTP_PASS', 'senha-do-email');
define('SMTP_FROM', 'email@seudominio.com.br');
define('SMTP_FROM_NAME', 'Sistema Amazon');
```

## 🔐 Segurança

### 1. Configurar Firewall

```bash
# Permitir SSH, HTTP e HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Habilitar firewall
sudo ufw enable
```

### 2. Criar Usuário Administrador

Acesse o sistema pela primeira vez e crie o usuário administrador através da interface de login, ou execute:

```bash
# Acessar o banco e criar admin manualmente
sudo mysql -u sistema_user -p sistema_amazon

# No MySQL:
INSERT INTO usuarios (nome, email, senha, cargo, ativo, data_criacao) 
VALUES ('Administrador', 'admin@seudominio.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());

-- Senha padrão: admin123 (criptografada acima)
EXIT;
```

### 3. Configurar .htaccess

O arquivo `.htaccess` já está configurado com regras de segurança básicas. Verifique se o Apache está permitindo overrides:

```bash
# Verificar se AllowOverride All está configurado (já incluído na configuração acima)
sudo nano /etc/apache2/apache2.conf

# Na seção <Directory /var/www/>, garantir que está:
# AllowOverride All
```

## 🔄 Atualizações Futuras

Para atualizar o sistema no VPS:

```bash
cd /var/www/sistema-amazon
sudo git pull origin main
sudo chown -R www-data:www-data .
```

## 📊 Verificar Logs

```bash
# Logs do Apache
sudo tail -f /var/log/apache2/sistema-amazon-error.log
sudo tail -f /var/log/apache2/sistema-amazon-access.log

# Logs do MySQL
sudo tail -f /var/log/mysql/error.log
```

## ✅ Checklist de Verificação Pós-Instalação

- [ ] Banco de dados criado e migrations executadas
- [ ] Arquivo config.php com credenciais corretas
- [ ] Apache configurado e site acessível
- [ ] Permissões de pasta corretas (www-data)
- [ ] HTTPS configurado com SSL válido
- [ ] E-mail SMTP configurado e testado
- [ ] Firewall ativo com portas corretas
- [ ] Usuário administrador criado
- [ ] Backup automático configurado (recomendado)

## 🗓️ Backup Recomendado

Configurar backup automático do banco de dados:

```bash
# Criar script de backup
sudo nano /usr/local/bin/backup-sistema.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u sistema_user -pSenhaSegura123! sistema_amazon > /backup/sistema_amazon_$DATE.sql
# Manter apenas últimos 7 dias
find /backup -name "sistema_amazon_*.sql" -mtime +7 -delete
```

```bash
# Tornar executável
sudo chmod +x /usr/local/bin/backup-sistema.sh

# Adicionar ao crontab (backup diário às 2h da manhã)
sudo crontab -e

# Adicionar linha:
0 2 * * * /usr/local/bin/backup-sistema.sh
```

## 🆘 Suporte

Para dúvidas ou problemas:
- Repositório: https://github.com/ronokedas/sistema-amazon-moderno
- Documentação: Consulte a pasta docs/

## 📝 Notas Importantes

1. **Senhas**: Altere todas as senhas padrão antes de colocar em produção
2. **Backup**: Configure backups regulares do banco de dados
3. **Atualizações**: Mantenha o sistema e dependências atualizados
4. **Monitoramento**: Considere configurar monitoramento de uptime
5. **Performance**: Para produção, considere usar Redis/Memcached para cache e OPcache para PHP