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

    public function addCotisation(Cotisation $cotisation): void {
        $this->_cotisations[] = $cotisation;
    }

    public function VerifPayerCotisation(): bool {
        if (!empty($this->_cotisations)) {
            $derniereCotisation = end($this->_cotisations);
            if ($derniereCotisation->verifValiditeCotisation()) {
                echo "La dernière cotisation est encore valide. Aucun paiement nécessaire.\n";
                return true;
            }
        }

        echo "La dernière cotisation n'est plus valide ou inexistante. Paiement nécessaire.\n";
        return false;
    }
}
?>