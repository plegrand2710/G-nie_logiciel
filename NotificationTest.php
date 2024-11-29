<?php

use PHPUnit\Framework\TestCase;
use App\Notification;
use InvalidArgumentException;

class NotificationTest extends TestCase {

    public function testConstructor()
    {
        $message = "Ceci est un message de notification";
        $notification = new Notification($message);
        
        // Vérification que l'attribut 'message' existe dans l'objet
    $this->assertTrue(property_exists($notification, '_message'));

    // Vérification que le message est bien stocké
    $this->assertEquals($message, $notification->getMessage());
    }

    // Test du constructeur avec un message vide
    public function testConstructorWithInvalidMessage() {
        // Vérification que le constructeur lève une exception si le message est vide
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le message ne peut pas être vide.");

        new Notification(""); // Message vide, doit lever une exception
    }

    // Test de la création de la notification et de la récupération du message
    public function testNotificationMessage() {
        $message = "C'est un test de notification!";
        $notification = new Notification($message);

        // On vérifie que le message est bien celui attendu
        $this->assertEquals($message, $notification->getMessage());
    }

    // Test de la méthode envoyerNotification
    public function testEnvoyerNotification() {
        $message = "Test de notification!";
        $notification = new Notification($message);
    
        // Nous allons capturer la sortie pour vérifier si le script JS est bien généré
        ob_start();
        $notification->envoyerNotification();
        $output = ob_get_clean();
    
        // On vérifie que la sortie contient le code JavaScript correct
        $this->assertStringContainsString("<script>alert('Test de notification!');</script>", $output);
    }
    
}

?>
