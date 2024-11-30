<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Cotisation.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';

class UtilisateurClasseTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('DELETE FROM RIB;');
        $this->pdo->exec('DELETE FROM Utilisateur;');
        $this->pdo->exec('DELETE FROM Personne;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testInsertionUtilisateurDansBaseDeDonnees(): void {
        $utilisateur = new Utilisateur(
            'John Doe',
            'johndoe',
            'password123',
            'johndoe@example.com',
            '0123456789'
        );

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => 'johndoe']);
        $personne = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($personne, "La personne doit exister dans la table `Personne`.");
        $this->assertSame('John Doe', $personne['nom']);
        $this->assertSame('johndoe@example.com', $personne['email']);
        $this->assertSame('0123456789', $personne['numTel']);

        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $personne['idPersonne']]);
        $utilisateurBD = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($utilisateurBD, "L'utilisateur doit exister dans la table `Utilisateur`.");
        $this->assertSame(0, $utilisateurBD['cotisation_active']);
    }

    public function testAjouterCotisationHorsBase(): void {
        $utilisateur = new Utilisateur(
            'Jane Doe',
            'janedoe',
            'securepassword',
            'janedoe@example.com',
            '0987654321'
        );

        $cotisation = $this->createMock(Cotisation::class);
        $cotisation->method('verifValiditeCotisation')->willReturn(true);

        $utilisateur->addCotisation($cotisation);

        $this->assertCount(1, $utilisateur->getCotisations());
        $this->assertTrue($utilisateur->VerifPayerCotisation(), "La cotisation valide devrait permettre à l'utilisateur d'avoir une cotisation active.");
    }

    public function testVerifierCotisationSansCotisation(): void {
        $utilisateur = new Utilisateur(
            'Alice Smith',
            'alicesmith',
            'mypassword',
            'alicesmith@example.com',
            '0123456789'
        );

        $this->assertFalse($utilisateur->VerifPayerCotisation(), "Un utilisateur sans cotisation ne doit pas être considéré comme ayant payé.");
    }

    public function testVerifierCotisationAvecCotisationNonValide(): void {
        $utilisateur = new Utilisateur(
            'Bob Brown',
            'bobbrown',
            'strongpassword',
            'bobbrown@example.com',
            '1234567890'
        );

        $cotisation = $this->createMock(Cotisation::class);
        $cotisation->method('verifValiditeCotisation')->willReturn(false);

        $utilisateur->addCotisation($cotisation);

        $this->assertFalse($utilisateur->VerifPayerCotisation(), "Un utilisateur avec une cotisation non valide ne doit pas être considéré comme ayant payé.");
    }

    public function testVerifierCotisationAvecCotisationValide(): void {
        $utilisateur = new Utilisateur(
            'Charlie Blue',
            'charlieblue',
            'securepass',
            'charlieblue@example.com',
            '9876543210'
        );

        $cotisation = $this->createMock(Cotisation::class);
        $cotisation->method('verifValiditeCotisation')->willReturn(true);

        $utilisateur->addCotisation($cotisation);

        $this->assertTrue($utilisateur->VerifPayerCotisation(), "Un utilisateur avec une cotisation valide doit être considéré comme ayant payé.");
    }
}