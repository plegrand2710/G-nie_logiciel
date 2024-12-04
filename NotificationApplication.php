<?php

namespace App;

class NotificationApplication extends Notification{

    private string $_message;

    function __construct(string $message ){
        if (empty($message)) {
            throw new \InvalidArgumentException("Le message ne peut pas être vide.");
        }
        $this->_message = $message;
    }

    public function NotificationApplication(): void {
        //L'application mobile envoie une requête PHP et la méthode renvoie une réponse contenant le message
        $response = [
            "status" => "success",
            "message" => $this->_message
        ];
        echo json_encode($response);
            
    }
}
?>