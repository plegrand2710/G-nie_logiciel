<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif et Paiement</title>
    <link rel="stylesheet" href="reservation-css.css">
</head>
<body>
    <div class="container">
        <h1>Récapitulatif de Réservation</h1>

        <?php
        session_start();
        include 'require.php';
        if (!isset($_SESSION['idPersonne'])) {
            header('Location: accueil.php?pb=connexion');
            exit;
        }

        $idUtilisateur = $_SESSION['idPersonne'];

        $bdd = new BaseDeDonnees();
        $pdo = $bdd->getConnexion();

        $date = htmlspecialchars($_GET['date'] ?? 'Non défini');
        $heureDebut = htmlspecialchars($_GET['heureDebut'] ?? 'Non défini');
        $heureFin = htmlspecialchars($_GET['heureFin'] ?? 'Non défini');
        $activite = htmlspecialchars($_GET['activite'] ?? '');
        $tarif = htmlspecialchars($_GET['tarif'] ?? '0');
        ?>

        <div class="recap">
            <p><strong>Date :</strong> <?= $date ?></p>
            <p><strong>Heure :</strong> <?= $heureDebut ?> - <?= $heureFin ?></p>
            <p><strong>Activité :</strong></p>

            <form method="GET" action="">
                <input type="hidden" name="date" value="<?= $date ?>"> 
                <input type="hidden" name="heureDebut" value="<?= $heureDebut ?>">
                <input type="hidden" name="heureFin" value="<?= $heureFin ?>">

                <select name="activite" onchange="this.form.submit()">
                    <option value="">-- Choisir une activité --</option>
                    <?php
                    $stmt = $pdo->query("SELECT idActivite, nom, tarif FROM Activite");
                    $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($activites as $act) {
                        $selected = ($activite === $act['idActivite']) ? 'selected' : '';
                        echo "<option value=\"{$act['idActivite']}\" data-tarif=\"{$act['tarif']}\" $selected>{$act['nom']} ({$act['tarif']} XPF)</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <?php if ($activite): ?>
            <?php
            $stmt = $pdo->prepare("SELECT nom, tarif FROM Activite WHERE idActivite = :idActivite");
            $stmt->execute([':idActivite' => $activite]);
            $activiteDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            $activiteNom = $activiteDetails['nom'];
            $tarif = $activiteDetails['tarif'];
            ?>
            <div class="recap">
                <p><strong>Activité Sélectionnée :</strong> <?= htmlspecialchars($activiteNom) ?></p>
                <p><strong>Tarif :</strong> <?= htmlspecialchars($tarif) ?> €</p>
            </div>
        <?php endif; ?>

        <h2>Choisissez un RIB pour le paiement</h2>
        <form action="reservation-action.php" method="POST">
            <input type="hidden" name="date" value="<?= $date ?>">
            <input type="hidden" name="heureDebut" value="<?= $heureDebut ?>">
            <input type="hidden" name="heureFin" value="<?= $heureFin ?>">
            <input type="hidden" name="activite" value="<?= $activite ?>">
            <input type="hidden" name="tarif" value="<?= $tarif ?>">

            <table class="rib-table">
                <thead>
                    <tr>
                        <th>Numéro de Compte</th>
                        <th>Code Guichet</th>
                        <th>IBAN</th>
                        <th>Sélectionner</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT numero_compte, code_guichet, code_iban 
                        FROM RIB 
                        WHERE idUtilisateur = (
                            SELECT idUtilisateur 
                            FROM Utilisateur 
                            WHERE idPersonne = :idPersonne
                        )
                    ");
                    $stmt->execute([':idPersonne' => $idUtilisateur]);
                    $ribs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($ribs)) {
                        echo "<tr><td colspan='4'>Aucun RIB enregistré. Veuillez ajouter un RIB dans votre profil.</td></tr>";
                    } else {
                        foreach ($ribs as $index => $rib) {
                            echo "<tr>
                                <td>{$rib['numero_compte']}</td>
                                <td>{$rib['code_guichet']}</td>
                                <td>{$rib['code_iban']}</td>
                                <td><input type='radio' name='rib' value='{$rib['code_iban']}' " . ($index === 0 ? 'checked' : '') . "></td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <?php if (!empty($ribs)): ?>
                <button type="submit" class="btn-pay">Valider et Finaliser la Réservation</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>