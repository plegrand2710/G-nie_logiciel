<?php
require_once 'BaseDeDonnees.php';
class GestionCreneauxActivite {
    private PDO $_pdo;
    private $_activite;
    public function __construct($activite) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->_activite = $activite;
    }

    public function visualisationCreneauxActivite(): array {
        $stmt = $this->_pdo->prepare("
            SELECT Creneau.idCreneau, Creneau.date, Creneau.heure_debut, Creneau.heure_fin, Activite.nom AS activite
            FROM gestionCreneauxActivite
            JOIN Creneau ON gestionCreneauxActivite.idCreneau = Creneau.idCreneau
            JOIN Activite ON gestionCreneauxActivite.idActivite = Activite.idActivite
            WHERE gestionCreneauxActivite.idActivite = :idActivite
        ");
        $stmt->execute([':idActivite' => $this->_activite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifierDisponibilite($idCreneau) {
        $stmt = $this->_pdo->prepare("
            SELECT reserve FROM Creneau WHERE idCreneau = :idCreneau
        ");
        $stmt->execute([':idCreneau' => $idCreneau]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? !$result['reserve'] : false;
    }

    public function ajouterCreneauActivite($creneaux) {
        if (!is_array($creneaux)) $creneaux = [$creneaux];
        foreach ($creneaux as $idCreneau) {
            $stmt = $this->_pdo->prepare("
                INSERT INTO gestionCreneauxActivite (idCreneau, idActivite) VALUES (:idCreneau, :idActivite)
            ");
            $stmt->execute([':idCreneau' => $idCreneau, ':idActivite' => $this->_activite]);
        }
    }

    public function supprimerCreneauActivite($creneaux) {
        if (!is_array($creneaux)) $creneaux = [$creneaux];
        foreach ($creneaux as $idCreneau) {
            $stmt = $this->_pdo->prepare("
                DELETE FROM gestionCreneauxActivite WHERE idCreneau = :idCreneau AND idActivite = :idActivite
            ");
            $stmt->execute([':idCreneau' => $idCreneau, ':idActivite' => $this->_activite]);
        }
    }

    public function modifierCreneauActivite($creneaux) {
        $this->supprimerCreneauActivite($this->getTableCreneau());
        $this->ajouterCreneauActivite($creneaux);
    }

    public function getTableCreneau() {
        $stmt = $this->_pdo->prepare("
            SELECT idCreneau FROM gestionCreneauxActivite WHERE idActivite = :idActivite
        ");
        $stmt->execute([':idActivite' => $this->_activite]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}