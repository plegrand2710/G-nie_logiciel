<?php
require_once __DIR__ . '/../src/GestionUtilisateur.php';
require_once __DIR__ . '/../src/GestionSuperUtilisateur.php';
require_once __DIR__ . '/../src/Paiement.php';
require_once __DIR__ . '/../src/Calendrier.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/GestionCreneauxActivite.php';
require_once __DIR__ . '/../src/PaiementVirement.php';
require_once __DIR__ . '/../src/RIB.php';
require_once __DIR__ . '/../src/Remboursement.php';
require_once __DIR__ . '/../src/Moderateur.php';

use PHPUnit\Framework\TestCase;

class GestionSuperUtilisateurTest extends TestCase {
    private $calendrier;
    private $activite;

    private $activite1;
    private $activite2;
    private $creneau1;
    private $creneau2;
    private $gestionSuperUtilisateur;

    protected function setUp(): void {
        $this->calendrier = $this->createMock(Calendrier::class);
        $this->activite1 = $this->createMock(Activite::class);
        $this->activite2 = $this->createMock(Activite::class);
        $this->creneau1 = $this->createMock(Creneau::class);
        $this->creneau2 = $this->createMock(Creneau::class);
        $this->activite = $this->createMock(Activite::class);

        $this->gestionSuperUtilisateur = new GestionSuperUtilisateur($this->calendrier, [$this->activite1, $this->activite2]);
    }

    public function testConstructeurValide(): void {
        $this->assertInstanceOf(GestionSuperUtilisateur::class, $this->gestionSuperUtilisateur);
        $this->assertCount(2, $this->gestionSuperUtilisateur->getActivites());
    }

    public function testConstructeurAvecActivitesInvalides(): void {
        $this->expectException(InvalidArgumentException::class);
        new GestionSuperUtilisateur($this->calendrier, ["invalid"]);
    }

    public function testCreerActiviteValide(): void {
        $this->calendrier->expects($this->once())
            ->method('ajouterGestionCreneauxActivite')
            ->with($this->isInstanceOf(Activite::class));

        $this->gestionSuperUtilisateur->creer_activite("Yoga", 50.0, "1h");
        $this->assertCount(3, $this->gestionSuperUtilisateur->getActivites());
    }

    public function testCreerActiviteInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->creer_activite("", -10.0, null);
    }

    public function testSupprimerActiviteExistante(): void {
        $activite1 = $this->createMock(Activite::class);
        $activite2 = $this->createMock(Activite::class);
    
        $this->calendrier->expects($this->once())
            ->method('supprimerGestionCreneauxActivite')
            ->with($activite1);
    
        $gestionSuperUtilisateur = new GestionSuperUtilisateur($this->calendrier, [$activite1, $activite2]);
    
        $gestionSuperUtilisateur->supprimer_activite($activite1);
    
        $activitesRestantes = $gestionSuperUtilisateur->getActivites();
        $this->assertCount(1, $activitesRestantes);
        $this->assertNotContains($activite1, $activitesRestantes);
        $this->assertContains($activite2, $activitesRestantes);
    }

    public function testSupprimerActiviteInexistante(): void {
        $this->expectException(LogicException::class);

        $autreActivite = $this->createMock(Activite::class);
        $this->gestionSuperUtilisateur->supprimer_activite($autreActivite);
    }

    public function testModifierActivite(): void {
        $this->activite1->expects($this->once())
            ->method('setNom')
            ->with("Pilates");
        $this->activite1->expects($this->once())
            ->method('setTarif')
            ->with(40.0);
        $this->activite1->expects($this->once())
            ->method('setDuree')
            ->with("45min");

        $this->gestionSuperUtilisateur->modifier_activite($this->activite1, "Pilates", 40.0, "45min");
    }

    public function testModifierActiviteAvecParametresPartiels(): void {
        $this->activite1->expects($this->once())
            ->method('setNom')
            ->with("Pilates");
        $this->activite1->expects($this->never())->method('setTarif');
        $this->activite1->expects($this->never())->method('setDuree');

        $this->gestionSuperUtilisateur->modifier_activite($this->activite1, "Pilates", null, null);
    }

    public function testModifierActiviteAvecParametresInvalides(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->modifier_activite($this->activite1, "", -20.0, null);
    }

    public function testModificationCreneauValide(): void {
        $gestionCreneaux = $this->createMock(GestionCreneauxActivite::class);

        $gestionCreneaux->expects($this->once())
            ->method('modifierCreneauActivite')
            ->with([$this->creneau1, $this->creneau2]);

        $this->calendrier->method('trouverGestionCreneauxPourActivite')
            ->with($this->activite1)
            ->willReturn($gestionCreneaux);

        $this->gestionSuperUtilisateur->modification_creneau([$this->creneau1, $this->creneau2], $this->activite1);
    }

    public function testModificationCreneauAvecCreneauInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->modification_creneau(["invalid"], $this->activite1);
    }

    public function testModificationCreneauAvecActiviteInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionSuperUtilisateur->modification_creneau([$this->creneau1], "invalid");
    }

    public function testModificationCreneauAvecActiviteInexistante(): void {
        $this->calendrier->method('trouverGestionCreneauxPourActivite')
            ->with($this->activite1)
            ->willReturn(null);

        $this->expectException(LogicException::class);
        $this->gestionSuperUtilisateur->modification_creneau([$this->creneau1], $this->activite1);
    }

    public function testAfficherToutesReservationsAvecReservationsConfirmees(): void {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getStatut')->willReturn('confirmée');
        $reservation1->method('getId')->willReturn(1);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getStatut')->willReturn('annulée');
        $reservation2->method('getId')->willReturn(2);
    
        $this->gestionSuperUtilisateur->ajouterReservation($reservation1);
        $this->gestionSuperUtilisateur->ajouterReservation($reservation2);
    
        $result = $this->gestionSuperUtilisateur->afficherToutesReservations();
    
        $this->assertCount(1, $result);
        $this->assertContains($reservation1, $result);
        $this->assertNotContains($reservation2, $result);
    }

    public function testAfficherToutesReservationsSansReservationsConfirmees(): void {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getStatut')->willReturn('annulée');
        $reservation1->method('getId')->willReturn(3);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getStatut')->willReturn('en attente');
        $reservation2->method('getId')->willReturn(4);
    
        $this->gestionSuperUtilisateur->ajouterReservation($reservation1);
        $this->gestionSuperUtilisateur->ajouterReservation($reservation2);
    
        $result = $this->gestionSuperUtilisateur->afficherToutesReservations();
    
        $this->assertEmpty($result);
    }
}

?>