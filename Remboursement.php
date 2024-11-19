<?php
class Remboursement {
    private date $date;
    private float $montant;
    private float $penalite;

    function __construct(date $date, float $montant, float $penalite){
        $this->$date = $date;
        $this->$montant = $montant;
        $this->$penalite = $penalite;
    }

    //Setter
    public function set_date(date $date): void{
        $this->$date = $date;
    }

    public function set_montant(float $montant): void{
        $this->$montant = $montant;
    }

    public function set_penalite(float $penalite): void{
        $this->$penalite = $penalite;
    }

    //Getter
    public function get_date(): date{
        return $this->$date;
    }

    public function get_montant(): float{
        $this->$montant;
    }

    public function get_penalite(): float{
        $this->$penalite;
    }
}