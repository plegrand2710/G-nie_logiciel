
<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/BaseDeDonnees.php';
require_once __DIR__ . '/GestionCreneauxActiviteReserve.php';

class TestGestionCreneauxActiviteReserve extends TestCase {
    private $pdoMock;
    private $gestionCreneauxActiviteReserve;

    protected function setUp(): void {
        // Mock PDO
        $this->pdoMock = $this->createMock(PDO::class);

        // Instancier la classe avec un mock de PDO
        $this->gestionCreneauxActiviteReserve = $this->getMockBuilder(GestionCreneauxActiviteReserve::class)
            ->setConstructorArgs(["2024-12-01"])
            ->onlyMethods(['validerDate'])
            ->getMock();
        
        // Mock de BaseDeDonnees pour injecter PDO mocké
        $bddMock = $this->createMock(BaseDeDonnees::class);
        $bddMock->method('getConnexion')->willReturn($this->pdoMock);

        $this->gestionCreneauxActivite = new GestionCreneauxActiviteReserve('2024-12-01');
    }

    public function testConstructeurAvecDateValide(): void{

        $this->assertEquals('2024-12-01', $this->gestionCreneauxActivite->get_date());
        $this->assertNull($this->gestionCreneauxActivite->get_reserver());
    }

    public function testConstructeurAvecDateInvalide(): void {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La date doit être valide au format YYYY-MM-DD.");
        $this->gestionCreneauxActivite = new GestionCreneauxActiviteReserve('date_invalide');
    }

    public function testSetDateReservationValid(): void {
        $this->gestionCreneauxActiviteReserve->method('validerDate')->willReturn(true);
        $this->gestionCreneauxActiviteReserve->set_dateReservation("2024-12-01");

        $this->assertEquals("2024-12-01", $this->gestionCreneauxActiviteReserve->get_date());
    }

    public function testSetDateReservationInvalid(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionCreneauxActiviteReserve->method('validerDate')->willReturn(false);
        $this->gestionCreneauxActiviteReserve->set_dateReservation("invalid-date");
    }

    public function testSetReserverValid(): void {
        $this->gestionCreneauxActiviteReserve->set_reserver(true);
        $this->assertTrue($this->gestionCreneauxActiviteReserve->_reserver);
    }

    public function testSetReserverInvalid(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionCreneauxActiviteReserve->set_reserver("not-a-boolean");
    }

    public function testAjouterCreneauActiviteReserverAvecDonneesValides(): void {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with([
            ':date' => '2024-12-01',
            ':idCreneau' => 1,
            ':idActivite' => 2,
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $this->gestionCreneauxActivite->ajouterCreneauActiviteReserver(1, 2);
    }

    public function testAjouterCreneauActiviteReserverAvecDonneesInvalides(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID du créneau doit être un entier positif.");
        $this->gestionCreneauxActivite->ajouterCreneauActiviteReserver(-1, 2);
    }
    
    public function testGetCreneauxActiviteReserverAvecDonneesValides(): void {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with([
            ':idActivite' => 2,
            ':date' => '2024-12-01',
        ]);
        $stmtMock->method('fetchAll')->willReturn([
            ['idCreneau' => 1, 'idActivite' => 2, 'date' => '2024-12-01']
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->gestionCreneauxActivite->getCreneauxActiviteReserver(2);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['idCreneau']);
        $this->assertEquals(2, $result[0]['idActivite']);
        $this->assertEquals('2024-12-01', $result[0]['date']);
    }

    public function testGetCreneauxActiviteReserverAvecDonneesInvalides(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de l'activité doit être un entier positif.");
        $this->gestionCreneauxActivite->getCreneauxActiviteReserver(-1);
    }

    public function testGetCreneauxActiviteReserverAucuneDonneeTrouvee(): void {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with([
            ':idActivite' => 2,
            ':date' => '2024-12-01',
        ]);
        $stmtMock->method('fetchAll')->willReturn([]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->gestionCreneauxActivite->getCreneauxActiviteReserver(2);
        $this->assertEmpty($result);
    }
}
?>
