<?php
/**
 * MAILER - Envio de e-mails via PHPMailer
 * 
 * Função central: enviarEmail()
 * Lê credenciais SMTP das constantes definidas em config.php
 * 
 * Uso:
 *   enviarEmail('destino@email.com', 'Nome', 'Assunto', '<html>...</html>');
 *   enviarEmail('destino@email.com', 'Nome', 'Assunto', '<html>...</html>', ['caminho/arquivo.pdf']);
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envia e-mail usando PHPMailer com configurações SMTP do config.php
 *
 * @param string $destinatario  E-mail do destinatário
 * @param string $nome          Nome do destinatário
 * @param string $assunto       Assunto do e-mail
 * @param string $htmlBody      Corpo do e-mail em HTML
 * @param array  $anexos        Array opcional com caminhos absolutos dos arquivos para anexar
 * 
 * @return array  ['success' => bool, 'message' => string]
 */
function enviarEmail(string $destinatario, string $nome, string $assunto, string $htmlBody, array $anexos = []): array
{
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Timeout e debug (desabilitado em produção)
        $mail->Timeout = 30;
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        // Remetente e destinatário
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($destinatario, $nome);
        $mail->addReplyTo(MAIL_USERNAME, MAIL_FROM_NAME);

        // Anexos
        if (!empty($anexos)) {
            foreach ($anexos as $caminho) {
                if (file_exists($caminho)) {
                    $mail->addAttachment($caminho);
                }
            }
        }

        // Conteúdo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $assunto;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();

        return [
            'success' => true,
            'message' => 'E-mail enviado com sucesso para ' . $destinatario
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo
        ];
    }
}