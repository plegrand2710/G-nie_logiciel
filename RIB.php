<?php
    class RIB{
        private int $_numero_compte, $_code_guichet, $_cle, $_identifiant_Rib;
        private String $_code_IBAN, $_titulaire_nom, $_titulaire_prenom;
        
        public function  __construct(){}

        public function initialiseRib($numCpt, $cGuichet,$cle1, $cIBAN, $tNom, $tPrenom, $identifiantRIB){
            $this->_numero_compte = $numCpt;
            $this->_code_guichet = $cGuichet;
            $this->_cle = $cle1;
            $this->_code_IBAN = $cIBAN;
            $this->_titulaire_nom =$tNom;
            $this->_titulaire_prenom = $tPrenom;
            $this->_identifiant_Rib =$identifiantRIB; 
        }
    }
?>