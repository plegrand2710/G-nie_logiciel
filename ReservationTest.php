<?php
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase {
    private $creneau;
    private $activite;
    private $utilisateur;

    protected function setUp(): void {
        $this->creneau = $this->createMock(Creneau::class);
        $this->activite = $this->createMock(Activite::class);
        $this->utilisateur = $this->createMock(Utilisateur::class);
    }

    public function testConstructeurValide() {
        $reservation = new Reservation(1, $this->creneau, $this->activite, $this->utilisateur);
        $this->assertEquals(1, $reservation->getId());
        $this->assertSame($this->creneau, $reservation->getCreneau());
        $this->assertSame($this->activite, $reservation->getActivite());
        $this->assertSame($this->utilisateur, $reservation->getPersonne());
        $this->assertEquals('en attente', $reservation->getStatut());
    }

    public function testConstructeurIdInvalide() {
        $this->expectException(InvalidArgumentException::class);
        new Reservation(-1, $this->creneau, $this->activite, $this->utilisateur);
    }

    public function testConstructeurIdDuplique() {
        new Reservation(20, $this->creneau, $this->activite, $this->utilisateur);
        $this->expectException(LogicException::class);
        new Reservation(20, $this->creneau, $this->activite, $this->utilisateur);
    }

    public function testConstructeurCreneauInvalide() {
        $this->expectException(InvalidArgumentException::class);
        new Reservation(2, null, $this->activite, $this->utilisateur);
    }

    public function testConstructeurActiviteInvalide() {
        $this->expectException(InvalidArgumentException::class);
        new Reservation(3, $this->creneau, null, $this->utilisateur);
    }

    public function testConstructeurPersonneInvalide() {
        $this->expectException(InvalidArgumentException::class);
        new Reservation(4, $this->creneau, $this->activite, null);
    }

    public function testExpirationReservation() {
        $reservation = new Reservation(5, $this->creneau, $this->activite, $this->utilisateur);
        $this->assertFalse($reservation->estExpirée());

        $reflection = new ReflectionClass($reservation);
        $property = $reflection->getProperty('_dateExpiration');
        $property->setAccessible(true);
        $property->setValue($reservation, (new DateTime())->modify('-1 day'));

        $this->assertTrue($reservation->estExpirée());
    }

    public function testAnnulerReservation() {
        $reservation = new Reservation(6, $this->creneau, $this->activite, $this->utilisateur);
        $reservation->setStatut('confirmée');
        $reservation->annulerReservation();
        $this->assertEquals('annulée', $reservation->getStatut());
    }

    public function testAnnulerReservationDejaAnnulee() {
        $reservation = new Reservation(7, $this->creneau, $this->activite, $this->utilisateur);
        $reservation->setStatut('confirmée');
        $reservation->annulerReservation();

        $this->expectException(LogicException::class);
        $reservation->annulerReservation();
    }

    public function testConfirmerReservation() {
        $reservation = new Reservation(8, $this->creneau, $this->activite, $this->utilisateur);
        $reservation->confirmerReservation();
        $this->assertEquals('confirmée', $reservation->getStatut());
    }

    public function testConfirmerReservationExpiree() {
        $reservation = new Reservation(9, $this->creneau, $this->activite, $this->utilisateur);

        $reflection = new ReflectionClass($reservation);
        $property = $reflection->getProperty('_dateExpiration');
        $property->setAccessible(true);
        $property->setValue($reservation, (new DateTime())->modify('-1 day'));

        $this->assertFalse($reservation->confirmerReservation());
    }

    public function testGetters() {
        $reservation = new Reservation(30, $this->creneau, $this->activite, $this->utilisateur);
    
        $this->assertEquals(30, $reservation->getId());
        $this->assertSame($this->creneau, $reservation->getCreneau());
        $this->assertSame($this->activite, $reservation->getActivite());
        $this->assertSame($this->utilisateur, $reservation->getPersonne());
        $this->assertEquals('en attente', $reservation->getStatut());
        $this->assertInstanceOf(DateTime::class, $reservation->getDateExpiration());
    }
    
    public function testSetCreneau() {
        $reservation = new Reservation(31, $this->creneau, $this->activite, $this->utilisateur);
    
        $nouveauCreneau = $this->createMock(Creneau::class);
        $reservation->setCreneau($nouveauCreneau);
        $this->assertSame($nouveauCreneau, $reservation->getCreneau());

        $this->expectException(InvalidArgumentException::class);
        $reservation->setCreneau(null);
    }
    
    public function testSetActivite() {
        $reservation = new Reservation(32, $this->creneau, $this->activite, $this->utilisateur);
    
        $nouvelleActivite = $this->createMock(Activite::class);
        $reservation->setActivite($nouvelleActivite);
        $this->assertSame($nouvelleActivite, $reservation->getActivite());

        $this->expectException(InvalidArgumentException::class);
        $reservation->setActivite(null);
    }
    
    public function testSetPersonne() {
        $reservation = new Reservation(33, $this->creneau, $this->activite, $this->utilisateur);
    
        $nouvelUtilisateur = $this->createMock(Personne::class);
        $reservation->setPersonne($nouvelUtilisateur);
        $this->assertSame($nouvelUtilisateur, $reservation->getPersonne());

        $this->expectException(InvalidArgumentException::class);
        $reservation->setPersonne(null);
    }
    
    public function testSetStatut() {
        $reservation = new Reservation(35, $this->creneau, $this->activite, $this->utilisateur);
    
        $reservation->setStatut('confirmée');
        $this->assertEquals('confirmée', $reservation->getStatut(), "La transition en attente -> confirmée a échoué.");
    
        $reservation->setStatut('annulée');
        $this->assertEquals('annulée', $reservation->getStatut(), "La transition confirmée -> annulée a échoué.");
    
        $this->expectException(LogicException::class);
        $reservation->setStatut('en attente');
    
        $reservation = new Reservation(36, $this->creneau, $this->activite, $this->utilisateur);
        $this->expectException(InvalidArgumentException::class);
        $reservation->setStatut('statut_inexistant');
    
        $reservation = new Reservation(37, $this->creneau, $this->activite, $this->utilisateur);
        $reservation->setStatut('annulée');
        $this->assertEquals('annulée', $reservation->getStatut(), "La transition en attente -> annulée a échoué.");
    
        $reservation = new Reservation(38, $this->creneau, $this->activite, $this->utilisateur);
        $reflection = new ReflectionClass($reservation);
        $property = $reflection->getProperty('_dateExpiration');
        $property->setAccessible(true);
        $property->setValue($reservation, (new DateTime())->modify('-1 day'));
    
        $this->assertTrue($reservation->estExpirée(), "La réservation devrait être expirée.");
        $this->assertEquals('expirée', $reservation->getStatut(), "Le statut devrait être 'expirée'.");
    }
}