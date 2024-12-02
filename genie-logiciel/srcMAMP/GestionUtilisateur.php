<?php

class GestionUtilisateur {
    private $_calendrier;
    private $_paiement;
    private $_remboursement;
    private static $_penalite = 50;
    private $_pdo;
    private static $_RIBEntreprise;

    public function __construct($calendrier) {
        if (!($calendrier instanceof Calendrier)) {
            throw new InvalidArgumentException("Le calendrier doit être une instance de Calendrier.");
        }

        $this->_calendrier = $calendrier;
        $this->_paiement = null;
        $this->_remboursement = null;
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
    }

    public function getCalendrier() {
        return $this->_calendrier;
    }

    public function setCalendrier($calendrier) {
        if (!($calendrier instanceof Calendrier)) {
            throw new InvalidArgumentException("Le calendrier doit être une instance de Calendrier.");
        }
        $this->_calendrier = $calendrier;
    }

    public function getPaiement() {
        return $this->_paiement;
    }

    public function setPaiement($paiement) {
        if (!($paiement instanceof Paiement)) {
            throw new InvalidArgumentException("Le paiement doit être une instance de Paiement.");
        }
        $this->_paiement = $paiement;
    }

    public static function getPenalite() {
        return self::$_penalite;
    }

    public static function setPenalite($penalite) {
        if (!is_float($penalite) && !is_int($penalite)) {
            throw new InvalidArgumentException("La pénalité doit être un nombre.");
        }
        self::$_penalite = (float) $penalite;
    }

    public function getRemboursement() {
        return $this->_remboursement;
    }

    public function setRemboursement($remboursement) {
        if (!($remboursement instanceof Remboursement)) {
            throw new InvalidArgumentException("Le remboursement doit être une instance de Remboursement.");
        }
        $this->_remboursement = $remboursement;
    }

