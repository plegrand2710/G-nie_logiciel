<?php
namespace App;
include "GestionCreneauxActivite.php";

class Calendrier {
    private string $_horaireOuvertureSalle;
    private string $_horaireFermetureSalle;
    private array $_jourFermeture = [];
    private array $_tableGestionCreneauxActivite = [];

    public function __construct(string $horaireOuvertureSalle,string $horaireFermetureSalle,array $jourFermeture = [],array $tableGestionCreneauxActivite = []) {

        $this->_horaireOuvertureSalle = $horaireOuvertureSalle;
        $this->_horaireFermetureSalle = $horaireFermetureSalle;
        $this->_jourFermeture = $jourFermeture;

        foreach ($tableGestionCreneauxActivite as $gestion) {
            if (!$gestion instanceof GestionCreneauxActivite) {
                throw new \InvalidArgumentException("Chaque élément doit être une instance de GestionCreneauxActivite.");
            }
        }

        $this->_tableGestionCreneauxActivite = $tableGestionCreneauxActivite;
    }

    public function visualisationCreneaux(): array {
        return $this->_tableGestionCreneauxActivite;
    }

    public function visualisationCreneauxLibre(): array {
        $libres = [];

        foreach ($this->_tableGestionCreneauxActivite as $gestion) {
            foreach ($gestion->get_CreneauActivite() as $creneau) {
                if ($gestion->verifierDisponibilite($creneau)) {
                    $libres[] = $creneau;
                }
            }
        }
        return $libres;
    }

    public function visualisationCreneauxOccupe(): array {
        $occupes = [];

        foreach ($this->_tableGestionCreneauxActivite as $gestion) {
            foreach ($gestion->get_CreneauActivite() as $creneau) {
                if (!$gestion->verifierDisponibilite($creneau)) {
                    $occupes[] = $creneau;
                }
            }
        }
        return $occupes;
    }

    public function ajouterJourFermeture(string $date): void {

        if (!in_array($date, $this->_jourFermeture, true)) {

            $this->_jourFermeture[] = $date;
        }
    }

    public function supprimerJourFermeture(string $date): void {

        $this->_jourFermeture = array_filter(
            $this->_jourFermeture,
            fn($jour) => $jour !== $date
        );
    }

    public function trouverCreneauxPourUneActivite(Activite $activite): array {
        $resultat = [];
        foreach ($this->_tableGestionCreneauxActivite as $gestion) {
            if ($gestion->get_ActiviteForCreneauActivite() === $activite) {
                $resultat = array_merge($resultat, $gestion->get_CreneauActivite());
            }
        }
        return $resultat;
    }

    public function ajouterGestionCreneauxActivite(GestionCreneauxActivite $gestion): void {
        $this->_tableGestionCreneauxActivite[] = $gestion;
    }
}
?>
