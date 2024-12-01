<?php

abstract class Notification {
    private $_message;

    public function envoyerNotification() {
        // Méthode pour envoyer une notification
    }

    public function afficherNotification() {
        // Méthode pour afficher une notification
    }

    public function getMessage() {
        return $this->_message;
    }

    public function setMessage($message) {
        $this->_message = $message;
    }
}