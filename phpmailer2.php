<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader

require './phpmailer/vendor/autoload.php';
require './phpmailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require './phpmailer/vendor/phpmailer/phpmailer/src/Exception.php';
require './phpmailer/vendor/phpmailer/phpmailer/src/SMTP.php';

function sendEmail($email, $subject, $message) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // SMTP settings for your external email provider
        // $mail->SMTPDebug = 3;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Your SMTP server host
        $mail->SMTPAuth   = true;               // SMTP authentication
        $mail->Username   = 'dsaminodin@gmail.com'; // Your SMTP username
        $mail->Password   = 'qjauebemdnunzkjh';       // Your SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;              // SMTP port

        // Sender and recipient
        $mail->setFrom('dsaminodin@gmail.com','B-Dental Care');

        if(is_array($email)){
            foreach($email as $e){
                $mail->addAddress($e, 'Admin');     //Add a recipient
            }
        }else{
            $mail->addAddress($email, 'Admin');     //Add a recipient
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = 'This is the plain text message body';

        // Send the email
        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}


?>