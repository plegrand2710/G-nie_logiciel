<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class Cotisation {
    private static float $_montant = 3000;
    private DateTime $_datePaiement;
    private DateTime $_DateFin;
    private PDO $_pdo;
    private int $_idUtilisateur;

    public function __construct(DateTime $date, int $idUtilisateur) {
        $this->setDatePaiement($date);
        $this->setUtilisateur($idUtilisateur);

        $this->_DateFin = clone $this->_datePaiement;
        $this->_DateFin->modify('+1 year');

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->effectuerPaiementCotisation();
    }

    public static function setMontant(float $montant): void {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant de la cotisation doit être supérieur à zéro.");
        }
        self::$_montant = $montant;
    }

    public function setDatePaiement(DateTime $date): void {
        $aujourdhui = new DateTime();
        if ($date > $aujourdhui) {
            throw new InvalidArgumentException("La date de paiement ne peut pas être dans le futur.");
        }
        $this->_datePaiement = $date;
    }

    public function setUtilisateur(int $idUtilisateur): void {
        if ($idUtilisateur <= 0) {
            throw new InvalidArgumentException("L'ID de l'utilisateur doit être un entier positif.");
        }
        $this->_idUtilisateur = $idUtilisateur;
    }

    public function verifValiditeCotisation(): bool {
        $aujourdhui = new DateTime();
        return $aujourdhui <= $this->_DateFin;
    }

    private function ajouterDansBase($idPaiement): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Cotisation (montant, date_paiement, date_fin, idUtilisateur, idPaiement)
                VALUES (:montant, :date_paiement, :date_fin, :idUtilisateur, :idPaiement)
            ");
            $stmt->execute([
                ':montant' => self::$_montant,
                ':date_paiement' => $this->_datePaiement->format('Y-m-d'),
                ':date_fin' => $this->_DateFin->format('Y-m-d'),
                ':idUtilisateur' => $this->_idUtilisateur,
                ':idPaiement' => $idPaiement,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout de la cotisation dans la base : " . $e->getMessage());
        }
    }

    private function mettreAJourCotisationActive(): void {
        try {
            $stmt = $this->_pdo->prepare("
                SELECT COUNT(*) 
                FROM Cotisation 
                WHERE idUtilisateur = :idUtilisateur AND date_fin >= :aujourdhui
            ");
            $stmt->execute([
                ':idUtilisateur' => $this->_idUtilisateur,
                ':aujourdhui' => (new DateTime())->format('Y-m-d'),
            ]);
            $cotisationValide = $stmt->fetchColumn() > 0;

            $stmt = $this->_pdo->prepare("
                UPDATE Utilisateur 
                SET cotisation_active = :cotisation_active 
                WHERE idUtilisateur = :idUtilisateur
            ");
            $stmt->execute([
                ':cotisation_active' => $cotisationValide ? 1 : 0,
                ':idUtilisateur' => $this->_idUtilisateur,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de la cotisation active : " . $e->getMessage());
        }
    }

    private function effectuerPaiementCotisation(): void {
        try {
            $stmt = $this->_pdo->prepare("
                SELECT idRIB 
                FROM RIB 
                WHERE idUtilisateur = :idUtilisateur
                LIMIT 1
            ");
            $stmt->execute([':idUtilisateur' => $this->_idUtilisateur]);
            $idRIBSource = $stmt->fetchColumn();

            if (!$idRIBSource) {
                throw new RuntimeException("RIB source introuvable pour l'utilisateur.");
            }

            $stmt = $this->_pdo->prepare("
                SELECT idRIBEntreprise 
                FROM RIBEntreprise
                LIMIT 1
            ");
            $stmt->execute();
            $idRIBDestinataire = $stmt->fetchColumn();

            if (!$idRIBDestinataire) {
                throw new RuntimeException("RIB destinataire (entreprise) introuvable.");
            }

            error_log("RIB Source ID: " . $idRIBSource);
            error_log("RIB Destinataire ID: " . $idRIBDestinataire);
            
            new PaiementVirement(self::$_montant, $this->_datePaiement, $idRIBSource, $idRIBDestinataire);
            
            $stmt = $this->_pdo->prepare("
                SELECT idPaiement
                FROM Paiement
                WHERE montant = :montant
                AND date_paiement = :date_paiement
                AND idRIB = :idRIB
                AND idRIBEntreprise = :idRIBEntreprise
            ");

            $stmt->execute([
                ':montant' => self::$_montant,
                ':date_paiement' => $this->_datePaiement->format('Y-m-d H:i:s'),
                ':idRIB' => $idRIBSource,
                ':idRIBEntreprise' => $idRIBDestinataire,
            ]);

            $idPaiement = $stmt->fetchColumn();
            $this->ajouterDansBase($idPaiement);
            $this->mettreAJourCotisationActive();
        } catch (PDOException $e) {
            error_log("rib source : " . $idRIBSource);
            error_log("rib entreprise : " . $idRIBDestinataire);
            throw new RuntimeException("Erreur lors du paiement de la cotisation : " . $e->getMessage());
        }
    } 
}
?>