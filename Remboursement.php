<?php
namespace App;
//require 'Paiement.php';

class Remboursement {

    private string $_date;
    private float $_montant;
    private float $_penalite;

    // Constructeur
    function __construct($date, $montant, $penalite) {
        $this->set_date($date);
        $this->set_montant($montant);
        $this->set_penalite($penalite);
    }

    // Setter
    public function set_date($date): void {
        if (!strtotime($date)) {
            throw new \InvalidArgumentException("La date est invalide.");
        }
        $this->_date = $date;
    }

    public function set_montant($montant): void {
        if (!is_numeric($montant) || $montant < 0) {
            throw new \InvalidArgumentException("Le montant doit être un nombre positif.");
        }
        $this->_montant = (float) $montant;
    }

    public function set_penalite($penalite): void {
        if (!is_numeric($penalite) || $penalite < 0) {
            throw new \InvalidArgumentException("La pénalité doit être un nombre positif.");
        }
        $this->_penalite = (float) $penalite;
    }

    // Getter 
    public function get_date(): string {
        return $this->_date;
    }

    public function get_montant(): float {
        return $this->_montant;
    }

    public function get_penalite(): float {
        return $this->_penalite;
    }

    // Fonction rembourser
    public function rembourser(): float {

        $remboursementTotal = $this->_montant - $this->_penalite;
        if ($remboursementTotal < 0) {
            $remboursementTotal = 0;
            throw new \RuntimeException("Le remboursement est négatif à cause d'une pénalité élevée.");
        }

        //effectuerPaiement();

        return $remboursementTotal;

    }
}
