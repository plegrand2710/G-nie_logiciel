<?php

use PHPUnit\Framework\TestCase;

class CalendrierTest extends TestCase {
    private $_pdo;
    private $_calendrier;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->_calendrier = new Calendrier('09:00:00', '18:00:00');
        $this->_calendrier->ajouterCalendrierDansBDD();
    }
    protected function tearDown(): void
    {
        $stmt = $this->_pdo->prepare("DELETE FROM JourFermeture");
        $stmt->execute();
        $stmt = $this->_pdo->prepare("DELETE FROM Fermeture");
        $stmt->execute();

    }


    public function testAjoutCalendrier(): void {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $this->_calendrier->getId()]);
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "Le calendrier doit être ajouté dans la base de données.");
    }

    public function testMiseAJourCalendrier(): void {
        $this->_calendrier->setHoraireOuvertureSalle('08:00:00');
        $this->_calendrier->setHoraireFermetureSalle('17:00:00');
        $this->_calendrier->mettreAJourCalendrierDansBDD();

        $stmt = $this->_pdo->prepare("SELECT horaire_ouverture, horaire_fermeture FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $this->_calendrier->getId()]);
        $calendrier = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('08:00:00', $calendrier['horaire_ouverture']);
        $this->assertEquals('17:00:00', $calendrier['horaire_fermeture']);
    }

    public function testSuppressionCalendrier(): void {
        $idCalendrier = $this->_calendrier->getId();
        $this->_calendrier->supprimerCalendrierDansBDD();

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $idCalendrier]);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count, "Le calendrier doit être supprimé de la base de données.");
    }

    public function testAjoutJourFermeture(): void {
        $jour = '2024-12-25';
        $this->_calendrier->ajouterJourFermetureDansBDD($jour);

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Fermeture WHERE idCalendrier = :idCalendrier AND idJourFermeture IN (SELECT idJourFermeture FROM JourFermeture WHERE dateJour = :jour)");
        $stmt->execute([':idCalendrier' => $this->_calendrier->getId(), ':jour' => $jour]);
        $countFerme = $stmt->fetchColumn();
    
        $this->assertGreaterThan(0, $countFerme, "Le jour de fermeture doit être ajouté à la table ferme.");
    }

    public function testSuppressionJourFermeture(): void {
        $jour = '2024-12-25';

        $this->_calendrier->ajouterJourFermetureDansBDD($jour);

        $this->_calendrier->supprimerJourFermetureDansBDD($jour);
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Fermeture WHERE idCalendrier = :idCalendrier AND idJourFermeture IN (SELECT idJourFermeture FROM JourFermeture WHERE dateJour = :jour)");
        $stmt->execute([':idCalendrier' => $this->_calendrier->getId(), ':jour' => $jour]);
        $countFermeture = $stmt->fetchColumn();
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM JourFermeture WHERE dateJour = :jour");
        $stmt->execute([':jour' => $jour]);
        $countJourFermeture = $stmt->fetchColumn();
    
        $this->assertEquals(0, $countFermeture, "Le jour de fermeture doit être supprimé de la table Fermeture.");
        $this->assertEquals(0, $countJourFermeture, "L'id de fermeture doit être supprimé de la table JourFermeture.");
    }
    public function testRecuperationJoursFermeture(): void {
        $jour = '2024-12-25';
        $this->_calendrier->ajouterJourFermetureDansBDD($jour);

        $joursFermeture = $this->_calendrier->getJoursFermeture();

        $this->assertContains($jour, $joursFermeture, "Le jour de fermeture doit être récupéré correctement.");
    }

    public function testSetJoursFermeture(): void {
        $jours = ['2024-12-25', '2024-12-31'];
        $this->_calendrier->setJoursFermeture($jours);

        $joursFermeture = $this->_calendrier->getJoursFermeture();

        $this->assertEquals($jours, $joursFermeture, "Les jours de fermeture doivent être correctement définis et récupérés.");
    }

    public function testRecuperationCalendrierInexistant(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Calendrier introuvable.");
        $this->_calendrier->recupererCalendrierDepuisBDD(9999); // ID fictif inexistant
    }

    public function testAjoutMultiplesJoursFermeture(): void {
        $jours = ['2024-12-25', '2024-12-31'];
        foreach ($jours as $jour) {
            $this->_calendrier->ajouterJourFermetureDansBDD($jour);
        }
    
        $joursFermeture = $this->_calendrier->getJoursFermeture();
        foreach ($jours as $jour) {
            $this->assertContains($jour, $joursFermeture, "Le jour $jour de fermeture doit être récupéré correctement.");
        }
    }

    public function testMiseAJourJourFermeture(): void {
        $jourOriginal = '2024-12-25';
        $nouveauJour = '2024-12-26';
    
        $this->_calendrier->ajouterJourFermetureDansBDD($jourOriginal);
        $this->_calendrier->supprimerJourFermetureDansBDD($jourOriginal);
        $this->_calendrier->ajouterJourFermetureDansBDD($nouveauJour);
    
        $joursFermeture = $this->_calendrier->getJoursFermeture();
        $this->assertContains($nouveauJour, $joursFermeture, "Le jour $nouveauJour de fermeture doit être récupéré correctement après mise à jour.");
    }

    public function testAjoutApresSuppressionJourFermeture(): void {
        $jour = '2024-12-25';
        $this->_calendrier->ajouterJourFermetureDansBDD($jour);
    
        $this->_calendrier->supprimerJourFermetureDansBDD($jour);
        $this->_calendrier->ajouterJourFermetureDansBDD($jour);
    
        $joursFermeture = $this->_calendrier->getJoursFermeture();
        $this->assertContains($jour, $joursFermeture, "Le jour de fermeture doit être réajouté après suppression.");
    }

    public function testSuppressionCalendrierAvecJoursFermeture(): void {
        $jour = '2024-12-25';
        $this->_calendrier->ajouterJourFermetureDansBDD($jour);
        $idCalendrier = $this->_calendrier->getId();
    
        $this->_calendrier->supprimerCalendrierDansBDD();
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Fermeture WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $idCalendrier]);
        $countFermeture = $stmt->fetchColumn();
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Fermeture WHERE idCalendrier = :calendrier");
        $stmt->execute([':calendrier' => $idCalendrier]);
        $countFermeture = $stmt->fetchColumn();
    
        $this->assertEquals(0, $countFermeture, "Les jours de fermeture doivent être supprimés avec le calendrier.");
        $this->assertEquals(0, $countFermeture, "La fermeture doit être supprimé de la table Fermeture.");
    }

    public function testRecuperationJoursFermetureApresSuppression(): void {
        $jours = ['2024-12-25', '2024-12-31'];
        foreach ($jours as $jour) {
            $this->_calendrier->ajouterJourFermetureDansBDD($jour);
        }
    
        foreach ($jours as $jour) {
            $this->_calendrier->supprimerJourFermetureDansBDD($jour);
        }
    
        $joursFermeture = $this->_calendrier->getJoursFermeture();
        $this->assertEmpty($joursFermeture, "La liste des jours de fermeture doit être vide après suppression.");
    }

    public function testAjoutJourFermetureDateInvalide(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Le format de la date est invalide. Attendu 'Y-m-d'.");
        $this->_calendrier->ajouterJourFermetureDansBDD('25-12-2024');
    }
}
?>