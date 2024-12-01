<?php

use PHPUnit\Framework\TestCase;
use App\Remboursement;

class RemboursementTest extends TestCase {

    // Test du constructeur
    public function testConstructor(): void {
        $remboursement = new Remboursement("2024-11-28", 100.0, 10.0);
        $this->assertEquals("2024-11-28", $remboursement->get_date());
        $this->assertEquals(100.0, $remboursement->get_montant());
        $this->assertEquals(10.0, $remboursement->get_penalite());
    }

    // Test des setters avec des valeurs valides
    public function testSettersValides(): void {
        $remboursement = new Remboursement("2024-11-28", 100.0, 10.0);
        $remboursement->set_date("2024-12-01");
        $remboursement->set_montant(200.0);
        $remboursement->set_penalite(20.0);

        $this->assertEquals("2024-12-01", $remboursement->get_date());
        $this->assertEquals(200.0, $remboursement->get_montant());
        $this->assertEquals(20.0, $remboursement->get_penalite());
    }

    // Test des setters avec des valeurs invalides
    public function testSettersInvalides(): void {
        $this->expectException(InvalidArgumentException::class);

        $remboursement = new Remboursement("invalid-date", -100, -10);
    }

    // Test du remboursement avec des valeurs normales
    public function testRemboursementValide(): void {
        $remboursement = new Remboursement("2024-11-28", 100.0, 10.0);
        $result = $remboursement->rembourser();
        $this->assertEquals(90.0, $result);
    }

    // Test du remboursement avec une pénalité élevée
    public function testRemboursementPenaliteElevée(): void {
        $this->expectException(RuntimeException::class);

        $remboursement = new Remboursement("2024-11-28", 100.0, 150.0);
        $remboursement->rembourser();
    }
}
