<?php
require_once 'BaseDeDonnees.php';

abstract class Personne {
    private string $_nom, $_identifiant, $_mdp, $_email, $_numTel, $_idPersonne;
    private PDO $_pdo;

    public function __construct($nomC, $id, $mdpC, $emailC, $numtelC, $idPersonne = null) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();

        $this->setNom($nomC);
        $this->setIdentifiant($id);
        $this->setMdp($mdpC);
        $this->setEmail($emailC);
        $this->setNumTel($numtelC);

        $this->_nom = $nomC;
        $this->_identifiant = $id;
        $this->_mdp = password_hash($mdpC, PASSWORD_DEFAULT);
        $this->_email = $emailC;
        $this->_numTel = $numtelC;

        if ($idPersonne) {
            $this->_idPersonne = $idPersonne;
        }
        else{
            $this->_idPersonne = "";
        }
    }

    public function getPDO() {
        return $this->_pdo;
    }

    public function getNom() {
        return $this->_nom;
    }

    public function getIdentifiant() {
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

    public function getIdPersonne() {
        return $this->_idPersonne;
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
    public function setIdentifiant($id1) {
        if (!is_string($id1) || empty($id1) || strlen($id1) > 255) {
            throw new InvalidArgumentException("L'identifiant doit être une chaîne de caractères non vide de maximum 255 caractères.");
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
        if (!preg_match('/^\d{8,10}$/', $numTel1)) {
            throw new InvalidArgumentException("Le numéro de téléphone doit contenir entre 8 et 10 chiffres.");
        }
        $this->_numTel = $numTel1;
    }

    public function setIdPersonne($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id de personne doit être un entier positif.");
        }
        $this->_idPersonne = $id;
    }

    public function connexion($id1, $mdp1) {
        if (!is_string($id1) || !is_string($mdp1)) {
            throw new InvalidArgumentException("L'identifiant et le mot de passe doivent être des chaînes de caractères.");
        }
        return $id1 === $this->_identifiant && password_verify($mdp1, $this->_mdp);
    }

    public function modifierMdp($ancienMdp, $nouveauMdp): void {
        if (!password_verify($ancienMdp, $this->_mdp)) {
            throw new InvalidArgumentException("Le mot de passe actuel est incorrect.");
        }
    
        if (!is_string($nouveauMdp) || strlen($nouveauMdp) < 8) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères.");
        }
    
        $nouveauMdpHache = password_hash($nouveauMdp, PASSWORD_DEFAULT);
    
        try {
            $stmt = $this->_pdo->prepare("UPDATE Personne SET mdp = :mdp WHERE idPersonne = :idPersonne");
            $stmt->execute([
                ':mdp' => $nouveauMdpHache,
                ':idPersonne' => $this->_idPersonne
            ]);
            $this->_mdp = $nouveauMdpHache; 
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du mot de passe : " . $e->getMessage());
        }
    }

    public function supprimerPersonne(): void {
        if (empty($this->_idPersonne)) {
            throw new RuntimeException("ID de la personne non défini.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $this->_idPersonne]);
        $exists = $stmt->fetchColumn();
    
        if ($exists == 0) {
            throw new RuntimeException("La personne n'existe pas dans la base de données.");
        }
    
        try {
            $stmt = $this->_pdo->prepare("DELETE FROM Personne WHERE idPersonne = :idPersonne");
            $stmt->execute([':idPersonne' => $this->_idPersonne]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression de la personne : " . $e->getMessage());
        }
    }
    
    public function modifierPersonne($nom, $email, $numTel): void {
        try {
            $stmt = $this->_pdo->prepare("UPDATE Personne SET nom = :nom, email = :email, numTel = :numTel WHERE idPersonne = :idPersonne");
            $stmt->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':numTel' => $numTel,
                ':idPersonne' => $this->_idPersonne
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de la personne : " . $e->getMessage());
        }
    }

    public function ajouterDansLaBase() {
        try {
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
        } catch (PDOException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
    
            if ($code === '23000') { 
                if (str_contains($message, 'Duplicate entry')) {
                    if (str_contains($message, 'identifiant')) {
                        throw new RuntimeException("L'identifiant est déjà utilisé. Veuillez en choisir un autre.");
                    } elseif (str_contains($message, 'email')) {
                        throw new RuntimeException("L'adresse email est déjà associée à un compte.");
                    } elseif (str_contains($message, 'numTel')) {
                        throw new RuntimeException("Le numéro de téléphone est déjà utilisé.");
                    }
                }
            }
                throw new RuntimeException("Une erreur s'est produite lors de l'inscription. Veuillez réessayer plus tard.");
        }
        $this->_idPersonne = $this->_pdo->lastInsertId();
    }
}
?>