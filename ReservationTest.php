<?php
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase {
    private $creneau;
    private $activite;    
    private $creneauxActivite;

    private $creneauxActiviteReserve;
    private $personne;
    private $reservation;
    private $pdo;
    
    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();

        $this->activite = new Activite('Yoga', 20, '01:00:00');
        $this->activite->ajouterActiviteBDD();
        $activiteId = (int) $this->activite->getId();
        
        $this->creneau = new Creneau('09:00:00', '10:00:00');
        $this->creneau->ajouterCreneauBDD(); 
        $creneauId = (int) $this->creneau->getId(); 

        $this->creneauxActivite = new CreneauxActivite($creneauId, $activiteId);
        $this->creneauxActivite->ajouterCreneauxActivite($this->creneau->getId(), $this->activite->getId());

        $this->creneauxActiviteReserve = new CreneauxActiviteReserve("2024-12-20");
        $this->creneauxActiviteReserve->ajouterReservation($this->creneauxActivite);

        $this->personne = new Utilisateur("pauline", "pauk", "azerrtyuiop", "gfihoreiog@gmail.com", "123456789");
        $this->personne->ajouterDansLaBDD(); 

        $this->reservation = new Reservation((int) $this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int) $this->personne->getIdPersonne());
    }

    protected function tearDown(): void {
        $this->pdo->exec("DELETE FROM CreneauxActivite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Personne");
    }

    public function testConstructeurValide() {
        $this->assertEquals((int) $this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), $this->reservation->getCreneauActiviteReserve());
        $this->assertEquals($this->personne->getIdPersonne(), $this->reservation->getIdPersonne());
        $this->assertEquals('en attente', $this->reservation->getStatut());
        $this->assertInstanceOf(DateTime::class, $this->reservation->getDateExpiration());
    }

    public function testSetStatutValide() {
        $this->reservation->setStatut('confirmée');
        $this->assertEquals('confirmée', $this->reservation->getStatut());
        
        $this->reservation->setStatut('annulée');
        $this->assertEquals('annulée', $this->reservation->getStatut());
    }

    public function testSetStatutInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $this->reservation->setStatut('invalid');
    }

    public function testConfirmerReservation() {
        $this->reservation->setStatut('en attente');
        $this->assertTrue($this->reservation->confirmerReservation());
        $this->assertEquals('confirmée', $this->reservation->getStatut());
    }

    public function testAnnulerReservation() {
        $this->reservation->setStatut('confirmée');
        $this->reservation->annulerReservation();
        $this->assertEquals('annulée', $this->reservation->getStatut());
    }

    public function testAnnulerReservationDejaAnnulee() {
        $this->reservation->setStatut('annulée');
        $this->expectException(LogicException::class);
        $this->reservation->annulerReservation();
    }

    public function testEstExpirée() {
        $this->assertFalse($this->reservation->estExpirée());
        
        $reflection = new ReflectionClass($this->reservation);
        $property = $reflection->getProperty('_dateExpiration');
        $property->setAccessible(true);
        $property->setValue($this->reservation, (new DateTime())->modify('-1 day'));

        $this->assertTrue($this->reservation->estExpirée());
    }

    public function testGetCreneau() {
        $creneau = $this->reservation->getCreneau();
        $this->assertIsArray($creneau);
        $this->assertArrayHasKey('idCreneau', $creneau);
    }

    public function testGetActivite() {
        $activite = $this->reservation->getActivite();
        $this->assertIsArray($activite);
        $this->assertArrayHasKey('idActivite', $activite);
    }

    public function testSetDateExpiration() {
        $newDate = new DateTime('2024-12-01 12:00:00');
        $this->reservation->setDateExpiration($newDate);
        $this->assertEquals($newDate, $this->reservation->getDateExpiration());
    }

    public function testSetDateExpirationWithString() {
        $dateString = '2024-12-01 12:00:00';
        $this->reservation->setDateExpiration($dateString);
        $this->assertEquals(new DateTime($dateString), $this->reservation->getDateExpiration());
    }

    public function testSetDateExpirationInvalid() {
        $this->expectException(InvalidArgumentException::class);
        $this->reservation->setDateExpiration('invalid date');
    }

    public function testGetHeureDebut() {
        $heureDebut = $this->reservation->getHeureDebut();
        $this->assertNotEmpty($heureDebut);
    }

    public function testAjouterDansLaBDDValide() {
        $this->reservation->ajouterDansLaBDD(); 
    
        $stmt = $this->pdo->prepare("SELECT * FROM Reservation WHERE idReservation = :idReservation");
        $stmt->execute([':idReservation' => $this->reservation->getIdReservation()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $this->assertNotEmpty($result);
        $this->assertEquals($this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), $result['idCreneauxActiviteReserve']);
        $this->assertEquals($this->personne->getIdPersonne(), $result['idPersonne']);
    }

    public function testAjouterDansLaBDDCreneauInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $reservation = new Reservation(-1, $this->personne->getIdPersonne()); 
        $reservation->ajouterDansLaBDD();
    }

    public function testAjouterDansLaBDDUtilisateurInvalide() {
        $utilisateurInexistant = new Utilisateur('Utilisateur Inexistant', 'tes"tuser', 'password123', 'testuser@example.com', '1234567890');
        $this->expectException(InvalidArgumentException::class);
        $reservation = new Reservation(1, $utilisateurInexistant->getIdPersonne());
        $reservation->ajouterDansLaBDD(); 
    }

    public function testAjouterDansLaBDDPersonneNonValide() {
        $this->expectException(InvalidArgumentException::class);
        $reservation = new Reservation(1, new stdClass());
        $reservation->ajouterDansLaBDD(); 
    }
}
?>