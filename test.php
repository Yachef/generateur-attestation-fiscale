<?php
require_once "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library files
// require 'PHPMailer/Exception.php';
// require 'PHPMailer/PHPMailer.php';
// require 'PHPMailer/SMTP.php';

$mail = new PHPMailer;
$mail->setFrom('info@nosleepingboy.fr', 'Yacine');
$mail->addReplyTo('info@nosleepingboy.fr', 'Yacine');

// Add a recipient
$mail->addAddress('yachef.h@gmail.com');

// // Add cc or bcc 
// $mail->addCC('cc@example.com');
// $mail->addBCC('bcc@example.com');

// Email subject
$mail->Subject = 'Salut ca va ?';

// Set email format to HTML
$mail->isHTML(true);

// Email body content
$mailContent = 'Salut ! Ca va super et toi ? Quoi de 9 ?';
$mail->Body = $mailContent;

// Send email
if(!$mail->send()){
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}else{
    echo 'Message has been sent';
}
?>