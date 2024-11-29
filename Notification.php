<?php

namespace App;

class Notification{

    private string $_message;

    function __construct(string $message ){
        if (empty($message)) {
            throw new \InvalidArgumentException("Le message ne peut pas être vide.");
        }
        $this->_message = $message;
    }

    public function getMessage(): string {
        return $this->_message;
    }

    public function envoyerNotification(): void {
        //envoie un pop up sur la page web
        echo "<script>alert('" . addslashes($this->_message) . "');</script>";
    }
}
?>