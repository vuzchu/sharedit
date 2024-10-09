<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the PHPMailer files
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

function sendOtp($recipientEmail, $otp)
{
    $mail = new PHPMailer(true);

    try {

        $mail->SMTPDebug = 2;  // You can change this to 3 for more detailed output, or 0 to disable

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Set the SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'chuvuz04112001@gmail.com';  // Your Gmail email
        $mail->Password = 'tziahhlnkeevrsvj';  // Your Gmail password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email settings
        $mail->setFrom('chuvuz04112001@gmail.com', 'Sharedit');
        $mail->addAddress($recipientEmail); // Add recipient

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification';
        $mail->Body    = "Your OTP code is: <strong>$otp</strong>";
        $mail->AltBody = "Your OTP code is: $otp";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
