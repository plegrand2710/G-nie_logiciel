<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class RIBEntreprise {
    private int $_numero_compte;
    private int $_code_guichet;
    private int $_cle;
    private string $_code_IBAN;
    private string $_nom_entreprise;
    private string $_identifiant_RIB;
    private PDO $_pdo;
    private int $_idRib;
    public function __construct($numCpt, $cGuichet, $cle1, $cIBAN, $nomEntreprise, $identifiantRIB) {
        $this->setNumeroCompte($numCpt);
        $this->setCodeGuichet($cGuichet);
        $this->setCle($cle1);
        $this->setCodeIBAN($cIBAN);
        $this->setNomEntreprise($nomEntreprise);
        $this->setIdentifiantRIB($identifiantRIB);
    
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
    }

    public function getRib(): string {
        return "" . $this->_numero_compte . "" . $this->_code_guichet . "" . $this->_cle . "" . $this->_code_IBAN . "" . $this->_identifiant_RIB;
    }

    public function getNumeroCompte(): int {
        return $this->_numero_compte;
    }

    public function getCodeGuichet(): int {
        return $this->_code_guichet;
    }

    public function getCle(): int {
        return $this->_cle;
    }

    public function getCodeIBAN(): string {
        return $this->_code_IBAN;
    }

    public function getNomEntreprise(): string {
        return $this->_nom_entreprise;
    }

    public function getIdentifiantRIB(): string {
        return $this->_identifiant_RIB;
    }

    public function getIdRib(): int {
        return $this->_idRib;
    }
    public function setNumeroCompte($numCpt): void {
        if (!is_int($numCpt) || $numCpt <= 0) {
            throw new InvalidArgumentException("Le numéro de compte doit être un entier positif.");
        }
        $this->_numero_compte = (int)$numCpt;
    }

    public function setCodeGuichet($cGuichet): void {
        if (!is_int($cGuichet) || $cGuichet <= 0) {
            throw new InvalidArgumentException("Le code guichet doit être un entier positif.");
        }
        $this->_code_guichet = (int)$cGuichet;
    }

    public function setCle($cle1): void {
        if (!is_int($cle1) || $cle1 <= 0) {
            throw new InvalidArgumentException("La clé doit être un entier positif.");
        }
        $this->_cle = (int)$cle1;
    }

    public function setCodeIBAN($cIBAN): void {
        if (!preg_match('/^[A-Z0-9]{15,34}$/', $cIBAN)) {
            throw new InvalidArgumentException("Le code IBAN est invalide.");
        }
        $this->_code_IBAN = $cIBAN;
    }

    public function setNomEntreprise($nomEntreprise): void {
        if (empty($nomEntreprise) || !is_string($nomEntreprise)) {
            throw new InvalidArgumentException("Le nom de l'entreprise doit être une chaîne non vide.");
        }
        $this->_nom_entreprise = $nomEntreprise;
    }

    public function setIdentifiantRIB($identifiantRIB): void {
        if (empty($identifiantRIB) || !is_string($identifiantRIB)) {
            throw new InvalidArgumentException("L'identifiant RIB doit être une chaîne non vide.");
        }
        $this->_identifiant_RIB = $identifiantRIB;
    }

    public function setIdRib($idRib): void {
        if (!is_int($idRib) || $idRib <= 0) {
            throw new InvalidArgumentException("L'ID rib doit être un entier positif.");
        }
        $this->_idRib = (int)$idRib;
    }
    public function ajouterDansBase(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO RIBEntreprise (numero_compte, code_guichet, cle, code_iban, titulaire_nom, identifiant_rib)
                VALUES (:numero_compte, :code_guichet, :cle, :code_iban, :nom_entreprise, :identifiant_rib)
            ");
            $stmt->execute([
                ':numero_compte' => $this->_numero_compte,
                ':code_guichet' => $this->_code_guichet,
                ':cle' => $this->_cle,
                ':code_iban' => $this->_code_IBAN,
                ':nom_entreprise' => $this->_nom_entreprise,
                ':identifiant_rib' => $this->_identifiant_RIB
            ]);
            $this->_idRib = $this->_pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout du RIB Entreprise dans la base : " . $e->getMessage());
        }
    }

    public function lireRIB(): ?array {
        try {
            $stmt = $this->_pdo->prepare("SELECT * FROM RIBEntreprise WHERE idRIBEntreprise = :idRib");
            $stmt->execute([':idRib' => $this->_idRib]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la lecture du RIB Entreprise : " . $e->getMessage());
        }
    }

    public function mettreAJourRIB(): void {
        try {
            $stmt = $this->_pdo->prepare("
                UPDATE RIBEntreprise 
                SET 
                    numero_compte = :numero_compte, 
                    code_guichet = :code_guichet, 
                    cle = :cle, 
                    code_iban = :code_iban, 
                    titulaire_nom = :nom_entreprise,
                    identifiant_rib = :identifiant_rib
                WHERE idRIBEntreprise = :idRib
            ");
            $stmt->execute([
                ':numero_compte' => $this->_numero_compte,
                ':code_guichet' => $this->_code_guichet,
                ':cle' => $this->_cle,
                ':code_iban' => $this->_code_IBAN,
                ':nom_entreprise' => $this->_nom_entreprise,
                ':identifiant_rib' => $this->_identifiant_RIB,
                ':idRib' => $this->_idRib,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du RIB Entreprise : " . $e->getMessage());
        }
    }

    public function supprimerRIB(): void {
        try {
            $stmt = $this->_pdo->prepare("DELETE FROM RIBEntreprise WHERE idRibEntreprise = :idRib");
            $stmt->execute([':idRib' => $this->_idRib]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du RIB Entreprise : " . $e->getMessage());
        }
    }

    public function afficherRIB(): void {
        echo "RIB Entreprise : {$this->getRib()}\n";
    }
}
?>