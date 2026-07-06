# Manual de Backup do Banco de Dados (VPS com Docker)

Este documento detalha como configurar e manter a rotina de backup automático do banco de dados do Sistema Amazon em um ambiente VPS rodando com Docker.

## Visão Geral

O backup é gerado através do script `scripts/backup_docker.sh`. 
Como o sistema roda em containers, este script deve ser executado **diretamente no sistema operacional hospedeiro (VPS)**, e não dentro de um container. Ele se comunica com o container do MySQL (`erp_db`) para extrair os dados de forma segura.

O script executa as seguintes ações:
1. Conecta-se ao container MySQL e extrai o banco de dados via `mysqldump`.
2. Compacta o arquivo resultante (`.sql`) usando `gzip` (gerando `.sql.gz`).
3. Salva o backup compactado na pasta `storage/backups/`.
4. Limpa automaticamente backups antigos, mantendo apenas os **últimos 30 arquivos**.

---

## ⚙️ Passo a Passo para Configuração

### 1. Dar Permissão de Execução ao Script
Após clonar o repositório ou enviar os arquivos para a VPS, acesse a pasta raiz do projeto no terminal da VPS e conceda permissão de execução ao script:

```bash
cd /caminho/para/o/seu/projeto
chmod +x scripts/backup_docker.sh
```

### 2. Configurar a Tarefa Automática (Cron)
Para que o backup rode sozinho todos os dias (ex: às 03:00 da manhã), você precisa adicioná-lo ao `cron` do usuário root ou do usuário que gerencia o Docker na sua VPS.

1. Abra o editor do Cron no terminal da VPS:
   ```bash
   crontab -e
   ```

2. Adicione a linha abaixo no final do arquivo (substituindo `/caminho/para/o/projeto/` pelo caminho real onde o sistema está):
   ```bash
   0 3 * * * /caminho/para/o/projeto/scripts/backup_docker.sh >> /caminho/para/o/projeto/storage/backups/cron_backup.log 2>&1
   ```
   > **Dica:** O trecho `>> .../cron_backup.log 2>&1` faz com que qualquer mensagem ou erro gerado durante a madrugada seja salvo em um arquivo de log na mesma pasta dos backups, facilitando a identificação de problemas.

3. Salve e feche o arquivo. O próprio sistema Linux avisará `crontab: installing new crontab`, confirmando que o agendamento está ativo.

---

## 🧪 Testando o Backup Manualmente

Para garantir que tudo está funcionando perfeitamente, você pode rodar o backup manualmente a qualquer momento.

Na pasta raiz do projeto, execute:
```bash
./scripts/backup_docker.sh
```

Aguarde alguns segundos. Se tudo der certo, você verá a mensagem `Processo finalizado com sucesso.` e um arquivo compactado aparecerá na pasta `storage/backups/`.

---

## ☁️ Recomendação de Segurança (Backups Externos)

Embora esta rotina garanta que você tenha 30 dias de backup salvos, **todos eles ficam no mesmo servidor (VPS) onde o sistema está hospedado.** Se ocorrer uma falha grave na VPS (queima de disco, invasão ou bloqueio da conta na provedora), os backups serão perdidos junto com o sistema.

**Sugestões para evoluir o sistema no futuro:**
1. **Google Drive / Dropbox (via Rclone):** Instale o `rclone` na sua VPS e adicione uma linha no final do `backup_docker.sh` para sincronizar a pasta `storage/backups/` com a nuvem.
   Exemplo: `rclone sync storage/backups/ meu_gdrive:Backups_Sistema`
2. **AWS S3 / DigitalOcean Spaces:** Usar a ferramenta `aws-cli` na VPS para copiar os arquivos `.gz` diretamente para um bucket seguro usando `aws s3 cp ...`.