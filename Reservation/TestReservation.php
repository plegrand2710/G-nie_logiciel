<?php
use PHPUnit\Framework\TestCase;

require_once 'Reservation.php';
require_once 'BaseDeDonnees.php';
require_once 'Personne.php';

class ReservationTest extends TestCase {
    private $mockPersonne;

    protected function setUp(): void {
        // Création d'un mock de la classe Personne
        $this->mockPersonne = $this->createMock(Personne::class);
    }

    public function testConstructValid() {
        $reservation = new Reservation(1, $this->mockPersonne);

        $this->assertNull($reservation->getId());
        $this->assertSame(1, $reservation->getGestionCreneauActiviteReserve());
        $this->assertSame($this->mockPersonne, $reservation->getPersonne());
        $this->assertSame('en attente', $reservation->getStatut());
        $this->assertInstanceOf(DateTime::class, $reservation->getDateExpiration());
    }

    public function testConstructInvalidId() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de gestion du créneau-activité doit être un entier positif.");

        new Reservation(-1, $this->mockPersonne);
    }

    public function testConstructInvalidPersonne() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La personne doit être une instance de la classe Personne.");

        new Reservation(1, new stdClass());
    }

    public function testSetIdValid() {
        $reservation = new Reservation(1, $this->mockPersonne);
        $reservation->setId(10);

        $this->assertSame(10, $reservation->getId());
    }

    public function testSetIdInvalid() {
        $reservation = new Reservation(1, $this->mockPersonne);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'id doit être un nombre supérieur à 0.");

        $reservation->setId(-5);
    }

    public function testSetDateExpirationValid() {
        $reservation = new Reservation(1, $this->mockPersonne);
        $dateString = '2024-12-10 10:00:00';
        $reservation->setDateExpiration($dateString);

        $this->assertInstanceOf(DateTime::class, $reservation->getDateExpiration());
        $this->assertSame($dateString, $reservation->getDateExpiration()->format('Y-m-d H:i:s'));
    }

    public function testSetDateExpirationInvalid() {
        $reservation = new Reservation(1, $this->mockPersonne);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La chaîne de date d'expiration n'est pas valide ou n'est pas au format 'Y-m-d H:i:s'.");

        $reservation->setDateExpiration('invalid-date');
    }

    public function testSetStatutValid() {
        $reservation = new Reservation(1, $this->mockPersonne);
        $reservation->setStatut('confirmée');

        $this->assertSame('confirmée', $reservation->getStatut());
    }

    public function testSetStatutInvalid() {
        $reservation = new Reservation(1, $this->mockPersonne);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le statut doit être l'un des suivants : en attente, confirmée, annulée, expirée");

        $reservation->setStatut('inconnu');
    }

    public function testEstExpiree() {
        $reservation = new Reservation(1, $this->mockPersonne);

        // Date d'expiration dans le passé
        $reservation->setDateExpiration('2024-01-01 00:00:00');
        $this->assertTrue($reservation->estExpirée());
        $this->assertSame('expirée', $reservation->getStatut());

        // Date d'expiration dans le futur
        $futureDate = (new DateTime())->modify('+1 day')->format('Y-m-d H:i:s');
        $reservation->setDateExpiration($futureDate);
        $this->assertFalse($reservation->estExpirée());
        $this->assertSame('expirée', $reservation->getStatut());
    }

    public function testConfirmerReservation() {
        $reservation = new Reservation(1, $this->mockPersonne);

        // Cas normal
        $this->assertTrue($reservation->confirmerReservation());
        $this->assertSame('confirmée', $reservation->getStatut());

        // Cas déjà confirmée
        $this->assertTrue($reservation->confirmerReservation());
        $this->assertSame('confirmée', $reservation->getStatut());
    }

    public function testAnnulerReservation() {
        $reservation = new Reservation(1, $this->mockPersonne);

        $this->assertNotNull($reservation->annulerReservation());
        $this->assertSame('annulée', $reservation->getStatut());
    }

    public function testAnnulerReservationAlreadyCancelled() {
        $reservation = new Reservation(1, $this->mockPersonne);
        $reservation->annulerReservation();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("La réservation est déjà annulée.");

        $reservation->annulerReservation();
    }
}
