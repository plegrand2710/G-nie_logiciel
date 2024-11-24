<?php
class PaiementVirement implements Paiement{
    private float $_montant;
    private DateTime $_date;
    private RIB $_ribDestinataire;
    private RIB $_ribSource;

    public function __construct(float $montant, DateTime $date, RIB $ribDestinataire, RIB $ribSource) {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être supérieur à 0.");
        }

        $this->_montant = $montant;
        $this->_date = $date;
        $this->_ribDestinataire = $ribDestinataire;
        $this->_ribSource = $ribSource;
    }

    public function effectuerPaiement(): bool{
        // Simule un paiement
        return true;
    }
}