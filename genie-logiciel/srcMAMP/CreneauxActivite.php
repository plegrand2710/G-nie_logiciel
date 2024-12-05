<?php
require_once __DIR__ . '/BaseDeDonnees.php';

class CreneauxActivite {
    private int $_id_CreneauActivite;
    private int $_id_Creneau;
    private int $_id_Activite;
    private $_pdo;


    function __construct($id_CreneauActivite, $id_Creneau, $id_Activite) {
        $this->set_ID_CreneauActivite($id_CreneauActivite);
        $this->set_ID_Creneau($id_Creneau);
        $this->set_ID_Activite($id_Activite);
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
    }

    public function set_ID_CreneauActivite($id_CreneauActivite): void {
        if (!is_int($id_CreneauActivite) || $id_CreneauActivite <= 0) {
            throw new InvalidArgumentException("L'ID du créneau-activité doit être un entier positif.");
        }
        $this->_id_CreneauActivite = $id_CreneauActivite;
    }
    
    public function set_ID_Creneau($id_Creneau): void {
        if (!is_int($id_Creneau) || $id_Creneau <= 0) {
            throw new InvalidArgumentException("L'ID du créneau doit être un entier positif.");
        }
        $this->_id_Creneau = $id_Creneau;
    }
    
    public function set_ID_Activite($id_Activite): void {
        if (!is_int($id_Activite) || $id_Activite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }
        $this->_id_Activite = $id_Activite;
    }
    
    public function get_ID_CreneauActivite(): int {
        return $this->_id_CreneauActivite;
    }

    public function get_ID_Creneau(): int {
        return $this->_id_Creneau;
    }

    public function get_ID_Activite(): int {
        return $this->_id_Activite;
    }

    public function genererCreneauxPourActivite(int $idActivite): void {
        try {
            $stmt = $this->_pdo->prepare("SELECT duree FROM Activite WHERE idActivite = :idActivite");
            $stmt->execute([':idActivite' => $idActivite]);
            $activite = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$activite) {
                throw new Exception("Activité introuvable.");
            }
            $dureeActivite = new DateInterval('PT' . $activite['duree']);

            $stmt = $this->_pdo->query("SELECT horaire_ouverture, horaire_fermeture FROM Calendrier LIMIT 1");
            $calendrier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$calendrier) {
                throw new Exception("Horaires d'ouverture introuvables.");
            }

            $heureOuverture = new DateTime($calendrier['horaire_ouverture']);
            $heureFermeture = new DateTime($calendrier['horaire_fermeture']);

            $heureActuelle = clone $heureOuverture;
            while ($heureActuelle < $heureFermeture) {
                $heureFin = (clone $heureActuelle)->add($dureeActivite);

                if ($heureFin > $heureFermeture) {
                    break;
                }

                $stmt = $this->_pdo->prepare("
                    SELECT idCreneau FROM Creneau WHERE heure_debut = :heureDebut AND heure_fin = :heureFin
                ");
                $stmt->execute([
                    ':heureDebut' => $heureActuelle->format('H:i:s'),
                    ':heureFin' => $heureFin->format('H:i:s'),
                ]);
                $creneau = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$creneau) {
                    $stmt = $this->_pdo->prepare("
                        INSERT INTO Creneau (heure_debut, heure_fin, duree) 
                        VALUES (:heureDebut, :heureFin, :duree)
                    ");
                    $stmt->execute([
                        ':heureDebut' => $heureActuelle->format('H:i:s'),
                        ':heureFin' => $heureFin->format('H:i:s'),
                        ':duree' => $activite['duree'],
                    ]);
                    $idCreneau = $this->_pdo->lastInsertId();
                } else {
                    $idCreneau = $creneau['idCreneau'];
                }

                $stmt = $this->_pdo->prepare("
                    INSERT IGNORE INTO CreneauActivite (idCreneau, idActivite, disponible) 
                    VALUES (:idCreneau, :idActivite, 1)
                ");
                $stmt->execute([
                    ':idCreneau' => $idCreneau,
                    ':idActivite' => $idActivite,
                ]);

                $heureActuelle = $heureFin;
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la génération des créneaux : " . $e->getMessage());
        }
    }
}