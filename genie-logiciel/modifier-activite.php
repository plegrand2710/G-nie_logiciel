<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Activité</title>
    <link rel="stylesheet" href="admin-css.css">
</head>
<body>
    <div class="container">
        <h1>Modifier une Activité</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Tarif</th>
                    <th>Durée</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM Activite");
                while ($activite = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($activite['idActivite']) ?></td>
                        <td><?= htmlspecialchars($activite['nom']) ?></td>
                        <td><?= htmlspecialchars($activite['tarif']) ?> XPF</td>
                        <td><?= htmlspecialchars($activite['duree']) ?></td>
                        <td><a href="modifier-activite-form.php?id=<?= $activite['idActivite'] ?>" class="btn">Modifier</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="admin.php" class="btn">Retour</a>
    </div>
</body>
</html>