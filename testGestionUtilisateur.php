<?php
use PHPUnit\Framework\TestCase;

class GestionUtilisateurTest extends TestCase {
    public function testAjouterReservationSuccess() {
        $gestionUtilisateur = new GestionUtilisateur();
        $reservation = new Reservation(1, "9h-10h", "Tennis", "Utilisateur");

        $gestionUtilisateur->ajouterReservation($reservation);

        $this->assertCount(1, $gestionUtilisateur->get_reservations());
        $this->assertSame($reservation, $gestionUtilisateur->get_reservations()[0]);
    }

    public function testAjouterReservationFailure() {
        $gestionUtilisateur = new GestionUtilisateur();

        $this->expectException(TypeError::class);
        $gestionUtilisateur->ajouterReservation(null);
    }

    public function testReserverSuccess() {
        $gestion = new GestionUtilisateur();
        $creneau = new Creneau("8h-9h");
        $activite = new Activite("Tennis");
        $utilisateur = new Utilisateur("John Doe");
    
        $reservation = $gestion->reserver($creneau, $activite, $utilisateur);
    
        $this->assertEquals("8h-9h", $reservation->getCreneau()->getPlageHoraire());
        $this->assertEquals("Tennis", $reservation->getActivite()->getNom());
    }
    
    public function testReserverCreneauInvalide() {
        $this->expectException(InvalidArgumentException::class);
    
        $gestion = new GestionUtilisateur();
        $creneau = new Creneau("");
        $activite = new Activite("Tennis");
        $utilisateur = new Utilisateur("John Doe");
    
        $gestion->reserver($creneau, $activite, $utilisateur);
    }

    public function testAnnulerReservationSuccess() {
        $gestionUtilisateur = new GestionUtilisateur();
        $reservation = $gestionUtilisateur->reserver("9h-10h", "Tennis", "Utilisateur");
    
        $message = $gestionUtilisateur->annulerReservation($reservation->getId());
    
        $this->assertEquals("Réservation " . $reservation->getId() . " annulée.", $message);
        $this->assertEquals("annulée", $reservation->getStatut());
    }
    
    public function testAnnulerReservationFailure() {
        $gestionUtilisateur = new GestionUtilisateur();
    
        $message = $gestionUtilisateur->annulerReservation(999);
        $this->assertEquals("Réservation introuvable.", $message);
    }

    public function testVerifierDisponibiliteSuccess() {
        $gestionUtilisateur = new GestionUtilisateur();
        $this->assertTrue($gestionUtilisateur->verifierDisponibilite("9h-10h"));
    }
    
    public function testVerifierDisponibiliteFailure() {
        $gestionUtilisateur = new GestionUtilisateur();
        $utilisateur = (object) ['nom' => 'John Doe'];
        $gestionUtilisateur->reserver("9h-10h", "Tennis", $utilisateur);
    
        $this->assertFalse($gestionUtilisateur->verifierDisponibilite("9h-10h"));
    }

    public function testAfficherCreneauxDisponiblesSuccess() {
        $gestionUtilisateur = new GestionUtilisateur();
        $utilisateur = (object) ['nom' => 'John Doe'];
        $gestionUtilisateur->reserver("9h-10h", "Tennis", $utilisateur);
        $gestionUtilisateur->reserver("10h-11h", "Basketball", $utilisateur);
    
        $creneaux = $gestionUtilisateur->afficherCreneauxDisponibles();
    
        $this->assertCount(2, $creneaux);
        $this->assertContains("9h-10h", $creneaux);
        $this->assertContains("10h-11h", $creneaux);
    }
    
    public function testAfficherCreneauxDisponiblesEmpty() {
        $gestionUtilisateur = new GestionUtilisateur();
        $creneaux = $gestionUtilisateur->afficherCreneauxDisponibles();
    
        $this->assertEmpty($creneaux);
    }
}

?>