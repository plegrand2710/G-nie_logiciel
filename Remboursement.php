<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class Remboursement {
    private DateTime $_date;
    private float $_montantBase;
    private float $_penalite;
    private PDO $_pdo;
    private int $_idUtilisateur;
    private int $_idRemboursement;
    private int $_idReservation;
    private int $_idPaiement;
    private int $_idRibUtilisateur;
    private int $_idRibEntreprise;

    public function __construct($date, $montantBase, $penalite, $idUtilisateur, $idReservation, $idRIBUtilisateur, $idRIBEntreprise) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->setDate($date);
        $this->setMontantBase($montantBase);
        $this->setPenalite($penalite);
        $this->setIdUtilisateur($idUtilisateur);
        $this->setIdReservation($idReservation);
        $this->setIdRibUtilisateur($idRIBUtilisateur);
        $this->setIdRibEntreprise($idRIBEntreprise);
    }

    public function getDate(): DateTime {
        return $this->_date;
    }

    public function getMontantBase(): float {
        return $this->_montantBase;
    }

    public function getPenalite(): float {
        return $this->_penalite;
    }

    public function getIdUtilisateur(): int {
        return $this->_idUtilisateur;
    }

    public function getIdRemboursement(): int {
        return $this->_idRemboursement;
    }

    public function getIdReservation(): int {
        return $this->_idReservation;
    }

    public function getIdPaiement(): int {
        return $this->_idPaiement;
    }

    public function getIdRibUtilisateur(): int {
        return $this->_idRibUtilisateur;
    }

    public function getIdRibEntreprise(): int {
        return $this->_idRibEntreprise;
    }

    public function setDate($date): void {
        if(!$date instanceof DateTime){
            throw new InvalidArgumentException("La date de paiement du remboursement doit être une instance de dateTime.");

        }
        $this->_date = $date;
    }

    public function setMontantBase($montantBase): void {
        if (!is_float($montantBase) || $montantBase <= 0) {
            throw new InvalidArgumentException("Le montant de base doit être un float supérieur à 0.");
        }
        $this->_montantBase = $montantBase;
    }

    public function setPenalite($penalite): void {
        if (!is_float(value: $penalite) || $penalite < 0) {
            throw new InvalidArgumentException("La pénalité ne peut pas être négative et doit être un float.");
        }

        if ($penalite > $this->_montantBase) {
            throw new InvalidArgumentException("La pénalité ne peut pas dépasser le montant de base.");
        }

        $this->_penalite = $penalite;
    }

    public function setIdUtilisateur($idUtilisateur): void {
        if (!is_int($idUtilisateur) || $idUtilisateur <= 0) {
            throw new InvalidArgumentException("L'ID de l'utilisateur doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("L'utilisateur avec l'ID {$idUtilisateur} n'existe pas.");
        }
    
        $this->_idUtilisateur = $idUtilisateur;
    }
    
    public function setIdReservation($idReservation): void {
        if (!is_int($idReservation) || $idReservation <= 0) {
            throw new InvalidArgumentException("L'ID de la réservation doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE idReservation = :idReservation");
        $stmt->execute([':idReservation' => $idReservation]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("La réservation avec l'ID {$idReservation} n'existe pas.");
        }
    
        $this->_idReservation = $idReservation;
    }
    
    public function setIdPaiement($idPaiement): void {
        if (!is_int($idPaiement) || $idPaiement <= 0) {
            throw new InvalidArgumentException("L'ID du paiement doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Paiement WHERE idPaiement = :idPaiement");
        $stmt->execute([':idPaiement' => $idPaiement]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le paiement avec l'ID {$idPaiement} n'existe pas.");
        }
    
        $this->_idPaiement = $idPaiement;
    }
    
    public function setIdRibUtilisateur($idRibUtilisateur): void {
        if (!is_int($idRibUtilisateur) || $idRibUtilisateur <= 0) {
            throw new InvalidArgumentException("L'ID du RIB source doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM RIB WHERE idRib = :idRib");
        $stmt->execute([':idRib' => $idRibUtilisateur]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le RIB source avec l'ID {$idRibUtilisateur} n'existe pas.");
        }
    
        $this->_idRibUtilisateur = $idRibUtilisateur;
    }
    
    public function setIdRibEntreprise($idRibEntreprise): void {
        if (!is_int($idRibEntreprise) || $idRibEntreprise <= 0) {
            throw new InvalidArgumentException("L'ID du RIB destinataire doit être un entier positif.");
        }
    
        $this->_idRibEntreprise = $idRibEntreprise;
    }
    public function calculMontantRemboursement(): float {
        return max($this->_montantBase - $this->_penalite, 0);
    }

    public function effectuerRemboursement(): void {
        $this->effectuerVirement($this->_idRibUtilisateur, $this->_idRibEntreprise);
   
        $this->enregistrerRemboursement();
    }

    public function enregistrerRemboursement(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Remboursement (montant, date_remboursement, idReservation, idPaiement)
                VALUES (:montant, :date_remboursement, :idReservation, :idPaiement)
            ");
            $stmt->execute([
                ':montant' => $this->calculMontantRemboursement(),
                ':date_remboursement' => $this->_date->format('Y-m-d H:i:s'),
                ':idReservation' => $this->_idReservation,
                ':idPaiement' => $this->_idPaiement
            ]);
            $this->_idRemboursement = $this->_pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'enregistrement du remboursement dans la base de données : " . $e->getMessage());
        }
    }

    private function effectuerVirement(int $idRIBSource, int $idRIBDestinataire): void {
        $paiement = new PaiementVirement($this->calculMontantRemboursement(), $this->_date, $idRIBSource, $idRIBDestinataire, "remboursement");
        $this->setIdPaiement($paiement->getIdPaiement());
    }

    public function lireRemboursement(int $idRemboursement): ?array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM Remboursement WHERE idRemboursement = :idRemboursement");
            $stmt->execute([':idRemboursement' => $idRemboursement]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la lecture du remboursement : " . $e->getMessage());
        }
    }

    public function supprimerRemboursement(int $idRemboursement): void {
        try {
            $stmt = $this->_pdo->prepare("DELETE FROM Remboursement WHERE idRemboursement = :idRemboursement");
            $stmt->execute([':idRemboursement' => $idRemboursement]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du remboursement : " . $e->getMessage());
        }
    }

    public function mettreAJourRemboursement(): void {
        try {
            $nouveauMontant = $this->calculMontantRemboursement();
    
            $stmt = $this->_pdo->prepare("
                UPDATE Remboursement
                SET 
                    montant = :montant,
                    date_remboursement = :date_remboursement,
                    idReservation = :idReservation,
                    idPaiement = :idPaiement
                WHERE idRemboursement = :idRemboursement
            ");
    
            $stmt->execute([
                ':montant' => $nouveauMontant, 
                ':date_remboursement' => $this->_date->format('Y-m-d H:i:s'),
                ':idReservation' => $this->_idReservation,
                ':idPaiement' => $this->_idPaiement,
                ':idRemboursement' => $this->_idRemboursement
            ]);
            
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du remboursement : " . $e->getMessage());
        }
    }
}
?>