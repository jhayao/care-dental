<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './phpmailer/vendor/autoload.php';

function sendEmail($email, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dsaminodin@gmail.com'; // correct email
        $mail->Password   = 'qjauebemdnunzkjh';    // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        

        // Sender
        $mail->setFrom('dsaminodin@gmail.com', 'B-Dental Care');

        // Recipient
        if (is_array($email)) {
            foreach ($email as $e) {
                $mail->addAddress($e);
            }
        } else {
            $mail->addAddress($email);
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        echo 'Email has been sent successfully.';
    } catch (Exception $e) {
        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}

// Example usage
sendEmail('tare.kristian@gmail.com','Test Email','This is a test email from PHPMailer.');
