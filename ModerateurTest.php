<?php 
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Moderateur.php';
require_once __DIR__ . '/../src/Utilisateur.php';

use PHPUnit\Framework\TestCase;

class ModerateurTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('TRUNCATE TABLE RIB;');
        $this->pdo->exec('TRUNCATE TABLE Cotisation;');
        $this->pdo->exec('TRUNCATE TABLE Utilisateur;');
        $this->pdo->exec('TRUNCATE TABLE Moderateur;');
        $this->pdo->exec('TRUNCATE TABLE Personne;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testInsertionModerateurDansBaseDeDonnees(): void {
        $moderateur = new Moderateur("Jane Doe", "jdoe", "password123", "jane.doe@example.com", "0987654321");

        $stmt = $this->pdo->prepare("SELECT * FROM Moderateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $moderateur->getId()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Le modérateur devrait être inséré dans la base de données.");
    }

    public function testSuppressionUtilisateurParModerateur(): void {
        $utilisateur = new Utilisateur("John Smith", "jsmith", "password456", "john.smith@example.com", "0123456789");

        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getId()]);
        $idUtilisateur = $stmt->fetchColumn();

        $this->assertNotEmpty($idUtilisateur, "L'utilisateur doit être inséré avant de pouvoir être supprimé.");

        $moderateur = new Moderateur("Jane Doe", "moderator", "password123", "moderator@example.com", "0987654321");

        $result = $moderateur->supprimerUtilisateur($idUtilisateur);

        $this->assertTrue($result, "La suppression de l'utilisateur devrait réussir.");

        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :id");
        $stmt->execute([':id' => $idUtilisateur]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result, "L'utilisateur ne devrait plus exister dans la base de données.");

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $utilisateur->getId()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result, "La personne associée à l'utilisateur devrait être supprimée.");
    }

    public function testSuppressionUtilisateurInexistant(): void {
        $moderateur = new Moderateur("Jane Doe", "moderator", "password123", "moderator@example.com", "0987654321");

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Utilisateur introuvable avec l'ID 999");

        $moderateur->supprimerUtilisateur(999);
    }
}