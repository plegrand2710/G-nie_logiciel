<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class RIB {
    private int $_numero_compte;
    private int $_code_guichet;
    private int $_cle;
    private string $_code_IBAN;
    private string $_titulaire_nom;
    private string $_titulaire_prenom;
    private string $_identifiant_Rib;
    private PDO $_pdo;
    private int $_idUtilisateur;

    public function __construct() {
        
    }


    public function initialiseRIB($numCpt, $cGuichet, $cle1, $cIBAN, $tNom, $tPrenom, $identifiantRIB, $idUtilisateur){
        $this->setNumeroCompte($numCpt);
        $this->setCodeGuichet($cGuichet);
        $this->setCle($cle1);
        $this->setCodeIBAN($cIBAN);
        $this->setTitulaireNom($tNom);
        $this->setTitulairePrenom($tPrenom);
        $this->setIdentifiantRIB($identifiantRIB);
        $this->setIdUtilisateur($idUtilisateur);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->ajouterDansBase();
    }

    public function getRib(): String {
        return "" . $this->_numero_compte . "" . $this->_code_guichet . "" . $this->_cle . "" . $this->_code_IBAN . "" . $this->_identifiant_Rib;
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

    public function getTitulaireNom(): string {
        return $this->_titulaire_nom;
    }

    public function getTitulairePrenom(): string {
        return $this->_titulaire_prenom;
    }

    public function getIdentifiantRIB(): string {
        return $this->_identifiant_Rib;
    }

    public function getIdUtilisateur(): int {
        return $this->_idUtilisateur;
    }

    private function setNumeroCompte($numCpt): void {
        if (!is_int($numCpt) || $numCpt <= 0) {
            throw new InvalidArgumentException("Le numéro de compte doit être un entier positif.");
        }
        $this->_numero_compte = (int)$numCpt;
    }

    private function setCodeGuichet($cGuichet): void {
        if (!is_int($cGuichet) || $cGuichet <= 0) {
            throw new InvalidArgumentException("Le code guichet doit être un entier positif.");
        }
        $this->_code_guichet = (int)$cGuichet;
    }

    private function setCle($cle1): void {
        if (!is_int($cle1) || $cle1 <= 0) {
            throw new InvalidArgumentException("La clé doit être un entier positif.");
        }
        $this->_cle = (int)$cle1;
    }

    private function setCodeIBAN($cIBAN): void {
        if (!preg_match('/^[A-Z0-9]{15,34}$/', $cIBAN)) {
            throw new InvalidArgumentException("Le code IBAN est invalide.");
        }
        $this->_code_IBAN = $cIBAN;
    }

    private function setTitulaireNom($tNom): void {
        if (empty($tNom) || !is_string($tNom)) {
            throw new InvalidArgumentException("Le nom du titulaire doit être une chaîne non vide.");
        }
        $this->_titulaire_nom = $tNom;
    }

    private function setTitulairePrenom($tPrenom): void {
        if (empty($tPrenom) || !is_string($tPrenom)) {
            throw new InvalidArgumentException("Le prénom du titulaire doit être une chaîne non vide.");
        }
        $this->_titulaire_prenom = $tPrenom;
    }

    private function setIdentifiantRIB($identifiantRIB): void {
        if (empty($identifiantRIB) || !is_string($identifiantRIB)) {
            throw new InvalidArgumentException("L'identifiant RIB doit être une chaîne non vide.");
        }
        $this->_identifiant_Rib = $identifiantRIB;
    }

    private function setIdUtilisateur($idUtilisateur): void {
        if (!is_int($idUtilisateur) || $idUtilisateur <= 0) {
            throw new InvalidArgumentException("L'ID utilisateur doit être un entier positif.");
        }
        $this->_idUtilisateur = (int)$idUtilisateur;
    }

    private function ajouterDansBase(): void {
        try {
            $stmt = $this->_pdo->prepare("
                INSERT INTO RIB (numero_compte, code_guichet, cle, code_iban, titulaire_nom, titulaire_prenom, identifiant_rib, idUtilisateur)
                VALUES (:numero_compte, :code_guichet, :cle, :code_iban, :titulaire_nom, :titulaire_prenom, :identifiant_rib, :idUtilisateur)
            ");
            $stmt->execute([
                ':numero_compte' => $this->_numero_compte,
                ':code_guichet' => $this->_code_guichet,
                ':cle' => $this->_cle,
                ':code_iban' => $this->_code_IBAN,
                ':titulaire_nom' => $this->_titulaire_nom,
                ':titulaire_prenom' => $this->_titulaire_prenom,
                ':identifiant_rib' => $this->_identifiant_Rib,
                ':idUtilisateur' => $this->_idUtilisateur,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout du RIB dans la base : " . $e->getMessage());
        }
    }
}