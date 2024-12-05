<?php

require_once 'BaseDeDonnees.php';

class Creneau {
    private $_heureDebut;
    private $_heureFin;
    private $_id;
    private $_pdo;

    public function __construct($date, $hDebut, $hFin, $occupe = false) {
        $this->_pdo = (new BaseDeDonnees())->getConnexion();

        $this->setHeureDebut($hDebut);
        $this->setHeureFin($hFin);
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

    public function getHeureDebut() {
        return $this->_heureDebut;
    }

    public function setHeureDebut($heureDebut) {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heureDebut)) {
            throw new InvalidArgumentException("L'heure de début doit être au format HH:MM:SS.");
        }
        $this->_heureDebut = $heureDebut;
    }

    public function getHeureFin() {
        return $this->_heureFin;
    }

    public function setHeureFin($heureFin) {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heureFin)) {
            throw new InvalidArgumentException("L'heure de fin doit être au format HH:MM:SS.");
        }
        if (strtotime($heureFin) <= strtotime($this->_heureDebut)) {
            throw new InvalidArgumentException("L'heure de fin doit être supérieure à l'heure de début.");
        }
        $this->_heureFin = $heureFin;
    }

    private function ajouterCreneauBDD() {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Creneau (heure_debut, heure_fin) 
            VALUES (:heureDebut, :heureFin)
        ");
        $stmt->execute([
            ':heureDebut' => $this->_heureDebut,
            ':heureFin' => $this->_heureFin,
        ]);

        $this->_id = $this->_pdo->lastInsertId();
    }
}