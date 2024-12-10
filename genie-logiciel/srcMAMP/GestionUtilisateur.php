<?php
require_once 'BaseDeDonnees.php';

class GestionUtilisateur {
    private int $_idCalendrier;
    private $_paiement;
    private $_remboursement;
    private static $_penalite = 50;
    private $_pdo;

    public function __construct($idCalendrier) {
        $bdd = new BaseDeDonnees();
        $this->_pdo = $bdd->getConnexion();
        $this->setCalendrier((int)$idCalendrier);
        $this->_paiement = null;
        $this->_remboursement = null;

        error_log("je suis dans la classe");
    }

    public function getIdCalendrier(): int {
        return $this->_idCalendrier;
    }

    public function getPDO() {
        return $this->_pdo;
    }

    public function setCalendrier($idCalendrier) {
        if (!is_int($idCalendrier) || $idCalendrier <= 0) {
            throw new InvalidArgumentException("L'ID du calendrier doit être un entier positif.");
        }

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Calendrier WHERE idCalendrier = :idCalendrier");
        $stmt->execute([':idCalendrier' => $idCalendrier]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le calendrier avec l'ID {$idCalendrier} n'existe pas dans la base de données.");
        }

        $this->_idCalendrier = $idCalendrier;
    }

    public function setPDO($pdo) {
        if (!$pdo instanceof PDO) {
            throw new InvalidArgumentException("Le PDO doit être un PDO.");
        }
        $this->_pdo = $pdo;
    }
    public function getPaiement() {
        return $this->_paiement;
    }

    public function setPaiement($idPaiement) {
        if (!is_int($idPaiement) || $idPaiement <= 0) {
            throw new InvalidArgumentException("L'ID du paiement doit être un entier positif.");
        }    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Paiement WHERE idPaiement = :idPaiement");
        $stmt->execute([':idPaiement' => $idPaiement]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le paiement avec l'ID {$idPaiement} n'existe pas dans la base de données.");
        }
            $this->_paiement = $idPaiement;
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

    public function setRemboursement($idRemboursement) {
        if (!is_int($idRemboursement) || $idRemboursement <= 0) {
            throw new InvalidArgumentException("L'ID du remboursement doit être un entier positif.");
        }
        
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Remboursement WHERE idRemboursement = :idRemboursement");
        $stmt->execute([':idRemboursement' => $idRemboursement]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le remboursement avec l'ID {$idRemboursement} n'existe pas dans la base de données.");
        }
            $this->_remboursement = $idRemboursement;
    }

    public function reserver($idCreneauxActiviteReserve, $idPersonne) {
        $this->verifierIdCreneauxActiviteReserve($idCreneauxActiviteReserve);
        $this->verifierPersonne($idPersonne);

        $typePersonne = $this->getPersonneType($idPersonne);
        if ($typePersonne === 'Utilisateur') {

            $personne = $this->chargerUtilisateurDepuisId($idPersonne);

            if (!$personne->verifPayerCotisation()) {
                throw new LogicException("L'utilisateur doit avoir une cotisation valide pour réserver.");
            }
        } else {
            $personne = $this->chargerModerateurDepuisId($idPersonne);
        }

        $this->verifierDisponibiliteCreneau($idCreneauxActiviteReserve);
        error_log("je suis dans réservé");

        $reservation = new Reservation($idCreneauxActiviteReserve, $idPersonne);

        if ($personne instanceof Utilisateur) {
            $this->paiementActivite($personne->getIdUtilisateur(), $this->getActiviteByGestionId($idCreneauxActiviteReserve)->getId());
        }
    
        if (!$reservation->confirmerReservation()) {
            throw new LogicException("La réservation n'a pas pu être confirmée.");
        }

        try {

            $reservation->ajouterDansLaBDD();
   
        } catch (Exception $e) {
            throw new RuntimeException("Erreur lors de la création de la réservation : " . $e->getMessage());
        }
            $this->mettreCreneauReserve($idCreneauxActiviteReserve);
        return true;
    }
    
    private function verifierIdCreneauxActiviteReserve($idCreneauxActiviteReserve) {
        if (!is_int($idCreneauxActiviteReserve) || $idCreneauxActiviteReserve <= 0) {
            throw new InvalidArgumentException("L'ID de gestion du créneau-activité réservé doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActiviteReserve = :idCreneauxActiviteReserve");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("Le créneau avec l'ID {$idCreneauxActiviteReserve} n'existe pas dans la base de données.");
        }
    }
    
    private function verifierPersonne($idPersonne) {
        if (!is_int($idPersonne) || $idPersonne <= 0) {
            throw new InvalidArgumentException("L'ID de la personne doit être un entier positif.");
        }
    
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("La personne avec l'ID {$idPersonne} n'existe pas dans la base de données.");
        }
    }
    
