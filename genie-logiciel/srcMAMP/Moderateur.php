<?php
class Moderateur extends Personne {
    public function __construct($nomC, $id, $mdpC, $emailC, $numtelC) {
        parent::__construct($nomC, $id, $mdpC, $emailC, $numtelC);

    }

    public function supprimerUtilisateur($idUtilisateur): bool {
        if (!is_int($idUtilisateur)) {
            throw new InvalidArgumentException("L'ID utilisateur doit être un entier.");
        }
        $pdo = $this->getPdo();
        try {
            $pdo->beginTransaction();
    
            $stmt = $pdo->prepare("SELECT idPersonne FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([':idUtilisateur' => $idUtilisateur]);
            $idPersonne = $stmt->fetchColumn();
            if (!$idPersonne) {
                throw new Exception("Utilisateur introuvable avec l'ID $idUtilisateur.");
            }
    
            $stmt = $pdo->prepare("DELETE FROM RIB WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([':idUtilisateur' => $idUtilisateur]);
    
            $stmt = $pdo->prepare("DELETE FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([':idUtilisateur' => $idUtilisateur]);
    
            $stmt = $pdo->prepare("DELETE FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([':idUtilisateur' => $idUtilisateur]);
    
            $stmt = $pdo->prepare("DELETE FROM Personne WHERE idPersonne = :idPersonne");
            $stmt->execute([':idPersonne' => $idPersonne]);
    
            $pdo->commit();
    
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }

    public function ajouterDansLaBDD() {
        parent::ajouterDansLaBase();
    
        $pdo = $this->getPdo();
    
        $stmt = $pdo->prepare("SELECT idPersonne FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $this->getIdentifiant()]);
        $idPersonne = $stmt->fetchColumn();
    
        if (!$idPersonne) {
            throw new RuntimeException("Échec de l'insertion du modérateur. La personne n'a pas été trouvée.");
        }
    
        $stmt = $pdo->prepare("INSERT INTO Moderateur (idPersonne) VALUES (:idPersonne)");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            throw new RuntimeException("L'ajout du modérateur a échoué.");
        }
    }
}
?>