<?php
require_once __DIR__ . '/BaseDeDonnees.php';
class PaiementVirement implements Paiement {
    private float $_montant;
    private DateTime $_date;
    private int $_ribDestinataire;
    private int $_ribSource;
    private PDO $_pdo;

    public function __construct(float $montant, DateTime $date, int $ribSource, int $ribDestinataire) {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être supérieur à 0.");
        }

        $this->setMontant($montant);
        $this->setDate($date);
        $this->setRibDestinataire($ribDestinataire);
        $this->setRibSource($ribSource);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->effectuerPaiement();
        $this->enregistrerPaiement();
    }

    public function getMontant(): float {
        return $this->_montant;
    }

    private function setMontant(float $montant): void {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être supérieur à 0.");
        }
        $this->_montant = $montant;
    }

    public function getDate(): DateTime {
        return $this->_date;
    }

    private function setDate(DateTime $date): void {
        $this->_date = $date;
    }

    public function getRibDestinataire(): int {
        return $this->_ribDestinataire;
    }

    private function setRibDestinataire(int $ribDestinataire): void {
        $this->_ribDestinataire = $ribDestinataire;
    }

    public function getRibSource(): int {
        return $this->_ribSource;
    }

    private function setRibSource(int $ribSource): void {
        $this->_ribSource = $ribSource;
    }

    public function effectuerPaiement(): bool {
        return true;
    }

    private function enregistrerPaiement(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Paiement (montant, date_paiement, idRIB, idRIBEntreprise)
                VALUES (:montant, :date_paiement, :idRIB, :idRIBEntreprise)
            ");
            $stmt->execute([
                ':montant' => $this->_montant,
                ':date_paiement' => $this->_date->format('Y-m-d H:i:s'),
                ':idRIB' => $this->_ribSource,
                ':idRIBEntreprise' => $this->_ribDestinataire
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'enregistrement du paiement : " . $e->getMessage());
        }
    }
}