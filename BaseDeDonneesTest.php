<?php
require_once __DIR__ . '/../src/BaseDeDonnees.php';
use PHPUnit\Framework\TestCase;

class BaseDeDonneesTest extends TestCase {
    private $_pdo;

    protected function setUp(): void {
        $db = BaseDeDonnees::getInstance();
        $this->_pdo = $db->getConnexion();
    }

    // Test CRUD sur la table Personne
    public function testCRUDPersonne() {
        // CREATE
        $stmt = $this->_pdo->prepare("INSERT INTO Personne (nom, identifiant, mdp, email, numTel, type) VALUES (:nom, :id, :mdp, :email, :num, :type)");
        $stmt->execute([
            ':nom' => 'John Doe',
            ':id' => 'johndoe1',
            ':mdp' => 'password',
            ':email' => 'john@example.com',
            ':num' => '1234567890',
            ':type' => 'Utilisateur'
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        // READ
        $stmt = $this->_pdo->prepare("SELECT * FROM Personne WHERE identifiant = :id");
        $stmt->execute([':id' => 'johndoe1']);
        $personne = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($personne);
        $this->assertEquals('John Doe', $personne['nom']);

        // UPDATE
        $stmt = $this->_pdo->prepare("UPDATE Personne SET email = :email WHERE identifiant = :id");
        $stmt->execute([':email' => 'new_john@example.com', ':id' => 'johndoe1']);
        $this->assertEquals(1, $stmt->rowCount());

        // DELETE
        $stmt = $this->_pdo->prepare("DELETE FROM Personne WHERE identifiant = :id");
        $stmt->execute([':id' => 'johndoe1']);
        $this->assertEquals(1, $stmt->rowCount());
    }

    // Test CRUD sur la table Utilisateur
    public function testCRUDUtilisateur() {
        // CREATE
        $stmt = $this->_pdo->prepare("INSERT INTO Utilisateur (cotisation_active, idpersonne) VALUES (:cotisation_active, :idpersonne)");
        $stmt->execute([
            ':cotisation_active' => 1,
            ':idpersonne' => 1 // Assurez-vous d'avoir un idpersonne valide
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        // READ
        $stmt = $this->_pdo->prepare("SELECT * FROM Utilisateur WHERE idpersonne = :idpersonne");
        $stmt->execute([':idpersonne' => 1]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($utilisateur);

        // UPDATE
        $stmt = $this->_pdo->prepare("UPDATE Utilisateur SET cotisation_active = :cotisation_active WHERE idpersonne = :idpersonne");
        $stmt->execute([':cotisation_active' => 0, ':idpersonne' => 1]);
        $this->assertEquals(1, $stmt->rowCount());

        // DELETE
        $stmt = $this->_pdo->prepare("DELETE FROM Utilisateur WHERE idpersonne = :idpersonne");
        $stmt->execute([':idpersonne' => 1]);
        $this->assertEquals(1, $stmt->rowCount());
    }

    // Test CRUD sur la table Activite
    public function testCRUDActivite() {
        // CREATE
        $stmt = $this->_pdo->prepare("INSERT INTO Activite (nom, tarif, duree) VALUES (:nom, :tarif, :duree)");
        $stmt->execute([
            ':nom' => 'Yoga',
            ':tarif' => 20.0,
            ':duree' => '00:30:00'
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        // READ
        $stmt = $this->_pdo->prepare("SELECT * FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => 'Yoga']);
        $activite = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($activite);

        // UPDATE
        $stmt = $this->_pdo->prepare("UPDATE Activite SET tarif = :tarif WHERE nom = :nom");
        $stmt->execute([':tarif' => 25.0, ':nom' => 'Yoga']);
        $this->assertEquals(1, $stmt->rowCount());

        // DELETE
        $stmt = $this->_pdo->prepare("DELETE FROM Activite WHERE nom = :nom");
        $stmt->execute([':nom' => 'Yoga']);
        $this->assertEquals(1, $stmt->rowCount());
    }

    // Test CRUD sur la table Creneau
    public function testCRUDCreneau() {
        // CREATE
        $stmt = $this->_pdo->prepare("INSERT INTO Creneau (heure_debut, heure_fin, duree) VALUES (:heure_debut, :heure_fin, :duree)");
        $stmt->execute([
            ':heure_debut' => '10:00:00',
            ':heure_fin' => '10:30:00',
            ':duree' => '00:30:00'
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        // READ
        $stmt = $this->_pdo->prepare("SELECT * FROM Creneau WHERE heure_debut = :heure_debut");
        $stmt->execute([':heure_debut' => '10:00:00']);
        $creneau = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($creneau);

        // UPDATE
        $stmt = $this->_pdo->prepare("UPDATE Creneau SET heure_fin = :heure_fin WHERE heure_debut = :heure_debut");
        $stmt->execute([':heure_fin' => '11:00:00', ':heure_debut' => '10:00:00']);
        $this->assertEquals(1, $stmt->rowCount());

        // DELETE
        $stmt = $this->_pdo->prepare("DELETE FROM Creneau WHERE heure_debut = :heure_debut");
        $stmt->execute([':heure_debut' => '10:00:00']);
        $this->assertEquals(1, $stmt->rowCount());
    }

    // Test CRUD sur la table CreneauxActivite
    public function testCRUDCreneauxActivite() {
        // CREATE
        $stmt = $this->_pdo->prepare("INSERT INTO CreneauxActivite (idCreneau, idActivite) VALUES (:idCreneau, :idActivite)");
        $stmt->execute([
            ':idCreneau' => 1, // Assurez-vous d'avoir un idCreneau valide
            ':idActivite' => 1
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        $stmt = $this->_pdo->prepare("SELECT * FROM CreneauxActivite WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => 1]);
        $creneauxActivite = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($creneauxActivite);

        $stmt = $this->_pdo->prepare("UPDATE CreneauxActivite SET idActivite = :idActivite WHERE idCreneau = :idCreneau");
        $stmt->execute([':idActivite' => 2, ':idCreneau' => 1]);
        $this->assertEquals(1, $stmt->rowCount());

        $stmt = $this->_pdo->prepare("DELETE FROM CreneauxActivite WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => 1]);
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function testCRUDCreneauxActiviteReserve() {
        $stmt = $this->_pdo->prepare("INSERT INTO CreneauxActiviteReserve (idCreneauxActivite, date, reserver) VALUES (:idCreneauxActivite, :date, :reserver)");
        $stmt->execute([
            ':idCreneauxActivite' => 1,
            ':date' => '2024-12-01',
            ':reserver' => 1
        ]);
        $this->assertEquals(1, $stmt->rowCount());

        $stmt = $this->_pdo->prepare("SELECT * FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([':idCreneauxActivite' => 1, ':date' => '2024-12-01']);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($reservation);

        $stmt = $this->_pdo->prepare("UPDATE CreneauxActiviteReserve SET reserver = :reserver WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([':reserver' => 0, ':idCreneauxActivite' => 1, ':date' => '2024-12-01']);
        $this->assertEquals(1, $stmt->rowCount());

        $stmt = $this->_pdo->prepare("DELETE FROM CreneauxActiviteReserve WHERE idCreneauxActivite = :idCreneauxActivite AND date = :date");
        $stmt->execute([':idCreneauxActivite' => 1, ':date' => '2024-12-01']);
        $this->assertEquals(1, $stmt->rowCount());
    }
}
?>