<?php
class Utilisateur extends Personne {
    private Rib $_rib;
    private array $_cotisations;

    public function __construct($nomC, $id, $mdpC, $emailC, $numtelC) {
        parent::__construct($nomC, $id, $mdpC, $emailC, $numtelC);
        $this->_rib = new Rib();
        $this->_cotisations = [];

        $pdo = $this->getPdo();

        $stmt = $pdo->prepare("SELECT idPersonne FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $this->getId()]);
        $idPersonne = $stmt->fetchColumn();

        if (!$idPersonne) {
            throw new RuntimeException("Échec de l'insertion de l'utilisateur. La personne n'a pas été trouvée.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO Utilisateur (cotisation_active, idPersonne)
            VALUES (:cotisation_active, :idPersonne)
        ");
        $stmt->execute([
            ':cotisation_active' => $this->VerifPayerCotisation() ? 1 : 0,
            ':idPersonne' => $idPersonne
        ]);
    }

    public function getRib(): Rib {
        return $this->_rib;
    }

    public function getCotisations(): array {
        return $this->_cotisations;
    }

    public function setRib($rib): void {
        if (!$rib instanceof RIB) {
            throw new InvalidArgumentException("Le rib doit être une instance de RIB.");
        }
        $this->_rib = $rib;
    }

    public function addCotisation(Cotisation $cotisation): void {
        $this->_cotisations[] = $cotisation;
    }

    public function VerifPayerCotisation(): bool {
        $pdo = $this->getPdo();

        $stmt = $pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $this->getId()]);
        $idUtilisateur = $stmt->fetchColumn();

        if (!$idUtilisateur) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT date_fin FROM Cotisation WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
        $cotisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aujourdhui = new DateTime();
        foreach ($cotisations as $cotisation) {
            $dateFin = new DateTime($cotisation['date_fin']);
            if ($dateFin >= $aujourdhui) {
                $this->mettreAJourCotisationActive($idUtilisateur, true);
                echo "Une cotisation valide a été trouvée. Aucun paiement nécessaire.\n";
                return true;
            }
        }
        $this->mettreAJourCotisationActive($idUtilisateur, false);
        echo "Aucune cotisation valide trouvée. Paiement nécessaire.\n";
        return false;
    }

    private function mettreAJourCotisationActive(int $idUtilisateur, bool $active): void {
        $pdo = $this->getPdo();
    
        try {
            $stmt = $pdo->prepare("UPDATE Utilisateur SET cotisation_active = :active WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([
                ':active' => $active ? 1 : 0,
                ':idUtilisateur' => $idUtilisateur,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de la cotisation_active : " . $e->getMessage());
        }
    }
}
?>