<?php
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Moderateur.php';

use PHPUnit\Framework\TestCase;

class PersonneUtilisateurModerateurTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('DELETE FROM RIB;');
        $this->pdo->exec('DELETE FROM Cotisation;');
        $this->pdo->exec('DELETE FROM Utilisateur;');
        $this->pdo->exec('DELETE FROM Moderateur;');
        $this->pdo->exec('DELETE FROM Personne;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testAjoutPersonneUtilisateurModerateur(): void {
        $personne = new Utilisateur("Sarah Black", "sblack", "password123", "sarah.black@example.com", "0123456789");
        $personne->ajouterDansLaBDD();
        
        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $personne->getIdentifiant()]);
        $personneResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotEmpty($personneResult, "La personne n'a pas été ajoutée dans la base.");

        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
        $moderateur->ajouterDansLaBDD();
        
        $stmt = $this->pdo->prepare("SELECT * FROM Moderateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $moderateur->getIdentifiant()]);
        $moderateurResult = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($moderateurResult, "Le modérateur n'a pas été ajouté dans la base.");
    }

    public function testModificationPersonne(): void {
        $personne = new Utilisateur("John Doe", "jdoe", "password123", "john.doe@example.com", "0123456789");
        $personne->ajouterDansLaBDD();

        $personne->modifierPersonne("John Updated", "john.updated@example.com", "0987654321");

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $personne->getIdentifiant()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals("John Updated", $result['nom']);
        $this->assertEquals("john.updated@example.com", $result['email']);
        $this->assertEquals("0987654321", $result['numTel']);
    }

    public function testSuppressionPersonne(): void {
        $personne = new Utilisateur("Jane Smith", "jsmith", "password123", "jane.smith@example.com", "0123456789");
        $personne->ajouterDansLaBDD();

        $stmt = $this->pdo->prepare("SELECT idPersonne FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $personne->getIdentifiant()]);
        $idPersonne = $stmt->fetchColumn();

        $this->assertNotEmpty($idPersonne, "L'utilisateur doit exister avant la suppression.");

        $personne->supprimerPersonne();

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result, "La personne n'a pas été supprimée correctement.");
    }

    public function testSuppressionUtilisateurParModerateur(): void {
        $utilisateur = new Utilisateur("John Smith", "jsmith", "password456", "john.smith@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();

        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $idUtilisateur = $stmt->fetchColumn();

        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");

        $result = $moderateur->supprimerUtilisateur($idUtilisateur);

        $this->assertTrue($result, "La suppression de l'utilisateur devrait réussir.");

        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :id");
        $stmt->execute([':id' => $idUtilisateur]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEmpty($result, "L'utilisateur ne devrait plus exister dans la base de données.");
    }

    public function testSuppressionUtilisateurInexistant(): void {
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Utilisateur introuvable avec l'ID 999");

        $moderateur->supprimerUtilisateur(999);
    }

    public function testSuppressionUtilisateurAvecTypeInvalide(): void {
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID utilisateur doit être un entier.");

        $moderateur->supprimerUtilisateur("invalide");
    }

    public function testVerifPayerCotisation(): void {
        $utilisateur = new Utilisateur("Sarah Black", "sblack", "password123", "sarah.black@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();

        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $idUtilisateur = $stmt->fetchColumn();

        $cotisationDate = date('Y-m-d', strtotime('+1 year'));
        $stmt = $this->pdo->prepare("INSERT INTO Cotisation (montant, date_paiement, date_fin, idUtilisateur) VALUES (50, NOW(), :date_fin, :idUtilisateur)");
        $stmt->execute([':date_fin' => $cotisationDate, ':idUtilisateur' => $idUtilisateur]);

        $this->assertTrue($utilisateur->VerifPayerCotisation(), "La vérification de la cotisation active devrait être correcte.");
    }

    public function testAjoutRib(): void {
        $utilisateur = new Utilisateur("Sarah Black", "sblack", "password123", "sarah.black@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();

        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $utilisateurId = $stmt->fetchColumn();

        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Black', 'Sarah', 'RIB123', $utilisateurId);
        $utilisateur->ajouterRib($rib);

        $this->assertCount(1, $utilisateur->getRibs(), "Le RIB devrait être ajouté à l'utilisateur.");
    }
}
?>