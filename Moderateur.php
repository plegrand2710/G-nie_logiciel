<?php
    //Cette classe va hériter de la classe Personne,un Moderateur est une Personne avec d'autres fonction en plus.
    class Moderateur extends Personne{
    
        public function __construct(){
            parent::construct();
        }

        public function supprimerutilisateur(){
            //Supprime un utilisateur
            //Donc une Personne?
        }
    }
?>