    private function getPersonneType($idPersonne) {
        $stmt = $this->_pdo->prepare("SELECT type FROM Personne WHERE idPersonne = :idPersonne");
        $stmt->execute([':idPersonne' => $idPersonne]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            throw new RuntimeException("Impossible de récupérer le type de la personne avec l'ID {$idPersonne}.");
        }
    
        return $result['type'];
    }
    
    private function verifierDisponibiliteCreneau($idCreneauxActiviteReserve) {
        $stmt = $this->_pdo->prepare("SELECT reserver FROM CreneauxActiviteReserve WHERE idCreneauxActiviteReserve = :idCreneauxActiviteReserve AND reserver = true");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
        $reserver = $stmt->fetchColumn();
        error_log(($reserver));
        if ($reserver) {
            throw new LogicException("Le créneau est déjà réservé.");
        }
    }
    
    private function mettreCreneauReserve($idCreneauxActiviteReserve) {
        $stmt = $this->_pdo->prepare("UPDATE CreneauxActiviteReserve SET reserver = 1 WHERE idCreneauxActiviteReserve = :idCreneauxActiviteReserve");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
    }

    public function paiementActivite($idUtilisateur, $idActivite) {
        $this->verifierUtilisateur($idUtilisateur);
        $this->verifierActivite($idActivite);
    
        $montant = $this->getMontantActivite($idActivite);
    
        $idRIBSource = $this->getRIBSourceUtilisateur($idUtilisateur);
        $idRIBDestinataire = $this->getRIBDestinataireEntreprise();
    
        $this->effectuerPaiement($montant, $idRIBSource, $idRIBDestinataire);
    }
    
