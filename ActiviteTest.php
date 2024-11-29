<?php

use PHPUnit\Framework\TestCase;
use App\Activite;

class ActiviteTest extends TestCase {

    public function testConstructeurValid(): void {
        // Test de la création d'une activité valide
        $activite = new Activite("Yoga", 20.0, "1h", 1);

        $this->assertEquals("Yoga", $activite->get_nom());
        $this->assertEquals(20.0, $activite->get_tarif());
        $this->assertEquals("1h", $activite->get_duree());
        $this->assertEquals(1, $activite->get_id_Activite());
    }

    public function testSetNomInvalide(): void {
        // Test d'un nom invalide (chaîne vide)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit être une chaîne non vide.");

        $activite = new Activite("", 20.0, "1h", 1);
    }

    public function testSetNomNonString(): void {
        // Test d'un nom non-string
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit être une chaîne non vide.");

        $activite = new Activite(123, 20.0, "1h", 1);
    }

    public function testSetTarifInvalide(): void {
        // Test d'un tarif invalide (négatif)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le tarif doit être un nombre positif.");

        $activite = new Activite("Yoga", -10.0, "1h", 1);
    }

    public function testSetDureeInvalide(): void {
        // Test d'une durée invalide (chaîne vide)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La durée doit être une chaîne non vide.");

        $activite = new Activite("Yoga", 20.0, "", 1);
    }

    public function testSetIdActiviteInvalide(): void {
        // Test d'un id_Activite invalide (négatif)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'identifiant de l'activité doit être un entier positif et non nul.");

        $activite = new Activite("Yoga", 20.0, "1h", -1);
    }

    public function testSetIdActiviteNonInt(): void {
        // Test d'un id_Activite non entier
        $this->expectException(\TypeError::class);

        $activite = new Activite("Yoga", 20.0, "1h", "id_invalide");
    }

    public function testSetIdActiviteZero(): void {
        // Test de l'id_Activite égal à 0
        $this->expectException(\TypeError::class);

        $activite = new Activite("Yoga", 20.0, "1h", 0);
    }

    public function testSetterGettersValid(): void {
        // Test des setters et getters avec des données valides
        $activite = new Activite("Boxe", 30.0, "45min", 2);

        $activite->set_nom("Natation");
        $activite->set_tarif(25.0);
        $activite->set_duree("1h");
        $activite->set_id_Activite(3);

        $this->assertEquals("Natation", $activite->get_nom());
        $this->assertEquals(25.0, $activite->get_tarif());
        $this->assertEquals("1h", $activite->get_duree());
        $this->assertEquals(3, $activite->get_id_Activite());
    }

}
