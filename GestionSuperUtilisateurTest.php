<?php
require_once __DIR__ . '/../src/GestionUtilisateur.php';
require_once __DIR__ . '/../src/GestionSuperUtilisateur.php';
require_once __DIR__ . '/../src/Calendrier.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';

use PHPUnit\Framework\TestCase;

class GestionSuperUtilisateurTest extends TestCase {
    private $calendrier;
    private $gestionSuperUtilisateur;
    private $gestionUtilisateur;
    private $pdo;
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
        //$this->tearDown();

        $this->pdo->exec("DELETE FROM Cotisation");
        $this->pdo->exec("DELETE FROM Utilisateur");
        $this->pdo->exec("DELETE FROM Personne");
        $this->pdo->exec("DELETE FROM RIB");
        $this->pdo->exec("DELETE FROM RIBEntreprise");
        $this->pdo->exec("DELETE FROM Calendrier");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM CreneauxActivite");
        $this->pdo->exec("DELETE FROM CreneauxActiviteReserve");
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

        $this->activite = new Activite('tennis', 20, '01:00:00');
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
        $this->gestionUtilisateur = new GestionUtilisateur((int)$this->calendrier->getId()); 
        $this->gestionSuperUtilisateur = new GestionSuperUtilisateur((int)$this->calendrier->getId());

    }

    /*protected function tearDown(): void {
        $this->pdo->exec("DELETE FROM Cotisation");
        $this->pdo->exec("DELETE FROM Utilisateur");
        $this->pdo->exec("DELETE FROM Personne");
        $this->pdo->exec("DELETE FROM RIB");
        $this->pdo->exec("DELETE FROM RIBEntreprise");
        $this->pdo->exec("DELETE FROM Calendrier");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM CreneauxActivite");
        $this->pdo->exec("DELETE FROM CreneauxActiviteReserve");
    }*/

    public function testCreerActiviteValide(): void {
        
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");
        $activites = $this->gestionSuperUtilisateur->getActivites();
        $this->assertCount(1, $activites);
        $this->assertEquals("Yoga", $activites[0]->getNom());

    }

    /*public function testCreerActiviteInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->creerActivite("", -10.0, null);
    }

    public function testSupprimerActiviteExistante(): void {
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");

        $activites = $this->gestionSuperUtilisateur->getActivites();
        $activite = $activites[0];

        $this->gestionSuperUtilisateur->supprimerActivite((int)$activite->getId());

        $activitesRestantes = $this->gestionSuperUtilisateur->getActivites();
        $this->assertCount(0, $activitesRestantes);
    }

    public function testSupprimerActiviteInexistante(): void {
        $this->expectException(LogicException::class);

        $activiteInexistante = new Activite("Non Existante", 100.0, "02:00:00");
        $this->gestionSuperUtilisateur->supprimerActivite((int)$activiteInexistante->getId());
    }

    public function testModifierActivite(): void {
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");

        $activites = $this->gestionSuperUtilisateur->getActivites();
        $activite = $activites[0];

        $this->gestionSuperUtilisateur->modifierActivite((int)$activite->getId(), "Pilates", 60.0, "01:30:00");

        $activites = $this->gestionSuperUtilisateur->getActivites();
        $this->assertEquals("Pilates", $activites[0]->getNom());
        $this->assertEquals(60.0, $activites[0]->getTarif());
        $this->assertEquals("1h30", $activites[0]->getDuree());
    }

    public function testModifierActiviteInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->modifierActivite(0, "", -10.0, null);
    }

    public function testModifierCreneau(): void {
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");
        
        $activites = $this->gestionSuperUtilisateur->getActivites();
        $activite = $activites[0];

        $creneau = new Creneau(new DateTime('10:00'), new DateTime('11:00'));
        $this->gestionSuperUtilisateur->modifierCreneau((int)$creneau->getId(), new DateTime('11:00'), new DateTime('12:00'));
        
        $creneaux = $this->gestionSuperUtilisateur->getCreneauxPourActivite((int)$activite->getId());
        $this->assertCount(1, $creneaux);
        $this->assertEquals(new DateTime('11:00'), $creneaux[0]->getHeureDebut());
    }

    public function testSupprimerCreneau(): void {
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");

        $activites = $this->gestionSuperUtilisateur->getActivites();
        $activite = $activites[0];

        $creneau = new Creneau(new DateTime('10:00'), new DateTime('11:00'));
        $this->gestionSuperUtilisateur->supprimerCreneau((int)$creneau->getId());

        $creneaux = $this->gestionSuperUtilisateur->getCreneauxPourActivite($activite->getId());
        $this->assertCount(0, $creneaux);
    }

    public function testLibererCreneauLorsSuppressionReservation(): void {
        $this->gestionSuperUtilisateur->creerActivite("Yoga", 50.0, "01:00:00");

        $activites = $this->gestionSuperUtilisateur->getActivites();
        $activite = $activites[0];

        $creneau = new Creneau(new DateTime('10:00'), new DateTime('11:00'));
        $this->gestionSuperUtilisateur->libererCreneauLorsSuppressionReservation($creneau->getId());

        $creneaux = $this->gestionSuperUtilisateur->getCreneauxPourActivite($activite->getId());
        $this->assertCount(0, $creneaux);
    }

    public function testAfficherToutesReservations(): void {
        $activite = new Activite('Yoga', 20, '01:00:00');
        $activite->ajouterActiviteBDD();
        $activiteId =(int) $this->activite->getId();
        $creneau = new Creneau('09:00:00', '10:00:00');
        $creneau->ajouterCreneauBDD(); 
        $creneauId =(int) $this->creneau->getId(); 

        $creneauxActivite = new CreneauxActivite($creneauId, $activiteId);

        $creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $creneauxActivite->set_IdActivite((int)$this->activite->getId());
        $creneauxActivite->ajouterCreneauxActivite();

        $creneauxActiviteReserve = new CreneauxActiviteReserve("2024-12-20");
        $creneauxActiviteReserve->ajouterReservation($this->creneauxActivite);

        $this->gestionSuperUtilisateur->reserver((int)$this->creneauxActiviteReserve->getIdCreneauxActiviteReserve());
        $this->gestionSuperUtilisateur->reserver((int)$creneauxActiviteReserve->getIdCreneauxActiviteReserve());

        $reservations = $this->gestionSuperUtilisateur->afficherToutesReservations();
        $this->assertCount(1, $reservations);
        $this->assertEquals("confirmée", $reservations[0]->getStatut());
    }*/
}
?>