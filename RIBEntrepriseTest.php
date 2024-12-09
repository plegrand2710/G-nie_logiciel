<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/RIBEntreprise.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';

class RIBEntrepriseTest extends TestCase {
    private $_pdo;
    private $_ribEntreprise;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        
        $this->_ribEntreprise = new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', 'RIB123');
    }

    protected function tearDown(): void {
        $this->_pdo->exec("DELETE FROM RIBEntreprise");
    }

    public function testAjouterRIB(): void {
        $this->_ribEntreprise->ajouterDansBase();
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM RIBEntreprise WHERE identifiant_rib = :identifiant_rib");
        $stmt->execute([':identifiant_rib' => $this->_ribEntreprise->getIdentifiantRIB()]);
        $count = $stmt->fetchColumn();
        
        $this->assertGreaterThan(0, $count, "Le RIB de l'entreprise n'a pas été ajouté dans la base de données.");
    }

    public function testLireRIB(): void {
        $this->_ribEntreprise->ajouterDansBase();
        
        $result = $this->_ribEntreprise->lireRIB();
        
        $this->assertNotNull($result, "Le RIB de l'entreprise n'a pas pu être retrouvé.");
        $this->assertEquals($this->_ribEntreprise->getIdentifiantRIB(), $result['identifiant_rib'], "L'identifiant RIB récupéré ne correspond pas.");
    }

    public function testMettreAJourRIB(): void {
        $this->_ribEntreprise->ajouterDansBase();
        
        $this->_ribEntreprise->setNomEntreprise('Nouvelle Entreprise');
        $this->_ribEntreprise->mettreAJourRIB();
        
        $stmt = $this->_pdo->prepare("SELECT titulaire_nom FROM RIBEntreprise WHERE idRibEntreprise = :idRib");
        $stmt->execute([':idRib' => $this->_ribEntreprise->getIdRIB()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Nouvelle Entreprise', $result['titulaire_nom'], "Le nom de l'entreprise n'a pas été mis à jour.");
    }

    public function testSupprimerRIB(): void {
        $this->_ribEntreprise->ajouterDansBase();
        $this->_ribEntreprise->supprimerRIB();
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM RIBEntreprise WHERE identifiant_rib = :identifiant_rib");
        $stmt->execute([':identifiant_rib' => $this->_ribEntreprise->getIdentifiantRIB()]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count, "Le RIB de l'entreprise n'a pas été supprimé de la base de données.");
    }

    public function testExceptionNumeroCompteInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        new RIBEntreprise(-1234567890, 12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', 'RIB123');
    }

    public function testExceptionCodeGuichetInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        new RIBEntreprise(1234567890, -12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', 'RIB123');
    }

    public function testExceptionCodeIBANInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        new RIBEntreprise(1234567890, 12345, 67, 'invalidIBAN', 'Test Entreprise', 'RIB123');
    }

    public function testExceptionIdentifiantRIBInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        new RIBEntreprise(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Test Entreprise', '');
    }

    public function testAffichageRIB(): void {
        $this->_ribEntreprise->ajouterDansBase();
        
        ob_start();
        $this->_ribEntreprise->afficherRIB();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('RIB Entreprise', $output, "L'affichage du RIB n'a pas fonctionné correctement.");
    }
}
?>