<?php
    //Cette classe va hériter de la classe Personne,un Utilisateur est une Personne.
    class Utilisateur extends Personne {
        private Rib $_rib; 
        private  array $_cotisation ;

        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            parent::construct($nomC, $id, $mdpC, $emailC,$numtelC);
            $this->_rib = new Rib();
            $this->$_cotisation = [];
        }


        public function verifierUtilisateur(){
            $stmt = $dbConnection->prepare("SELECT * FROM Utilisateur WHERE identifiant = ?");
            $stmt->execute([$this->getId()]);
            if($stmt->rowCount() > 0 && $this->VerifPayerCotisation());
        }


        public function VerifPayerCotisation(){
            if (!empty($this->_cotisation)) {
                // Récupérer la dernière cotisation
                $derniereCotisation = end($this->_cotisation);
        
                // Vérifier si la dernière cotisation est encore valide
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