<?php
class Utilisateur extends Personne {
    private $_ribs;
    private array $_cotisations;
    private $_idUtilisateur;

    public function __construct($nomC, $id, $mdpC, $emailC, $numtelC) {
        parent::__construct($nomC, $id, $mdpC, $emailC, $numtelC);
        $this->_ribs = [];
        $this->_cotisations = [];
    }

    public function ajouterRib($rib) {
        if(!$rib instanceof RIB){
            throw new InvalidArgumentException("Le RIB doit être de type RIB.");
        }
        $this->_ribs[] = $rib;
    }

    public function ajouterCotisation(Cotisation $cotisation) {
        $this->_cotisations[] = $cotisation;
    }
    public function setCotisations($cotisations) {
        $this->cotisations = $cotisations;
    }
    public function getRibs() {
        return $this->_ribs;
    }

    public function getCotisations() {
        return $this->_cotisations;
    }

    public function getRib(int $index): ?RIB {
        return $this->_ribs[$index] ?? null;
    }

    public function getIdUtilisateur(): int {
        return $this->_idUtilisateur;
    }

    public function setIdUtilisateur($id): void {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id d'utilisateur doit être un entier positif.");
        }
        $this->_idUtilisateur = $id;
    }

    public function setRib(int $index, RIB $rib): void {
        if (!isset($this->_ribs[$index])) {
            throw new InvalidArgumentException("Le RIB spécifié n'existe pas.");
        }
        $this->_ribs[$index] = $rib;
    }

    public function addCotisation(Cotisation $cotisation): void {
        $this->_cotisations[] = $cotisation;
    }

    public function VerifPayerCotisation(): bool {
        $pdo = $this->getPdo();

        $stmt = $pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
        $stmt->execute([':identifiant' => $this->getIdentifiant()]);
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
                return true;
            }
        }
        $this->mettreAJourCotisationActive($idUtilisateur, false);
        return false;
    }

    public function mettreAJourCotisationActive(int $idUtilisateur, bool $active): void {
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

    public function ajouterDansLaBDD() {
        parent::ajouterDansLaBase();
        $pdo = $this->getPdo();
    
        $stmt = $pdo->prepare("SELECT idPersonne FROM Personne WHERE identifiant = :identifiant");
        $stmt->execute([':identifiant' => $this->getIdentifiant()]);
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
        $this->_idUtilisateur = $pdo->lastInsertId();
        foreach ($this->_ribs as $rib) {
            $rib->ajouterDansBase();
        }
    }
    
}
?>