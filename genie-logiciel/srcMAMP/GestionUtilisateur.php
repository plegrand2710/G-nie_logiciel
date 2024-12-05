<?php
require_once 'BaseDeDonnees.php';

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

    public function reserver($idGestionCreneauActiviteReserve, $personne) {
        if (!is_int($idGestionCreneauActiviteReserve) || $idGestionCreneauActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de gestion du créneau-activité réservé doit être un entier positif.");
        }

        if (!($personne instanceof Personne)) {
            throw new InvalidArgumentException("La personne doit être une instance de Personne.");
        }

        if ($personne instanceof Utilisateur && !$personne->verifPayerCotisation()) {
            throw new LogicException("La personne doit avoir une cotisation valide pour réserver.");
        }

        $stmt = $this->_pdo->prepare("
            SELECT reserver 
            FROM gestionCreneauxActiviteReserve 
            WHERE idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $idGestionCreneauActiviteReserve]);
        $reserver = $stmt->fetchColumn();

        if ($reserver) {
            throw new LogicException("Le créneau est déjà réservé.");
        }

        $reservation = new Reservation($idGestionCreneauActiviteReserve, $personne);

        if ($personne instanceof Utilisateur) {
            $this->paiementActivite($personne, $this->getActiviteByGestionId($idGestionCreneauActiviteReserve));
        }

        if (!$reservation->confirmerReservation()) {
            throw new LogicException("La réservation n'a pas pu être confirmée.");
        }
        
        try {
            $this->ajouterDansLaBase($reservation);
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la création de la réservation : " . $e->getMessage());
        }

        $stmt = $this->_pdo->prepare("
            UPDATE gestionCreneauxActiviteReserve 
            SET reserver = 1 
            WHERE idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $idGestionCreneauActiviteReserve]);

        return true;
    }

    private function ajouterDansLaBase($reservation) {
        $stmt = $this->_pdo->prepare("
            INSERT INTO Reservation (statut, date_expiration, idPersonne, idGestionCreneauActiviteReserve) 
            VALUES (:statut, :date, :idPersonne, :idGestionCreneauActiviteReserve)
        ");
        $stmt->execute([
            ':statut' => $reservation->getStatut(),
            ':date' => $reservation->getDateExpiration(),
            ':idPersonne' => $reservation->getPersonne()->getId(),
            ':idGestionCreneauActiviteReserve' => $reservation->getGestionCreneauActiviteReserve(),
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

        $idGestion = $reservation->getGestionCreneauActiviteReserve();

        $stmt = $this->_pdo->prepare("
            SELECT CreneauxActivite.*, Activite.* 
            FROM gestionCreneauxActiviteReserve
            JOIN CreneauxActivite ON gestionCreneauxActiviteReserve.idCreneauxActivite = CreneauxActivite.idCreneauxActivite
            JOIN Activite ON CreneauxActivite.idActivite = Activite.idActivite
            WHERE gestionCreneauxActiviteReserve.idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $idGestion]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new LogicException("Le gestionCreneauActiviteReserve associé est introuvable.");
        }
        $reservation->annulerReservation();

        if ($reservation->getStatut() == 'confirmée' && $reservation->getPersonne() instanceof Utilisateur) {
            $penalite = $this->calculerPenalite($reservation);
            $activite = new Activite($result['nom'], $result['tarif'], $result['duree']);
            $this->remboursementActivite($reservation->getPersonne(), $activite, $penalite);
        } 

        $stmt = $this->_pdo->prepare("
            UPDATE gestionCreneauxActiviteReserve
            SET reserver = 0
            WHERE idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $reservation->getGestionCreneauActiviteReserve()]);
    }

    private function calculerPenalite(Reservation $reservation): float {
        $stmt = $this->_pdo->prepare("
            SELECT gca.date AS dateCreneau, ca.idCreneau 
            FROM gestionCreneauxActiviteReserve gca
            JOIN CreneauxActivite ca ON gca.idCreneauxActivite = ca.idCreneauxActivite
            WHERE gca.idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $reservation->getGestionCreneauActiviteReserve()]);
        $creneauData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$creneauData) {
            throw new LogicException("Les informations du créneau réservé sont introuvables.");
        }
            $heureDebut = new DateTime($creneauData['dateCreneau'] . ' ' . $reservation->getHeureDebut());
        $heureActuelle = new DateTime();
        if ($heureActuelle > $heureDebut) {
            return 0; 
        }
        $interval = $heureActuelle->diff($heureDebut);
        $heuresAvant = ($interval->days * 24) + $interval->h;
    
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
            SELECT gcar.date AS creneau_date, c.heure_debut, c.heure_fin
            FROM gestionCreneauxActiviteReserve gcar
            JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            JOIN Creneau c ON ca.idCreneau = c.idCreneau
            WHERE gcar.idPersonne = :idPersonne
            AND ca.idActivite = :idActivite
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

    public function afficherCreneauxDisponiblesParActivite($idActivite): array {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }
        $dateDebut = new DateTime(); 
        $dateFin = (clone $dateDebut)->modify('+6 months');
        $stmt = $this->_pdo->prepare("
            SELECT c.idCreneau, c.heure_debut, c.heure_fin 
            FROM CreneauxActivite ca
            JOIN Creneau c ON ca.idCreneau = c.idCreneau
            WHERE ca.idActivite = :idActivite
        ");
        $stmt->execute([':idActivite' => $idActivite]);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $creneauxBase = [];
        foreach ($resultats as $data) {
            $key = $data['heure_debut'] . '_' . $data['heure_fin'];
            $creneauxBase[$key] = [
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
            ];
        }
        $creneauxDisponibles = [];
        $periode = new DatePeriod(
            $dateDebut,
            new DateInterval('P1D'),
            $dateFin
        );
        foreach ($periode as $date) {
            $dateString = $date->format('Y-m-d');
    
            $stmt = $this->_pdo->prepare("
                SELECT c.heure_debut, c.heure_fin 
                FROM gestionCreneauxActiviteReserve gcar
                JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
                JOIN Creneau c ON ca.idCreneau = c.idCreneau
                WHERE ca.idActivite = :idActivite AND gcar.date = :date AND gcar.reserver = 1
            ");
            $stmt->execute([
                ':idActivite' => $idActivite,
                ':date' => $dateString,
            ]);
            $resultatsReserves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $creneauxDuJour = $creneauxBase;
    
            foreach ($resultatsReserves as $dataReserve) {
                $key = $dataReserve['heure_debut'] . '_' . $dataReserve['heure_fin'];
                if (isset($creneauxDuJour[$key])) {
                    $creneauxDuJour[$key]['disponible'] = false;
                }
            }
            foreach ($creneauxDuJour as $key => $creneau) {
                if (!isset($creneau['disponible'])) {
                    $creneau['disponible'] = true;
                }
                $creneauxDisponibles[] = [
                    'date' => $dateString,
                    'heure_debut' => $creneau['heure_debut'],
                    'heure_fin' => $creneau['heure_fin'],
                    'disponible' => $creneau['disponible'],
                ];
            }
        }
        return $creneauxDisponibles;
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
                   gcar.date AS creneau_date, 
                   c.heure_debut, 
                   c.heure_fin, 
                   a.nom AS activite_nom, 
                   a.tarif, 
                   a.duree
            FROM Reservation
            INNER JOIN gestionCreneauxActiviteReserve gcar ON Reservation.idGestion = gcar.idGestion
            INNER JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            INNER JOIN Creneau c ON ca.idCreneau = c.idCreneau
            INNER JOIN Activite a ON ca.idActivite = a.idActivite
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
            $creneau = new Creneau(
                $row['creneau_date'],
                $row['heure_debut'],
                $row['heure_fin']
            );
    
            $activite = new Activite(
                $row['activite_nom'],
                $row['tarif'],
                $row['duree']
            );
    
            $reservation = new Reservation(
                $row['idGestion'],
                $personne
            );
            $reservation->setId($row['idReservation']);
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
            SELECT Reservation.idReservation, Reservation.statut, Reservation.date_expiration,
                   gcar.date AS creneau_date, 
                   c.heure_debut, c.heure_fin, 
                   a.nom AS activite_nom, a.tarif AS activite_tarif
            FROM Reservation
            INNER JOIN gestionCreneauxActiviteReserve gcar ON Reservation.idGestion = gcar.idGestion
            INNER JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            INNER JOIN Creneau c ON ca.idCreneau = c.idCreneau
            INNER JOIN Activite a ON ca.idActivite = a.idActivite
            WHERE Reservation.idPersonne = :idPersonne
              AND ca.idActivite = :idActivite
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
        foreach ($resultats as $data) {
            $reservation = new Reservation(
                $data['idGestion'],
                $personne
            );
            $reservation->setId($data['idReservation']);
            $reservation->setStatut($data['statut']);
            $reservation->setDateExpiration(new DateTime($data['date_expiration']));
            $reservations[] = $reservation;
        }
    
        return $reservations;
    }

    private function getActiviteByGestionId($idGestionCreneauActiviteReserve) {
        $stmt = $this->_pdo->prepare("
            SELECT Activite.* 
            FROM gestionCreneauxActiviteReserve 
            JOIN CreneauxActivite ON gestionCreneauxActiviteReserve.idCreneauxActivite = CreneauxActivite.idCreneauxActivite
            JOIN Activite ON CreneauxActivite.idActivite = Activite.idActivite
            WHERE gestionCreneauxActiviteReserve.idGestion = :idGestion
        ");
        $stmt->execute([':idGestion' => $idGestionCreneauActiviteReserve]);
    
        $activiteData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$activiteData) {
            throw new RuntimeException("Aucune activité trouvée pour l'ID de gestion $idGestionCreneauActiviteReserve.");
        }
    
        return new Activite(
            $activiteData['nom'],
            $activiteData['tarif'],
            $activiteData['duree']
        );
    }
}