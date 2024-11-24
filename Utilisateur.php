<?php
    class Utilisateur extends Personne {
        private Rib $_rib; 
        private array $_cotisation ;

        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            parent::__construct($nomC, $id, $mdpC, $emailC,$numtelC);
            $this->_rib = new Rib();
            $this->_cotisation = [];
        }

        public function getRib(){
            return $this->_rib;
        }

        public function verifPayerCotisation(){
            if (!empty($this->_cotisation)) {
                $derniereCotisation = end($this->_cotisation);
        
                if ($derniereCotisation->verifValiditeCotisation()) {
                    echo "La dernière cotisation est encore valide. Aucun paiement nécessaire.";
                    return true;
                }
            }
            else{
                echo "La dernière cotisation n'est plus valide. Paiement nécessaire.";
                return false;
            }
        }

    }
?>