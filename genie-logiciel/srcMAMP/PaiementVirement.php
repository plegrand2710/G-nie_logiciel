<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class PaiementVirement implements Paiement {
    private float $_montant;
    private DateTime $_date;
    private int $_ribDestinataire;
    private int $_ribSource;
    private string $_type;
    private PDO $_pdo;
    private $_idPaiementVirement;

    public function __construct($montant, $date, $ribSource, $ribDestinataire, $type) {
        $this->setMontant($montant);
        $this->setDate($date);
        $this->setRibDestinataire($ribDestinataire);
        $this->setRibSource($ribSource);
        $this->setType($type);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        if ($this->effectuerPaiement()) {
            $this->enregistrerPaiement();
        }
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

    public function getIdPaiement(): int {
        return $this->_idPaiementVirement;
    }

    public function setIdPaiement($idPaiementVirement): void {
        if (!is_int($idPaiementVirement) || $idPaiementVirement <= 0) {
            throw new InvalidArgumentException("L'ID du RIB destinataire doit être un entier positif.");
        }
    
        $this->_idPaiementVirement = $idPaiementVirement;
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
        if ($ribDestinataire <= 0) {
            throw new InvalidArgumentException("Le RIB destinataire doit être un entier positif.");
        }
        $this->_ribDestinataire = $ribDestinataire;
    }

    public function getRibSource(): int {
        return $this->_ribSource;
    }

    private function setRibSource(int $ribSource): void {
        if ($ribSource <= 0) {
            throw new InvalidArgumentException("Le RIB source doit être un entier positif.");
        }
        $this->_ribSource = $ribSource;
    }

    public function getType(): string {
        return $this->_type;
    }

    private function setType(string $type): void {
        if (!in_array($type, ['paiement', 'remboursement'])) {
            throw new InvalidArgumentException("Le type doit être 'paiement' ou 'remboursement'.");
        }
        $this->_type = $type;
    }

    public function effectuerPaiement(): bool {
        return true;
    }

    public function enregistrerPaiement(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO Paiement (montant, date_paiement, idRIB, idRIBEntreprise, type)
                VALUES (:montant, :date_paiement, :idRIB, :idRIBEntreprise, :type_transaction)
            ");
            $stmt->execute([
                ':montant' => $this->_montant,
                ':date_paiement' => $this->_date->format('Y-m-d H:i:s'),
                ':idRIB' => $this->_ribSource,
                ':idRIBEntreprise' => $this->_ribDestinataire,
                ':type_transaction' => $this->_type
            ]);
            $this->_idPaiementVirement = $this->_pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'enregistrement du paiement : " . $e->getMessage());
        }
    }
}
?>