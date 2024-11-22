<?php
    class PaiementVirement{

        private float $_montant;
        private date $_date;
        private RIB $_RibCrediteur, $_RibDebiteur;

        public function __construct($montant,$date, $RibC,$RibD){
             $this->_montant = $montant;
             $this->_date = $date;
             $this->_RibCrediteur = $RibC;
             $this->_RibDebiteur = $RibD;   
        }

        public function effectuerPaiement(){
            return True;
        }

    }
?>