    public function reserver($creneau, $activite, $personne) {
        if (!($creneau instanceof Creneau)) {
            throw new InvalidArgumentException("Le créneau doit être une instance de Creneau.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        if (!($personne instanceof Personne)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if ($personne instanceof Utilisateur && !$personne->verifPayerCotisation()) {
            throw new LogicException("La personne doit avoir une cotisation valide pour réserver.");
        }

        $gestionCreneaux = $this->_calendrier->trouverGestionCreneauxPourActivite($activite);
        if ($gestionCreneaux === null) {
            throw new LogicException("Aucune gestion de créneaux trouvée pour cette activité.");
        }

        if (!$gestionCreneaux->verifierDisponibilite($creneau)) {
            throw new LogicException("Le créneau demandé n'est pas disponible.");
        }

        $reservation = new Reservation($creneau, $activite, $personne);

        if ($personne instanceof Utilisateur) {
            $this->paiementActivite($personne, $activite);
        }

        if (!$reservation->confirmerReservation()) {
            throw new LogicException("La réservation n'a pas pu être confirmée.");
        }
        
        $this->ajouterDansLaBase($reservation);
        $creneau->reserverCreneau();
        return true;
    }

    private function ajouterDansLaBase($reservation) {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Reservation (statut, date_reservation, idPersonne, idCreneau, idActivite) 
            VALUES (:statut, NOW(), :idPersonne, :idCreneau, :idActivite)
        ");
        $stmt->execute([
            ':statut' => $reservation->getStatut(),
            ':idPersonne' => $reservation->getPersonne()->getId(),
            ':idCreneau' => $reservation->getCreneau()->getId(),
            ':idActivite' => $reservation->getActivite()->getId()
        ]);

        $reservationId = $this->_pdo->lastInsertId();
        $reservation->setId($reservationId);
    }

    public function paiementActivite($personne, $activite) {
        if (!($personne instanceof Utilisateur)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        $montant = $activite->getTarif();

        $stmt = $this->_pdo->prepare("
                SELECT idRIB 
                FROM RIB 
                WHERE idUtilisateur = :idUtilisateur
                LIMIT 1
            ");
            $stmt->execute([':idUtilisateur' => $personne->getId()]);
            $idRIBSource = $stmt->fetchColumn();

            if (!$idRIBSource) {
                throw new RuntimeException("RIB source introuvable pour l'utilisateur.");
            }

            $stmt = $this->_pdo->prepare("
                SELECT idRIBEntreprise 
                FROM RIBEntreprise
                LIMIT 1
            ");
            $stmt->execute();
            $idRIBDestinataire = $stmt->fetchColumn();

            if (!$idRIBDestinataire) {
                throw new RuntimeException("RIB destinataire (entreprise) introuvable.");
            }

        $this->_paiement = new PaiementVirement($montant, new DateTime(), $idRIBSource, $idRIBDestinataire);

        if (!$this->_paiement->effectuerPaiement()) {
            throw new LogicException("Le paiement a échoué.");
        }
    }

    public function annulerReservation($reservation) {
        if (!($reservation instanceof Reservation)) {
            throw new InvalidArgumentException("La réservation doit être une instance de Reservation.");
        }

        if ($reservation->getStatut() !== 'confirmée' && $reservation->getStatut() !== 'en attente') {
            throw new LogicException("Seules les réservations confirmées ou en attente peuvent être annulées.");
        }

        $creneau = $reservation->getCreneau();
        if (!$creneau instanceof Creneau) {
            throw new LogicException("Le créneau associé à la réservation est introuvable.");
        }

        $reservation->annulerReservation();
        $creneau->libererCreneau();

        if ($reservation->getStatut() == 'confirmée' && $reservation->getPersonne() instanceof Utilisateur) {
            $penalite = $this->calculerPenalite($reservation);
            $this->remboursementActivite($reservation->getPersonne(), $reservation->getActivite(), $penalite);
        } 

        $this->supprimerDansLaBase($reservation);

    }

    private function calculerPenalite(Reservation $reservation): float {
        $heureActuelle = new DateTime();
        $creneauHeure = $reservation->getCreneau()->getHeureDebut();
    
        if (!$creneauHeure instanceof DateTimeInterface) {
            throw new LogicException("L'heure de début du créneau est introuvable ou invalide.");
        }
    
        $interval = $heureActuelle->diff($creneauHeure);
        $heuresAvant = $interval->h + ($interval->days * 24);
    
        return $heuresAvant < 24 ? self::$_penalite : 0;
    }

    public function remboursementActivite($personne, $activite, $penalite): void {
        if (!($personne instanceof Utilisateur)) {
            throw new InvalidArgumentException("La personne doit être une instance de Utilisateur.");
        }

        if (!($activite instanceof Activite)) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        if (!is_float($penalite) && !is_int($penalite)) {
            throw new InvalidArgumentException("La pénalité doit être un nombre.");
        }

        $this->_remboursement = new Remboursement(new DateTime(), $activite->getTarif(), $penalite);

        if (!$this->_remboursement->effectuerPaiement()) {
            throw new LogicException("Le remboursement a échoué.");
        }
    }

    public function afficherCreneauxParActiviteParPersonne($idPersonne, $idActivite): array {
        $stmt = $this->_pdo->prepare("
            SELECT Creneau.idCreneau, Creneau.date, Creneau.heure_debut, Creneau.heure_fin
            FROM Reservation, Creneau
            WHERE Reservation.idCreneau = Creneau.idCreneau
              AND Reservation.idPersonne = :idPersonne
              AND Reservation.idActivite = :idActivite
        ");
        $stmt->execute([
            ':idPersonne' => $idPersonne,
            ':idActivite' => $idActivite
        ]);

        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $creneaux = [];
        foreach ($resultats as $row) {
            $creneaux[] = new Creneau($row['date'], $row['heure_debut'], $row['heure_fin']);
        }
    
        return $creneaux;
    }

    public function afficherCreneauxDisponiblesParActivite($idActivite) {

        $stmt = $this->_pdo->prepare("
            SELECT Creneau.* 
            FROM Creneau 
            JOIN gestionCreneauxActivite ON Creneau.idCreneau = gestionCreneauxActivite.idCreneau
            WHERE gestionCreneauxActivite.idActivite = :idActivite AND Creneau.reserve = 0
        ");
        $stmt->execute([':idActivite' => $idActivite]);

        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $creneaux = [];

        foreach ($resultats as $data) {
            $creneaux[] = new Creneau($data['date'], $data['heure_debut'], $data['heure_fin']);
        }

        return $creneaux;
    }

    private function supprimerDansLaBase($reservation) {
        $stmt = $this->_pdo->prepare("
            DELETE FROM Reservation WHERE idReservation = :idReservation
        ");
        $stmt->execute([':idReservation' => $reservation->getId()]);
    }

    public function afficherReservationsParUtilisateur($idPersonne): array {

        $stmt = $this->_pdo->prepare("
            SELECT Reservation.*, 
                   Creneau.date AS creneau_date, Creneau.heure_debut, Creneau.heure_fin, 
                   Activite.nom AS activite_nom, Activite.tarif, Activite.duree
            FROM Reservation
            INNER JOIN Creneau ON Reservation.idCreneau = Creneau.idCreneau
            INNER JOIN Activite ON Reservation.idActivite = Activite.idActivite
            WHERE Reservation.idPersonne = :idPersonne
        ");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        $personneStmt = $this->_pdo->prepare("
            SELECT * FROM Personne WHERE idPersonne = :idPersonne
        ");
        $personneStmt->execute([':idPersonne' => $idPersonne]);
        $personneData = $personneStmt->fetch(PDO::FETCH_ASSOC);

        if (!$personneData) {
            throw new RuntimeException("Personne avec l'ID $idPersonne introuvable.");
        }

        if (!filter_var($personneData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email extrait de la base de données n'est pas valide : " . $personneData['email']);
        }
        
        $personne = null;
        if ($personneData['type'] === 'Utilisateur') {
            $personne = new Utilisateur(
                $personneData['nom'],
                $personneData['identifiant'],
                $personneData['mdp'],
                $personneData['email'],
                $personneData['numTel']
            );
        } elseif ($personneData['type'] === 'Moderateur') {
            $personne = new Moderateur(
                $personneData['nom'],
                $personneData['identifiant'],
                $personneData['mdp'],
                $personneData['email'],
                $personneData['numTel']
            );
        } else {
            throw new LogicException("Type de personne inconnu : " . $personneData['type']);
        }

        $reservations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            error_log("erreur tarif : " . gettype($row['tarif']));
            $creneau = new Creneau($row['creneau_date'], $row['heure_debut'], $row['heure_fin']);
            $activite = new Activite($row['activite_nom'], $row['tarif'], $row['duree']);
            $reservation = new Reservation($creneau, $activite, $personne);
            $reservation->setStatut($row['statut']);
            $reservations[] = $reservation;
        }
    
        return $reservations;
    }
    
    public function afficherTousLesCreneauxPourUtilisateur($idPersonne): array {

        $stmt = $this->_pdo->prepare("
            SELECT Creneau.idCreneau, Creneau.date, Creneau.heure_debut, Creneau.heure_fin, Creneau.reserve
            FROM Reservation, Creneau
            WHERE Reservation.idCreneau = Creneau.idCreneau
              AND Reservation.idPersonne = :idPersonne
        ");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $creneaux = [];
        foreach ($resultats as $data) {
            $creneaux[] = new Creneau(
                $data['date'],
                $data['heure_debut'],
                $data['heure_fin']
            );
        }

        return $creneaux;
    }

    public function afficherReservationsUtilisateurParActivite($idPersonne, $idActivite): array {
        $stmt = $this->_pdo->prepare("
            SELECT Reservation.idReservation, Reservation.statut, Reservation.date_reservation, 
                   Creneau.date AS creneau_date, Creneau.heure_debut, Creneau.heure_fin, 
                   Activite.nom AS activite_nom, Activite.tarif AS activite_tarif
            FROM Reservation, Creneau, Activite
            WHERE Reservation.idCreneau = Creneau.idCreneau
              AND Reservation.idActivite = Activite.idActivite
              AND Reservation.idPersonne = :idPersonne
              AND Activite.idActivite = :idActivite
        ");
        $stmt->execute([
            ':idPersonne' => $idPersonne,
            ':idActivite' => $idActivite
        ]);
    
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $personneStmt = $this->_pdo->prepare("
            SELECT * FROM Personne WHERE idPersonne = :idPersonne
        ");
        $personneStmt->execute([':idPersonne' => $idPersonne]);
        $personneData = $personneStmt->fetch(PDO::FETCH_ASSOC);

        if (!$personneData) {
            throw new RuntimeException("Personne avec l'ID $idPersonne introuvable.");
        }

        $personne = new Utilisateur(
            $personneData['nom'],
            $personneData['identifiant'],
            $personneData['email'],
            $personneData['numTel'],
            $personneData['type']
        );
        $personne->setId($idPersonne);

        $reservations = [];
        foreach ($resultats as $data) {
            $creneau = new Creneau(
                $data['creneau_date'],
                $data['heure_debut'],
                $data['heure_fin']
            );

            $activite = new Activite(
                $data['activite_nom'],
                $data['tarif'],
                $data['duree']
            );

            $reservation = new Reservation($creneau, $activite, $personne);
            $reservation->setStatut($data['statut']);
            $reservations[] = $reservation;
        }

        return $reservations;
    }
}