<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../class/BaseDeDonnees.php';
require_once __DIR__ . '/../class/CreneauxActivite.php';


class CreneauxActiviteTest extends TestCase {

    private $pdoMock;
    private $activiteClass;

    public function testConstructeurCreneauxActiviteValide(): void {
        $creneauxActivite = new CreneauxActivite(1, 2, 3);

        $this->assertEquals(1, $creneauxActivite->get_ID_CreneauxActivite());
        $this->assertEquals(2, $creneauxActivite->get_ID_Creneau());
        $this->assertEquals(3, $creneauxActivite->get_ID_Activite());

    }

    //Test des setter
    public function testSetIdCreneauxActiviteValide(): void{
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau-activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, 2, 3);
    }

    public function testSetIdCreneauxActiviteStringInvalide(): void{
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau-activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite("1", 2, 3);
    }

    public function testSetIdCreneauxActiviteNombreNegatifInvalide(): void{
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau-activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(-1, 2, 3);
    }

    public function testSetIdCreneauValide(): void{

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, 2, 3);

    }

    public function testSetIdCreneauStringInvalide(): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, "2", 3);
    }

    public function testSetIdCreneauNombreNegatifInvalide(): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, -2, 3);
    }
    
    public function testSetIdActiviteValide(): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de l'activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, 2, 3);

    }

    public function testSetIdActiviteStringInvalide(): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de l'activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, 2, "3");
    }

    public function testSetIdActiviteNombreNegatifInvalide(): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de l'activité doit être un entier positif.");

        $creneauxActivite = new CreneauxActivite(1, 2, -3);
    }

    /*protected function setUp(): void
    {
        // Mock de l'objet PDO
        $this->pdoMock = $this->createMock(PDO::class);

        // Injection de dépendances dans la classe contenant la méthode
        $this->activiteClass = $this->getMockBuilder(YourClass::class)
            ->setConstructorArgs([$this->pdoMock])
            ->getMock();
    }

    public function testGenererCreneauxPourActivite_ActiviteIntrouvable()
    {
        // Mock du comportement PDO
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        // Test de l'exception levée pour une activité introuvable
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Activité introuvable.");

        $this->activiteClass->genererCreneauxPourActivite(1);
    }

    public function testGenererCreneauxPourActivite_CalendrierIntrouvable()
    {
        $stmtMock1 = $this->createMock(PDOStatement::class);
        $stmtMock1->method('fetch')->willReturn(['duree' => 60]);

        $stmtMock2 = $this->createMock(PDOStatement::class);
        $stmtMock2->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->will($this->onConsecutiveCalls($stmtMock1));
        $this->pdoMock->method('query')->willReturn($stmtMock2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Horaires d'ouverture introuvables.");

        $this->activiteClass->genererCreneauxPourActivite(1);
    }

    public function testGenererCreneauxPourActivite_Success()
    {
        // Mock des résultats de la base de données
        $stmtMock1 = $this->createMock(PDOStatement::class);
        $stmtMock1->method('fetch')->willReturn(['duree' => 60]);

        $stmtMock2 = $this->createMock(PDOStatement::class);
        $stmtMock2->method('fetch')->willReturn([
            'horaire_ouverture' => '08:00:00',
            'horaire_fermeture' => '18:00:00',
        ]);

        $stmtMock3 = $this->createMock(PDOStatement::class);
        $stmtMock3->method('fetch')->willReturn(false); // Aucun créneau existant

        $this->pdoMock->method('prepare')->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock3);
        $this->pdoMock->method('query')->willReturn($stmtMock2);

        // Test sans exception
        $this->activiteClass->genererCreneauxPourActivite(1);

        // Assertions (vous pouvez également vérifier si les bonnes requêtes ont été appelées)
        $this->assertTrue(true); // Remplacez par des assertions spécifiques
    }*/

    
}
?>