DROP TABLE IF EXISTS gestionCreneauxActiviteReserve;
DROP TABLE IF EXISTS CreneauxActivite;
DROP TABLE IF EXISTS recoit;
DROP TABLE IF EXISTS ferme;
DROP TABLE IF EXISTS Fermeture;
DROP TABLE IF EXISTS Notification;
DROP TABLE IF EXISTS Remboursement;
DROP TABLE IF EXISTS Reservation;
DROP TABLE IF EXISTS Creneau;
DROP TABLE IF EXISTS Activite;
DROP TABLE IF EXISTS Cotisation;
DROP TABLE IF EXISTS Paiement;
DROP TABLE IF EXISTS RIBEntreprise;
DROP TABLE IF EXISTS RIB;
DROP TABLE IF EXISTS Moderateur;
DROP TABLE IF EXISTS Utilisateur;
DROP TABLE IF EXISTS Calendrier;
DROP TABLE IF EXISTS Personne;


CREATE TABLE Personne (
    idPersonne INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    identifiant VARCHAR(100) NOT NULL UNIQUE,
    mdp VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    numTel VARCHAR(15) NOT NULL UNIQUE,
    type ENUM('Utilisateur', 'Moderateur') NOT NULL
);

CREATE TABLE Utilisateur (
    idUtilisateur INT PRIMARY KEY AUTO_INCREMENT,
    cotisation_active BOOLEAN NOT NULL,
    idPersonne INT NOT NULL,
    FOREIGN KEY (idPersonne) REFERENCES Personne(idPersonne)  ON DELETE CASCADE
);

CREATE TABLE Moderateur (
    idModerateur INT PRIMARY KEY AUTO_INCREMENT,
    idPersonne INT NOT NULL,
    FOREIGN KEY (idPersonne) REFERENCES Personne(idPersonne)  ON DELETE CASCADE
);

CREATE TABLE RIB (
    idRIB INT PRIMARY KEY AUTO_INCREMENT,
    numero_compte BIGINT NOT NULL,
    code_guichet INT NOT NULL,
    cle INT NOT NULL,
    code_iban VARCHAR(34) NOT NULL UNIQUE,
    titulaire_nom VARCHAR(100) NOT NULL,
    titulaire_prenom VARCHAR(100) NOT NULL,
    identifiant_rib VARCHAR(100) UNIQUE,
    idUtilisateur INT NOT NULL,
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(idUtilisateur)  ON DELETE CASCADE
);

CREATE TABLE RIBEntreprise (
    idRIBEntreprise INT PRIMARY KEY AUTO_INCREMENT,
    numero_compte BIGINT NOT NULL,
    code_guichet INT NOT NULL,
    cle INT NOT NULL,
    code_iban VARCHAR(34) NOT NULL UNIQUE,
    titulaire_nom VARCHAR(100) NOT NULL,
    titulaire_prenom VARCHAR(100) NOT NULL,
    identifiant_rib VARCHAR(100) UNIQUE
);

CREATE TABLE Paiement (
    idPaiement INT PRIMARY KEY AUTO_INCREMENT,
    montant FLOAT NOT NULL,
    date_paiement DATETIME NOT NULL,
    idRIB INT NOT NULL,
    idRIBEntreprise INT NOT NULL,
    FOREIGN KEY (idRIB) REFERENCES RIB(idRIB) ON DELETE CASCADE,
    FOREIGN KEY (idRIBEntreprise) REFERENCES RIBEntreprise(idRIBEntreprise)  ON DELETE CASCADE
);

CREATE TABLE Cotisation (
    idCotisation INT PRIMARY KEY AUTO_INCREMENT,
    montant FLOAT NOT NULL,
    date_paiement DATE NOT NULL,
    date_fin DATE NOT NULL,
    idUtilisateur INT NOT NULL,
    idPaiement INT,
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(idUtilisateur) ON DELETE CASCADE,
    FOREIGN KEY (idPaiement) REFERENCES Paiement(idPaiement) ON DELETE SET NULL
);

CREATE TABLE Activite (
    idActivite INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    tarif FLOAT NOT NULL,
    duree TIME NOT NULL
);

CREATE TABLE Creneau (
    idCreneau int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    heure_debut time NOT NULL,
    heure_fin time NOT NULL,
    duree time NOT NULL
);

CREATE TABLE CreneauxActivite (
    idCreneauxActivite int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    idCreneau int NOT NULL,
    idActivite int NOT NULL,
    FOREIGN KEY (idCreneau) REFERENCES Creneau(idCreneau) ON DELETE CASCADE,
    FOREIGN KEY (idActivite) REFERENCES Activite(idActivite) ON DELETE CASCADE
);

CREATE TABLE gestionCreneauxActiviteReserve (
    idGestion int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    idCreneauxActivite int NOT NULL,
    date date NOT NULL,
    FOREIGN KEY (idCreneauxActivite) REFERENCES CreneauxActivite(idCreneauxActivite) ON DELETE CASCADE
);


CREATE TABLE Calendrier (
    idCalendrier INT PRIMARY KEY AUTO_INCREMENT,
    horaire_ouverture TIME NOT NULL,
    horaire_fermeture TIME NOT NULL
);

