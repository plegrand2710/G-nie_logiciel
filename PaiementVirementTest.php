<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/RIB.php';
require_once __DIR__ . '/../src/RIBEntreprise.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
class PaiementVirementTest extends TestCase {
    private $_pdo;
    private $_paiementVirement;
    private $_utilisateur;
    private $_ribSource;
    private $_ribDestinataire;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->_utilisateur = new Utilisateur("Test User", "Test","coucoulol","test@example.com", "123456789");
        $this->_utilisateur->ajouterDansLaBDD();

        $this->_ribSource = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->_utilisateur->getIdUtilisateur());

        $this->_ribSource->ajouterDansBase();

        $this->_ribDestinataire = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'salledesport', 'RIB123');
        $this->_ribDestinataire->ajouterDansBase();

    }

    protected function tearDown(): void {
        $this->_pdo->exec("DELETE FROM Paiement");
        $this->_pdo->exec("DELETE FROM RIB");
        $this->_pdo->exec("DELETE FROM RIBEntreprise");
        $this->_pdo->exec("DELETE FROM Utilisateur");
        $this->_pdo->exec("DELETE FROM Personne");
    }

    public function testCreationPaiementVirement(): void {
        $date = new DateTime('2024-01-01');
        $paiement = new PaiementVirement(1000, $date, $this->_ribSource->getIdRib(), $this->_ribDestinataire->getIdRib(), "paiement");

        $this->assertInstanceOf(PaiementVirement::class, $paiement, "L'objet PaiementVirement n'a pas été correctement créé.");
   
        $paiement = new PaiementVirement(1000, $date, $this->_ribSource->getIdRib(), $this->_ribDestinataire->getIdRib(), "remboursement");

        $this->assertInstanceOf(PaiementVirement::class, $paiement, "L'objet PaiementVirement n'a pas été correctement créé.");
   
    }

    public function testEnregistrementPaiement(): void {
        $date = new DateTime('2024-01-01');
        $paiement = new PaiementVirement(1000, $date, $this->_ribSource->getIdRib(), $this->_ribDestinataire->getIdRib(), "paiement");

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Paiement WHERE montant = :montant");
        $stmt->execute([':montant' => 1000]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "Le paiement n'a pas été enregistré dans la base de données.");
    
        $paiement = new PaiementVirement(1000, $date, $this->_ribSource->getIdRib(), $this->_ribDestinataire->getIdRib(), "remboursement");

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Paiement WHERE montant = :montant");
        $stmt->execute([':montant' => 1000]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(1, $count, "Le paiement n'a pas été enregistré dans la base de données.");
    
    }

    public function testExceptionMontantInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        new PaiementVirement(0, new DateTime('2024-01-01'), $this->_ribSource->getIdRib(), $this->_ribDestinataire->getIdRib(), "paiement");
    }
}
?>