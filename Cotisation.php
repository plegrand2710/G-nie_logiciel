<?php
    class Cotisation{
        private float $_montant;
        private DateTime $_datePaiement;
        private DateTime $_DateFin;

        public function __construct(){}

        public function initialiseCotisation($montant,$date){
            $this->_montant = $montant;
            $this->_datePaiement = $date;
            $this->_DateFin = clone $this->_datePaiement;
            $this->_DateFin-> modify('+1 year');
        }
        
        public function verifValiditeCotisation(){
            $aujourdhui = new DateTime();
            if($aujourdhui > $this->_DateFin){
                return false;
            }
            return true;
        }

    }
?>