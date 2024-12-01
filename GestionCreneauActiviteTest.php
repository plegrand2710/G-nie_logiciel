<?php

use PHPUnit\Framework\TestCase;
use App\GestionCreneauxActivite;
use App\Activite; // Ajoute les namespaces nécessaires
use App\Creneau;

class GestionCreneauActiviteTest extends TestCase {

    public function testConstructeur() {
        $gestion = new GestionCreneauxActivite();
        $this->assertEmpty($gestion->get_CreneauActivite());
    }

    public function testSetActiviteValide() {
        $activite = $this->createMock(Activite::class);
        $gestion = new GestionCreneauxActivite();
        $gestion->set_Activite($activite);
        $this->assertSame($activite, $gestion->get_ActiviteForCreneauActivite());
    }

    public function testSetTableCreneauxValide() {
        $creneau1 = $this->createMock(Creneau::class);
        $creneau2 = $this->createMock(Creneau::class);
        $gestion = new GestionCreneauxActivite();
        $gestion->set_tableCreneaux([$creneau1, $creneau2]);
        $this->assertCount(2, $gestion->get_CreneauActivite());
    }

    public function testSetTableCreneauxInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $gestion = new GestionCreneauxActivite();
        $gestion->set_tableCreneaux([new stdClass()]);
    }

    public function testAjouterCreneau() {
        $creneau = $this->createMock(Creneau::class);
        $gestion = new GestionCreneauxActivite();
        $gestion->ajouterCreneauActivite($creneau);
        $this->assertCount(1, $gestion->get_CreneauActivite());
    }

    public function testModifierCreneauValide() {
        $creneau1 = $this->createMock(Creneau::class);
        $creneau1->method('get_ID_Creneau')->willReturn(1);
        $nouveauCreneau = $this->createMock(Creneau::class);

        $gestion = new GestionCreneauxActivite();
        $gestion->ajouterCreneauActivite($creneau1);
        $gestion->modifierCreneauActivite(1, $nouveauCreneau);

        $this->assertSame($nouveauCreneau, $gestion->get_CreneauActivite()[0]);
    }

    public function testModifierCreneauInvalide() {
        $this->expectException(Exception::class);
        $gestion = new GestionCreneauxActivite();
        $gestion->modifierCreneauActivite(99, $this->createMock(Creneau::class));
    }

    public function testSupprimerCreneau() {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($this->createMock(PDOStatement::class));

        $creneau = $this->createMock(Creneau::class);
        $creneau->method('get_ID_Creneau')->willReturn(1);

        $gestion = new GestionCreneauxActivite();
        $gestion->ajouterCreneauActivite($creneau);
        $gestion->supprimerCreneauActivite($creneau, $pdo);

        $this->assertEmpty($gestion->get_CreneauActivite());
    }

    public function testSupprimerToutCreneau() {
        $creneau1 = $this->createMock(Creneau::class);
        $creneau2 = $this->createMock(Creneau::class);
        $creneau3 = $this->createMock(Creneau::class);
    
        $gestion = new GestionCreneauxActivite();
    
        $gestion->ajouterCreneauActivite($creneau1);
        $gestion->ajouterCreneauActivite($creneau2);
        $gestion->ajouterCreneauActivite($creneau3);
    
        $this->assertCount(3, $gestion->get_CreneauActivite());
    
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($this->createMock(PDOStatement::class));
        $gestion->supprimerToutCreneauActivite($pdo);
    
        // Vérifier qu'il n'y a plus de créneaux après la suppression
        $this->assertEmpty($gestion->get_CreneauActivite());
    }
    

    public function testVerifierDisponibilite() {
        $creneauOccupe = $this->createMock(Creneau::class);
        $creneauLibre = $this->createMock(Creneau::class);
    
        // Configuration des mocks pour renvoyer les valeurs appropriées
        $creneauOccupe->method('get_occupation')->willReturn(true);
        $creneauLibre->method('get_occupation')->willReturn(false);
    
        $gestion = new GestionCreneauxActivite();
        
        // Test sur le créneau occupé
        $this->assertTrue($gestion->verifierDisponibilite($creneauOccupe));
    
        // Test sur le créneau libre
        $this->assertFalse($gestion->verifierDisponibilite($creneauLibre));
    }
    
}
?>
