-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : lun. 09 déc. 2024 à 09:20
-- Version du serveur : 8.0.35
-- Version de PHP : 8.2.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_salle_sport`
--

-- --------------------------------------------------------

--
-- Structure de la table `Activite`
--

CREATE TABLE `Activite` (
  `idActivite` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `tarif` float NOT NULL,
  `duree` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Calendrier`
--

CREATE TABLE `Calendrier` (
  `idCalendrier` int NOT NULL,
  `horaire_ouverture` time NOT NULL,
  `horaire_fermeture` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `Calendrier`
--

INSERT INTO `Calendrier` (`idCalendrier`, `horaire_ouverture`, `horaire_fermeture`) VALUES
(2230, '08:00:00', '21:00:00'),
(2231, '08:00:00', '21:00:00'),
(2232, '08:00:00', '21:00:00'),
(2233, '08:00:00', '21:00:00'),
(2234, '08:00:00', '21:00:00'),
(2235, '08:00:00', '21:00:00'),
(2236, '08:00:00', '21:00:00'),
(2237, '08:00:00', '21:00:00'),
(2238, '08:00:00', '21:00:00'),
(2239, '08:00:00', '21:00:00'),
(2240, '08:00:00', '21:00:00'),
(2241, '08:00:00', '21:00:00'),
(2242, '08:00:00', '21:00:00'),
(2243, '08:00:00', '21:00:00'),
(2244, '08:00:00', '21:00:00'),
(2245, '08:00:00', '21:00:00'),
(2246, '08:00:00', '21:00:00'),
(2247, '08:00:00', '21:00:00'),
(2248, '08:00:00', '21:00:00'),
(2249, '08:00:00', '21:00:00'),
(2250, '08:00:00', '21:00:00'),
(2251, '08:00:00', '21:00:00'),
(2252, '08:00:00', '21:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `Cotisation`
--

CREATE TABLE `Cotisation` (
  `idCotisation` int NOT NULL,
  `montant` float NOT NULL,
  `date_paiement` date NOT NULL,
  `date_fin` date NOT NULL,
  `idUtilisateur` int NOT NULL,
  `idPaiement` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Creneau`
--

CREATE TABLE `Creneau` (
  `idCreneau` int NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `duree` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `CreneauxActivite`
--

CREATE TABLE `CreneauxActivite` (
  `idCreneauxActivite` int NOT NULL,
  `idCreneau` int NOT NULL,
  `idActivite` int NOT NULL,
  `idCalendrier` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `CreneauxActiviteReserve`
--

CREATE TABLE `CreneauxActiviteReserve` (
  `idCreneauxActiviteReserve` int NOT NULL,
  `idCreneauxActivite` int NOT NULL,
  `date` date NOT NULL,
  `reserver` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Fermeture`
--

CREATE TABLE `Fermeture` (
  `idJourFermeture` int NOT NULL,
  `idCalendrier` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `JourFermeture`
--

CREATE TABLE `JourFermeture` (
  `idJourFermeture` int NOT NULL,
  `dateJour` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Moderateur`
--

CREATE TABLE `Moderateur` (
  `idModerateur` int NOT NULL,
  `idPersonne` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Notification`
--

CREATE TABLE `Notification` (
  `idNotification` int NOT NULL,
  `message` text NOT NULL,
  `type` enum('Email','SMS','Application') NOT NULL,
  `date_envoi` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Paiement`
--

CREATE TABLE `Paiement` (
  `idPaiement` int NOT NULL,
  `montant` float NOT NULL,
  `date_paiement` datetime NOT NULL,
  `idRIB` int NOT NULL,
  `idRIBEntreprise` int NOT NULL,
  `type` enum('Paiement','Remboursement') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Personne`
--

CREATE TABLE `Personne` (
  `idPersonne` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `identifiant` varchar(100) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `numTel` varchar(15) NOT NULL,
  `type` enum('Utilisateur','Moderateur') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recoit`
--

CREATE TABLE `recoit` (
  `idPersonne` int NOT NULL,
  `idNotification` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Remboursement`
--

CREATE TABLE `Remboursement` (
  `idRemboursement` int NOT NULL,
  `montant` float NOT NULL,
  `date_remboursement` datetime NOT NULL,
  `idReservation` int NOT NULL,
  `idPaiement` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Reservation`
--

CREATE TABLE `Reservation` (
  `idReservation` int NOT NULL,
  `statut` enum('confirmée','annulée','en attente','expirée') NOT NULL,
  `date_expiration` datetime NOT NULL,
  `idPersonne` int NOT NULL,
  `idCreneauxActiviteReserve` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `RIB`
--

CREATE TABLE `RIB` (
  `idRIB` int NOT NULL,
  `numero_compte` bigint NOT NULL,
  `code_guichet` int NOT NULL,
  `cle` int NOT NULL,
  `code_iban` varchar(34) NOT NULL,
  `titulaire_nom` varchar(100) NOT NULL,
  `identifiant_rib` varchar(100) DEFAULT NULL,
  `idUtilisateur` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `RIBEntreprise`
--

CREATE TABLE `RIBEntreprise` (
  `idRIBEntreprise` int NOT NULL,
  `numero_compte` bigint NOT NULL,
  `code_guichet` int NOT NULL,
  `cle` int NOT NULL,
  `code_iban` varchar(34) NOT NULL,
  `titulaire_nom` varchar(100) NOT NULL,
  `identifiant_rib` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateur`
--

CREATE TABLE `Utilisateur` (
  `idUtilisateur` int NOT NULL,
  `cotisation_active` tinyint(1) NOT NULL,
  `idPersonne` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Activite`
--
ALTER TABLE `Activite`
  ADD PRIMARY KEY (`idActivite`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `Calendrier`
--
ALTER TABLE `Calendrier`
  ADD PRIMARY KEY (`idCalendrier`);

--
-- Index pour la table `Cotisation`
--
ALTER TABLE `Cotisation`
  ADD PRIMARY KEY (`idCotisation`),
  ADD KEY `idUtilisateur` (`idUtilisateur`),
  ADD KEY `idPaiement` (`idPaiement`);

--
-- Index pour la table `Creneau`
--
ALTER TABLE `Creneau`
  ADD PRIMARY KEY (`idCreneau`);

--
-- Index pour la table `CreneauxActivite`
--
ALTER TABLE `CreneauxActivite`
  ADD PRIMARY KEY (`idCreneauxActivite`),
  ADD KEY `idCreneau` (`idCreneau`),
  ADD KEY `idActivite` (`idActivite`),
  ADD KEY `idCalendrier` (`idCalendrier`);

--
-- Index pour la table `CreneauxActiviteReserve`
--
ALTER TABLE `CreneauxActiviteReserve`
  ADD PRIMARY KEY (`idCreneauxActiviteReserve`),
  ADD KEY `idCreneauxActivite` (`idCreneauxActivite`);

--
-- Index pour la table `Fermeture`
--
ALTER TABLE `Fermeture`
  ADD PRIMARY KEY (`idJourFermeture`,`idCalendrier`),
  ADD KEY `idCalendrier` (`idCalendrier`);

--
-- Index pour la table `JourFermeture`
--
ALTER TABLE `JourFermeture`
  ADD PRIMARY KEY (`idJourFermeture`);

--
-- Index pour la table `Moderateur`
--
ALTER TABLE `Moderateur`
  ADD PRIMARY KEY (`idModerateur`),
  ADD KEY `idPersonne` (`idPersonne`);

--
-- Index pour la table `Notification`
--
ALTER TABLE `Notification`
  ADD PRIMARY KEY (`idNotification`);

--
-- Index pour la table `Paiement`
--
ALTER TABLE `Paiement`
  ADD PRIMARY KEY (`idPaiement`),
  ADD KEY `idRIB` (`idRIB`),
  ADD KEY `idRIBEntreprise` (`idRIBEntreprise`);

--
-- Index pour la table `Personne`
--
ALTER TABLE `Personne`
  ADD PRIMARY KEY (`idPersonne`),
  ADD UNIQUE KEY `identifiant` (`identifiant`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `numTel` (`numTel`);

--
-- Index pour la table `recoit`
--
ALTER TABLE `recoit`
  ADD PRIMARY KEY (`idPersonne`,`idNotification`),
  ADD KEY `idNotification` (`idNotification`);

--
-- Index pour la table `Remboursement`
--
ALTER TABLE `Remboursement`
  ADD PRIMARY KEY (`idRemboursement`),
  ADD KEY `idReservation` (`idReservation`),
  ADD KEY `idPaiement` (`idPaiement`);

--
-- Index pour la table `Reservation`
--
ALTER TABLE `Reservation`
  ADD PRIMARY KEY (`idReservation`),
  ADD KEY `idPersonne` (`idPersonne`),
  ADD KEY `idCreneauxActiviteReserve` (`idCreneauxActiviteReserve`);

--
-- Index pour la table `RIB`
--
ALTER TABLE `RIB`
  ADD PRIMARY KEY (`idRIB`),
  ADD UNIQUE KEY `code_iban` (`code_iban`),
  ADD UNIQUE KEY `identifiant_rib` (`identifiant_rib`),
  ADD KEY `idUtilisateur` (`idUtilisateur`);

--
-- Index pour la table `RIBEntreprise`
--
ALTER TABLE `RIBEntreprise`
  ADD PRIMARY KEY (`idRIBEntreprise`),
  ADD UNIQUE KEY `code_iban` (`code_iban`),
  ADD UNIQUE KEY `identifiant_rib` (`identifiant_rib`);

--
-- Index pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
  ADD PRIMARY KEY (`idUtilisateur`),
  ADD KEY `idPersonne` (`idPersonne`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `Activite`
--
ALTER TABLE `Activite`
  MODIFY `idActivite` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3346;

--
-- AUTO_INCREMENT pour la table `Calendrier`
--
ALTER TABLE `Calendrier`
  MODIFY `idCalendrier` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2253;

--
-- AUTO_INCREMENT pour la table `Cotisation`
--
ALTER TABLE `Cotisation`
  MODIFY `idCotisation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1903;

--
-- AUTO_INCREMENT pour la table `Creneau`
--
ALTER TABLE `Creneau`
  MODIFY `idCreneau` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5684;

--
-- AUTO_INCREMENT pour la table `CreneauxActivite`
--
ALTER TABLE `CreneauxActivite`
  MODIFY `idCreneauxActivite` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7310;

--
-- AUTO_INCREMENT pour la table `CreneauxActiviteReserve`
--
ALTER TABLE `CreneauxActiviteReserve`
  MODIFY `idCreneauxActiviteReserve` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2591;

--
-- AUTO_INCREMENT pour la table `JourFermeture`
--
ALTER TABLE `JourFermeture`
  MODIFY `idJourFermeture` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `Moderateur`
--
ALTER TABLE `Moderateur`
  MODIFY `idModerateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `Notification`
--
ALTER TABLE `Notification`
  MODIFY `idNotification` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Paiement`
--
ALTER TABLE `Paiement`
  MODIFY `idPaiement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4147;

--
-- AUTO_INCREMENT pour la table `Personne`
--
ALTER TABLE `Personne`
  MODIFY `idPersonne` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2077;

--
-- AUTO_INCREMENT pour la table `Remboursement`
--
ALTER TABLE `Remboursement`
  MODIFY `idRemboursement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT pour la table `Reservation`
--
ALTER TABLE `Reservation`
  MODIFY `idReservation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2104;

--
-- AUTO_INCREMENT pour la table `RIB`
--
ALTER TABLE `RIB`
  MODIFY `idRIB` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2046;

--
-- AUTO_INCREMENT pour la table `RIBEntreprise`
--
ALTER TABLE `RIBEntreprise`
  MODIFY `idRIBEntreprise` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2141;

--
-- AUTO_INCREMENT pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
  MODIFY `idUtilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2067;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `Cotisation`
--
ALTER TABLE `Cotisation`
  ADD CONSTRAINT `cotisation_ibfk_1` FOREIGN KEY (`idUtilisateur`) REFERENCES `Utilisateur` (`idUtilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotisation_ibfk_2` FOREIGN KEY (`idPaiement`) REFERENCES `Paiement` (`idPaiement`) ON DELETE SET NULL;

--
-- Contraintes pour la table `CreneauxActivite`
--
ALTER TABLE `CreneauxActivite`
  ADD CONSTRAINT `creneauxactivite_ibfk_1` FOREIGN KEY (`idCreneau`) REFERENCES `Creneau` (`idCreneau`) ON DELETE CASCADE,
  ADD CONSTRAINT `creneauxactivite_ibfk_2` FOREIGN KEY (`idActivite`) REFERENCES `Activite` (`idActivite`) ON DELETE CASCADE,
  ADD CONSTRAINT `creneauxactivite_ibfk_3` FOREIGN KEY (`idCalendrier`) REFERENCES `Calendrier` (`idCalendrier`) ON DELETE CASCADE;

--
-- Contraintes pour la table `CreneauxActiviteReserve`
--
ALTER TABLE `CreneauxActiviteReserve`
  ADD CONSTRAINT `creneauxactivitereserve_ibfk_1` FOREIGN KEY (`idCreneauxActivite`) REFERENCES `CreneauxActivite` (`idCreneauxActivite`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Fermeture`
--
ALTER TABLE `Fermeture`
  ADD CONSTRAINT `fermeture_ibfk_1` FOREIGN KEY (`idJourFermeture`) REFERENCES `JourFermeture` (`idJourFermeture`) ON DELETE CASCADE,
  ADD CONSTRAINT `fermeture_ibfk_2` FOREIGN KEY (`idCalendrier`) REFERENCES `Calendrier` (`idCalendrier`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Moderateur`
--
ALTER TABLE `Moderateur`
  ADD CONSTRAINT `moderateur_ibfk_1` FOREIGN KEY (`idPersonne`) REFERENCES `Personne` (`idPersonne`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Paiement`
--
ALTER TABLE `Paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`idRIB`) REFERENCES `RIB` (`idRIB`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiement_ibfk_2` FOREIGN KEY (`idRIBEntreprise`) REFERENCES `RIBEntreprise` (`idRIBEntreprise`) ON DELETE CASCADE;

--
-- Contraintes pour la table `recoit`
--
ALTER TABLE `recoit`
  ADD CONSTRAINT `recoit_ibfk_1` FOREIGN KEY (`idPersonne`) REFERENCES `Personne` (`idPersonne`) ON DELETE CASCADE,
  ADD CONSTRAINT `recoit_ibfk_2` FOREIGN KEY (`idNotification`) REFERENCES `Notification` (`idNotification`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Remboursement`
--
ALTER TABLE `Remboursement`
  ADD CONSTRAINT `remboursement_ibfk_1` FOREIGN KEY (`idReservation`) REFERENCES `Reservation` (`idReservation`) ON DELETE CASCADE,
  ADD CONSTRAINT `remboursement_ibfk_2` FOREIGN KEY (`idPaiement`) REFERENCES `Paiement` (`idPaiement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Reservation`
--
ALTER TABLE `Reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`idPersonne`) REFERENCES `Personne` (`idPersonne`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`idCreneauxActiviteReserve`) REFERENCES `CreneauxActiviteReserve` (`idCreneauxActiviteReserve`) ON DELETE CASCADE;

--
-- Contraintes pour la table `RIB`
--
ALTER TABLE `RIB`
  ADD CONSTRAINT `rib_ibfk_1` FOREIGN KEY (`idUtilisateur`) REFERENCES `Utilisateur` (`idUtilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
  ADD CONSTRAINT `utilisateur_ibfk_1` FOREIGN KEY (`idPersonne`) REFERENCES `Personne` (`idPersonne`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
