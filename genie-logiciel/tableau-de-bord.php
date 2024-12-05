<?php
session_start();
include 'require.php';

if (!isset($_SESSION['idPersonne']) || !isset($_SESSION['identifiant'])) {
    header('Location: accueil.php?pb=connexion');
    exit;
}

$idUtilisateur = $_SESSION['idPersonne'];

$bdd = new BaseDeDonnees();
$pdo = $bdd->getConnexion();

$stmt = $pdo->query("SELECT horaire_ouverture, horaire_fermeture FROM Calendrier LIMIT 1");
$calendrierData = $stmt->fetch(PDO::FETCH_ASSOC);
$calendrier = new Calendrier($calendrierData['horaire_ouverture'], $calendrierData['horaire_fermeture']);

$horaireOuverture = $calendrierData['horaire_ouverture'];
$horaireFermeture = $calendrierData['horaire_fermeture'];

$gestionUtilisateur = new GestionUtilisateur($calendrier);

$stmt = $pdo->query("SELECT dateJour FROM Fermeture");
$joursFermes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$mode = $_GET['mode'] ?? 'general';

$date = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$weekStart = clone $date;
$weekStart->modify(('Monday' === $weekStart->format('l')) ? 'this Monday' : 'last Monday');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

$prevWeek = $weekStart->modify('-7 days')->format('Y-m-d');
$weekStart->modify('+7 days');
$nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
$weekStart->modify('-7 days');

