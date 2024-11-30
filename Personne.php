<?php
require_once 'BaseDeDonnees.php';

abstract class Personne {
    private string $_nom, $_identifiant, $_mdp, $_email, $_numTel;
    private PDO $_pdo;

    public function __construct($nomC, $id, $mdpC, $emailC, $numtelC) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        if (!is_string($nomC) || empty($nomC)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne de caractères non vide.");
        }
        if (!is_string($id) || empty($id)) {
            throw new InvalidArgumentException("L'identifiant doit être une chaîne de caractères non vide.");
        }
        if (!is_string($mdpC) || strlen($mdpC) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit être une chaîne d'au moins 8 caractères.");
        }
        if (!filter_var($emailC, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email n'est pas valide.");
        }
        if (!preg_match('/^\d{10}$/', $numtelC)) {
            throw new InvalidArgumentException("Le numéro de téléphone doit contenir exactement 10 chiffres.");
        }

        $this->_nom = $nomC;
        $this->_identifiant = $id;
        $this->_mdp = password_hash($mdpC, PASSWORD_DEFAULT);
        $this->_email = $emailC;
        $this->_numTel = $numtelC;

        $this->ajouterDansLaBase();
    }

    public function getPDO() {
        return $this->_pdo;
    }

    public function getNom() {
        return $this->_nom;
    }

    public function getId() {
        return $this->_identifiant;
    }

    public function getMdp() {
        return $this->_mdp;
    }

    public function getEmail() {
        return $this->_email;
    }

    public function getNumTel() {
        return $this->_numTel;
    }

    public function setNom($nom1) {
        if (!is_string($nom1) || empty($nom1)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne de caractères non vide.");
        }
        $this->_nom = $nom1;
    }

    public function setPDO($pdo) {
        if (!$pdo instanceof PDO) {
            throw new InvalidArgumentException("Le PDO doit être un PDO.");
        }
        $this->_pdo = $pdo;
    }
    public function setId($id1) {
        if (!is_string($id1) || empty($id1)) {
            throw new InvalidArgumentException("L'identifiant doit être une chaîne de caractères non vide.");
        }
        $this->_identifiant = $id1;
    }

    public function setMdp($mdp1) {
        if (!is_string($mdp1) || strlen($mdp1) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit être une chaîne d'au moins 8 caractères.");
        }
        $this->_mdp = password_hash($mdp1, PASSWORD_BCRYPT);
    }

    public function setEmail($email1) {
        if (!filter_var($email1, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email n'est pas valide.");
        }
        $this->_email = $email1;
    }

    public function setNumTel($numTel1) {
        if (!preg_match('/^\d{10}$/', $numTel1)) {
            throw new InvalidArgumentException("Le numéro de téléphone doit contenir exactement 10 chiffres.");
        }
        $this->_numTel = $numTel1;
    }

    public function connexion($id1, $mdp1) {
        if (!is_string($id1) || !is_string($mdp1)) {
            throw new InvalidArgumentException("L'identifiant et le mot de passe doivent être des chaînes de caractères.");
        }
        return $id1 === $this->_identifiant && password_verify($mdp1, $this->_mdp);
    }

    public function modifierInfoConnexion($id2, $mdp2) {
        $this->setId($id2);
        $this->setMdp($mdp2);
    }

    private function ajouterDansLaBase() {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Personne (nom, identifiant, mdp, email, numTel, type)
            VALUES (:nom, :identifiant, :mdp, :email, :numTel, :type)
        ");
        $stmt->execute([
            ':nom' => $this->_nom,
            ':identifiant' => $this->_identifiant,
            ':mdp' => $this->_mdp,
            ':email' => $this->_email,
            ':numTel' => $this->_numTel,
            ':type' => get_class($this),
        ]);
    }
}
?>