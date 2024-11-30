<?php


require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Personne.php';

use PHPUnit\Framework\TestCase;

class PersonneTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();

        $this->pdo->exec("DELETE FROM Personne");
    }

    public function testAjoutPersonneDansLaBase(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = $this->getMockForAbstractClass(
            Personne::class,
            [$nom, $identifiant, $mdp, $email, $numTel]
        );

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $identifiant]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($result);
        $this->assertEquals($nom, $result['nom']);
        $this->assertEquals($identifiant, $result['identifiant']);
        $this->assertTrue(password_verify($mdp, $result['mdp']));
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($numTel, $result['numTel']);
        $this->assertEquals(get_class($personne), $result['type']);
    }

    public function testValidationNomInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit être une chaîne de caractères non vide.");

        $this->getMockForAbstractClass(Personne::class, ["", "id", "password123", "email@example.com", "0123456789"]);
    }

    public function testValidationIdentifiantInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant doit être une chaîne de caractères non vide.");

        $this->getMockForAbstractClass(Personne::class, ["John Doe", "", "password123", "email@example.com", "0123456789"]);
    }

    public function testValidationMotDePasseInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit être une chaîne d'au moins 8 caractères.");

        $this->getMockForAbstractClass(Personne::class, ["John Doe", "johndoe", "short", "email@example.com", "0123456789"]);
    }

    public function testValidationEmailInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        $this->getMockForAbstractClass(Personne::class, ["John Doe", "johndoe", "password123", "invalid-email", "0123456789"]);
    }

    public function testValidationNumeroDeTelephoneInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir exactement 10 chiffres.");

        $this->getMockForAbstractClass(Personne::class, ["John Doe", "johndoe", "password123", "email@example.com", "123"]);
    }

    public function testConnexionAvecIdentifiantsCorrects(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = $this->getMockForAbstractClass(
            Personne::class,
            [$nom, $identifiant, $mdp, $email, $numTel]
        );

        $this->assertTrue($personne->connexion($identifiant, $mdp));
    }

    public function testConnexionAvecIdentifiantsIncorrects(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = $this->getMockForAbstractClass(
            Personne::class,
            [$nom, $identifiant, $mdp, $email, $numTel]
        );

        $this->assertFalse($personne->connexion($identifiant, "wrongpassword"));
        $this->assertFalse($personne->connexion("wrongid", $mdp));
    }
}
?>