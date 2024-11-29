<?php

namespace App;

class Creneau {

    private string $_date;
    private string $_heureDebut;
    private string $_heureFin;
    private bool $_occupe;
    private int $_id_Creneau;


    //constructeur
    function __construct(string $date, string $heureDebut, string $heureFin, bool $occupe, int $Id_Creneau) {
        $this->set_date($date);
        $this->set_heureDebut($heureDebut);
        $this->set_heureFin($heureFin);
        $this->set_occupation($occupe);
        $this->set_creneauID($Id_Creneau);
    }

    //Setter
    public function set_date($date): void{

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException("La date doit être au format 'YYYY-MM-DD'.");
        }

        $this->_date = $date;
    }

    public function set_heureDebut($heureDebut): void{
        if (!preg_match('/^\d{2}:\d{2}$/', $heureDebut)) {
            throw new \InvalidArgumentException("L'heure de début doit être au format 'HH:MM'.");
        }

        $this->_heureDebut = $heureDebut;
    }

    public function set_heureFin($heureFin): void{
        if (!preg_match('/^\d{2}:\d{2}$/', $heureFin)) {
            throw new \InvalidArgumentException("L'heure de fin doit être au format 'HH:MM'.");
        }
        $this->_heureFin = $heureFin;
    }

    public function set_occupation($occupe): void{

        if (!is_bool($occupe)) {
            throw new \InvalidArgumentException("Le statut 'occupé' doit être un booléen");
        }
        $this->_occupe = $occupe;
    }

    public function set_creneauID($Id_Creneau): void{

        if (!is_numeric($Id_Creneau) || $Id_Creneau <= 0) {
            throw new \InvalidArgumentException("L'identifiant du créneau doit être un entier positif et non nul.");
        }

        $this->_id_Creneau = $Id_Creneau;
    }

    //Getter
    public function get_date(): string {
        return $this->_date;
    }

    public function get_heureDebut(): string {
        return $this->_heureDebut;
    }

    public function get_heureFin(): string{
        return $this->_heureFin;
    }

    public function get_occupation(): bool {
        return $this->_occupe;
    }

    public function get_ID_Creneau(): int{
        return $this->_id_Creneau;
    }

    public function reserverCreneau(): void{
        if($this->get_occupation()){
            throw new \Exception("Ce créneau est déjà réservé.");
        }

        $this->_occupe = true;
        
    }

    public function libererCreneau(): void{
        $this->_occupe = false;
    }
}
?>