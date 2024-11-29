<?php

namespace App;

class Activite {

    private string $_nom;
    private float $_tarif;
    private string $_duree;
    private int $_id_Activite;

    function __construct(string $nom, float $tarif, string $duree, int $ID_Activite){
        $this->set_nom($nom);
        $this->set_tarif($tarif);
        $this->set_duree($duree);
        $this->set_id_Activite($ID_Activite);
    }

    //Setter
    public function set_nom(string $nom): void {

        if (!is_string($nom) || empty($nom)) {
            throw new \InvalidArgumentException("Le nom doit être une chaîne non vide.");
        }

        $this->_nom = $nom;
    }

    public function set_tarif($tarif): void {
        if (!is_numeric($tarif) || $tarif < 0) {
            throw new \InvalidArgumentException("Le tarif doit être un nombre positif.");
        }
        $this->tarif = (float)$tarif;
    }

    public function set_duree($duree): void {
        if (!is_string($duree) || empty($duree)) {
            throw new \InvalidArgumentException("La durée doit être une chaîne non vide.");
        }
        $this->duree = $duree;
    }

    public function set_id_Activite(int $ID_Activite) {

        if (!is_numeric($ID_Activite) || empty($ID_Activite) || $ID_Activite > 0) {
            throw new \InvalidArgumentException("L'identifiant de l'activité doit être un entier positif et non nul.");
        }
        
        $this->_id_Activite = (int)$ID_Activite;
    }

    //Getter 
    public function get_nom(): string {
        return $this->_nom;
    }

    public function get_tarif(): float {
        return $this->_tarif;
    }

    public function get_duree(): string {
        return $this->_duree;
    }

    public function get_id_Activite(): int{
        return $this->_id_Activite;
    }

}
?>