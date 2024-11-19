<?php
class Creneau {

    private date $_date;
    private time $_heureDebut;
    private time $_heureFin;
    private bool $_occupe;


    //constructeur
    function __construct(date $date, time $heureDebut, time $heureFin) {
        $this->$date = $date;
        $this->$heureDebut = $heureDebut;
        $this->$heureFin = $heureFin;
    }

    /*public function verifierDisponibilite(Activite $activite): bool {

    }

    public function libererCreneau(Activite $activite): void {
        //Changement de statut
    }

    public function reserverCreneau(Activite $activite, Utilisateur $utilisateur): void {

    }*/


    //Setter
    public function set_date(date $date): void{
        $this->$date = $date;
    }

    public function set_heureDebut(time $heureDebut): void{
        $this->$heureDebut = $heureDebut;
    }

    public function set_heureFin(time $heureFin): void{
        $this->$heureFin = $heureFin;
    }

    //Getter
    public function get_date(): date {
        return $this->$date;
    }

    public function get_heureDebut(): time {
        return $this->$heureDebut;
    }

    public function get_heureFin(): time{
        return $this->$heureFin;
    }
}
?>