<?php
class Activite {

    private string $nom;
    private float $tarif;
    private time $duree;

    function __construct(string $nom, float $tarif, time $duree){
        $this->$nom = $nom;
        $this->$tarif = $tarif;
        $this->$duree = $duree;
    }

    //Setter
    public function set_nom(string $nom): void {
        $this->$nom = $nom;
    }

    public function set_tarif(float $tarif): void {
        $this->$tarif = $tarif;
    }

    public function set_duree(time $duree): void {
        $this->$duree = $duree;
    }

    //Getter
    public function get_nom(): string {
        return $this->$nom = $nom;
    }

    public function get_tarif(): float {
        return $this->$tarif = $tarif;
    }

    public function get_duree(): time {
        return $this->$duree = $duree;
    }

}
?>