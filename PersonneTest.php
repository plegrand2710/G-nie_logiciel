<?php
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Moderateur.php';

use PHPUnit\Framework\TestCase;

class PersonneTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE Utilisateur;");
        $this->pdo->exec("TRUNCATE TABLE Moderateur;");
        $this->pdo->exec("TRUNCATE TABLE RIB;");
        $this->pdo->exec("TRUNCATE TABLE Cotisation;");
        $this->pdo->exec("TRUNCATE TABLE Personne;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    public function testAjoutPersonneDansLaBase(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);

        $personne->ajouterDansLaBase();

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $identifiant]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($result, "La personne devrait être insérée dans la base de données.");
        $this->assertEquals($nom, $result['nom']);
        $this->assertEquals($identifiant, $result['identifiant']);
        $this->assertTrue(password_verify($mdp, $result['mdp']));
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($numTel, $result['numTel']);
    }

    public function testValidationNomInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit être une chaîne de caractères non vide.");

        new Utilisateur("", "id", "password123", "email@example.com", "0123456789");
    }

    public function testValidationIdentifiantInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant doit être une chaîne de caractères non vide de maximum 255 caractères.");

        new Utilisateur("John Doe", "", "password123", "email@example.com", "0123456789");
    }

    public function testValidationMotDePasseInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit être une chaîne d'au moins 8 caractères.");

        new Utilisateur("John Doe", "johndoe", "short", "email@example.com", "0123456789");
    }

    public function testValidationEmailInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        new Utilisateur("John Doe", "johndoe", "password123", "invalid-email", "0123456789");
    }

    public function testValidationNumeroDeTelephoneInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Utilisateur("John Doe", "johndoe", "password123", "email@example.com", "123");
    }

    public function testConnexionAvecIdentifiantsCorrects(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);
        $personne->ajouterDansLaBase();

        $this->assertTrue($personne->connexion($identifiant, $mdp));
    }

    public function testConnexionAvecIdentifiantsIncorrects(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);
        $personne->ajouterDansLaBase();

        $this->assertFalse($personne->connexion($identifiant, "wrongpassword"));
        $this->assertFalse($personne->connexion("wrongid", $mdp));
    }

    public function testIdentifiantTropLong(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant doit être une chaîne de caractères non vide de maximum 255 caractères.");

        $identifiantTropLong = str_repeat("a", 256);
        new Utilisateur("John Doe", $identifiantTropLong, "password123", "email@example.com", "0123456789");
    }

    public function testMotDePasseTropCourt(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit être une chaîne d'au moins 8 caractères.");

        new Utilisateur("John Doe", "johndoe", "short", "email@example.com", "0123456789");
    }

    public function testEmailInvalideAvecCaractereSpecial(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        new Utilisateur("John Doe", "johndoe", "password123", "invalid@ema!l.com", "0123456789");
    }

    public function testNumeroDeTelephoneTropCourt(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Utilisateur("John Doe", "johndoe", "password123", "email@example.com", "12345");
    }

    public function testNumeroDeTelephoneTropLong(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Utilisateur("John Doe", "johndoe", "password123", "email@example.com", "012345678901234");
    }

    public function testEmailDejaExistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'adresse email est déjà associée à un compte.");

        $utilisateur1 = new Utilisateur("John Doe", "johndoe1", "password123", "email@example.com", "0123456789");
        $utilisateur1->ajouterDansLaBase();

        $utilisateur2 = new Utilisateur("Jane Doe", "janedoe", "password123", "email@example.com", "0123456789");
        $utilisateur2->ajouterDansLaBase();
    }

    public function testIdentifiantDejaExistant(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'identifiant est déjà utilisé. Veuillez en choisir un autre.");

        $utilisateur1 = new Utilisateur("John Doe", "johndoe", "password123", "email@example.com", "0123456789");
        $utilisateur1->ajouterDansLaBase();

        $utilisateur2 = new Utilisateur("Jane Doe", "johndoe", "password123", "janedoe@example.com", "0123456789");
        $utilisateur2->ajouterDansLaBase();
    }

    public function testMiseAJourMdpIncorrect(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe actuel est incorrect.");

        $utilisateur = new Utilisateur("John Doe", "johndoe", "password123", "email@example.com", "0123456789");
        $utilisateur->ajouterDansLaBase();

        $utilisateur->modifierMdp("wrongpassword", "newpassword123");
    }

    public function testSuppressionPersonneInexistante(): void {
        $validPersonne = new Utilisateur("Valid User", "validuser", "password123", "validuser@example.com", "0123456789");
        $validPersonne->ajouterDansLaBase();
    
        $personneInexistante = new Utilisateur("Non Existent", "nonexistent", "password123", "nonexistent@example.com", "0123456789");
    
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ID de la personne non défini.");
    
        $personneInexistante->supprimerPersonne();
    }

    public function testAjoutPersonneDansLaBaseM(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);

        $personne->ajouterDansLaBase();

        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $identifiant]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($result, "La personne devrait être insérée dans la base de données.");
        $this->assertEquals($nom, $result['nom']);
        $this->assertEquals($identifiant, $result['identifiant']);
        $this->assertTrue(password_verify($mdp, $result['mdp']));
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($numTel, $result['numTel']);
    }

    public function testValidationNomInvalideM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit être une chaîne de caractères non vide.");

        new Moderateur("", "id", "password123", "email@example.com", "0123456789");
    }

    public function testValidationIdentifiantInvalideM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant doit être une chaîne de caractères non vide de maximum 255 caractères.");

        new Moderateur("John Doe", "", "password123", "email@example.com", "0123456789");
    }

    public function testValidationMotDePasseInvalideM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit être une chaîne d'au moins 8 caractères.");

        new Moderateur("John Doe", "johndoe", "short", "email@example.com", "0123456789");
    }

    public function testValidationEmailInvalideM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        new Moderateur("John Doe", "johndoe", "password123", "invalid-email", "0123456789");
    }

    public function testValidationNumeroDeTelephoneInvalideM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Moderateur("John Doe", "johndoe", "password123", "email@example.com", "123");
    }

    public function testConnexionAvecIdentifiantsCorrectsM(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);
        $personne->ajouterDansLaBase();

        $this->assertTrue($personne->connexion($identifiant, $mdp));
    }

    public function testConnexionAvecIdentifiantsIncorrectsM(): void {
        $nom = "John Doe";
        $identifiant = "johndoe";
        $mdp = "password123";
        $email = "johndoe@example.com";
        $numTel = "0123456789";

        $personne = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);
        $personne->ajouterDansLaBase();

        $this->assertFalse($personne->connexion($identifiant, "wrongpassword"));
        $this->assertFalse($personne->connexion("wrongid", $mdp));
    }

    public function testIdentifiantTropLongM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant doit être une chaîne de caractères non vide de maximum 255 caractères.");

        $identifiantTropLong = str_repeat("a", 256);
        new Moderateur("John Doe", $identifiantTropLong, "password123", "email@example.com", "0123456789");
    }

    public function testMotDePasseTropCourtM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit être une chaîne d'au moins 8 caractères.");

        new Moderateur("John Doe", "johndoe", "short", "email@example.com", "0123456789");
    }

    public function testEmailInvalideAvecCaractereSpecialM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email n'est pas valide.");

        new Moderateur("John Doe", "johndoe", "password123", "invalid@ema!l.com", "0123456789");
    }

    public function testNumeroDeTelephoneTropCourtM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Moderateur("John Doe", "johndoe", "password123", "email@example.com", "12345");
    }

    public function testNumeroDeTelephoneTropLongM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");

        new Moderateur("John Doe", "johndoe", "password123", "email@example.com", "012345678901234");
    }

    public function testEmailDejaExistantM(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'adresse email est déjà associée à un compte.");

        $utilisateur1 = new Moderateur("John Doe", "johndoe1", "password123", "email@example.com", "0123456789");
        $utilisateur1->ajouterDansLaBase();

        $utilisateur2 = new Moderateur("Jane Doe", "janedoe", "password123", "email@example.com", "0123456789");
        $utilisateur2->ajouterDansLaBase();
    }

    public function testIdentifiantDejaExistantM(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("L'identifiant est déjà utilisé. Veuillez en choisir un autre.");

        $utilisateur1 = new Moderateur("John Doe", "johndoe", "password123", "email@example.com", "0123456789");
        $utilisateur1->ajouterDansLaBase();

        $utilisateur2 = new Moderateur("Jane Doe", "johndoe", "password123", "janedoe@example.com", "0123456789");
        $utilisateur2->ajouterDansLaBase();
    }

    public function testMiseAJourMdpIncorrectM(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe actuel est incorrect.");

        $utilisateur = new Moderateur("John Doe", "johndoe", "password123", "email@example.com", "0123456789");
        $utilisateur->ajouterDansLaBase();

        $utilisateur->modifierMdp("wrongpassword", "newpassword123");
    }

    public function testSuppressionPersonneInexistanteM(): void {
        $validPersonne = new Moderateur("Valid User", "validuser", "password123", "validuser@example.com", "0123456789");
        $validPersonne->ajouterDansLaBase();
    
        $personneInexistante = new Moderateur("Non Existent", "nonexistent", "password123", "nonexistent@example.com", "0123456789");
    
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ID de la personne non défini.");
    
        $personneInexistante->supprimerPersonne();
    }
}
?>