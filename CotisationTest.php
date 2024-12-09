<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Cotisation.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';
require_once __DIR__ . '/../src/Paiement.php';
require_once __DIR__ . '/../src/PaiementVirement.php';

class CotisationTest extends TestCase {
    private $_pdo;
    private $_cotisation;
    private $_utilisateur;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->_utilisateur = new Utilisateur("Test User", "Test", "password123", "test@example.com", "123456789");
        $this->_utilisateur->ajouterDansLaBDD();

        $ribSource = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->_utilisateur->getIdUtilisateur());
        $ribSource->ajouterDansBase();

        $ribDestinataire = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'salledesport', 'RIB123');
        $ribDestinataire->ajouterDansBase();
    }

    protected function tearDown(): void {
        $this->_pdo->exec("DELETE FROM Cotisation");
        $this->_pdo->exec("DELETE FROM Paiement");
        $this->_pdo->exec("DELETE FROM Utilisateur");
        $this->_pdo->exec("DELETE FROM Personne");
        $this->_pdo->exec("DELETE FROM RIB");
        $this->_pdo->exec("DELETE FROM RIBEntreprise");
    }

    public function testCotisationCreation(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();
        $this->assertInstanceOf(Cotisation::class, $cotisation, "La cotisation n'a pas été correctement créée.");
    }

    public function testAjoutCotisation(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "La cotisation n'a pas été ajoutée correctement.");
    }

    public function testValiditeCotisation(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());

        $this->assertTrue($cotisation->verifValiditeCotisation(), "La cotisation n'est pas valide.");
    }

    public function testMiseAJourCotisation(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $cotisation->setMontant(3500);
        $cotisation->updateCotisation($cotisation->getIdCotisation());
        $stmt = $this->_pdo->prepare("SELECT montant FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $montant = $stmt->fetchColumn();

        $this->assertEquals(3500, $montant, "Le montant de la cotisation n'a pas été mis à jour.");
    }

    public function testCotisationActif(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());

        $this->assertTrue($cotisation->verifValiditeCotisation(), "La cotisation doit être valide.");
    }

    public function testExceptionMontantCotisation(): void {
        $this->expectException(InvalidArgumentException::class);

        $cotisation = new Cotisation(new DateTime(), $this->_utilisateur->getIdUtilisateur());
        Cotisation::setMontant(-1);
    }

    public function testExceptionUtilisateurInvalide(): void {
        $this->expectException(RuntimeException::class);

        $cotisation = new Cotisation(new DateTime(), 999999); 
    }

    public function testSuppressionCotisation(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $countBeforeDelete = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $countBeforeDelete, "La cotisation n'a pas été correctement enregistrée.");

        $stmt = $this->_pdo->prepare("SELECT idCotisation FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $idCotisation = $stmt->fetchColumn();

        $cotisation->deleteCotisation($idCotisation);

        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $countAfterDelete = $stmt->fetchColumn();

        $this->assertEquals(0, $countAfterDelete, "La cotisation n'a pas été supprimée correctement.");
    }

    public function testCotisationById(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();
        $stmt = $this->_pdo->prepare("SELECT idCotisation FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $this->_utilisateur->getIdUtilisateur()]);
        $idCotisation = $stmt->fetchColumn();

        $cotisationDetails = $cotisation->getCotisationById($idCotisation);

        $this->assertNotEmpty($cotisationDetails, "La cotisation n'a pas été récupérée correctement.");
    }

    public function testGetAllCotisations(): void {
        $date = new DateTime('2024-01-01');
        $cotisation = new Cotisation($date, $this->_utilisateur->getIdUtilisateur());
        $cotisation->effectuerPaiementCotisation();

        $cotisations = $cotisation->getAllCotisations();

        $this->assertGreaterThan(0, count($cotisations), "Aucune cotisation n'a été récupérée.");
    }
}
?>