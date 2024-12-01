<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class Cotisation {
    private float $_montant;
    private DateTime $_datePaiement;
    private DateTime $_DateFin;
    private PDO $_pdo;
    private int $_idUtilisateur;

    public function __construct(float $montant, DateTime $date, int $idUtilisateur) {
        $this->setMontant($montant);
        $this->setDatePaiement($date);
        $this->setUtilisateur($idUtilisateur);

        $this->_DateFin = clone $this->_datePaiement;
        $this->_DateFin->modify('+1 year');

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->ajouterDansBase();
    }

    public function setMontant(float $montant): void {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant de la cotisation doit être supérieur à zéro.");
        }
        $this->_montant = $montant;
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

    private function ajouterDansBase(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Cotisation (montant, date_paiement, date_fin, idUtilisateur)
                VALUES (:montant, :date_paiement, :date_fin, :idUtilisateur)
            ");
            $stmt->execute([
                ':montant' => $this->_montant,
                ':date_paiement' => $this->_datePaiement->format('Y-m-d'),
                ':date_fin' => $this->_DateFin->format('Y-m-d'),
                ':idUtilisateur' => $this->_idUtilisateur,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout de la cotisation dans la base : " . $e->getMessage());
        }
    }
}