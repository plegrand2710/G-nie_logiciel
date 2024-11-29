<?php
    class Personne{
        private string $_nom, $_identifiant, $_mdp, $_email, $_numTel;
      
        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            if(!is_string($nomC)){
                throw new InvalidArgumentException("Le nom doit etre une chaine de caractere");
            }
            
            if(!is_string($id)){
                throw new InvalidArgumentException("L'identifiant doit etre une chaine de caractere");
            }

            if(!is_string($mdpC)){
                throw new InvalidArgumentException("Le mot de passe doit etre une chaine de caractere");
            }

            if(!is_string($emailC)){
                throw new InvalidArgumentException("L'email doit etre une chaine de caractere");
            }

            if(!is_string($numtelC)){
                throw new InvalidArgumentException("Le numero de téléphone doit etre une chaine de caractere");
            }
            
            $this->_nom = $nomC;
            $this->_identifiant = $id;
            $this->_mdp = $mdpC;
            $this->_email = $emailC;
            $this->_numTel =$numtelC;
        }

        public function getNom(){
            return $this->_nom;
        }
        public function getId(){
            return $this->_identifiant;
        }
        public function getMdp(){
            return $this->_mdp;
        }
        public function getEmail(){
            return $this->_email;
        }
        public function getNumTel(){
            return $this->_numTel;
        }

        public function setNom($nom1){
            if(!is_string($nom1)){
                throw new InvalidArgumentException("Le nom doit etre une chaine de caractere");
            }
            $this->_nom = $nom1;
        }

        public function setId($id1){
            if(!is_string($id1)){
                throw new InvalidArgumentException("L'identifiant doit etre une chaine de caractere");
            }
            $this->_identifiant = $id1;
        }

        public function setMdp($mdp1){
            if(!is_string($mdp1)){
                throw new InvalidArgumentException("Le mot de passe doit etre une chaine de caractere");
            }
            $this->_mdp = $mdp1;
        }
        public function setEmail($email1){
            if(!is_string($email1)){
                throw new InvalidArgumentException("L'email doit etre une chaine de caractere");
            }
            $this->_email= $email1;
        }

        public function setNumTel($numTel1){
            if(!is_string($numTel1)){
                throw new InvalidArgumentException("Le numero de téléphone doit etre une chaine de caractere");
            }
            $this->_numTel =$numTel1;
         }


        public function verifPersonne($dbConnection){
            $stmt = $dbConnection->prepare("SELECT * FROM Personne WHERE identifiant = ?");
            $stmt->execute([$this->_identifiant]);
            return $stmt->rowCount() > 0;
        }

        public function connexion($id1, $mdp1){
            if ($id1 !=$this->_identifiant || $mdp1 != $this->_mdp){
                return False;
            }
            else{
                return True;
            }
        }

        public function modifierInfoConnexion($id2,$mdp2){
            $this->setId($id2);
            $this->setMdp($mdp2);
        }

    }
?>
        