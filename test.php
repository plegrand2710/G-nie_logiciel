<?php

use PHPUnit\Framework\TestCase;

require_once 'GestionUtilisateur.php';
require_once 'GestionSuperUtilisateur.php';
require_once 'Reservation.php';

class GestionTest extends TestCase {
    public function testSuperUtilisateurHeredite() {
        $gestionSuperUtilisateur = new GestionSuperUtilisateur();
        $utilisateur = (object) ['nom' => 'John Doe']; // Simulation d'un utilisateur
        $reservation = $gestionSuperUtilisateur->reserver("8h-9h", "Tennis", $utilisateur);

        $this->assertInstanceOf(GestionUtilisateur::class, $gestionSuperUtilisateur);
        $this->assertEquals("8h-9h", $reservation->getCreneau());
    }

    public function testAggregationReservation() {
        $gestionUtilisateur = new GestionUtilisateur();
        $utilisateur = (object) ['nom' => 'Jane Doe'];
        $reservation = $gestionUtilisateur->reserver("10h-11h", "Basketball", $utilisateur);

        $this->assertEquals("Basketball", $reservation->getActivite());
        $this->assertTrue($gestionUtilisateur->verifierDisponibilite("12h-13h"));
        $this->assertFalse($gestionUtilisateur->verifierDisponibilite("10h-11h"));
    }

    public function testAnnulationReservation() {
        $gestionUtilisateur = new GestionUtilisateur();
        $utilisateur = (object) ['nom' => 'Jane Doe'];
        $reservation = $gestionUtilisateur->reserver("10h-11h", "Tennis", $utilisateur);

        $gestionUtilisateur->annulerReservation($reservation->getId());
        $this->assertEquals("annulée", $reservation->getStatut());
    }

    public function testModificationCreneauSuperUtilisateur() {
        $gestionSuperUtilisateur = new GestionSuperUtilisateur();
        $utilisateur = (object) ['nom' => 'John Doe'];
        $reservation = $gestionSuperUtilisateur->reserver("8h-9h", "Tennis", $utilisateur);

        $gestionSuperUtilisateur->modificationCreneau($reservation, "9h-10h");
        $this->assertEquals("9h-10h", $reservation->getCreneau());
    }

    public function testAjouterReservation() {
        $gestionUtilisateur = new GestionUtilisateur();
        $reservation = new Reservation(1, "9h-10h", "Tennis", "Utilisateur");

        $gestionUtilisateur->ajouterReservation($reservation);

        $this->assertCount(1, $gestionUtilisateur->get_reservations());
        $this->assertSame($reservation, $gestionUtilisateur->get_reservations()[0]);
    }
}