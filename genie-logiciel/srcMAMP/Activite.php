<?php
require_once 'BaseDeDonnees.php';

class Activite {
    private $_nom;
    private $_tarif;
    private $_duree;
    private $_pdo;
    private $_id;

    public function __construct($nom, $tarif, $duree) {
        $this->setNom($nom);
        $this->setTarif($tarif);
        $this->setDuree($duree);

        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->_id = null;
        //$this->ajouterActiviteBDD();
    }

    public function getNom() {
        return $this->_nom;
    }

    public function setNom($nom) {
        if (!is_string($nom) || empty($nom)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne de caractères non vide.");
        }
        $this->_nom = $nom;
    }

    public function getTarif() {
        return $this->_tarif;
    }

    public function setTarif($tarif) {
        if (!is_numeric($tarif) || $tarif <= 0) {
            throw new InvalidArgumentException("Le tarif doit être un nombre supérieur à 0.");
        }
        $this->_tarif = $tarif;
    }

    public function getDuree() {
        return $this->_duree;
    }

    public function setDuree($duree) {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $duree)) {
            throw new InvalidArgumentException("La durée doit être au format HH:MM:SS.");
        }
        $this->_duree = $duree;
    }
    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException("L'id doit être un nombre supérieur à 0.");
        }
        $this->_id = $id;
    }
    private function ajouterActiviteBDD() {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Activite (nom, tarif, duree) 
            VALUES (:nom, :tarif, :duree)
        ");
        $stmt->execute([
            ':nom' => $this->_nom,
            ':tarif' => $this->_tarif,
            ':duree' => $this->_duree
        ]);
        $this->_id = $this->_pdo->lastInsertId();
    }

    private function mettreAJourActiviteBDD() {
        $stmt = $this->_pdo->prepare("
            UPDATE Activite
            SET tarif = :tarif, duree = :duree
            WHERE nom = :nom
        ");
        $stmt->execute([
            ':nom' => $this->_nom,
            ':tarif' => $this->_tarif,
            ':duree' => $this->_duree
        ]);
    }
}