    private function verifierUtilisateur($idUtilisateur) {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE idUtilisateur = :idUtilisateur");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("L'utilisateur avec l'ID {$idUtilisateur} n'existe pas.");
        }
    }
    
    private function verifierActivite($idActivite) {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        if ($stmt->fetchColumn() == 0) {
            throw new RuntimeException("L'activité avec l'ID {$idActivite} n'existe pas.");
        }
    }
    
    private function getMontantActivite($idActivite) {
        $stmt = $this->_pdo->prepare("SELECT tarif FROM Activite WHERE idActivite = :idActivite");
        $stmt->execute([':idActivite' => $idActivite]);
        return $stmt->fetchColumn();
    }
    
    private function getRIBSourceUtilisateur($idUtilisateur) {
        $stmt = $this->_pdo->prepare("SELECT idRIB FROM RIB WHERE idUtilisateur = :idUtilisateur LIMIT 1");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
        $idRIBSource = $stmt->fetchColumn();
        if (!$idRIBSource) {
            throw new RuntimeException("RIB source introuvable pour l'utilisateur avec l'ID {$idUtilisateur}.");
        }
        return $idRIBSource;
    }
    
    private function getRIBDestinataireEntreprise() {
        $stmt = $this->_pdo->prepare("SELECT idRIBEntreprise FROM RIBEntreprise LIMIT 1");
        $stmt->execute();
        $idRIBDestinataire = $stmt->fetchColumn();
        if (!$idRIBDestinataire) {
            throw new RuntimeException("RIB destinataire (entreprise) introuvable.");
        }
        return $idRIBDestinataire;
    }
    
    private function effectuerPaiement($montant, $idRIBSource, $idRIBDestinataire) {
        $this->_paiement = new PaiementVirement($montant, new DateTime(), $idRIBSource, $idRIBDestinataire, "paiement");
    
        if (!$this->_paiement->effectuerPaiement()) {
            throw new LogicException("Le paiement a échoué.");
        }
    }

    public function annulerReservation($idReservation) {
        $reservation = $this->verifierReservation($idReservation);
        $this->verifierStatutReservation($reservation);
    
        $idCreneauxActiviteReserve = $reservation->getCreneauActiviteReserve();
        $detailsCreneauActivite = $this->getDetailsCreneauActivite($idCreneauxActiviteReserve);
    
    
        if ($reservation->getStatut() == 'confirmée' && $reservation->getIdPersonne() instanceof Utilisateur) {
            $penalite = $this->calculerPenalite($reservation);
    
            $idRIBSource = $this->getRibUtilisateur($reservation->getIdPersonne()->getIdPersonne());
            $idRIBDestinataire = $this->getRibEntreprise();
    
            $this->remboursementActivite($reservation->getIdPersonne()->getIdPersonne(), $idReservation, $penalite, $idRIBSource, $idRIBDestinataire);
        }

        $reservation->annulerReservation();

    
        $this->libererCreneauActivite($idCreneauxActiviteReserve);
    }
    
    private function getRibUtilisateur(int $idPersonne): int {
        $stmt = $this->_pdo->prepare("SELECT idRIB FROM RIB WHERE idUtilisateur = :idUtilisateur LIMIT 1");
        $stmt->execute([':idUtilisateur' => $idPersonne]);
        $idRIBSource = $stmt->fetchColumn();
    
        if (!$idRIBSource) {
            throw new RuntimeException("RIB source introuvable pour l'utilisateur.");
        }
    
        return $idRIBSource;
    }
    
    private function getRibEntreprise(): int {
        $stmt = $this->_pdo->prepare("SELECT idRIBEntreprise FROM RIBEntreprise LIMIT 1");
        $stmt->execute();
        $idRIBDestinataire = $stmt->fetchColumn();
    
        if (!$idRIBDestinataire) {
            throw new RuntimeException("RIB destinataire (entreprise) introuvable.");
        }
    
        return $idRIBDestinataire;
    }
    
    private function verifierReservation($idReservation) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Reservation WHERE idReservation = :idReservation");
        $stmt->execute([':idReservation' => $idReservation]);
        $reservationData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservationData['idReservation']) {
            throw new LogicException("La réservation avec l'ID {$idReservation} n'existe pas.");
        }

        $reservation=new Reservation((int)$reservationData['idCreneauxActiviteReserve'], (int)$reservationData['idPersonne']);
        $reservation->setIdReservation($reservationData['idReservation']);
        $reservation->setStatut($reservationData['statut']);
        return $reservation;
    }
    
    private function verifierStatutReservation($reservation) {
        if ($reservation->getStatut() !== 'confirmée' && $reservation->getStatut() !== 'en attente') {
            throw new LogicException("Seules les réservations confirmées ou en attente peuvent être annulées.");
        }
    }
    
    private function getDetailsCreneauActivite($idCreneauxActiviteReserve) {
        $stmt = $this->_pdo->prepare("
            SELECT CreneauxActivite.*, Activite.* 
            FROM CreneauxActiviteReserve
            JOIN CreneauxActivite ON CreneauxActiviteReserve.idCreneauxActivite = CreneauxActivite.idCreneauxActivite
            JOIN Activite ON CreneauxActivite.idActivite = Activite.idActivite
            WHERE CreneauxActiviteReserve.idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new LogicException("Le CreneauxActiviteReserve associé est introuvable.");
        }
        return $result;
    }
    
    private function libererCreneauActivite($idCreneauxActiviteReserve) {
        $stmt = $this->_pdo->prepare("
            UPDATE CreneauxActiviteReserve
            SET reserver = 0
            WHERE idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
    }

    private function calculerPenalite($idReservation): float {
        $this->verifierReservationExistante($idReservation);
    
        $creneauData = $this->getCreneauData($idReservation);
        
        $penalite = $this->calculerPenaliteBase($creneauData);
        
        return $penalite;
    }
    
    private function verifierReservationExistante($idReservation): void {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM CreneauxActiviteReserve WHERE idCreneauxActiviteReserve = :idReservation");
        $stmt->execute([':idReservation' => $idReservation]);
        if ($stmt->fetchColumn() == 0) {
            throw new LogicException("La réservation avec l'ID {$idReservation} n'existe pas.");
        }
    }
    
    private function getCreneauData($idReservation): array {
        $stmt = $this->_pdo->prepare("
            SELECT gca.date AS dateCreneau, ca.idCreneau 
            FROM CreneauxActiviteReserve gca
            JOIN CreneauxActivite ca ON gca.idCreneauxActivite = ca.idCreneauxActivite
            WHERE gca.idCreneauxActiviteReserve = :idReservation
        ");
        $stmt->execute([':idReservation' => $idReservation]);
        $creneauData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$creneauData) {
            throw new LogicException("Les informations du créneau réservé pour la réservation {$idReservation} sont introuvables.");
        }
        
        return $creneauData;
    }
    
    private function calculerPenaliteBase($creneauData): float {
        $heureDebut = new DateTime($creneauData['dateCreneau'] . ' ' . $this->getHeureDebut($creneauData['idCreneau']));
        $heureActuelle = new DateTime();
    
        if ($heureActuelle > $heureDebut) {
            return 0;
        }
    
        $interval = $heureActuelle->diff($heureDebut);
        $heuresAvant = ($interval->days * 24) + $interval->h;
        
        return $heuresAvant < 24 ? self::$_penalite : 0;
    }
    
    private function getHeureDebut($idCreneau): string {
        $stmt = $this->_pdo->prepare("SELECT heure_debut FROM Creneau WHERE idCreneau = :idCreneau");
        $stmt->execute([':idCreneau' => $idCreneau]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            throw new LogicException("L'heure de début du créneau avec l'ID {$idCreneau} est introuvable.");
        }
    
        return $result['heure_debut'];
    }

    public function remboursementActivite($idPersonne, $idReservation, $penalite, $idRIBSource, $idRIBDestinataire): void {
        $this->verifierPersonne($idPersonne);
    
        $utilisateur = $this->chargerUtilisateurDepuisId($idPersonne);
        
        $this->verifierReservation($idReservation);
    
        if (!is_float($penalite) && !is_int($penalite)) {
            throw new InvalidArgumentException("La pénalité doit être un nombre.");
        }
    
        $this->verifierRibSource($idRIBSource);
        $this->verifierRibDestinataire($idRIBDestinataire);
    
        $tarif = $this->getTarifActiviteByReservation($idReservation); 
        $this->_remboursement = new Remboursement(
            new DateTime(),     
            $tarif,         
            $penalite,         
            $utilisateur->getIdUtilisateur(),
            $idReservation, 
            $idRIBSource, 
            $idRIBDestinataire 
        );
    
        $this->_remboursement->effectuerRemboursement();
    }
    
    private function verifierRibSource(int $idRIBSource): void {
        $stmt = $this->_pdo->prepare("SELECT idRIB FROM RIB WHERE idRIB = :idRIB");
        $stmt->execute([':idRIB' => $idRIBSource]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException("Le RIB source avec l'ID {$idRIBSource} n'existe pas.");
        }
    }
    
    private function verifierRibDestinataire(int $idRIBDestinataire): void {
        $stmt = $this->_pdo->prepare("SELECT idRIBEntreprise FROM RIBEntreprise WHERE idRIBEntreprise = :idRIBDestinataire");
        $stmt->execute([':idRIBDestinataire' => $idRIBDestinataire]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException("Le RIB destinataire avec l'ID {$idRIBDestinataire} n'existe pas.");
        }
    }
    
    private function getTarifActiviteByReservation($idReservation): float {
        $stmt = $this->_pdo->prepare("
            SELECT a.tarif
            FROM Reservation r
            INNER JOIN CreneauxActiviteReserve car ON r.idCreneauxActiviteReserve = car.idCreneauxActiviteReserve
            INNER JOIN CreneauxActivite ca ON car.idCreneauxActivite = ca.idCreneauxActivite
            INNER JOIN Activite a ON ca.idActivite = a.idActivite
            WHERE r.idReservation = :idReservation
        ");
        $stmt->execute([':idReservation' => $idReservation]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            throw new RuntimeException("Impossible de récupérer le tarif de l'activité pour la réservation {$idReservation}.");
        }
    
        return (float) $result['tarif'];
    }

    public function afficherCreneauxReserveParActiviteParPersonne($idPersonne, $idActivite): array {
        $this->verifierPersonne($idPersonne);
    
        $this->verifierActivite($idActivite);
    
        try {
            $stmt = $this->_pdo->prepare("
                SELECT gcar.date AS creneau_date, c.heure_debut, c.heure_fin
                FROM CreneauxActiviteReserve gcar
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
            
            if (empty($resultats)) {
                return [];
            }
            
            $creneaux = [];
            foreach ($resultats as $row) {
                $creneaux[] = new Creneau($row['heure_debut'], $row['heure_fin']);
            }
        
            return $creneaux;
    
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des créneaux : " . $e->getMessage());
        }
    }

    public function afficherCreneauxDisponiblesParActivite($idActivite): array {
        $this->verifierIdActivite($idActivite);
        
        $dateDebut = new DateTime();
        $dateFin = (clone $dateDebut)->modify('+6 months');
    
        $creneauxBase = $this->getCreneauxBaseParActivite($idActivite);
        
        $creneauxDisponibles = [];
        $periode = new DatePeriod(
            $dateDebut,
            new DateInterval('P1D'),
            $dateFin
        );
    
        foreach ($periode as $date) {
            $dateString = $date->format('Y-m-d');
            $creneauxDuJour = $this->verifierDisponibiliteCreneauxParJour($idActivite, $dateString, $creneauxBase);
    
            foreach ($creneauxDuJour as $key => $creneau) {
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
    
    private function verifierIdActivite($idActivite): void {
        if (!is_int($idActivite) || $idActivite <= 0) {
            throw new InvalidArgumentException("L'ID de l'activité doit être un entier positif.");
        }
    }
    
    private function getCreneauxBaseParActivite($idActivite): array {
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
    
        return $creneauxBase;
    }
    
    private function verifierDisponibiliteCreneauxParJour($idActivite, $dateString, $creneauxBase): array {
        $stmt = $this->_pdo->prepare("
            SELECT c.heure_debut, c.heure_fin
            FROM CreneauxActiviteReserve gcar
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
        }
    
        return $creneauxDuJour;
    }

    public function afficherReservationsParUtilisateur($idPersonne): array {
        $this->verifierPersonne($idPersonne);
        
        $personneData = $this->getPersonneType($idPersonne);
        
        $personne = $this->chargerPersonneDepuisId($idPersonne, $personneData);
        
        $reservations = $this->getReservationsParPersonne($idPersonne);
    
        return $reservations;
    }
    
    private function chargerPersonneDepuisId($idPersonne, $type) {
        if ($type === 'Utilisateur') {
            return $this->chargerUtilisateurDepuisId($idPersonne);
        } elseif ($type === 'Moderateur') {
            return $this->chargerModerateurDepuisId($idPersonne);
        } else {
            throw new LogicException("Type de personne inconnu : " . $type);
        }
    }
    
    private function getReservationsParPersonne($idPersonne): array {
        $stmt = $this->_pdo->prepare("
            SELECT Reservation.*, 
                   gcar.date AS creneau_date, 
                   c.heure_debut, 
                   c.heure_fin, 
                   a.nom AS activite_nom, 
                   a.tarif, 
                   a.duree
            FROM Reservation
            INNER JOIN CreneauxActiviteReserve gcar ON Reservation.idCreneauxActiviteReserve = gcar.idCreneauxActiviteReserve
            INNER JOIN CreneauxActivite ca ON gcar.idCreneauxActivite = ca.idCreneauxActivite
            INNER JOIN Creneau c ON ca.idCreneau = c.idCreneau
            INNER JOIN Activite a ON ca.idActivite = a.idActivite
            WHERE Reservation.idPersonne = :idPersonne
        ");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        $reservations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $creneau = new Creneau(
                $row['creneau_date'],
                $row['heure_debut']
            );
    
            $activite = new Activite(
                $row['activite_nom'],
                $row['tarif'],
                $row['duree']
            );
    
            $reservation = new Reservation(
                $row['idCreneauxActiviteReserve'],
                $activite
            );
            $reservation->setIdReservation($row['idReservation']);
            $reservation->setStatut($row['statut']);
            $reservations[] = $reservation;
        }
    
        return $reservations;
    }
    
    public function afficherTousLesCreneauxPourUtilisateur($idPersonne): array {
        $this->verifierUtilisateur($idPersonne);
    
        $creneaux = $this->getCreneauxPourUtilisateur($idPersonne);
    
        return $creneaux;
    }

    private function getCreneauxPourUtilisateur($idPersonne): array {
        $stmt = $this->_pdo->prepare("
            SELECT Creneau.idCreneau, Creneau.date, Creneau.heure_debut, Creneau.heure_fin, Creneau.reserve
            FROM Reservation
            INNER JOIN Creneau ON Reservation.idCreneau = Creneau.idCreneau
            WHERE Reservation.idPersonne = :idPersonne
        ");
        $stmt->execute([':idPersonne' => $idPersonne]);
    
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->creerCreneauxDepuisResultats($resultats);
    }
    
    private function creerCreneauxDepuisResultats(array $resultats): array {
        $creneaux = [];
        foreach ($resultats as $data) {
            $creneaux[] = new Creneau(
                $data['date'],
                $data['heure_debut']
            );
        }
        return $creneaux;
    }

    public function afficherReservationsUtilisateurParActivite($idPersonne, $idActivite): array {
        $personneData = $this->verifierPersonne($idPersonne);
        
        $reservationsData = $this->getReservationsData($idPersonne, $idActivite);
    
        $personne = $this->chargerPersonneDepuisId($idPersonne, $personneData['type']);    

        return $this->creerReservations($reservationsData, $personne);
    }
    
    
    private function getReservationsData($idPersonne, $idActivite): array {
        $stmt = $this->_pdo->prepare("
            SELECT Reservation.idReservation, Reservation.statut, Reservation.date_expiration,
                   gcar.date AS creneau_date, 
                   c.heure_debut, c.heure_fin, 
                   a.nom AS activite_nom, a.tarif AS activite_tarif
            FROM Reservation
            INNER JOIN CreneauxActiviteReserve gcar ON Reservation.idCreneauxActiviteReserve = gcar.idCreneauxActiviteReserve
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
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    private function creerReservations($reservationsData, $personne): array {
        $reservations = [];
    
        foreach ($reservationsData as $data) {
            $reservation = new Reservation(
                $data['idCreneauxActiviteReserve'],
                $personne
            );
            $reservation->setIdReservation($data['idReservation']);
            $reservation->setStatut($data['statut']);
            $reservation->setDateExpiration(new DateTime($data['date_expiration']));
            
            $reservations[] = $reservation;
        }
    
        return $reservations;
    }

    private function getActiviteByGestionId($idCreneauxActiviteReserve) {
        $stmt = $this->_pdo->prepare("
            SELECT Activite.* 
            FROM CreneauxActiviteReserve 
            JOIN CreneauxActivite ON CreneauxActiviteReserve.idCreneauxActivite = CreneauxActivite.idCreneauxActivite
            JOIN Activite ON CreneauxActivite.idActivite = Activite.idActivite
            WHERE CreneauxActiviteReserve.idCreneauxActiviteReserve = :idCreneauxActiviteReserve
        ");
        $stmt->execute([':idCreneauxActiviteReserve' => $idCreneauxActiviteReserve]);
    
        $activiteData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$activiteData) {
            throw new RuntimeException("Aucune activité trouvée pour l'ID de gestion $idCreneauxActiviteReserve.");
        }
        $activite = new Activite(
            $activiteData['nom'],
            $activiteData['tarif'],
            $activiteData['duree']
        );

        $activite->setId($activiteData['idActivite']);
        return $activite;
    }

    
    public function chargerUtilisateurDepuisId($idPersonne) {
        $stmt =$this->_pdo->prepare("
            SELECT p.*, u.*
            FROM Personne p 
            INNER JOIN Utilisateur u ON p.idPersonne = u.idPersonne 
            WHERE u.idPersonne = :idPersonne
        ");
        $stmt->execute([':idPersonne' => $idPersonne]);
        $personneData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$personneData) {
            throw new RuntimeException("Utilisateur introuvable avec l'ID $idPersonne.");
        }
    
        $utilisateur = new Utilisateur(
            $personneData['nom'],
            $personneData['identifiant'],
            $personneData['mdp'],
            $personneData['email'],
            $personneData['numTel']
        );
        $utilisateur->setIdUtilisateur((int)$personneData['idUtilisateur']);

        $cotisations = $this->getCotisations($utilisateur->getIdUtilisateur());
        
        $utilisateur->setCotisations($cotisations);
        return $utilisateur;
    }

    public function getCotisations($idUtilisateur) {
        $stmt = $this->_pdo->prepare("
            SELECT c.*
            FROM Cotisation c
            WHERE c.idUtilisateur = :idUtilisateur
        ");
        $stmt->execute([':idUtilisateur' => $idUtilisateur]);
    
        $cotisations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $cotisations;
    }
    public function chargerModerateurDepuisId($idModerateur) {
        $stmt =$this->_pdo->prepare("
            SELECT p.*
            FROM Personne p 
            INNER JOIN Moderateur m ON p.idPersonne = m.idPersonne 
            WHERE m.idModerateur = :idModerateur
        ");
        $stmt->execute([':idModerateur' => $idModerateur]);
        $personneData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$personneData) {
            throw new RuntimeException("Utilisateur introuvable avec l'ID $idModerateur.");
        }
    
        return new Moderateur(
            $personneData['nom'],
            $personneData['identifiant'],
            $personneData['mdp'],
            $personneData['email'],
            $personneData['numTel']
        );
    }
}