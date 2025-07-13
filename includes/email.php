<?php
// includes/emails.php

// Inclua a biblioteca PHPMailer (se ainda não o fez)
// Você precisará baixar e configurar o PHPMailer.
// Exemplo de como incluir (se usar Composer para gerenciar dependências):
// require_once __DIR__ . '/../vendor/autoload.php'; 
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

/**
 * Função para enviar e-mails.
 * Requer configuração de um servidor SMTP.
 *
 * @param string $to_email Endereço de e-mail do destinatário.
 * @param string $to_name Nome do destinatário.
 * @param string $subject Assunto do e-mail.
 * @param string $body Conteúdo do e-mail (pode ser HTML).
 * @return bool True se o e-mail foi enviado com sucesso, false caso contrário.
 */
function send_email($to_email, $to_name, $subject, $body) {
    // Para fins de desenvolvimento e teste, vamos apenas logar o e-mail.
    // Em um ambiente de produção, você integraria uma biblioteca como PHPMailer ou um serviço de e-mail.

    $log_message = "--- INÍCIO DO E-MAIL ---\n";
    $log_message .= "Para: {$to_name} <{$to_email}>\n";
    $log_message .= "Assunto: {$subject}\n";
    $log_message .= "Corpo:\n{$body}\n";
    $log_message .= "--- FIM DO E-MAIL ---\n\n";

    // Salva o e-mail em um arquivo de log para simular o envio
    $log_file = __DIR__ . '/../logs/email_log.txt';
    // Garante que o diretório logs exista
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    file_put_contents($log_file, $log_message, FILE_APPEND);

    // Em um ambiente real, você faria algo como:
    /*
    $mail = new PHPMailer(true);
    try {
        // Configurações do Servidor SMTP (EXEMPLO - SUBSTITUA PELAS SUAS)
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Seu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'seu_email@example.com'; // Seu e-mail
        $mail->Password = 'sua_senha'; // Sua senha
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Ou PHPMailer::ENCRYPTION_SMTPS
        $mail->Port = 587; // Ou 465 para SMTPS

        // Remetente
        $mail->setFrom('noreply@sisreserva.com.br', 'SISReserva'); // Seu e-mail e nome

        // Destinatário
        $mail->addAddress($to_email, $to_name);

        // Conteúdo
        $mail->isHTML(true); // Definir formato de e-mail para HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Versão em texto plano para clientes de e-mail que não suportam HTML

        $mail->send();
        error_log("E-mail enviado com sucesso para {$to_email}");
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail para {$to_email}. Erro do Mailer: {$mail->ErrorInfo}");
        return false;
    }
    */
    
    // Para o nosso propósito de teste, sempre retorna true (simula sucesso)
    return true;
}
?>