<?php
    abstract class Personne{
        private string $_nom, $_identifiant, $_mdp, $_email, $_numTel;

        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            $this->_nom = $nomC;
            $this->_identifiant = $id;
            $this->_mdp = $mdpC;
            $this->_email = $emailC;
            $this->_numTel =$numtelC;
        }

        public function getNom(){return $this->_nom;}
        public function getId(){return $this->_identifiant;}
        public function getMdp(){return $this->_mdp;}
        public function getEmail(){return $this->_email;}
        public function getNumTel(){return $this->_numTel;}

        public function setNom($nom1){$this->_nom = $nom1;return;}
        public function setId($id1){
            $this->_identifiant = $id1;
        }
        public function setMdp($mdp1){$this->_mdp = $mdp1;return;}
        public function setEmail($email1){$this->_email= $email1;return;}
        public function setNumTel($numTel1){$this->_numTel =$numTel1 ;}


        public function connexion($id1, $mdp1){
            if ($id1 !=$this->_identifiant || $mdp1 != $this->_mdp){
                return False;
            }
            else{
                return True;
            }
        }

        //voir s'il est utile, peut-etre avoir seulement modifier mot de passe ou identifiant,
        //verifier s'il ne modifie pas par le meme, et le faire taper deux fois le mot de passe changer
        public function modifierInfoConnexion($id2,$mdp2){
            $this->setId($id2);
            $this->setMdp($mdp2);
        }

    }
?>
        