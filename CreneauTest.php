<?php
use PHPUnit\Framework\TestCase;
use App\Creneau;

class CreneauTest extends TestCase {

    public function testConstructeur() {
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);
        $this->assertEquals("2024-11-28", $creneau->get_date());
        $this->assertEquals("09:00", $creneau->get_heureDebut());
        $this->assertEquals("10:00", $creneau->get_heureFin());
        $this->assertFalse($creneau->get_occupation());
        $this->assertEquals(1, $creneau->get_ID_Creneau());
    }

    public function testSettersValides() {

        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);

        $creneau->set_date("2024-12-01");
        $creneau->set_heureDebut("14:00");
        $creneau->set_heureFin("15:00");
        $creneau->set_occupation(true);
        $creneau->set_creneauID(2);

        $this->assertEquals("2024-12-01", $creneau->get_date());
        $this->assertEquals("14:00", $creneau->get_heureDebut());
        $this->assertEquals("15:00", $creneau->get_heureFin());
        $this->assertTrue($creneau->get_occupation());
        $this->assertEquals(2, $creneau->get_ID_Creneau());
    }

    public function testSetDateInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);
        $creneau->set_date("28/11/2024");
    }

    public function testSetHeureDebutInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);
        $creneau->set_heureDebut("0900");
    }

    public function testSetHeureFinInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);
        $creneau->set_heureFin("0100");
    }

    public function testReserverCreneau() {
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", false, 1);
        $creneau->reserverCreneau();
        $this->assertTrue($creneau->get_occupation());
    }

    public function testLibererCreneau() {
        $creneau = new Creneau("2024-11-28", "09:00", "10:00", true, 1);
        $creneau->libererCreneau();
        $this->assertFalse($creneau->get_occupation());
    }
}
?>
