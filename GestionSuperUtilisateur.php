<?php
    include './connexionBDD.php';

class GestionSuperUtilisateur extends GestionUtilisateur {
    private array $_activites;

    public function __construct($calendrier, $activites = []) {
        parent::__construct($calendrier);

        if(empty($activites) || !is_array($activites)){
            throw new InvalidArgumentException("La liste des activités doit être une instance de liste.");
        }

        foreach ($activites as $activite) {
            if (!$activite instanceof Activite) {
                throw new InvalidArgumentException("Chaque élément de la liste des activités doit être une instance de Activite.");
            }
        }

        $this->_activites = $activites;
    }

    public function creer_activite($nom, $tarif, $duree): void {
        global $pdo;
        if (empty($nom) || !is_string($nom)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne non vide.");
        }

        if ($tarif <= 0 || !is_float($tarif)) {
            throw new InvalidArgumentException("Le tarif doit être un nombre positif.");
        }

        if (empty($duree) || !is_string($duree)) {
            throw new InvalidArgumentException("La durée doit être une chaîne non vide.");
        }

        $nouvelleActivite = new Activite($nom, $tarif, $duree);
        $this->_activites[] = $nouvelleActivite;

        $stmt = $pdo->prepare("
            INSERT INTO Activite (nom, tarif, duree) 
            VALUES (:nom, :tarif, :duree)
        ");
        $stmt->execute([
            ':nom' => $nom,
            ':tarif' => $tarif,
            ':duree' => $duree,
        ]);

        $nouvelleActivite->setId($pdo->lastInsertId());

        $this->getCalendrier()->ajouterGestionCreneauxActivite($nouvelleActivite);
    }

    public function supprimer_activite($activite): void {
        global $pdo;
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }
    
        $index = array_search($activite, $this->_activites, true);
    
        if ($index === false) {
            throw new LogicException("L'activité spécifiée n'existe pas.");
        }
    
        unset($this->_activites[$index]);
        $this->_activites = array_values($this->_activites);

        $stmt = $pdo->prepare("
            DELETE FROM Activite 
            WHERE idActivite = :id
        ");
        $stmt->execute([':id' => $activite->getId()]);
    
        $this->getCalendrier()->supprimerGestionCreneauxActivite($activite);
    }

    public function modification_creneau($nouveauxCreneaux, $activite) {
        global $pdo;
        if(empty($nouveauxCreneaux) || !is_array($nouveauxCreneaux)){
            throw new InvalidArgumentException("La liste des activités doit être une instance de liste.");
        }

        foreach ($nouveauxCreneaux as $creneau) {
            if (!$creneau instanceof Creneau) {
                throw new InvalidArgumentException("Tous les créneaux doivent être des instances de la classe Creneau.");
            }
        }
        
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        $gestionCreneaux = $this->getCalendrier()->trouverGestionCreneauxPourActivite($activite);

        if ($gestionCreneaux === null) {
            throw new LogicException("Aucune gestion de créneaux trouvée pour cette activité.");
        }

        $stmt = $pdo->prepare("
            DELETE FROM Creneau 
            WHERE idActivite = :idActivite
        ");
        $stmt->execute([':idActivite' => $activite->getId()]);

        $stmt = $pdo->prepare("
            INSERT INTO Creneau (idActivite, heureDebut, heureFin) 
            VALUES (:idActivite, :heureDebut, :heureFin)
        ");
        foreach ($nouveauxCreneaux as $creneau) {
            $stmt->execute([
                ':idActivite' => $activite->getId(),
                ':heureDebut' => $creneau->getHeureDebut()->format('H:i:s'),
                ':heureFin' => $creneau->getHeureFin()->format('H:i:s'),
            ]);
        }

        $gestionCreneaux->modifierCreneauActivite($nouveauxCreneaux);
    }

    public function modifier_activite($activite, $nom = null, $tarif = null, $duree = null): void {
        global $pdo;
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }
    
        if ($nom !== null && trim($nom) === "") {
            throw new InvalidArgumentException("Le nom de l'activité ne peut pas être vide.");
        }
    
        if ($tarif !== null && (!is_float($tarif) || $tarif <= 0)) {
            throw new InvalidArgumentException("Le tarif doit être un nombre positif.");
        }
    
        if ($duree !== null && trim($duree) === "") {
            throw new InvalidArgumentException("La durée ne peut pas être vide.");
        }
    
        if ($nom !== null) {
            $activite->setNom($nom);
        }
    
        if ($tarif !== null) {
            $activite->setTarif($tarif);
        }
    
        if ($duree !== null) {
            $activite->setDuree($duree);
        }

        $stmt = $pdo->prepare("
            UPDATE Activite 
            SET nom = :nom, tarif = :tarif, duree = :duree 
            WHERE idActivite = :id
        ");
        $stmt->execute([
            ':nom' => $activite->getNom(),
            ':tarif' => $activite->getTarif(),
            ':duree' => $activite->getDuree(),
            ':id' => $activite->getId(),
        ]);
    }

    public function getActivites(): array {
        return $this->_activites;
    }

    public function afficherToutesReservations(): array {
        $reservationsConfirmees = [];
        foreach ($this->getReservations() as $reservation) {
            if ($reservation->getStatut() === 'confirmée') {
                $reservationsConfirmees[] = $reservation;
            }
        }
        return $reservationsConfirmees;
    }
}