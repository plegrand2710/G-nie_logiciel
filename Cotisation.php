<?php
    class Cotisation{
        private float $_montant;
        private DateTime $_datePaiement;
        private DateTime $_DateFin;

        public function __construct(){}

        public function initialiseCotisation($montant,$date){
            if(!is_float($montant)){
                throw new InvalidArgumentException("Le montant doit etre un float");
            }
            if(!$date instanceof DateTime){
                throw new InvalidArgumentException("la date doit etre de type int");
            }
            $this->_montant = $montant;
            $this->_datePaiement = $date;
            $this->_DateFin = clone $this->_datePaiement;
            $this->_DateFin-> modify('+1 year');
        }
        
        public function getMontant(){
            return $this->_montant;
        }
        public function getDateFin(){
            return $this->_DateFin;
        }
        public function setMontant($montant){
            if(!is_float($montant)){
                throw new InvalidArgumentException("Le montant doit etre un float");
            }
            $this->_montant = $montant;
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