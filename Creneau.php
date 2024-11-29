<?php
class Creneau {

    private DateTime $_date;
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
        $this->_date = $date;
    }

    public function set_heureDebut($heureDebut): void{
        $this->_heureDebut = $heureDebut;
    }

    public function set_heureFin($heureFin): void{
        $this->_heureFin = $heureFin;
    }

    public function set_occupation($occupe): void{
        $this->_occupe = $occupe;
    }

    public function set_creneauID($Id_Creneau): void{
        $this->_id_Creneau = $Id_Creneau;
    }

    //Getter
    public function get_date(): DateTime {
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

    public function reserverCreneau(): void{
        $this->_occupe = true;
    }

    public function libererCreneau(): void{
        $this->_occupe = false;
    }
}
?>