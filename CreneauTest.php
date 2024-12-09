<?php

require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Creneau.php';

use PHPUnit\Framework\TestCase;

class CreneauTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();

        $stmt = $this->pdo->prepare("SELECT * FROM Creneau");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($result);
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('DELETE FROM Creneau;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testAjoutCreneauValide(): void {
        $creneau = new Creneau("09:00:00", "10:00:00");
        $creneau->ajouterCreneauBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([':heureDebut' => $creneau->getHeureDebut()->format("h:i:s"), ':heureFin' => $creneau->getHeureFin()->format("h:i:s")]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Le créneau doit être inséré dans la base de données.");
        $this->assertEquals("09:00:00", $result['heure_debut']);
        $this->assertEquals("10:00:00", $result['heure_fin']);
    }

    public function testCreneauExistante(): void {
        $creneau1 = new Creneau("09:00:00", "10:00:00");
        $creneau1->ajouterCreneauBDD();

        $creneau2 = new Creneau("09:00:00", "10:00:00");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Un créneau avec les mêmes horaires existe déjà.");

        $creneau2->ajouterCreneauBDD();
    }

    public function testHeureDebutInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de début doit être au format HH:MM:SS.");

        new Creneau("invalid", "10:00:00");
    }

    public function testHeureFinInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de fin doit être au format HH:MM:SS.");

        new Creneau("09:00:00", "invalid");
    }

    public function testHeureFinAvantHeureDebut(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de fin doit être supérieure à l'heure de début.");

        new Creneau("10:00:00", "09:00:00");
    }

    public function testCalculDuree(): void {
        $creneau = new Creneau("09:00:00", "11:30:00");
        $this->assertEquals("02:30", $creneau->getDuree(), "La durée calculée du créneau doit être correcte.");
    }
    public function testDureeLimiteMax(): void {
        $creneau = new Creneau("00:00:00", "23:59:59");
        $this->assertEquals("23:59", $creneau->getDuree(), "La durée maximale (24 heures) doit être correctement calculée.");
    }

    public function testCreneauAvecUneSeuleMinute(): void {
        $creneau = new Creneau("12:00:00", "12:01:00");
        $this->assertEquals("00:01", $creneau->getDuree(), "La durée pour une différence d'une minute doit être correcte.");
    }

    public function testCreneauAvecGrandeDiffusion(): void {
        $creneau = new Creneau("00:00:00", "12:00:00");
        $this->assertEquals("12:00", $creneau->getDuree(), "La durée de 12 heures doit être correctement calculée.");
    }

    public function testRejetCreneauAvecHeureInvalides(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de début est invalide.");
        $creneau = new Creneau("26:00:00", "28:00:00");
        $creneau->ajouterCreneauBDD();
    }

    public function testVérifierInsertionDePlusieursCreneaux(): void {
        $creneau1 = new Creneau("09:00:00", "10:00:00");
        $creneau1->ajouterCreneauBDD();

        $creneau2 = new Creneau("10:00:00", "11:00:00");
        $creneau2->ajouterCreneauBDD();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Creneau");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count, "Il devrait y avoir 2 créneaux dans la base de données.");
    }

    public function testCreationCreneauAvecDateInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de début doit être au format HH:MM:SS.");
        new Creneau("INVALIDDATE", "12:00:00");
    }

    public function testLectureCreneau(): void {
        $creneau = new Creneau("10:00:00", "11:00:00");
        $creneau->ajouterCreneauBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => "10:00:00",
            ':heureFin' => "11:00:00"
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Le créneau ne devrait pas être vide.");
    }

    public function testModificationCreneau(): void {
        $creneau = new Creneau("12:00:00", "13:00:00");
        $creneau->ajouterCreneauBDD();

        $stmt = $this->pdo->prepare("SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => "12:00:00",
            ':heureFin' => "13:00:00"
        ]);
        $creneauId = $stmt->fetchColumn();

        $creneau->setHeureDebut("14:00:00");
        $creneau->setHeureFin("15:00:00");
        $creneau->modifierCreneauBDD($creneauId);

        $stmt = $this->pdo->prepare("SELECT * FROM Creneau WHERE idCreneau = :id");
        $stmt->execute([':id' => $creneauId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals("14:00:00", $result['heure_debut']);
        $this->assertEquals("15:00:00", $result['heure_fin']);
    }

    public function testSuppressionCreneau(): void {
        $creneau = new Creneau("16:00:00", "17:00:00");
        $creneau->ajouterCreneauBDD();

        $stmt = $this->pdo->prepare("SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => "16:00:00",
            ':heureFin' => "17:00:00"
        ]);
        $creneauId = $stmt->fetchColumn();

        $creneau->supprimerCreneauBDD($creneauId);

        $stmt = $this->pdo->prepare("SELECT * FROM Creneau WHERE idCreneau = :id");
        $stmt->execute([':id' => $creneauId]);
        $this->assertEmpty($stmt->fetch(), "Le créneau devrait être supprimé.");
    }

    public function testLireCreneauInexistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Creneau non trouvé avec l'ID 999");
    
        $creneau = new Creneau("08:00:00", "09:00:00");
        $creneau->lireCreneauBDD(999); 
    }
    
    public function testLireCreneauValide(): void {
        $creneau = new Creneau("10:00:00", "11:00:00");
        $creneau->ajouterCreneauBDD();
    
        $stmt = $this->pdo->prepare("SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => "10:00:00",
            ':heureFin' => "11:00:00"
        ]);
        $creneauId = $stmt->fetchColumn();
    
        $creneau->lireCreneauBDD($creneauId);
        
        $this->assertNotNull($creneauId, "Le créneau doit exister dans la base de données.");
    }

    public function testModifierCreneauInexistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Le créneau avec l'ID 999 n'existe pas.");
    
        $creneau = new Creneau("12:00:00", "13:00:00");
        $creneau->modifierCreneauBDD(999);
    }
    
    public function testModifierCreneauAvecHeureDebutInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de début doit être au format HH:MM:SS.");
    
        $creneau = new Creneau("12:00:00", "13:00:00");
        $creneau->ajouterCreneauBDD();
        
        $creneau->setHeureDebut("invalid");
        $creneau->modifierCreneauBDD($creneau->getId());
    }
    
    public function testModifierCreneauAvecHeureFinInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de fin doit être au format HH:MM:SS.");
    
        $creneau = new Creneau("12:00:00", "13:00:00");
        $creneau->ajouterCreneauBDD();
        
        $creneau->setHeureFin("invalid");
        $creneau->modifierCreneauBDD($creneau->getId());
    }
    
    public function testModifierCreneauAvecHeureFinAvantDebut(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure de fin doit être supérieure à l'heure de début.");
    
        $creneau = new Creneau("12:00:00", "13:00:00");
        $creneau->ajouterCreneauBDD();
        
        $creneau->setHeureFin("11:00:00");
        $creneau->modifierCreneauBDD($creneau->getId());
    }

    public function testSupprimerCreneauInexistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Le créneau avec l'ID 999 n'existe pas.");
    
        $creneau = new Creneau("14:00:00", "15:00:00");
        $creneau->supprimerCreneauBDD(999);
    }
    
    public function testSupprimerCreneauValide(): void {
        $creneau = new Creneau("16:00:00", "17:00:00");
        $creneau->ajouterCreneauBDD();
    
        $stmt = $this->pdo->prepare("SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin");
        $stmt->execute([
            ':heureDebut' => "16:00:00",
            ':heureFin' => "17:00:00"
        ]);
        $creneauId = $stmt->fetchColumn();
    
        $creneau->supprimerCreneauBDD($creneauId);
    
        $stmt = $this->pdo->prepare("SELECT * FROM Creneau WHERE idCreneau = :id");
        $stmt->execute([':id' => $creneauId]);
        $this->assertEmpty($stmt->fetch(), "Le créneau devrait être supprimé.");
    }
    
    public function testSupprimerCreneauAvecErreurBaseDeDonnees(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Erreur lors de la suppression du créneau dans la base de données : Erreur SQL simulée");
    
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willThrowException(new PDOException("Erreur SQL simulée"));
        
        $creneau = new Creneau("18:00:00", "19:00:00");
        $creneau->setPDO($mockPdo);
        $creneau->supprimerCreneauBDD(1);
    }
}
?>