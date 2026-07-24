<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send an email using PHPMailer with optional CC, BCC, attachments.
 *
 * @param string $to            Recipient email address.
 * @param string $subject       Email subject.
 * @param string $body          Email body (HTML or plain text).
 * @param bool   $isHtml        Whether the body is HTML (default: true).
 * @param string|null $cc       CC recipient(s) – comma separated or array.
 * @param string|null $bcc      BCC recipient(s) – comma separated or array.
 * @param array  $attachments   Array of file paths or arrays with 'path' and 'name'.
 * @param string|null $from     Sender email (optional, defaults to noreply@bluesun.com).
 * @param string|null $fromName Sender name (optional, defaults to 'BlueSun Call Sheet').
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function sendEmail($to, $subject, $body, $isHtml = true, $cc = null, $bcc = null, $attachments = [], $from = null, $fromName = null) {
    // Default sender
    $from = $from ?? 'noreply@bluesun.com';
    $fromName = $fromName ?? 'BlueSun Call Sheet';

    // Handle CC and BCC if passed as strings (comma separated)
    if (is_string($cc)) {
        $cc = array_map('trim', explode(',', $cc));
    }
    if (is_string($bcc)) {
        $bcc = array_map('trim', explode(',', $bcc));
    }

    $mail = new PHPMailer(true);
    try {
        // ----- SMTP Configuration -----
        // You can also use environment variables: getenv('SMTP_HOST'), etc.
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reymarklarida.bspi@gmail.com';
        $mail->Password   = 'tuog yusp qify znvp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ----- Sender -----
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        // CC
        if (!empty($cc)) {
            foreach ($cc as $ccEmail) {
                if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($ccEmail);
                }
            }
        }

        // BCC
        if (!empty($bcc)) {
            foreach ($bcc as $bccEmail) {
                if (filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($bccEmail);
                }
            }
        }

        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    // Assume it's a file path
                    $mail->addAttachment($attachment);
                } elseif (is_array($attachment) && isset($attachment['path'])) {
                    $name = $attachment['name'] ?? basename($attachment['path']);
                    $mail->addAttachment($attachment['path'], $name);
                }
            }
        }

        // ----- Content -----
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'error'   => null
        ];
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo;
        error_log("PHPMailer Error: " . $errorMsg);
        return [
            'success' => false,
            'message' => 'Email failed',
            'error'   => $errorMsg
        ];
    }
}