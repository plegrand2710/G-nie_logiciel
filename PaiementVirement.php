<?php
    class PaiementVirement{

        private float $_montant;
        private DateTime $_date;
        private RIB $_RibCrediteur, $_RibDebiteur;

        public function __construct($montant,$date, $RibC,$RibD){
            if(!is_float($montant)){
                throw new InvalidArgumentException("Le montant doit etre un float");
            }
            if(!$date instanceof DateTime){
                throw new InvalidArgumentException("la date doit etre de type int");
            }
            if(!$RibD instanceof RIB ){
                throw new InvalidArgumentException("Le rib du débiteur doit etre un Rib");
            }
            if(!$RibC instanceof RIB ){
                throw new InvalidArgumentException("Le rib du créditeur doit etre un Rib");
            }

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