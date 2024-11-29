<?php

namespace App;

use PDO;
use PDOException;
use InvalidArgumentException;
use Exception;

class GestionCreneauxActivite {

    private Activite $_activite;
    private array $_tableCreneaux;

    public function __construct() {
        $this->_tableCreneaux = [];
    }

    // Setter pour l'activité
    public function set_Activite(Activite $activite): void {
        $this->_activite = $activite;
    }

    // Setter pour les créneaux
    public function set_tableCreneaux(array $creneaux): void {
        foreach ($creneaux as $creneau) {
            if (!$creneau instanceof Creneau) {
                throw new InvalidArgumentException("Chaque élément doit être une instance de Creneau.");
            }
        }
        $this->_tableCreneaux = $creneaux;
    }

    // Getter pour l'activité
    public function get_ActiviteForCreneauActivite(): Activite {
        return $this->_activite;
    }

    // Getter pour les créneaux
    public function get_CreneauActivite(): array {
        return $this->_tableCreneaux;
    }

    // Affiche tous les créneaux
    public function visualisationCreneauxActivite(): void {
        foreach ($this->_tableCreneaux as $creneau) {
            echo "Date : " . $creneau->get_date() . ", Début : " . $creneau->get_heureDebut() . ", Fin : " 
            . $creneau->get_heureFin() . ", Occupé : " 
            . ($creneau->get_occupation() ? "Oui" : "Non") . "\n";
        }
    }

    // Modifier un créneau
    public function modifierCreneauActivite(int $id_creneauAModifier, Creneau $creneauModifie): void {
        foreach ($this->_tableCreneaux as &$creneau) {
            if ($creneau->get_id() === $id_creneauAModifier) {
                $creneau = $creneauModifie;
                return;
            }
        }
        throw new InvalidArgumentException("Créneau avec l'ID $id_creneauAModifier introuvable.");
    }

    // Ajouter un créneau
    public function ajouterCreneauActivite(Creneau $creneau): void {
        $this->_tableCreneaux[] = $creneau;
    }

    // Supprimer un créneau
    public function supprimerCreneauActivite(Creneau $creneau, PDO $pdo): void {
        $id_creneau = $creneau->get_id();
        try {
            $query = "DELETE FROM CreneauActivite WHERE id_creneau = :id_creneau";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau, PDO::PARAM_INT);
            $stmt->execute();
            $this->_tableCreneaux = array_filter($this->_tableCreneaux, fn($c) => $c->get_id() !== $id_creneau);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }

    // Supprimer tous les créneaux
    public function supprimerToutCreneauActivite(PDO $pdo): void {
        try {
            $pdo->beginTransaction();
            $query = "DELETE FROM CreneauActivite";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $this->_tableCreneaux = [];
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la suppression de tous les créneaux : " . $e->getMessage());
        }
    }

    // Vérifier la disponibilité d'un créneau
    public function verifierDisponibilite(Creneau $creneau): bool {
        return $creneau->get_occupation();
    }
}
?>
