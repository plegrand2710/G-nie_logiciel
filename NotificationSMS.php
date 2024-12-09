<?php
class NotificationSMS extends Notification {
    private $_numero;
    public function __construct(string $numero, string $message) {
        $this->_numero = $numero;
        $this->setMessage($message);
    }

    public function envoyerNotification(): bool {

        echo "Envoi du SMS au numéro {$this->_numero}: {$this->getMessage()}\n";
        return true;
    }

    public function afficherNotification(): void {
        echo "Notification SMS : {$this->getMessage()}\n";
    }
}
?>
