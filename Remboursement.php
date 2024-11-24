<?php
class Remboursement implements Paiement{
    private DateTime $_date;
    private float $_montant;
    private float $_penalite;

    function __construct(dateTime $date, float $montant, float $penalite){
        $this->_date = $date;
        $this->_montant = $montant;
        $this->_penalite = $penalite;
    }

    //Setter
    public function set_date(dateTime $date): void{
        $this->_date = $date;
    }

    public function set_montant(float $montant): void{
        $this->_montant = $montant;
    }

    public function set_penalite(float $penalite): void{
        $this->_penalite = $penalite;
    }

    //Getter
    public function get_date(): dateTime{
        return $this->_date;
    }

    public function getMontant(): float{
        return $this->_montant;
    }

    public function get_penalite(): float{
        return $this->_penalite;
    }

    public function effectuerPaiement(): bool{
        $this->_montant = $this->_montant - $this->_penalite;
        return true;
    }
}