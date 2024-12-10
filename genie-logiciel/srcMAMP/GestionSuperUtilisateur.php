<?php
class GestionSuperUtilisateur extends GestionUtilisateur {

    private array $_activites;
    
    public function __construct($calendrier, array $activites = []) {
        parent::__construct($calendrier);
    
        if (!is_array($activites)) {
            throw new InvalidArgumentException("La liste des activités doit être une instance de tableau d'activités.");
        }
    
        foreach ($activites as $activite) {
            if (!$activite instanceof Activite) {
                throw new InvalidArgumentException("Chaque élément de la liste des activités doit être une instance de Activite.");
            }
        }
    
        $this->_activites = $activites;
    }

    public function creerActivite($nom, $tarif, $duree): void {
        try {
            $activite = new Activite($nom, $tarif, $duree);
            $activite->ajouterActiviteBDD();
            $this->_activites[] = $activite;

            $this->genererCreneauxPourActivite((int)$activite->getId());
            $stmt = $this->getPDO()->prepare("
                SELECT *
                FROM Creneau
            ");
        $stmt->execute();
        print_r($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la création de l'activité : " . $e->getMessage());
        }
    }

    public function getHeuresOuvertureFermetureSalle($idCalendrier) {
        if (!is_int($idCalendrier) || $idCalendrier <= 0) {
            throw new InvalidArgumentException("L'ID du calendrier doit être un entier positif.");
        }
    
        $stmt = $this->getPDO()->prepare("
            SELECT horaire_ouverture, horaire_fermeture 
            FROM Calendrier 
            WHERE idCalendrier = :idCalendrier
        ");
    
        $stmt->execute([':idCalendrier' => $idCalendrier]);
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            $heureOuverture = new DateTime($result['horaire_ouverture']);
            $heureFermeture = new DateTime($result['horaire_fermeture']);
    
            return [$heureOuverture, $heureFermeture];
        } else {
            throw new RuntimeException("Aucune donnée trouvée pour le calendrier ID: $idCalendrier.");
        }
    }

    public function genererCreneauxPourActivite($idActivite, $dureeActivite = null): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activite doit être un entier positif.");
        }
    
        try {
            $activite = $this->getActivite($idActivite);
    
            if ($dureeActivite === null) {
                $dureeActivite = $this->getDureeActivite($idActivite);
            }
    
            if ($this->isDureeInvalid($dureeActivite)) {
                throw new Exception("La durée de l'activité doit être comprise entre 30 minutes et 5 heures.");
            }
    
            list($heureOuverture, $heureFermeture) = $this->getHeuresOuvertureFermetureSalle((int)$this->getIdCalendrier());
    
            $heureActuelle = clone $heureOuverture;
    
            while ($heureActuelle < $heureFermeture) {
                $heureFin = (clone $heureActuelle)->add($dureeActivite);
    
                if ($heureFin > $heureFermeture) {
                    break;
                }
    
                $idCreneau = $this->getCreneauId($heureActuelle, $heureFin, $idActivite);
    
                // Vérifier si idCreneau est valide
                if ($idCreneau <= 0) {
                    throw new RuntimeException("Erreur : ID du créneau invalide.");
                }
    
                $this->ajouterCreneauxActivite($idCreneau, $idActivite);
    
                $heureActuelle = $heureFin;
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la génération des créneaux pour l'activité (ID: $idActivite) : " . $e->getMessage());
        }
    }
    
    
    private function isDureeInvalid(DateInterval $dureeActivite): bool{
        $minutes = $dureeActivite->h * 60 + $dureeActivite->i;
        return $minutes < 30 || $minutes > 300; 
    }

    public function ajouterCreneauxActivite(int $idCreneau, int $idActivite): void {
        $creneauxActivite = new CreneauxActivite($idCreneau, $idActivite);
        $creneauxActivite->ajouterCreneauxActivite();
        try {
            $stmt = $this->getPDO()->prepare("
                INSERT INTO CreneauxActivite (idCreneau, idActivite, idCalendrier) 
                VALUES (:idCreneau, :idActivite, :idCalendrier)
            ");
            $stmt->execute([
                ':idCreneau' => $idCreneau,
                ':idActivite' => $idActivite,
                ':idCalendrier' => $this->getIdCalendrier(),
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de l'ajout du créneau activité : " . $e->getMessage());
        }
    }

    private function getCreneauId(DateTime $heureDebut, DateTime $heureFin, int $idActivite): int {
        try {
            // Validation des paramètres
            $format = 'H:i:s';
            $heureDebutStr = $heureDebut->format($format);
            $heureFinStr = $heureFin->format($format);
    
            if (!$heureDebutStr || !$heureFinStr) {
                throw new InvalidArgumentException("Les heures de début ou de fin sont invalides.");
            }
    
            // Recherche du créneau existant
            $stmt = $this->getPDO()->prepare("
                SELECT idCreneau 
                FROM Creneau 
                WHERE heure_debut = :heureDebut AND heure_fin = :heureFin
            ");
            $stmt->execute([
                ':heureDebut' => $heureDebutStr,
                ':heureFin' => $heureFinStr,
            ]);
    
            $creneau = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($creneau) {
                // Créneau trouvé
                return (int) $creneau['idCreneau'];
            } else {
                // Aucun créneau trouvé, insertion d'un nouveau
                $duree = $this->getDureeActivite($idActivite)->format('%H:%I:%S');
                if (!$duree) {
                    throw new RuntimeException("La durée de l'activité est invalide.");
                }
    
                $stmt = $this->getPDO()->prepare("
                    INSERT INTO Creneau (heure_debut, heure_fin, duree) 
                    VALUES (:heureDebut, :heureFin, :duree)
                ");
                $stmt->execute([
                    ':heureDebut' => $heureDebutStr,
                    ':heureFin' => $heureFinStr,
                    ':duree' => $duree,
                ]);
    
                $idCreneau = (int) $this->getPDO()->lastInsertId();
    
                // Vérifier que l'ID est bien valide
                if ($idCreneau <= 0) {
                    throw new RuntimeException("L'ID du créneau généré est invalide.");
                }
    
                return $idCreneau;
            }
        } catch (PDOException $e) {
            // Gestion des erreurs SQL
            error_log("Erreur PDO : " . $e->getMessage());
            throw new RuntimeException("Erreur lors de l'accès à la base de données.");
        } catch (Exception $e) {
            // Gestion des erreurs générales
            error_log("Erreur : " . $e->getMessage());
            throw new RuntimeException("Une erreur est survenue : " . $e->getMessage());
        }
    }
    

    private function getDureeActivite(int $idActivite): DateInterval {
        $stmt = $this->getPDO()->prepare("SELECT duree FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        $activite = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$activite) {
            throw new Exception("Activité introuvable.");
        }
    
        $duree = $activite['duree'];

        list($hours, $minutes, $seconds) = explode(":", $duree);

        return new DateInterval('PT' . $hours . 'H' . $minutes . 'M' . $seconds . 'S');

    }
    private function verifierCreneauExistant($heureDebut, $heureFin) {
        if(!$heureDebut instanceof DateTime || !$heureFin instanceof DateTime){
            throw new InvalidArgumentException("Le format des horaires n'est pas correcte.");
        }
        $heureDebutStr = $heureDebut->format('H:i:s');
        $heureFinStr = $heureFin->format('H:i:s');

        $stmt = $this->getPDO()->prepare("
            SELECT COUNT(*) 
            FROM Creneau
            Where heure_debut = :heureDebut 
            AND heure_fin = :heureFin
        ");
        $stmt->execute([
            ':heureDebut' => $heureDebutStr,
            ':heureFin' => $heureFinStr,
        ]);
        $nb = $stmt->fetchColumn();
        gettype($nb);
        echo "nb = " .$nb;
        return $nb;
    }

    public function getActivite($idActivite) {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activite doit être un entier positif.");
        }
        $stmt = $this->getPDO()->prepare("SELECT * FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        $activiteData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$activiteData) {
            throw new RuntimeException("Aucune activité trouvée pour l'ID de gestion $idActivite.");
        }
    
        $activite = new Activite(
            $activiteData['nom'],
            $activiteData['tarif'],
            $activiteData['duree']
        );

        $activite->setId($idActivite);

        return $activite;
    }


    private function getCreneau($idCreneau): Creneau {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du creneau doit être un entier positif.");
        }
        $stmt = $this->getPDO()->prepare("SELECT idCreneau, heure_debut, heure_fin FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $idCreneau]);
        $creneauData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($creneauData) {
            $creneau = new Creneau($creneauData['heure_debut'], $creneauData['heure_fin']);
            $creneau->setId($creneauData['idCreneau']);
            return $creneau;
        } else {
            throw new RuntimeException("Creneau avec l'ID {$idCreneau} introuvable.");
        }
    }

    public function verifierEtRegenererCreneaux($idActivite): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activite doit être un entier positif.");
        }
        try {
            $activite = $this->getActivite($idActivite);
            $dureeActivite = $activite->getDuree();
    
            $stmt = $this->getPDO()->prepare("
                SELECT C.idCreneau, C.heure_debut, C.heure_fin
                FROM Creneau AS C
                INNER JOIN CreneauxActivite AS CA ON CA.idCreneau = C.idCreneau
                WHERE CA.idActivite = :idActivite
            ");
            $stmt->execute([':idActivite' => $idActivite]);
            $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($creneaux as $creneau) {
                $heureDebut = new DateTime($creneau['heure_debut']);
                $heureFin = new DateTime($creneau['heure_fin']);
                $dureeCreneau = $heureDebut->diff($heureFin);
    
                if ($dureeCreneau->h != $dureeActivite->h || $dureeCreneau->i != $dureeActivite->i) {
                    $this->supprimerCreneauxActivite($creneau['idCreneau']);
                    
                    
                    $this->genererCreneauxPourActivite($idActivite, $dureeActivite);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification et génération des créneaux pour l'activité (ID: $idActivite) : " . $e->getMessage());
        }
    }

    public function supprimerCreneauxActivite($idCreneau): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du creneau doit être un entier positif.");
        }
        try {
            $stmt = $this->getPDO()->prepare("DELETE FROM CreneauxActivite WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $idCreneau]);
    
            $stmt = $this->getPDO()->prepare("SELECT COUNT(*) FROM CreneauxActivite WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $idCreneau]);
            $count = $stmt->fetchColumn();
    
            if ($count == 0) {
                $stmt = $this->getPDO()->prepare("DELETE FROM Creneau WHERE idCreneau = :idCreneau");
                $stmt->execute([':idCreneau' => $idCreneau]);
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du créneau activité : " . $e->getMessage());
        }
    }

    public function getActivites(): array {
        return $this->_activites;
    }

    public function afficherToutesReservations(): array {
        try {
            $stmt = $this->getPDO()->prepare("
                SELECT * FROM Reservation WHERE statut = 'confirmée'
            ");
            $stmt->execute();
            $reservationsConfirmees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return $reservationsConfirmees;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des réservations confirmées : " . $e->getMessage());
        }
    }

    public function afficherToutesPersonnes(): array {
        try {
            $stmt = $this->getPDO()->prepare("SELECT * FROM Personne");
            $stmt->execute();
            $personnes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return $personnes;
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des personnes : " . $e->getMessage());
        }
    }

    public function supprimerActivite($idActivite): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activite doit être un entier positif.");
        }
        try {
            $activite = $this->getActivite($idActivite);

            $this->supprimerCreneauxPourActivite($idActivite);
            $activite->supprimerActiviteBDD();

            $this->_activites = array_filter($this->_activites, function($a) use ($idActivite) {
                return $a->getId() !== $idActivite;
            });
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la suppression de l'activité : " . $e->getMessage());
        }
    }

    private function supprimerCreneauxPourActivite($idActivite): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activite doit être un entier positif.");
        }
        try {
            $stmt = $this->getPDO()->prepare("
                SELECT idCreneau FROM CreneauxActivite WHERE idActivite = :idActivite
            ");
            $stmt->execute([':idActivite' => $idActivite]);
            $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($creneaux as $creneau) {
                $this->supprimerCreneauxActivite($creneau['idCreneau']);
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression des créneaux pour l'activité : " . $e->getMessage());
        }
    }

    public function modifierActivite($idActivite, $nom = null, $tarif = null, $duree = null): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID du activite doit être un entier positif.");
        }
        try {
            $activite = $this->getActivite($idActivite);
   
            if ($nom) $activite->setNom($nom);
            if ($tarif) $activite->setTarif($tarif);
            if ($duree) $activite->setDuree($duree);
   
            $activite->mettreAJourActiviteBDD();
            $this->verifierEtRegenererCreneaux($idActivite);
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la modification de l'activité : " . $e->getMessage());
        }
    }

    

    public function modifierCreneau($idCreneau, $nouvelleHeureDebut, $nouvelleHeureFin): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du creneau doit être un entier positif.");
        }
        if(!$nouvelleHeureDebut instanceof DateTime || !$nouvelleHeureFin instanceof DateTime){
            throw new InvalidArgumentException("Le format des horaires n'est pas correcte.");
        }
        try {
            $creneau = $this->getCreneau($idCreneau);
            $creneau->setHeureDebut($nouvelleHeureDebut);
            $creneau->setHeureFin($nouvelleHeureFin);
            $creneau->modifierCreneauBDD();

            foreach ($this->_activites as $activite) {
                $this->verifierEtRegenererCreneaux($activite->getId());
            }
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la modification du créneau : " . $e->getMessage());
        }
    }

    public function supprimerCreneau($idCreneau): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du creneau doit être un entier positif.");
        }
        try {
            $creneau = $this->getCreneau($idCreneau);
            $this->supprimerCreneauxActivite($creneau->getId());
            $creneau->supprimerCreneauBDD();

            foreach ($this->_activites as $activite) {
                $this->verifierEtRegenererCreneaux($activite->getId());
            }
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la suppression du créneau : " . $e->getMessage());
        }
    }

    public function libererCreneauLorsSuppressionReservation($idCreneau): void {
        if (!is_int($idCreneau) || $idCreneau <= 0) {
            throw new InvalidArgumentException("L'ID du créneau doit être un entier positif.");
        }
        try {
            $stmt = $this->getPDO()->prepare("DELETE FROM Reservation WHERE idCreneau = :idCreneau");
            $stmt->execute([':idCreneau' => $idCreneau]);

            $this->supprimerCreneauxActivite($idCreneau);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la suppression du créneau : " . $e->getMessage());
        }
    }

    public function getCreneauxPourActivite(int $idActivite): array {
        if ($idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }
    
        $this->getActivite($idActivite);
        try {
            $stmt = $this->getPDO()->prepare("
                SELECT C.idCreneau, C.heure_debut, C.heure_fin
                FROM Creneau AS C
                INNER JOIN CreneauxActivite AS CA ON CA.idCreneau = C.idCreneau
                WHERE CA.idActivite = :idActivite
            ");
            $stmt->execute([':idActivite' => $idActivite]);
    
            $creneauxData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $creneaux = [];
            foreach ($creneauxData as $data) {
                $creneau = new Creneau($data['heure_debut'], $data['heure_fin']);
                $creneau->setId($data['idCreneau']);
                $creneaux[] = $creneau;
            }
    
            return $creneaux;
    
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des créneaux pour l'activité (ID: $idActivite) : " . $e->getMessage());
        }
    }
}
