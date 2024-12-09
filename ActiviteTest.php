<?php
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Activite.php';

use PHPUnit\Framework\TestCase;

class ActiviteTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('DELETE FROM Activite;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testAjouterActiviteBDD(): void {
        $activite = new Activite("Yoga", 20.5, "01:00:00");
        $activite->ajouterActiviteBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => "Yoga"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "L'activité devrait être ajoutée à la base de données.");
        $this->assertEquals("Yoga", $result['nom']);
        $this->assertEquals(20.5, $result['tarif']);
        $this->assertEquals("01:00:00", $result['duree']);
    }

    public function testLireActiviteBDD(): void {
        $activite = new Activite("Pilates", 25.0, "01:30:00");
        $activite->ajouterActiviteBDD();

        $stmt = $this->pdo->prepare("SELECT idActivite FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => "Pilates"]);
        $idActivite = $stmt->fetchColumn();

        $activite->setId($idActivite);
        $activityFromDB = $activite->lireActiviteBDD();

        $this->assertEquals("Pilates", $activityFromDB['nom']);
        $this->assertEquals(25.0, $activityFromDB['tarif']);
        $this->assertEquals("01:30:00", $activityFromDB['duree']);
    }

    public function testLireToutesActivitesBDD(): void {
        $activite1 = new Activite("Natation", 15.0, "00:45:00");
        $activite1->ajouterActiviteBDD();

        $activite2 = new Activite("Cyclisme", 30.0, "02:00:00");
        $activite2->ajouterActiviteBDD();

        $activites = $activite1->lireToutesActivitesBDD();

        $this->assertCount(2, $activites, "Il devrait y avoir 2 activités dans la base de données.");
        $this->assertEquals("Natation", $activites[0]['nom']);
        $this->assertEquals("Cyclisme", $activites[1]['nom']);
    }

    public function testModifierActiviteBDD(): void {
        $activite = new Activite("Boxe", 20.0, "01:00:00");
        $activite->ajouterActiviteBDD();

        $activite->setTarif(22.0);
        $activite->setDuree("01:30:00");
        $activite->mettreAJourActiviteBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => "Boxe"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "L'activité mise à jour devrait exister.");
        $this->assertEquals(22.0, $result['tarif']);
        $this->assertEquals("01:30:00", $result['duree']);
    }

    public function testSupprimerActiviteBDD(): void {
        $activite = new Activite("Escalade", 50.0, "02:00:00");
        $activite->ajouterActiviteBDD();

        $stmt = $this->pdo->prepare("SELECT idActivite FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => "Escalade"]);
        $idActivite = $stmt->fetchColumn();

        $activite->setId($idActivite);
        $activite->supprimerActiviteBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Activite WHERE idActivite = :id");
        $stmt->execute([':id' => $idActivite]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result, "L'activité devrait être supprimée de la base de données.");
    }

    public function testSupprimerActiviteInexistante(): void {
        $activite = new Activite("Tennis", 30.0, "01:00:00");
        $activite->setId(999);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'activité avec l'ID 999 n'existe pas.");
        
        $activite->supprimerActiviteBDD();
    }

    public function testModifierActiviteAvecIdInvalide(): void {
        $activite = new Activite("Badminton", 15.0, "00:45:00");
        $activite->ajouterActiviteBDD(); 
    
        $stmt = $this->pdo->prepare("SELECT idActivite FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => "Badminton"]);
        $idActivite = $stmt->fetchColumn();
    
        $activite->setId($idActivite);
    
        $nonExistentId = 99999;
    
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Aucune activité trouvée avec l'ID {$nonExistentId}. Impossible de mettre à jour.");
    
        $activite->setId($nonExistentId);
    
        $activite->mettreAJourActiviteBDD();
    }
}
?>