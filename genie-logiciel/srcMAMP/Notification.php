<?php

abstract class Notification {
    private $_message;

    abstract public function envoyerNotification(): bool;

    abstract public function afficherNotification(): void;

    public function getMessage(): string {
        return $this->_message;
    }

    public function setMessage(string $message): void {
        $this->_message = $message;
    }
}
?>