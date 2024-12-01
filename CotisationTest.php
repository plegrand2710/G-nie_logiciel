<?php
use PHPUnit\Framework\TestCase;

class CotisationTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $bdd = new BaseDeDonnees();
        $this->pdo = $bdd->getConnexion();

        // Nettoyage de la base de données
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        $this->pdo->exec('TRUNCATE TABLE Cotisation;');
        $this->pdo->exec('TRUNCATE TABLE Utilisateur;');
        $this->pdo->exec('TRUNCATE TABLE Personne;');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function testAjoutCotisationDansBase(): void {
        $datePaiement = new DateTime('2024-11-08');

        // Préparer un utilisateur
        $this->pdo->exec("
            INSERT INTO Personne (nom, identifiant, mdp, email, numTel, type)
            VALUES ('John Doe', 'johndoe', 'password123', 'johndoe@example.com', '0123456789', 'Utilisateur')
        ");
        $idPersonne = $this->pdo->lastInsertId();
        $this->pdo->exec("
            INSERT INTO Utilisateur (cotisation_active, idPersonne)
            VALUES (1, $idPersonne)
        ");
        $idUtilisateur = $this->pdo->lastInsertId();

        // Créer une cotisation
        $montant = 100.0;
        $cotisation = new Cotisation($montant, $datePaiement, $idUtilisateur);

        // Vérifier dans la base
        $stmt = $this->pdo->prepare("SELECT * FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result);
        $this->assertEquals($montant, $result['montant']);
        $this->assertEquals($datePaiement->format('Y-m-d'), $result['date_paiement']);
    }
}