<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/CreneauxActivite.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Calendrier.php';

class CreneauxActiviteTest extends TestCase
{
    private $pdo;
    private $creneauxActivite;
    private $activite;
    private $calendrier;
    private $creneau;


    protected function setUp(): void
    {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();

        $this->calendrier = new Calendrier('08:00:00', '21:00:00');
        $this->calendrier->ajouterCalendrierDansBDD();

        $this->activite = new Activite("Yoga" . uniqid(), 20.0, "00:30:00");
        $this->activite->ajouterActiviteBDD();

        $this->creneau = new Creneau('10:00:00', '11:00:00');
        $this->creneau->ajouterCreneauBDD();

        $this->creneauxActivite = new CreneauxActivite((int)$this->creneau->getId(), (int)$this->activite->getId());
    }

    protected function tearDown(): void {
        $this->pdo->exec("DELETE FROM Utilisateur");
        $this->pdo->exec("DELETE FROM Personne");
        $this->pdo->exec("DELETE FROM Activite");
        $this->pdo->exec("DELETE FROM Creneau");
        $this->pdo->exec("DELETE FROM CreneauxActivite");
    }
    public function testAjoutCreneauxActivite(): void
    {
        $duree = new DateInterval('PT0H30M');
        $this->creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $this->creneauxActivite->set_IdActivite((int)$this->activite->getId());

        $this->creneauxActivite->ajouterCreneauxActivite();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $this->activite->getId()]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "Des créneaux devraient être ajoutés pour l'activité.");
    }

    public function testGetCreneauxActiviteById(): void
    {
        
        $this->creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $this->creneauxActivite->set_IdActivite((int)$this->activite->getId());
        $this->creneauxActivite->ajouterCreneauxActivite();

        $result = $this->creneauxActivite->getCreneauxActiviteById((int)$this->creneauxActivite->get_idCreneauxActivite());
        $this->assertNotEmpty($result, "Le créneau activité devrait être récupéré.");
        $this->assertEquals((int)$this->creneauxActivite->get_idCreneauxActivite(), $result['idCreneauxActivite'], "L'ID du créneau activité devrait correspondre.");
    }

    public function testModifierCreneauxActivite(): void
    {

        $this->creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $this->creneauxActivite->set_IdActivite((int)$this->activite->getId());
        $this->creneauxActivite->ajouterCreneauxActivite();

        $this->creneauxActivite->modifierCreneauxActivite((int)$this->creneauxActivite->get_idCreneauxActivite());

        $stmt = $this->pdo->prepare("SELECT * FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => (int)$this->creneauxActivite->get_idCreneauxActivite()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Le créneau activité modifié devrait être récupéré.");
    }

    public function testSupprimerCreneauxActivite(): void
    {

        $this->creneauxActivite->set_IdCreneau((int)$this->creneau->getId());
        $this->creneauxActivite->set_IdActivite((int)$this->activite->getId());
        $this->creneauxActivite->ajouterCreneauxActivite();

        $this->creneauxActivite->supprimerCreneauxActivite((int)$this->creneauxActivite->get_idCreneauxActivite());

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idCreneauxActivite = :idCreneauxActivite");
        $stmt->execute([':idCreneauxActivite' => (int)$this->creneauxActivite->get_idCreneauxActivite()]);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count, "Le créneau activité devrait être supprimé.");
    }

    public function testGetDureeActivite(): void
    {
        $duree = $this->creneauxActivite->getDureeActivite((int)$this->activite->getId());
        $this->assertInstanceOf(DateInterval::class, $duree, "La durée devrait être un objet DateInterval.");
        $this->assertEquals('PT00H30M', $duree->format('PT%HH%IM'), "La durée de l'activité devrait être correcte.");
    }

    public function testSupprimerCreneauSansActiviteAssociee(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO Creneau (heure_debut, heure_fin, duree) VALUES (:heureDebut, :heureFin, :duree)");
        $stmt->execute([':heureDebut' => '10:00:00', ':heureFin' => '10:30:00', ':duree' => '00:30:00']);
        $idCreneau = $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("DELETE FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $idCreneau]);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $idCreneau]);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count, "Le créneau sans activité doit être supprimé.");
    }
}
?>