# Instalação via Docker - Sistema Amazon Moderno

Este guia explica como rodar o sistema usando Docker e Docker Compose.

## 📋 Pré-requisitos

- Docker Engine 20.10+ instalado
- Docker Compose 2.0+ instalado
- Git
- 2GB de RAM disponível
- 5GB de espaço em disco

## 🚀 Instalação Rápida

### 1. Clonar o Repositório

```bash
git clone https://github.com/ronokedas/sistema-amazon-moderno.git
cd sistema-amazon
```

### 2. Iniciar os Containers

```bash
# Primeira vez (build + up)
docker-compose up -d --build

# Próximas vezes (apenas start)
docker-compose up -d
```

### 3. Verificar se os Containers Estão Rodando

```bash
docker-compose ps
```

Você deve ver 3 containers ativos:
- `erp_app` (Apache + PHP)
- `erp_db` (MySQL 8.0)
- `erp_phpmyadmin` (phpMyAdmin - opcional)

### 4. Acessar o Sistema

- **Sistema:** http://localhost:8082
- **phpMyAdmin:** http://localhost:8083
  - Usuário: `erp_user`
  - Senha: `erp_pass_2026`

## 🗄️ Banco de Dados

O banco de dados é automaticamente inicializado na primeira execução:

1. **Estrutura principal:** `docker/02-erp_sistema_mysql.sql`
2. **Configurações customizadas:** `docker/init-custom.sql` (timezone, sql_mode)

### Credenciais do Banco

- **Host:** `db` (interno) / `localhost:3307` (externo)
- **Database:** `erp_sistema`
- **Usuário:** `erp_user`
- **Senha:** `erp_pass_2026`
- **Root:** `root_pass_2026`

## ⚙️ Configurações

### Variáveis de Ambiente

Edite o `docker-compose.yml` para alterar configurações:

```yaml
services:
  app:
    environment:
      - DB_HOST=db
      - DB_NAME=erp_sistema
      - DB_USER=erp_user
      - DB_PASS=erp_pass_2026
      - APP_URL=http://localhost:8082/
```

### Portas

Altere as portas no `docker-compose.yml` se necessário:

```yaml
services:
  app:
    ports:
      - "8080:80"  # Host:Container
  
  db:
    ports:
      - "3306:3306"  # Host:Container
  
  phpmyadmin:
    ports:
      - "8081:80"  # Host:Container
```

## 📝 Comandos Úteis

### Gerenciamento de Containers

```bash
# Iniciar
docker-compose up -d

# Parar
docker-compose stop

# Reiniciar
docker-compose restart

# Parar e remover containers
docker-compose down

# Parar e remover containers + volumes (CUIDADO: apaga dados!)
docker-compose down -v
```

### Logs

```bash
# Logs de todos os serviços
docker-compose logs -f

# Logs de um serviço específico
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f phpmyadmin
```

### Acesso aos Containers

```bash
# Acessar container da aplicação
docker-compose exec app bash

# Acessar MySQL
docker-compose exec db mysql -u erp_user -p erp_sistema

# Acessar phpMyAdmin (navegador)
# http://localhost:8083
```

### Backup do Banco de Dados

```bash
# Exportar banco
docker-compose exec db mysqldump -u erp_user -p erp_pass_2026 erp_sistema > backup.sql

# Importar banco
docker-compose exec -T db mysql -u erp_user -p erp_pass_2026 erp_sistema < backup.sql
```

### Rebuild (após alterações no código)

```bash
# Rebuild da aplicação
docker-compose build app

# Restart
docker-compose up -d
```

## 🔧 Troubleshooting

### Problema: Porta já em uso

```bash
# Verificar qual processo está usando a porta
sudo netstat -tulpn | grep :8082

# Alterar porta no docker-compose.yml
ports:
  - "8080:80"  # Use outra porta
```

### Problema: Banco não inicializa

```bash
# Verificar logs do MySQL
docker-compose logs db

# Remover volume e recriar (CUIDADO: apaga dados!)
docker-compose down -v
docker-compose up -d
```

### Problema: Permissões de pasta

```bash
# No host (Linux/Mac)
sudo chown -R $USER:$USER .

# Dentro do container (já configurado no Dockerfile)
```

### Problema: Erro de conexão com banco

```bash
# Verificar se o container do banco está saudável
docker-compose ps

# Testar conexão
docker-compose exec app ping db

# Verificar variáveis de ambiente
docker-compose exec app env | grep DB
```

## 🛠️ Desenvolvimento

### Modo de Desenvolvimento (com hot-reload)

Edite o `docker-compose.yml` para adicionar volume mount:

```yaml
services:
  app:
    volumes:
      - ./:/var/www/html  # Já está configurado
```

Alterações no código são refletidas imediatamente (não precisa rebuild).

### Instalar dependências PHP

