<?php

namespace App;

use PDO;
use PDOException;
use InvalidArgumentException;
use Exception;
//require_once 'CreneauActivite.php';

class GestionCreneauxActiviteReserver implements CreneauActivite{

    private array $_paireCreneauActivite;  //Array de CreneauActivite
    private string $_date;

    function __construct(int $paireCreneauActivite, string $date) {
        $this->set_paireCreneauActivite($paireCreneauActivite);
        $this->set_dateReservation($date);
    }

    // Setter 
    public function set_paireCreneauActivite(CreneauActivite $paireCreneauActivite): void {
        $this->_paireCreneauActivite = $paireCreneauActivite;
        $this->_paireCreneauActivite->set_Disponibilite(false);
    }

    public function set_dateReservation(string $date): void {
        $this->_date = $date;
    }

    // Getter
    public function get_paireCreneauActivite(): array {
        return $this->_paireCreneauActivite;
    }

    public function get_date(): string {
        return $this->_date;
    }
    
    
    //s méthodes de visualisation pour : les créneaux réservés, non réservés, all pour une activité 

    // Affiche tous les créneaux une activité réservé -- obsolète
    public function visualisationCreneauxActivite(): void {
        foreach ($this->_tableCreneaux as $creneau) {
            echo "Date : " . $creneau->get_date() . ", Début : " . $creneau->get_heureDebut() . ", Fin : " 
            . $creneau->get_heureFin() . ", Occupé : " 
            . ($creneau->get_occupation() ? "Oui" : "Non") . "\n";
        }
    }

    // Modifier un créneau ( modifie la plage horaire du créneau) -- obsolète
    public function modifierCreneauActivite(int $id_creneauAModifier, Creneau $creneauModifie): void {
        foreach ($this->_tableCreneaux as &$creneau) {
            if ($creneau->get_ID_Creneau() === $id_creneauAModifier) {
                $creneau = $creneauModifie;
                return;
            }
        }
        throw new InvalidArgumentException("Créneau avec l'ID $id_creneauAModifier introuvable.");
    }

    // Ajouter une paire Creneau:Activite 
    public function ajouterCreneauActivite(int $ID_Activite, $ID_Creneau): void {
        $this->_paireCreneauActivite = [$ID_Activite, $ID_Creneau];
    }


    // Vérifier la disponibilité d'un créneau --- modification : paramètres et méthodes obsolètes -- obsolète
    public function verifierDisponibilite(Creneau $creneau): bool {
        return $creneau->get_occupation();
    }
}
?>