if ($mode === 'reservations') {
    error_log("Erreur d'id: " . gettype($idUtilisateur));
    $reservations = $gestionUtilisateur->afficherReservationsParUtilisateur($idUtilisateur);
} elseif ($mode === 'activite' && isset($_GET['activite']) && !empty($_GET['activite'])) {
    $creneauxParActivite = $gestionUtilisateur->afficherCreneauxParActiviteParPersonne($idUtilisateur, (int)$_GET['activite']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="tableau-de-bord-css.css">
    <script src="tableau-de-bord-js.js"></script>

</head>
<body>
    <h1>Tableau de Bord</h1>
    <h2>Bienvenue, <?= htmlspecialchars($_SESSION['identifiant']) ?></h2>

    <?php
    $stmt = $pdo->prepare("SELECT * FROM Personne WHERE idPersonne = :idPersonne");
    $stmt->execute([':idPersonne' => $idUtilisateur]);
    $donnees = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>

    <?php if ($donnees && $donnees['type'] === 'Moderateur'): ?>
        <div>
            <a href="admin.php" class="btn">Accéder à l'interface administrateur</a>
        </div>
    <?php endif; 
    ?>

    <h3>Modes de visualisation</h3>
    <form method="GET" action="">
        <label>
            <input type="radio" name="mode" value="general" <?= !isset($_GET['mode']) || $_GET['mode'] === 'general' ? 'checked' : '' ?>>
            Visualisation générale
        </label>
        <label>
            <input type="radio" name="mode" value="activite" <?= isset($_GET['mode']) && $_GET['mode'] === 'activite' ? 'checked' : '' ?>>
            Créneaux disponibles filtrer par activité
        </label>
        <label>
            <input type="radio" name="mode" value="reservations" <?= isset($_GET['mode']) && $_GET['mode'] === 'reservations' ? 'checked' : '' ?>>
            Mes réservations
        </label>

        <button type="submit">Appliquer</button>
    </form>

    <?php if ($mode === 'activite'): ?>
        <h3>Filtrer par activité</h3>
        <form method="GET" action="">
            <select name="activite">
                <option value="">Toutes les activités</option>
                <?php
                $stmt = $pdo->query("SELECT idActivite, nom FROM Activite");
                while ($activite = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = isset($_GET['activite']) && $_GET['activite'] == $activite['idActivite'] ? 'selected' : '';
                    echo "<option value=\"{$activite['idActivite']}\" $selected>{$activite['nom']}</option>";
                }
                ?>
            </select>
            <button type="submit">Filtrer</button>
        </form>
    <?php endif; ?>

    <?php if ($mode === 'reservations' && !empty($reservations)): ?>
        <h3>Mes Réservations</h3>
        <ul>
            <?php foreach ($reservations as $reservation): ?>
                <li>
                Activité : <?= htmlspecialchars($reservation->getActivite()->getNom()) ?> <br>
                Date : <?= htmlspecialchars($reservation->getCreneau()->getDate()) ?> <br>
                Heure : <?= htmlspecialchars($reservation->getCreneau()->getHeureDebut()) ?> - <?= htmlspecialchars($reservation->getCreneau()->getHeureFin()) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; 
    
    if ($mode === 'reservations' && !empty($reservations)) {
        foreach ($reservations as $reservation) {
            $reservationCases[] = [
                'date' => $reservation->getCreneau()->getDate(),
                'heureDebut' => $reservation->getCreneau()->getHeureDebut(),
                'heureFin' => $reservation->getCreneau()->getHeureFin(),
                'activiteNom' => $reservation->getActivite()->getNom()
            ];
        }
    }
    
    print_r($reservationCases);
    ?>

    <?php if ($mode === 'activite' && isset($creneauxParActivite)): ?>
        <h3>Créneaux disponibles pour l'activité sélectionnée</h3>
        <ul>
            <?php foreach ($creneauxParActivite as $creneau): ?>
                <li>
                    Date : <?= htmlspecialchars($creneau->getDate()) ?> <br>
                    Heure : <?= htmlspecialchars($creneau->getHeureDebut()) ?> - <?= htmlspecialchars($creneau->getHeureFin()) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    $formatter = new IntlDateFormatter(
        'fr_FR',
        IntlDateFormatter::MEDIUM,
        IntlDateFormatter::NONE,
        'Europe/Paris',
        IntlDateFormatter::GREGORIAN 
    );

    $dateStartFr = $formatter->format($weekStart);
    $dateEndFr = $formatter->format($weekEnd);
    ?>

    <div class="navigation">
        <a href="?mode=<?= $mode ?>&date=<?= $prevWeek ?>">← Semaine précédente</a>
        <strong><?= htmlspecialchars($dateStartFr) ?> - <?= htmlspecialchars($dateEndFr) ?></strong>
        <a href="?mode=<?= $mode ?>&date=<?= $nextWeek ?>">Semaine suivante →</a>
    </div>

    <div class="calendar-wrapper">
        <div class="hour-cell"></div>
        <?php
        setlocale(LC_TIME, 'fr_FR.UTF-8');
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE
        );

        for ($i = 0; $i < 7; $i++) {
            $currentDay = clone $weekStart;
            $currentDay->modify("+$i days");

            $formattedDate = $formatter->format($currentDay);

            echo "<div class='day-header'>" . htmlspecialchars($formattedDate) . "</div>";
        }
        ?>

    <?php

        for ($hour = strtotime($horaireOuverture); $hour < strtotime($horaireFermeture); $hour = strtotime('+1 hour', $hour)) {
            $hourDisplay = date('H:i', $hour);
            echo "<div class='hour-cell'>$hourDisplay</div>";

            for ($i = 0; $i < 7; $i++) {
                $currentDay = clone $weekStart;
                $currentDay->modify("+$i days");
                $date = $currentDay->format('Y-m-d');
                $isClosed = in_array($date, $joursFermes);

                $isReserved = false;
                $isAvailable = !$isClosed;
                $reservationDetails = null;

                if ($mode === 'reservations' && isset($reservationCases)) {
                    foreach ($reservationCases as $reservation) {
                        if ($reservation['date'] === $date && $reservation['heureDebut'] === date('H:i:s', $hour)) {
                            $isReserved = true;
                            $isAvailable = false;
                            $reservationDetails = $reservation;
                            break;
                        }
                    }
                }

                $cellClass = $isClosed ? 'closed' : ($isReserved ? 'cell reserve' : ($isAvailable ? 'cell disponible' : ''));

                $onclick = $isAvailable ? "onclick=\"reserverCreneau('$date', '" . date('H:i:s', $hour) . "', '" . date('H:i:s', strtotime('+1 hour', $hour)) . "')\"" : '';

                echo "<div class='$cellClass' $onclick>";
                if ($isReserved && $reservationDetails) {
                    echo "Réservé : " . htmlspecialchars($reservationDetails['activiteNom']);
                } elseif ($isAvailable) {
                    echo "Disponible";
                }
                echo "</div>";
            }
        }
        ?>
    </div>
</body>
</html>