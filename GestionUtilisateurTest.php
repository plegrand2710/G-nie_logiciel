<?php

require_once __DIR__ . '/../src/GestionUtilisateur.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Moderateur.php';
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Paiement.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
use PHPUnit\Framework\TestCase;

class GestionUtilisateurTest extends TestCase {
    private $gestionUtilisateur;
    private $pdo;
    private $calendrier;
    private $utilisateur;
    private $activite;
    private $creneau;
    private $reservation;
    private $paiement;
    private $ribUtilisateur;
    private $ribEntreprise;
    private $remboursement;
    private $creneauxActivite;
    private $creneauxActiviteReserve;
    private $cotisation;

    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();
        $this->tearDown();
        $this->calendrier = new Calendrier("08:00:00", "21:00:00");
        $this->calendrier->ajouterCalendrierDansBDD();

        $this->utilisateur = new Utilisateur('Test3 User', 'testuser', 'password123', 'testuser@example.com', '1234567890');
        $this->utilisateur->ajouterDansLaBDD();
        $userId = $this->utilisateur->getIdUtilisateur();


        $this->ribUtilisateur = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $userId);
        $this->ribUtilisateur->ajouterDansBase();
        $ribUtilisateurId = $this->ribUtilisateur->getIdRib();
    
        $this->ribEntreprise = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Entreprise', 'RIB123');
        $this->ribEntreprise->ajouterDansBase();
        $ribEntrepriseId = $this->ribEntreprise->getIdRib();
        $date = new DateTime('2024-01-01');
        $this->cotisation = new Cotisation($date, $this->utilisateur->getIdUtilisateur());
        $this->cotisation->effectuerPaiementCotisation();

        $this->activite = new Activite('Yoga', 20, '01:00:00');
        $this->activite->ajouterActiviteBDD();
        $activiteId =(int) $this->activite->getId();
        $this->creneau = new Creneau('09:00:00', '10:00:00');
        $this->creneau->ajouterCreneauBDD(); 
        $creneauId =(int) $this->creneau->getId(); 

        $this->creneauxActivite = new CreneauxActivite($creneauId, $activiteId);

        $this->creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $this->creneauxActivite->set_IdActivite((int)$this->activite->getId());
        $this->creneauxActivite->ajouterCreneauxActivite();

        $this->creneauxActiviteReserve = new CreneauxActiviteReserve("2024-12-20");
        $this->creneauxActiviteReserve->ajouterReservation($this->creneauxActivite);

        $this->reservation = new Reservation((int) $this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int) $this->utilisateur->getIdPersonne());
        $this->reservation->ajouterDansLaBDD();
        
        $this->paiement = new PaiementVirement(150.0, new DateTime('2024-12-01 12:00:00'), $ribUtilisateurId, $ribEntrepriseId, "remboursement");
        $this->paiement->effectuerPaiement();
        $this->gestionUtilisateur = new GestionUtilisateur($this->calendrier->getId()); 
    }

    protected function tearDown(): void {
        $this->pdo->exec("DELETE FROM Cotisation");
        $this->pdo->exec("DELETE FROM Utilisateur");
        $this->pdo->exec("DELETE FROM Personne");
        $this->pdo->exec("DELETE FROM RIB");
        $this->pdo->exec("DELETE FROM RIBEntreprise");
        $this->pdo->exec("DELETE FROM Cotisation");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM CreneauxActivite");
        $this->pdo->exec("DELETE FROM CreneauxActiviteReserve");
    }

    public function testReserverValid(): void {
        $this->assertTrue($this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int)$this->utilisateur->getIdPersonne()));
    }

    public function testPaiementActivite(): void {
        $idUtilisateur = $this->utilisateur->getIdUtilisateur();
        $idActivite = $this->activite->getId();

        $this->gestionUtilisateur->paiementActivite($idUtilisateur, $idActivite);
        $this->assertNotNull($this->gestionUtilisateur->getPaiement());
    }

    public function testAnnulerReservationAvecPenalite(): void {
        $idReservation = $this->reservation->getIdReservation();
        $this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int)$this->utilisateur->getIdPersonne());

        $this->gestionUtilisateur->annulerReservation( $idReservation);

        $stmt = $this->pdo->prepare("SELECT statut FROM Reservation WHERE idReservation = :idReservation");
        $stmt->execute([':idReservation' => $idReservation]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('annulée', $result['statut']);
    }

    public function testRemboursementActivite(): void {
        $idPersonne = (int)$this->utilisateur->getIdPersonne();
        $idReservation = (int)$this->reservation->getIdReservation();
        $penalite = 5.0;
        $idRIBSource = (int)$this->ribUtilisateur->getIdRib();
        $idRIBDestinataire = (int)$this->ribEntreprise->getIdRib();

        $this->gestionUtilisateur->remboursementActivite($idPersonne, $idReservation, $penalite, $idRIBSource, $idRIBDestinataire);
        $this->assertNotNull($this->gestionUtilisateur->getRemboursement());
    }

    public function testAfficherCreneauxDisponiblesParActivite(): void {
        $idActivite = (int) $this->activite->getId(); 

        $creneauxDisponibles = $this->gestionUtilisateur->afficherCreneauxDisponiblesParActivite($idActivite);
        $this->assertNotEmpty($creneauxDisponibles);
    }

    public function testReserverUtilisateurSansCotisation(): void {
        $idCreneauxActiviteReserve = (int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve();
        $personne = new Utilisateur('Test User sans cotisation', 'testsanscotisation', 'password123', 'testuser4@example.com', '1224567890');
        $personne->ajouterDansLaBDD();
        $idPersonne = (int)$personne->getIdPersonne();
    
        $this->utilisateur->setCotisations([]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("L'utilisateur doit avoir une cotisation valide pour réserver.");

        $this->gestionUtilisateur->reserver($idCreneauxActiviteReserve, $idPersonne);
    }

    public function testReserverCreneauIndisponible(): void {
        $idPersonne = (int)$this->utilisateur->getIdPersonne();
        $this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), $idPersonne);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Le créneau est déjà réservé.");
        $this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), $idPersonne);;
    }

    public function testAnnulerReservationInvalide(): void {
        $idReservation = $this->reservation->getIdReservation();
        $this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), (int)$this->utilisateur->getIdPersonne());

        $this->gestionUtilisateur->annulerReservation( $idReservation);


        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->annulerReservation($idReservation);
    }
    public function testReserverAvecUtilisateurNonExistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La personne avec l'ID 9999 n'existe pas dans la base de données.");
        $this->gestionUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve(), 9999); // Utilisateur non existant
    }


}
?>