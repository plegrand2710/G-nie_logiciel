<?php
    class Moderateur extends Personne{
    
        public function __construct($nomC, $id, $mdpC, $emailC,$numtelC){
            parent::__construct($nomC, $id, $mdpC, $emailC,$numtelC);
        }

        public function supprimerutilisateur($id, $dbConnection) {
            try {
                $query = "DELETE FROM Utilisateur WHERE id = :id";
                $stmt = $dbConnection->prepare($query);
        
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
                if ($stmt->execute()) {
                    echo "Utilisateur avec l'ID $id a été supprimé avec succès.";
                    return true;
                } else {
                    echo "Erreur lors de la suppression de l'utilisateur avec l'ID $id.";
                    return false;
                }
            } catch (PDOException $e) {
                echo "Erreur : " . $e->getMessage();
                return false;
            }
        }
        
    }
?>