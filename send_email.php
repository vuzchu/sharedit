<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the PHPMailer files
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

function sendResetEmail($recipientEmail, $resetLink)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'chuvuz04112001@gmail.com';
        $mail->Password = 'tziahhlnkeevrsvj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email settings
        $mail->setFrom('chuvuz04112001@gmail.com', 'Sharedit');
        $mail->addAddress($recipientEmail);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body    = "Click the link below to reset your password: <br><a href='$resetLink'>Reset Password</a>";
        $mail->AltBody = "Click the link below to reset your password: $resetLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
