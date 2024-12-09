<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/Cotisation.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/RIB.php';
require_once __DIR__ . '/../src/RIBEntreprise.php';
require_once __DIR__ . '/../src/PaiementVirement.php';
require_once __DIR__ . '/../src/Paiement.php';

class UtilisateurClasseTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('DELETE FROM RIB;');
        $this->pdo->exec('DELETE FROM RIBEntreprise;');
        $this->pdo->exec('DELETE FROM Cotisation;');
        $this->pdo->exec('DELETE FROM Paiement;');
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
        $utilisateur->ajouterDansLaBDD();

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

    public function testInsertionUtilisateurAvecRibsDansBaseDeDonnees(): void {
        $utilisateur = new Utilisateur(
            'Mike Green',
            'mikegreen',
            'password456',
            'mikegreen@example.com',
            '0123456789'
        );
    
        $utilisateur->ajouterDansLaBDD();
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateur->getIdUtilisateur());
        $rib1->ajouterDansBase();
        $utilisateur->ajouterRib($rib1);
    
        $stmt = $this->pdo->prepare("SELECT * FROM RIB WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateur->getIdUtilisateur()]);
        $ribBD = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $this->assertNotFalse($ribBD, "Le RIB de l'utilisateur doit exister dans la table `RIB`.");
        $this->assertSame(1234567890, $ribBD['numero_compte']);
    }
    
    public function testVerifierCotisationSansCotisation(): void {
        $utilisateur = new Utilisateur(
            'Alice Smith',
            'alicesmith',
            'mypassword',
            'alicesmith@example.com',
            '0123456789'
        );

        $utilisateur->ajouterDansLaBDD();
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

        $utilisateur->ajouterDansLaBDD();
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateur->getIdUtilisateur());
        $rib1->ajouterDansBase();

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

        $utilisateur->ajouterDansLaBDD();
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateur->getIdUtilisateur());
        $rib1->ajouterDansBase();
    
        $ribEntreprise = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', 'RIB123');
        $ribEntreprise->ajouterDansBase();

        $paiement = new PaiementVirement(1500, new DateTime(), $rib1->getIdRib(), $ribEntreprise->getIdRib(), "paiement");
        $paiement->enregistrerPaiement();
        $cotisation = new Cotisation(new DateTime(),$utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $utilisateur->addCotisation($cotisation);

        $this->assertTrue($utilisateur->VerifPayerCotisation(), "Un utilisateur avec une cotisation valide doit être considéré comme ayant payé.");
    }

    public function testVerificationCotisationInactive(): void {
        $utilisateur = new Utilisateur(
            'Eve White',
            'evewhite',
            'password321',
            'evewhite@example.com',
            '0987654321'
        );

        $cotisation = $this->createMock(Cotisation::class);
        $cotisation->method('verifValiditeCotisation')->willReturn(false);

        $utilisateur->addCotisation($cotisation);

        $this->assertFalse($utilisateur->VerifPayerCotisation(), "L'utilisateur ne doit pas avoir une cotisation valide.");
    }

    public function testGestionMultipleRibs(): void {
        $utilisateur = new Utilisateur(
            'David Orange',
            'davidorange',
            'orangepassword',
            'davidorange@example.com',
            '0222333444'
        );
    
        $rib1 = $this->createMock(RIB::class);
        $rib1->method('getNumeroCompte')->willReturn(1234567890);
        $rib2 = $this->createMock(RIB::class);
        $rib2->method('getNumeroCompte')->willReturn(9876543210);
    
        $utilisateur->ajouterRib($rib1);
        $utilisateur->ajouterRib($rib2);
    
        $this->assertCount(2, $utilisateur->getRibs(), "L'utilisateur devrait avoir 2 RIBs.");
    }
    
    public function testAjoutRIBInvalide(): void {
        $this->expectException(InvalidArgumentException::class);

        $utilisateur = new Utilisateur(
            'Invalid RIB User',
            'invaliduser',
            'password123',
            'invaliduser@example.com',
            '0112233445'
        );

        $ribInvalide = null;
        $utilisateur->ajouterRib($ribInvalide); 
    }

    public function testMiseAJourCotisationActive(): void {
        $utilisateur = new Utilisateur(
            'Test User',
            'testuser',
            'password123',
            'testuser@example.com',
            '0123456789'
        );

        $utilisateur->ajouterDansLaBDD();
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateur->getIdUtilisateur());
        $rib1->ajouterDansBase();
    
        $ribEntreprise = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', 'RIB123');
        $ribEntreprise->ajouterDansBase();

        $paiement = new PaiementVirement(1500, new DateTime(), $rib1->getIdRib(), $ribEntreprise->getIdRib(), "paiement");
        $paiement->enregistrerPaiement();
        $cotisation = new Cotisation(new DateTime(),$utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $utilisateur->addCotisation($cotisation);

        $utilisateur->mettreAJourCotisationActive($utilisateur->getIdUtilisateur(), false);

        $this->assertTrue($utilisateur->VerifPayerCotisation(), "L'utilisateur ne doit pas être actif sans cotisation valide.");
    }
}
?>
