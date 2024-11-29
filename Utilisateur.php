<?php
    //Cette classe va hériter de la classe Personne,un Utilisateur est une Personne.
    class Utilisateur extends Personne {
        private Rib $_rib; 
        private  array $_cotisations ;

        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            parent::__construct($nomC, $id, $mdpC, $emailC,$numtelC);
            $this->_rib = new Rib();
            $this->_cotisations = [];
        }

        public function getRib(){
            return $this->_rib;
        }

        public function getCotisations(){
            return $this->_cotisations;
        }

        public function addCotisation( $cotisation){
            if( !$cotisation instanceof Cotisation){
                throw new \InvalidArgumentException("la cotisation à ajouter doit etre de une Cotisation");
            }
            $this->_cotisations[] = $cotisation;

        }

        public function verifierUtilisateur($dbConnection){
            $stmt = $dbConnection->prepare("SELECT * FROM Utilisateur WHERE identifiant = ?");
            $stmt->execute([$this->getId()]);
            if($stmt->rowCount() > 0 && $this->VerifPayerCotisation());
        }


        public function VerifPayerCotisation(){
            if (!empty($this->_cotisations)) {
                $derniereCotisation = end($this->_cotisations);
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