<?php
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase {
    public function testConstructorValid() {
        $creneau = new Creneau("8h-9h");
        $activite = new Activite("Tennis");
        $utilisateur = new Utilisateur("John Doe");

        $reservation = new Reservation(1, $creneau, $activite, $utilisateur);

        $this->assertEquals(1, $reservation->getId());
        $this->assertEquals("8h-9h", $reservation->getCreneau()->getPlageHoraire());
    }

    public function testConstructorInvalidId() {
        $this->expectException(InvalidArgumentException::class);
        new Reservation(-1, new Creneau("8h-9h"), new Activite("Tennis"), new Utilisateur("John Doe"));
    }

    public function testSetStatutInvalid() {
        $this->expectException(InvalidArgumentException::class);

        $reservation = new Reservation(1, new Creneau("8h-9h"), new Activite("Tennis"), new Utilisateur("John Doe"));
        $reservation->setStatut("invalide");
    }

    public function testConfirmerReservationInvalidStatut() {
        $this->expectException(LogicException::class);

        $reservation = new Reservation(1, new Creneau("8h-9h"), new Activite("Tennis"), new Utilisateur("John Doe"));
        $reservation->setStatut("confirmée");
        $reservation->confirmerReservation();
    }

    public function testAnnulerReservationAlreadyCancelled() {
        $this->expectException(LogicException::class);

        $reservation = new Reservation(1, new Creneau("8h-9h"), new Activite("Tennis"), new Utilisateur("John Doe"));
        $reservation->setStatut("annulée");
        $reservation->annulerReservation();
    }
}

?>