<?php
namespace App;

use PDO;
use PDOException;

class CreneauActivite {

    private int $_id_CreneauActivite;
    private int $_id_Creneau;
    private int $_id_Activite;
    private bool $_disponible;
    

    function __construct(int $ID_CreneauActivite, int $ID_Creneau, int $ID_Activite, bool $disponible) {
        //$this->set_ID_CreneauActivite($ID_CreneauActivite);
        $this->set_ID_Creneau($ID_Creneau);
        $this->set_ID_Activite($ID_Activite);
        $this->set_Disponibilité($disponible);
    }

    //Setter
    public function set_ID_CreneauActivite(int $ID_CreneauActivite): void{
        $this->_id_CreneauActivite = $ID_CreneauActivite;
    } 

    public function set_ID_Creneau(int $ID_Creneau): void{
        $this->_id_Creneau = $ID_Creneau;
    } 

    public function set_ID_Activite(int $ID_Activite): void{
        $this->_id_Activite = $ID_Activite;
    }

    public function set_Disponibilité(bool $disponible): void{
        $this->_disponible = $disponible;
    }

    //Getter
    public function get_ID_CreneauActivite(): int{
        return $this->_id_CreneauActivite;
    }

    public function get_ID_Creneau(): int{
        return $this->_id_Creneau;
    }

    public function get_ID_Activite(): int{
        return $this->_id_Activite;
    }

    public function get_disponibilité(): bool{
        return $this->_disponible;
    }

    //Méthodes
    public function get_numberCreneauActivite(): int {
        try {
            // Connexion à la base de données avec PDO
            $pdo = new PDO("mysql:host=localhost;dbname=bdd;charset=utf8");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
            // Nom de la table dont on veut récupérer le nombre de lignes
            $table = "CreneauActivite";
        
            // Requête pour compter les lignes
            $query = $pdo->prepare("SELECT COUNT(*) AS total FROM $table");
            $query->execute();
        
            // Récupération du résultat
            $result = $query->fetch(PDO::FETCH_ASSOC);
            $nombreCreneauActivite = $result['total'];
        
            return $nombreCreneauActivite + 1;
        } catch (PDOException $e) {
            // Gestion des erreurs
            echo "Erreur : " . $e->getMessage();
            return 0;
        }
    }

    public function matchCreneauActivite(array $liste_creneaux, Activite $activite): void{
        foreach($liste_creneaux as $creneau) {
            if ($creneau->_dureeGlobal == $activite->_duree) {
                $id_creneauActivite = get_numberCreneauActivite();
                $CreneauActivite = new CreneauActivite($id_creneauActivite, $creneau->_id_Creneau, $activite->_id_Activite, false);
            }
        }
    }
    public function ajouterCreneauPourActivite(int $ID_Activite, int $ID_Creneau, PDO $pdo): void {

        $pdo->beginTransaction();

        try{
            $query = "INSERT INTO CreneauActivite VALUES (:idCreneau, :idActivite)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':idCreneau', $ID_Creneau);
            $stmt->bindParam(':idActivite', $ID_Activite);
            $stmt->execute();   

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de l'ajout CreneauACtivite : " . $e->getMessage());
        }

    }

    public function supprimerCreneauActivite(PDO $pdo): void {

        $pdo->beginTransaction();

        try{

            $query = "DELETE FROM CreneauActivite WHERE idCreneau = :id_Creneau";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_Creneau', $this->_id_Creneau);
            $stmt->execute();   

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la suppression CreneauACtivite: " . $e->getMessage());
        }
    }

    public function supprimerToutCreneauActivite(PDO $pdo): void {
        $pdo->beginTransaction();
        try {
            $pdo->beginTransaction();
            $query = "DELETE FROM CreneauActivite";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $this->_tableCreneaux = [];
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la suppression de tous les créneaux : " . $e->getMessage());
        }
    }

}
?>