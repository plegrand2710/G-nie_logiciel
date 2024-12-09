<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/RIB.php';
require_once __DIR__ . '/../src/BaseDeDonnees.php';

class RIBTest extends TestCase {
    private PDO $pdo;
    private int $utilisateurId; 

    protected function setUp(): void {
        $this->pdo = (new BaseDeDonnees())->getConnexion();
        $this->cleanDatabase();

        $this->utilisateurId = $this->createUtilisateur();
    }

    private function cleanDatabase(): void {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
        
        $this->pdo->exec('DELETE FROM RIB;');
        $this->pdo->exec('DELETE FROM Utilisateur;');
        $this->pdo->exec('DELETE FROM Personne;');
        
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    }
    

    function generateRandomSequence($length) {
        $sequence = '';
        for ($i = 0; $i < $length; $i++) {
            $sequence .= mt_rand(0, 9);
        }
        return $sequence;
    }
        
    private function createUtilisateur(): int {
        $uniqueIdentifiant = 'testuser_' . uniqid();
        $uniqueMail = 'mail@' . uniqid() . ".com";
        $uniqueNum = $this->generateRandomSequence(9);
        $stmt = $this->pdo->prepare("
            INSERT INTO Personne (nom, identifiant, mdp, email, numTel) 
            VALUES (:nom, :identifiant, :mdp, :email, :numTel)
        ");
        $stmt->execute([
            ':nom' => 'Test User',
            ':identifiant' => $uniqueIdentifiant,
            ':mdp' => 'password123',
            ':email' => $uniqueMail,
            ':numTel' => $uniqueNum
        ]);
        $personneId = $this->pdo->lastInsertId();
        if (!$personneId) {
            throw new RuntimeException('L\'ID de la personne n\'a pas été correctement récupéré.');
        }
        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $personneId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("La personne avec ID $personneId n'a pas été correctement insérée.");
        }
        $stmt = $this->pdo->prepare("INSERT INTO Utilisateur (cotisation_active, idPersonne) VALUES (0, :idPersonne)");
        $stmt->execute([':idPersonne' => $personneId]);
        $utilisateurId = $this->pdo->lastInsertId();
        if (!$utilisateurId) {
            throw new RuntimeException('L\'ID de l\'utilisateur n\'a pas été correctement récupéré.');
        }
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("L'utilisateur avec ID $utilisateurId n'a pas été correctement inséré.");
        }
            return $utilisateurId;
    }
    


    public function testRIBConstructor(): void {
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        $this->assertSame(1234567890, $rib->getNumeroCompte());
        $this->assertSame(12345, $rib->getCodeGuichet());
        $this->assertSame(67, $rib->getCle());
        $this->assertSame('FR7612345678901234567890123', $rib->getCodeIBAN());
        $this->assertSame('Dupont', $rib->getTitulaireNom());
        $this->assertSame('Jean', $rib->getTitulairePrenom());
        $this->assertSame('RIB123', $rib->getIdentifiantRIB());
    }

    public function testSettersAndGetters(): void {
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        
        $rib->setNumeroCompte(9876543210);
        $this->assertSame(9876543210, $rib->getNumeroCompte());

        $rib->setCodeGuichet(54321);
        $this->assertSame(54321, $rib->getCodeGuichet());

        $rib->setCle(98);
        $this->assertSame(98, $rib->getCle());

        $rib->setCodeIBAN('GB29NWBK60161331926819');
        $this->assertSame('GB29NWBK60161331926819', $rib->getCodeIBAN());

        $rib->setTitulaireNom('Martin');
        $this->assertSame('Martin', $rib->getTitulaireNom());

        $rib->setTitulairePrenom('Paul');
        $this->assertSame('Paul', $rib->getTitulairePrenom());

        $rib->setIdentifiantRIB('RIB456');
        $this->assertSame('RIB456', $rib->getIdentifiantRIB());
    }

    public function testSettersInvalidValues(): void {
        $this->expectException(InvalidArgumentException::class);

        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        
        $rib->setNumeroCompte(-1);
    }

    public function testAddRIBToDatabase(): void {
        $utilisateurId = $this->utilisateurId;
        
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateurId);
    
        $stmt = $this->pdo->prepare("SELECT * FROM RIB WHERE identifiant_rib = :identifiant_rib");
        $stmt->execute([':identifiant_rib' => 'RIB123']);
        $initialCount = $stmt->rowCount();
    
        $rib->ajouterDansBase();
    
        $stmt->execute([':identifiant_rib' => 'RIB123']);
        $this->assertSame($initialCount + 1, $stmt->rowCount());
    }
    
    

    public function testDatabaseInsertionFailure(): void {
        $this->expectException(RuntimeException::class);
    
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', 1);
        $rib->ajouterDansBase();
    }

    public function testAddingRIBWithNegativeAccountNumber(): void {
        $this->expectException(InvalidArgumentException::class);

        $rib = new RIB(-1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
    }

    public function testAddingRIBWithEmptyName(): void {
        $this->expectException(InvalidArgumentException::class);

        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', '', 'Jean', 'RIB123', $this->utilisateurId);
    }

    public function testAddingRIBWithEmptyID(): void {
        $this->expectException(InvalidArgumentException::class);

        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', '', $this->utilisateurId);
    }

    public function testRIBWithDuplicateIdentifier(): void {
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        $rib1->ajouterDansBase();
    
        $this->expectException(RuntimeException::class);
    
        $rib2 = new RIB(9876543210, 54321, 98, 'GB29NWBK60161331926819', 'Martin', 'Paul', 'RIB123', $this->utilisateurId);
        $rib2->ajouterDansBase();
    }

    public function testUpdateRIB(): void {
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        $rib->ajouterDansBase();
    
        $ribFromDb = $rib->lireRIB();
        $this->assertSame('Dupont', $ribFromDb['titulaire_nom']);
    
        $rib->setTitulaireNom('Lemoine');
        $rib->setCodeIBAN('GB29NWBK60161331926819');
        $rib->mettreAJourRIB();
    
        $ribFromDb = $rib->lireRIB();
        $this->assertSame('Lemoine', $ribFromDb['titulaire_nom']);
        $this->assertSame('GB29NWBK60161331926819', $ribFromDb['code_iban']);
    }
    
    
    public function testDeleteRIB(): void {
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $this->utilisateurId);
        $rib->ajouterDansBase();
    
        $ribFromDb = $rib->lireRIB();
        $this->assertNotNull($ribFromDb);
    
        $rib->supprimerRIB();
    
        $ribFromDb = $rib->lireRIB();
        $this->assertNull($ribFromDb);
    }
    
    
    public function testDeleteUserCascadeRIB(): void {
        $utilisateurId = $this->createUtilisateur();
        
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $this->assertSame(1, $stmt->rowCount(), "L'utilisateur doit exister avant l'ajout du RIB.");
        
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB123', $utilisateurId);
        $rib->ajouterDansBase();
        
        $stmt = $this->pdo->prepare("SELECT * FROM RIB WHERE identifiant_rib = :identifiant_rib");
        $stmt->execute([':identifiant_rib' => 'RIB123']);
        $initialRibCount = $stmt->rowCount();
        $this->assertSame(1, $initialRibCount, "Le RIB doit être ajouté correctement.");
        
        $stmt = $this->pdo->prepare(query: "SELECT * FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $this->assertSame(1, $stmt->rowCount(), "L'utilisateur doit exister avant la suppression.");
        
        $stmt = $this->pdo->prepare("SELECT idPersonne FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $idPersonne = $result['idPersonne'];

        $this->pdo->beginTransaction();
    
        $stmt = $this->pdo->prepare("DELETE FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $this->assertSame(0, $stmt->rowCount(), "L'utilisateur doit être supprimé.");
    
        $stmt = $this->pdo->prepare("SELECT * FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
        $this->assertSame(0, $stmt->rowCount(), "La personne doit être supprimée.");
    
        $stmt = $this->pdo->prepare("SELECT * FROM RIB WHERE identifiant_rib = :identifiant_rib");
        $stmt->execute([':identifiant_rib' => 'RIB123']);
        $finalRibCount = $stmt->rowCount();
        $this->assertSame(0, $finalRibCount, "Le RIB doit être supprimé après la suppression de l'utilisateur.");
    
        $this->pdo->commit();
    }
    
    
    
    public function testValidIBANFormat(): void {
        $rib = new RIB(1234567890, 12345, 67, 'GB29NWBK60161331926819', 'Dupont', 'Jean', 'RIB124', $this->utilisateurId);
        $this->assertSame('GB29NWBK60161331926819', $rib->getCodeIBAN(), 'IBAN valide accepté');
    }
    
    public function testInvalidIBANFormat(): void {
        $this->expectException(InvalidArgumentException::class);
    
        $rib = new RIB(1234567890, 12345, 67, 'INVALIDIBAN123', 'Dupont', 'Jean', 'RIB125', $this->utilisateurId);
    }
    
    public function testAddingRIBWithMissingTitulaireNom(): void {
        $this->expectException(InvalidArgumentException::class);
    
        $rib = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', '', 'Jean', 'RIB126', $this->utilisateurId);
    }
    
    public function testMultipleRIBsForOneUser(): void {
        $utilisateurId = $this->utilisateurId;
    
        $rib1 = new RIB(1234567890, 12345, 67, 'FR7612345678901234567890123', 'Dupont', 'Jean', 'RIB127', $utilisateurId);
        $rib1->ajouterDansBase();
    
        $rib2 = new RIB(9876543210, 54321, 98, 'GB29NWBK60161331926819', 'Martin', 'Paul', 'RIB128', $utilisateurId);
        $rib2->ajouterDansBase();
    
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM RIB WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $utilisateurId]);
        $count = $stmt->fetchColumn();
        $this->assertSame(2, $count, "Deux RIBs doivent être associés à cet utilisateur.");
    }
    
}
