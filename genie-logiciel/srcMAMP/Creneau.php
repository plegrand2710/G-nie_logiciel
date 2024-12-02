<?php

require_once 'BaseDeDonnees.php';

class Creneau {
    private $_date;
    private $_heureDebut;
    private $_heureFin;
    private $_occupe;
    private $_id;
    private $_pdo;

    public function __construct($date, $hDebut, $hFin, $occupe = false) {
        $this->_pdo = (new BaseDeDonnees())->getConnexion();

        $this->setDate($date);
        $this->setHeureDebut($hDebut);
        $this->setHeureFin($hFin);
        $this->setOccupe($occupe);

        //$this->ajouterCreneauBDD();
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

    public function getDate() {
        return $this->_date;
    }

    public function setDate($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException("La date doit être au format YYYY-MM-DD.");
        }
        $this->_date = $date;
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

    public function getOccupe() {
        return $this->_occupe;
    }

    public function setOccupe($occupe) {
        if (!is_bool($occupe)) {
            throw new InvalidArgumentException("La propriété occupé doit être un booléen.");
        }
        $this->_occupe = $occupe;
    }

    private function ajouterCreneauBDD() {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Creneau (date, heure_debut, heure_fin, reserve) 
            VALUES (:date, :heureDebut, :heureFin, :reserve)
        ");
        $stmt->execute([
            ':date' => $this->_date,
            ':heureDebut' => $this->_heureDebut,
            ':heureFin' => $this->_heureFin,
            ':reserve' => $this->_occupe ? 1 : 0
        ]);

        $this->_id = $this->_pdo->lastInsertId();
    }

    public function reserverCreneau() {
        if ($this->_occupe) {
            throw new LogicException("Ce créneau est déjà réservé.");
        }

        $this->_occupe = true;
        $this->mettreAJourCreneauBDD();
    }

    public function libererCreneau() {
        if (!$this->_occupe) {
            throw new LogicException("Ce créneau n'est pas actuellement réservé.");
        }

        $this->_occupe = false;
        $this->mettreAJourCreneauBDD();
    }

    private function mettreAJourCreneauBDD() {
        $stmt = $this->_pdo->prepare("
            UPDATE Creneau 
            SET reserve = :reserve
            WHERE idCreneau = :idCreneau
        ");
        $stmt->execute([
            ':reserve' => $this->_occupe ? 1 : 0,
            ':idCreneau' => $this->_id
        ]);
    }
}