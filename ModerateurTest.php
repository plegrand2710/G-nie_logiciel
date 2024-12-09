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
        $this->pdo->exec('DELETE FROM RIB;');
        $this->pdo->exec('DELETE FROM Cotisation;');
        $this->pdo->exec('DELETE FROM Utilisateur;');
        $this->pdo->exec('DELETE FROM Moderateur;');
        $this->pdo->exec('DELETE FROM Personne;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testInsertionModerateurDansBaseDeDonnees(): void {
        $moderateur = new Moderateur("Jane Doe", "jdoe", "password123", "jane.doe@example.com", "0987654321");
        $moderateur->ajouterDansLaBDD();

        $stmt = $this->pdo->prepare("SELECT * FROM Moderateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $moderateur->getIdentifiant()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Le modérateur devrait être inséré dans la base de données.");
    }

    public function testSuppressionUtilisateurParModerateur(): void {
        $utilisateur = new Utilisateur("John Smith", "jsmith", "password456", "john.smith@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();
    
        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $idUtilisateur = $stmt->fetchColumn();
    
        $this->assertNotEmpty($idUtilisateur, "L'utilisateur doit être inséré avant de pouvoir être supprimé.");
    
        $moderateur = new Moderateur("Jane Doe", "moderator", "password123", "moderator@example.com", "0987654321");
        $result = $moderateur->supprimerUtilisateur((int)$idUtilisateur);
    
        $this->assertTrue($result, "La suppression de l'utilisateur devrait réussir.");
    
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :id");
        $stmt->execute([':id' => $idUtilisateur]);
        $this->assertEmpty($stmt->fetch(), "L'utilisateur ne devrait plus exister dans la base de données.");
    
        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $this->assertEmpty($stmt->fetch(), "La personne associée à l'utilisateur devrait être supprimée.");
    }
    public function testSuppressionUtilisateurInexistant(): void {
        $moderateur = new Moderateur("Jane Doe", "moderator", "password123", "moderator@example.com", "0987654321");
    
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

    public function testSuppressionUtilisateurAvecErreurBaseDeDonnees(): void {
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
    
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('beginTransaction')->will($this->throwException(new PDOException("Erreur SQL simulée")));
    
        $moderateur->setPDO($mockPdo);
    
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Erreur lors de la suppression : Erreur SQL simulée");
    
        $moderateur->supprimerUtilisateur(1); 
    }

    public function testInsertionModerateurDansBaseDeDonneesIdentifiantUnique(): void {
        $moderateur1 = new Moderateur("Alice Brown", "abrown", "password123", "alice.brown@example.com", "0123456789");
        $moderateur1->ajouterDansLaBDD();
    
        $moderateur2 = new Moderateur("Bob White", "abrown", "password123", "bob.white@example.com", "0987654321");
    
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'identifiant est déjà utilisé. Veuillez en choisir un autre.");
        $moderateur2->ajouterDansLaBDD();
    }
    
    public function testSuppressionUtilisateurAvecRelationCotisation(): void {
        $utilisateur = new Utilisateur("Sarah Black", "sblack", "password123", "sarah.black@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();
        
        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $utilisateurId = $stmt->fetchColumn();
    
        $montant = 50.00;
        $datePaiement = date('Y-m-d');
        $dateFin = date('Y-m-d', strtotime('+1 year')); 
    
        $stmt = $this->pdo->prepare("INSERT INTO Cotisation (montant, date_paiement, date_fin, idUtilisateur) VALUES (:montant, :date_paiement, :date_fin, :idUtilisateur)");
        $stmt->execute([
            ':montant' => $montant,
            ':date_paiement' => $datePaiement,
            ':date_fin' => $dateFin,
            ':idUtilisateur' => $utilisateurId
        ]);
    
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
        $result = $moderateur->supprimerUtilisateur($utilisateurId);
        
        $this->assertTrue($result, "La suppression de l'utilisateur, avec cotisation, devrait réussir.");
    }
    
    public function testSuppressionUtilisateurNonAdministratif(): void {
        $utilisateur = new Utilisateur("Test User", "testuser", "password123", "testuser@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();
        $utilisateurId = $utilisateur->getIdentifiant();
    
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID utilisateur doit être un entier.");
        
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
        $moderateur->supprimerUtilisateur("non_integer");
    }
    
    public function testSuppressionUtilisateurAffecteBase(): void {
        $utilisateur = new Utilisateur("Test User", "testuser", "password123", "testuser@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();
        $utilisateurId = $utilisateur->getIdentifiant();

        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $utilisateurId = $stmt->fetchColumn();
    
        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
        $result = $moderateur->supprimerUtilisateur($utilisateurId);
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $count = $stmt->fetchColumn();
    
        $this->assertEquals(0, $count, "L'utilisateur doit être supprimé de la base de données.");
    }
    
    public function testMultipleSupressionOfSameUtilisateur(): void {
        $utilisateur = new Utilisateur("David Lee", "dlee", "password123", "david.lee@example.com", "0123456789");
        $utilisateur->ajouterDansLaBDD();
        $utilisateurId = $utilisateur->getIdentifiant();
    
        $stmt = $this->pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $utilisateur->getIdentifiant()]);
        $utilisateurId = $stmt->fetchColumn();

        $moderateur = new Moderateur("Admin", "admin", "securepass", "admin@example.com", "0987654321");
        $moderateur->supprimerUtilisateur($utilisateurId);
    
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Utilisateur introuvable avec l'ID $utilisateurId.");
        $moderateur->supprimerUtilisateur($utilisateurId);
    }
}
?>