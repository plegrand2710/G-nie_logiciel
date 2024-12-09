<?php
require_once __DIR__ . '/../src/Remboursement.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Paiement.php';
require_once __DIR__ . '/../src/RIB.php';
use PHPUnit\Framework\TestCase;

class RemboursementTest extends TestCase {
    private $pdo;
    private $utilisateur;
    private $reservation;
    private $paiement;
    private $ribUtilisateur;
    private $ribEntreprise;
    private $remboursement;
    private $activite;
    private $creneau;
    private $creneauxActivite;
    private $creneauxActiviteReserve;

    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();

        $this->utilisateur = new Utilisateur('Test3 User', 'testuser', 'password123', 'testuser@example.com', '1234567890');
        $this->utilisateur->ajouterDansLaBDD();
        $userId = $this->utilisateur->getIdUtilisateur();

        $this->activite = new Activite('Yoga', 20, '01:00:00');
        $this->activite->ajouterActiviteBDD();
        $activiteId =(int) $this->activite->getId();
        $this->creneau = new Creneau('09:00:00', '10:00:00');
        $this->creneau->ajouterCreneauBDD(); 
        $creneauId =(int) $this->creneau->getId(); 

        $this->creneauxActivite = new CreneauxActivite($creneauId, $activiteId);
        $this->creneauxActivite->ajouterCreneauxActivite($this->creneau->getId(), $this->activite->getId());

        $this->creneauxActiviteReserve = new CreneauxActiviteReserve("2024-12-20");
        $this->creneauxActiviteReserve->ajouterReservation($this->creneauxActivite);

        $this->reservation = new Reservation((int) $this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int) $this->utilisateur->getIdPersonne());
        $this->reservation->ajouterDansLaBDD();


        $this->ribUtilisateur = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $userId);
        $this->ribUtilisateur->ajouterDansBase();
        $ribUtilisateurId = $this->ribUtilisateur->getIdRib();
    
        $this->ribEntreprise = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Entreprise', 'RIB123');
        $this->ribEntreprise->ajouterDansBase();
        $ribEntrepriseId = $this->ribEntreprise->getIdRib();
        
        $this->paiement = new PaiementVirement(150.0, new DateTime('2024-12-01 12:00:00'), $ribUtilisateurId, $ribEntrepriseId, "remboursement");
        $this->paiement->effectuerPaiement();
        $paiementId = $this->paiement->getIdPaiement();

        $this->remboursement = new Remboursement(new DateTime('2024-12-01 12:00:00'), 100.0, 5.0, $userId, (int) $this->reservation->getIdReservation() , $ribUtilisateurId, $ribEntrepriseId);
        $this->remboursement->effectuerRemboursement(); 
    

    }

    protected function tearDown(): void {
        $this->pdo->exec("DELETE FROM Utilisateur");
        $this->pdo->exec("DELETE FROM Personne");
        $this->pdo->exec("DELETE FROM Reservation");
        $this->pdo->exec("DELETE FROM Paiement");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM RIB");
        $this->pdo->exec("DELETE FROM RIBEntreprise");
        $this->pdo->exec("DELETE FROM Remboursement");
    }

    public function testEnregistrerRemboursement() {
        $this->remboursement->effectuerRemboursement();

        $stmt = $this->pdo->prepare("SELECT * FROM Remboursement WHERE idRemboursement = :idRemboursement");
        $stmt->execute([':idRemboursement' => $this->remboursement->getIdRemboursement()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result);
        $this->assertEquals($this->remboursement->calculMontantRemboursement(), $result['montant']);
        $this->assertEquals($this->remboursement->getIdReservation(), $result['idReservation']);
        $this->assertEquals($this->remboursement->getIdPaiement(), $result['idPaiement']);
    }

    public function testMettreAJourRemboursement() {
        $this->remboursement->effectuerRemboursement();

        $this->remboursement->setPenalite(5.0); 
        $this->remboursement->mettreAJourRemboursement();

        $stmt = $this->pdo->prepare("SELECT * FROM Remboursement WHERE idRemboursement = :idRemboursement");
        $stmt->execute([':idRemboursement' => $this->remboursement->getIdRemboursement()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result);
        $this->assertEquals($this->remboursement->calculMontantRemboursement(), $result['montant']);
    }

    public function testSupprimerRemboursement() {
        $this->remboursement->effectuerRemboursement();

        $this->remboursement->supprimerRemboursement($this->remboursement->getIdRemboursement());

        $stmt = $this->pdo->prepare("SELECT * FROM Remboursement WHERE idRemboursement = :idRemboursement");
        $stmt->execute([':idRemboursement' => $this->remboursement->getIdRemboursement()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertFalse($result);
    }

    public function testCalculMontantRemboursement() {
        $this->remboursement->setPenalite(10.0);
        $this->assertEquals(90.0, $this->remboursement->calculMontantRemboursement());

        $this->remboursement->setPenalite(0.0);
        $this->assertEquals(100.0, $this->remboursement->calculMontantRemboursement());
    }
}
?>