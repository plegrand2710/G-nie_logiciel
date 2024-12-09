<?php
require './../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationEmail extends Notification {
    private $_email;

    public function __construct(string $email, string $message) {
        $this->_email = $email;
        $this->setMessage($message);
    }

    public function envoyerNotification(): bool {
        $mail = new PHPMailer(true); 

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'sallefit7@gmail.com';
            $mail->Password = 'kkwjzkwvsadfjfrq';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('sallefit7@gmail.com', 'SalleFit7');
            $mail->addAddress($this->_email); 

            $mail->isHTML(true);
            $mail->Subject = "Notification de Nouveau Message";

            $mail->Body    = $this->getMessage();  
            $mail->AltBody = strip_tags($this->getMessage());  

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function afficherNotification(): void {
        echo "Notification Email : {$this->getMessage()}\n";
    }
}
?>