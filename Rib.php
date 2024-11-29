<?php
    class RIB{
        private int $_numero_compte, $_code_guichet, $_cle, $_identifiant_Rib;
        private String $_code_IBAN, $_titulaire_nom, $_titulaire_prenom;
        
        public function  __construct(){}

        public function initialiseRib($numCpt, $cGuichet,$cle1, $cIBAN, $tNom, $tPrenom, $identifiantRIB){
            if(!is_int($numCpt)){
                throw new InvalidArgumentException("Le numero de compte doit etre un int");
            }
            if(!is_int($cGuichet)){
                throw new InvalidArgumentException("Le code guichet doit etre un int");
            }
            if(!is_int($cle1)){
                throw new InvalidArgumentException("La cle doit etre un int");
            }
            if(!is_int($identifiantRIB)){
                throw new InvalidArgumentException("L'identifiant doit etre un int");
            }

            if(!is_string($cIBAN)){
                throw new InvalidArgumentException("Le code IBAN doit etre un String");
            }
            if(!is_string($tNom)){
                throw new InvalidArgumentException("Le nom doit etre un string");
            }
            if(!is_string($tPrenom)){
                throw new InvalidArgumentException("Le prenom doit etre un string");
            }
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