```bash
# Acessar container
docker-compose exec app bash

# Instalar via Composer
composer install

# Ou no host
docker-compose exec app composer install
```

## 📦 Estrutura de Arquivos Docker

```
sistema-amazon/
├── docker-compose.yml          # Orquestração dos serviços
├── Dockerfile                  # Build da aplicação PHP
├── docker/
│   ├── 000-default.conf       # Configuração Apache
│   ├── php.ini                # Configurações PHP
│   ├── init-custom.sql        # Inicialização customizada MySQL
│   └── 02-erp_sistema_mysql.sql  # Estrutura do banco
└── docs/
    └── INSTALACAO_DOCKER.md   # Este arquivo
```

## 🔒 Segurança em Produção

### NÃO use em produção sem antes:

1. **Alterar senhas padrão** no `docker-compose.yml`
2. **Remover phpMyAdmin** ou restringir acesso:
   ```yaml
   phpmyadmin:
     ports:
       - "127.0.0.1:8083:80"  # Apenas localhost
   ```
3. **Usar variáveis de ambiente** (arquivo `.env`):
   ```bash
   # Criar .env
   DB_PASS=senha_super_segura
   MYSQL_ROOT_PASSWORD=root_super_segura
   
   # Usar no docker-compose.yml
   environment:
     - DB_PASS=${DB_PASS}
   ```
4. **Configurar SSL/HTTPS** com proxy reverso (Nginx/Traefik)
5. **Backup regular** dos volumes Docker

### Exemplo com variáveis de ambiente

Crie um arquivo `.env`:

```env
# Banco de Dados
MYSQL_ROOT_PASSWORD=root_super_segura_2026
MYSQL_DATABASE=erp_sistema
MYSQL_USER=erp_user
MYSQL_PASSWORD=senha_super_segura_2026

# Aplicação
DB_HOST=db
DB_NAME=erp_sistema
DB_USER=erp_user
DB_PASS=senha_super_segura_2026
APP_URL=https://seudominio.com.br
```

Atualize o `docker-compose.yml`:

```yaml
services:
  db:
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
  
  app:
    environment:
      - DB_HOST=${DB_HOST}
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASS=${DB_PASS}
      - APP_URL=${APP_URL}
```

Adicione `.env` ao `.gitignore`:

```bash
echo ".env" >> .gitignore
```

## 🚀 Deploy em Produção com Docker

### Opção 1: Docker Compose direto

```bash
# No servidor
git clone https://github.com/ronokedas/sistema-amazon-moderno.git
cd sistema-amazon

# Configurar .env com credenciais de produção
cp .env.example .env
nano .env

# Iniciar
docker-compose up -d --build
```

### Opção 2: Docker Swarm

```bash
# Inicializar swarm
docker swarm init

# Deploy do stack
docker stack deploy -c docker-compose.yml sistema-amazon
```

### Opção 3: Kubernetes

Converta o `docker-compose.yml` para Kubernetes:

```bash
# Usar kompose
kompose convert -f docker-compose.yml

# Aplicar
kubectl apply -f .
```

## 📊 Monitoramento

### Verificar uso de recursos

```bash
# Estatísticas em tempo real
docker stats

# Uso de espaço
docker system df
```

### Health Check

O container do MySQL já possui healthcheck configurado. Para adicionar na aplicação:

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 3s
      retries: 3
```

## 🔄 Atualizações

```bash
# Atualizar código
git pull origin main

# Rebuild e restart
docker-compose down
docker-compose up -d --build

# Verificar logs
docker-compose logs -f
```

## 📋 Checklist Pós-Instalação

- [ ] Containers rodando: `docker-compose ps`
- [ ] Sistema acessível: http://localhost:8082
- [ ] Banco de dados inicializado
- [ ] phpMyAdmin acessível (se necessário): http://localhost:8083
- [ ] Logs sem erros: `docker-compose logs`
- [ ] Backup configurado
- [ ] Senhas alteradas para produção
- [ ] `.env` adicionado ao `.gitignore`

## 🆘 Suporte

- **Repositório:** https://github.com/ronokedas/sistema-amazon-moderno
- **Issues:** Reporte problemas no GitHub
- **Documentação:** Consulte também `docs/INSTALACAO_VPS.md` para instalação tradicional

## 📝 Notas

1. **Desenvolvimento:** Use `docker-compose up -d` e edite arquivos diretamente (hot-reload)
2. **Produção:** Sempre use variáveis de ambiente, nunca commit credenciais
3. **Backup:** Regularmente faça backup dos volumes Docker
4. **Performance:** Ajuste recursos no Docker Compose se necessário:
   ```yaml
   services:
     app:
       deploy:
         resources:
           limits:
             cpus: '1'
             memory: 1G
   ```
5. **Windows/Mac:** Docker Desktop funciona perfeitamente, apenas garanta ter recursos suficientes alocados