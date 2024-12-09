<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class Cotisation {
    private static float $_montant = 3000;
    private DateTime $_datePaiement;
    private DateTime $_DateFin;
    private PDO $_pdo;
    private int $_idUtilisateur;
    private int $_idCotisation;

    public function __construct(DateTime $date, int $idUtilisateur, PDO $pdo = null) {
        try {
            $bdd = new BaseDeDonnees();
            $this->_pdo = $bdd->getConnexion();

            $this->setDatePaiement($date);
            $this->setUtilisateur($idUtilisateur);

            $this->_DateFin = clone $this->_datePaiement;
            $this->_DateFin->modify('+1 year');

        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de l'initialisation de la cotisation : " . $e->getMessage());
        }
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

        try {
            $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
            $stmt->execute([':idUtilisateur' => $idUtilisateur]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                throw new RuntimeException("Utilisateur non trouvé.");
            }
            $this->_idUtilisateur = $idUtilisateur;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la vérification de l'utilisateur : " . $e->getMessage());
        }
    }

    public function setId($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id doit être un nombre supérieur à 0.");
        }
        $this->_idCotisation = $id;
    }

    public function verifValiditeCotisation(): bool {
        $aujourdhui = new DateTime();
        return $aujourdhui <= $this->_DateFin;
    }

    public function ajouterDansBase($idPaiement): void {
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
            $this->_idCotisation = $this->_pdo->lastInsertId();
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

    public function effectuerPaiementCotisation(): void {
        try {
            $idRIBSource = $this->getIdRIBSource();
            $idRIBDestinataire = $this->getIdRIBDestinataire();

            $this->effectuerVirement($idRIBSource, $idRIBDestinataire);

            $idPaiement = $this->getIdPaiement($idRIBSource, $idRIBDestinataire);

            $this->ajouterDansBase($idPaiement);
            $this->mettreAJourCotisationActive();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors du paiement de la cotisation : " . $e->getMessage());
        }
    }

    private function getIdRIBSource(): int {
        try {
            $stmt = $this->_pdo->prepare("SELECT idRIB FROM RIB WHERE idUtilisateur = :idUtilisateur LIMIT 1");
            $stmt->execute([':idUtilisateur' => $this->_idUtilisateur]);
            $idRIBSource = $stmt->fetchColumn();

            if (!$idRIBSource) {
                throw new RuntimeException("RIB source introuvable pour l'utilisateur.");
            }

            return $idRIBSource;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération du RIB source : " . $e->getMessage());
        }
    }

    private function getIdRIBDestinataire(): int {
        try {
            $stmt = $this->_pdo->prepare("SELECT idRIBEntreprise FROM RIBEntreprise LIMIT 1");
            $stmt->execute();
            $idRIBDestinataire = $stmt->fetchColumn();

            if (!$idRIBDestinataire) {
                throw new RuntimeException("RIB destinataire (entreprise) introuvable.");
            }

            return $idRIBDestinataire;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération du RIB destinataire : " . $e->getMessage());
        }
    }

    private function effectuerVirement(int $idRIBSource, int $idRIBDestinataire): void {
        try {
            new PaiementVirement(self::$_montant, $this->_datePaiement, $idRIBSource, $idRIBDestinataire, "paiement");
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors du virement : " . $e->getMessage());
        }
    }

    private function getIdPaiement(int $idRIBSource, int $idRIBDestinataire): int {
        try {
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
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération de l'id de paiement : " . $e->getMessage());
        }
    }

    public function updateCotisation(int $idCotisation): void {
        try {
            $stmt = $this->_pdo->prepare("
                UPDATE Cotisation 
                SET montant = :montant, date_paiement = :date_paiement, date_fin = :date_fin
                WHERE idCotisation = :idCotisation
            ");
            $stmt->execute([
                ':montant' => self::$_montant,
                ':date_paiement' => $this->_datePaiement->format('Y-m-d'),
                ':date_fin' => $this->_DateFin->format('Y-m-d'),
                ':idCotisation' => $idCotisation
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de la cotisation : " . $e->getMessage());
        }
    }

    public function deleteCotisation(int $idCotisation): void {
        try {
            $stmt = $this->_pdo->prepare("DELETE FROM Cotisation WHERE idCotisation = :idCotisation");
            $stmt->execute([':idCotisation' => $idCotisation]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression de la cotisation : " . $e->getMessage());
        }
    }

    public function getCotisationById(int $idCotisation): array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM Cotisation WHERE idCotisation = :idCotisation");
            $stmt->execute([':idCotisation' => $idCotisation]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération de la cotisation : " . $e->getMessage());
        }
    }

    public function getAllCotisations(): array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM Cotisation");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des cotisations : " . $e->getMessage());
        }
    }

    public function getMontant(): float {
        return self::$_montant;
    }

    public function getDatePaiement(): DateTime {
        return $this->_datePaiement;
    }

    public function getDateFin(): DateTime {
        return $this->_DateFin;
    }

    public function getIdUtilisateur(): int {
        return $this->_idUtilisateur;
    }

    public function getIdCotisation() {
        return $this->_idCotisation;
    }
}