CREATE TABLE Reservation (
    idReservation INT PRIMARY KEY AUTO_INCREMENT,
    statut ENUM('confirmée', 'annulée', 'en attente', 'expirée') NOT NULL,
    date_reservation DATETIME NOT NULL,
    idPersonne INT NOT NULL,
    idCreneau INT NOT NULL,
    idActivite INT NOT NULL,
    idCalendrier INT,
    FOREIGN KEY (idPersonne) REFERENCES Personne(idPersonne) ON DELETE CASCADE,
    FOREIGN KEY (idCreneau) REFERENCES Creneau(idCreneau) ON DELETE CASCADE,
    FOREIGN KEY (idActivite) REFERENCES Activite(idActivite)  ON DELETE CASCADE,
    FOREIGN KEY (idCalendrier) REFERENCES Calendrier(idCalendrier)
);

CREATE TABLE Remboursement (
    idRemboursement INT PRIMARY KEY AUTO_INCREMENT,
    montant FLOAT NOT NULL,
    date_remboursement DATETIME NOT NULL,
    idReservation INT NOT NULL,
    idPaiement INT NOT NULL,
    FOREIGN KEY (idReservation) REFERENCES Reservation(idReservation) ON DELETE CASCADE,
    FOREIGN KEY (idPaiement) REFERENCES Paiement(idPaiement) ON DELETE CASCADE
);

CREATE TABLE Notification (
    idNotification INT PRIMARY KEY AUTO_INCREMENT,
    message TEXT NOT NULL,
    type ENUM('Email', 'SMS', 'Application') NOT NULL,
    date_envoi DATE NOT NULL
);

CREATE TABLE Fermeture (
    idFermeture INT PRIMARY KEY AUTO_INCREMENT,
    dateJour DATE NOT NULL
);

CREATE TABLE recoit (
    idPersonne INT NOT NULL,
    idNotification INT NOT NULL,
    PRIMARY KEY (idPersonne, idNotification),
    FOREIGN KEY (idPersonne) REFERENCES Personne(idPersonne) ON DELETE CASCADE,
    FOREIGN KEY (idNotification) REFERENCES Notification(idNotification) ON DELETE CASCADE
);

CREATE TABLE ferme (
    idFermeture INT NOT NULL,
    idCalendrier INT NOT NULL,
    PRIMARY KEY (idFermeture, idCalendrier),
    FOREIGN KEY (idFermeture) REFERENCES Fermeture(idFermeture) ON DELETE CASCADE,
    FOREIGN KEY (idCalendrier) REFERENCES Calendrier(idCalendrier) ON DELETE CASCADE
);

INSERT INTO `RIBEntreprise` (`idRIBEntreprise`, `numero_compte`, `code_guichet`, `cle`, `code_iban`, `titulaire_nom`, `titulaire_prenom`, `identifiant_rib`) VALUES ('1', '98765', '56', '876', 'FR34567898765432123465', 'salle de sport', 'Entreprise', '123');
INSERT INTO Personne (nom, identifiant, mdp, email, numTel, type)
/*motdepasse123*/
VALUES ('Admin', 'admin123', '$2y$10$X.iK4DEglFWsjE1LCBrfuemGU3RSwpwVU5SYDh4vzqQnhJ54qK42q', 'admin@example.com', '0000000000', 'Moderateur');
INSERT INTO Moderateur (idPersonne)
VALUES (LAST_INSERT_ID());

INSERT INTO `Calendrier` (`idCalendrier`, `horaire_ouverture`, `horaire_fermeture`) VALUES ('1', '08:00:00', '21:00:00');
INSERT INTO `Fermeture` (`idFermeture`, `dateJour`) VALUES ('1', '2024-12-25'), ('2', '2025-01-01'), ('3', '2025-03-05'), ('4', '2025-05-01'), ('5', '2025-05-08');
INSERT INTO `ferme` (`idFermeture`, `idCalendrier`) VALUES ('1', '1'), ('2', '1'), ('3', '1'), ('4', '1'), ('5', '1');
INSERT INTO `Activite` (`idActivite`, `nom`, `tarif`, `duree`) VALUES (NULL, 'tennis', '1000', '02:00:00'), (NULL, 'basketball', '500', '01:00:00'), (NULL, 'fitness', '800', '01:30:00');

/*
--Personne (idPersonne, nom, identifiant, mdp, email, numTel, type)  
--Utilisateur (idUtilisateur, cotisation_active, #idpersonne)  
--Moderateur (idModerateur, #idpersonne)  
--RIB (idRIB, numero_compte, code_guichet, cle, code_iban, titulaire_nom, titulaire_prenom, identifiant_rib, #idUtilisateur)  
--RIBEntreprise (idRIBEntreprise, numero_compte, code_guichet, cle, code_iban, titulaire_nom, titulaire_prenom, identifiant_rib)  
--Cotisation (idCotisation, montant, date_paiement, date_fin, #idUtilisateur, #idpaiement)  
--Activite (idActivite, nom, tarif, duree)  
--Creneau (idCreneau, date, heure_debut, heure_fin, reserve)  
--Reservation (idReservation, statut, date_reservation, #idPersonne, #idCreneau, #idActivite, #idCalendrier)  
--Paiement (idPaiement, montant, date_paiement, #idRIB, #idRIBEntreprise)  
--Remboursement (idRemboursement, montant, date_remboursement, #idreservation, #idpaiement)  
--Notification (idNotification, message, type, date_envoi)  
--Calendrier (idCalendrier, horaire_ouverture, horaire_fermeture)  
--Fermeture (idFermeture, dateJour)  
--reçoit (#idPersonne, #idNotification)  
--ferme (#idFermeture, #idCalendrier) 
--CreneauxActivite (idCreneauxActivite, #idCreneau, #idActivite) 
--gestionCreneauxActiviteReserve (idGestion, #idCreneauxActivite, date) 
*/