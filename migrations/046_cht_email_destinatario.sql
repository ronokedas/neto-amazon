ALTER TABLE `certificados_cht`
  ADD COLUMN `email_destinatario` varchar(150) DEFAULT NULL AFTER `cpf_cnpj`;
