<?php
    class Moderateur extends Personne{
    
        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            parent::__construct($nomC, $id, $mdpC, $emailC,$numtelC);
        }

        public function supprimerutilisateur(){
            //Supprime un utilisateur
            //Donc une Personne?
        }
    }
?>