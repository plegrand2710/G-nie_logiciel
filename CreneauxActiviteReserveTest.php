<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/CreneauxActivite.php';
require_once __DIR__ . '/../src/CreneauxActiviteReserve.php';

class CreneauxActiviteReserveTest extends TestCase
{
    private $_pdo;
    private $_gestionCreneaux;
    private $_date;
    private $_idActivite;
    private $_idCreneau;
    private $_idCreneauxActivite;
    private $_creneauxActivite;
    
    protected function setUp(): void
    {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->_date = '2024-12-01';

        $activite = new Activite('Yoga' . uniqid(), 20.0, '00:30:00');
        $activite->ajouterActiviteBDD();
        $this->_idActivite =(int) $activite->getId();

        $calendrier = new Calendrier('08:00:00', '21:00:00');
        $calendrier->ajouterCalendrierDansBDD();

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $this->_idActivite]);
        $countActivite = $stmt->fetchColumn();
        $this->assertGreaterThan(0, $countActivite, "L'activité doit être insérée correctement.");

        $creneau = new Creneau('11:00:00', '11:30:00');
        $creneau->ajouterCreneauBDD();
        $this->_idCreneau =(int) $creneau->getId();

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $this->_idCreneau]);
        $countCreneau = $stmt->fetchColumn();
        $this->assertGreaterThan(0, $countCreneau, "Le créneau doit être inséré correctement.");

        $this->_creneauxActivite = new CreneauxActivite($this->_idCreneau, $this->_idActivite);

        $this->_creneauxActivite->set_IdCreneau((int)$this->_idCreneau);
        $this->_creneauxActivite->set_IdActivite((int)$this->_idActivite);
        $this->_creneauxActivite->ajouterCreneauxActivite();
        $this->_idCreneauxActivite =(int) $this->_creneauxActivite->get_idCreneauxActivite(); 
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => $this->_idCreneauxActivite]);
        $countCreneauxActivite = $stmt->fetchColumn();
        $this->assertGreaterThan(0, $countCreneauxActivite, "La relation CreneauxActivite doit être insérée correctement.");

        $this->_gestionCreneaux = new CreneauxActiviteReserve($this->_date);
    }

    protected function tearDown(): void
    {
        $stmt = $this->_pdo->prepare("DELETE FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => $this->_idCreneauxActivite]);

        $stmt = $this->_pdo->prepare("DELETE FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => $this->_idCreneauxActivite]);

        $stmt = $this->_pdo->prepare("DELETE FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $this->_idActivite]);

        $stmt = $this->_pdo->prepare("DELETE FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $this->_idCreneau]);
    }
    public function testAjouterReservation(): void{
        $this->_gestionCreneaux->set_reserver(true);
        
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date AND reserver = 1");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $count = $stmt->fetchColumn();
        
        $this->assertGreaterThan(0, $count, "La réservation doit être ajoutée avec succès.");

    }

    public function testReservationDejaExistante(): void
    {        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ce créneau est déjà réservé pour cette date.");
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
    }

    public function testGetCreneauxActiviteReserver(): void
    {
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);

        $result = $this->_gestionCreneaux->getCreneauxActiviteReserver($this->_creneauxActivite);

        $this->assertIsArray($result, "Les créneaux réservés doivent être récupérés sous forme de tableau.");
        $this->assertNotEmpty($result, "La récupération des créneaux réservés doit renvoyer des résultats.");
    }

    public function testAnnulerReservation(): void
    {
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);

        $this->_gestionCreneaux->annulerReservation($this->_creneauxActivite);

        $stmt = $this->_pdo->prepare("SELECT reserver FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(0, $result['reserver'], "La réservation doit être annulée avec succès.");
    }

    public function testSupprimerReservation(): void
    {        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);

        $this->_gestionCreneaux->supprimerReservation($this->_creneauxActivite);

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count, "La réservation doit être supprimée avec succès.");
    }

    public function testDateValide(): void
    {
        $this->_gestionCreneaux->set_dateReservation('2024-12-01');
        $this->assertEquals('2024-12-01', $this->_gestionCreneaux->get_date(), "La date doit être valide.");
    }

    public function testDateInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La date doit être valide au format YYYY-MM-DD.");
        $this->_gestionCreneaux->set_dateReservation('2024-13-01');
    }

    public function testDateReservationNonValide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La date doit être valide au format YYYY-MM-DD.");
        
        $this->_gestionCreneaux->set_dateReservation('2024-13-01');
    }

    public function testReservationDouble(): void
    {
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ce créneau est déjà réservé pour cette date.");
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
    }

    public function testSupprimerReservationEtVérification(): void
    {
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
        
        $this->_gestionCreneaux->supprimerReservation($this->_creneauxActivite);
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count, "La réservation doit être supprimée avec succès.");
    }
    public function testAnnulerReservationNonExistante(): void
    {
        $creneauxActivite = new CreneauxActivite($this->_idCreneau, $this->_idActivite);

        $this->_gestionCreneaux->set_reserver(false);
        $this->expectException(Error::class);
        $this->_gestionCreneaux->annulerReservation($creneauxActivite);
    }

    public function testReservationDateFutur(): void
    {
        $futureDate = '2025-12-01';
        $this->_gestionCreneaux->set_dateReservation($futureDate);
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $futureDate
        ]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "La réservation doit être ajoutée pour la date future.");
    }

    public function testRéinitialisationDuStatutDeRéservation(): void
    {
        
        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
        
        $this->_gestionCreneaux->annulerReservation($this->_creneauxActivite);
        
        $stmt = $this->_pdo->prepare("SELECT reserver FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([
            ':idCreneauxActivite' => $this->_creneauxActivite->get_IDCreneauxActivite(),
            ':date' => $this->_date
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(0, $result['reserver'], "Le statut de réservation doit être réinitialisé à 0 après l'annulation.");
    }

    public function testAnnulerRéservationDéjàAnnulée(): void
    {

        $this->_gestionCreneaux->set_reserver(true);
        $this->_gestionCreneaux->ajouterReservation($this->_creneauxActivite);
        $this->_gestionCreneaux->annulerReservation($this->_creneauxActivite);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La réservation a déjà été annulée.");
        $this->_gestionCreneaux->annulerReservation($this->_creneauxActivite);
    }
}
?>