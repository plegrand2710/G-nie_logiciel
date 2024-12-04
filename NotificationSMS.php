<?php
namespace App;

class NotificationSMS extends Notification{

    private string $_message;

    function __construct(string $message ){
        if (empty($message)) {
            throw new \InvalidArgumentException("Le message ne peut pas être vide.");
        }
        $this->_message = $message;
    }

    public function NotificationSMS(): void {
        $this->envoyerNotification();
    }

}
?>