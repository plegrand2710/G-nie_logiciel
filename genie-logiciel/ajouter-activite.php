<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Activité</title>
    <link rel="stylesheet" href="admin-css.css">
</head>
<body>
    <div class="container">
        <h1>Ajouter une Activité</h1>
        <form action="ajouter-activite-action.php" method="POST">
            <label for="nom">Nom de l'Activité :</label>
            <input type="text" id="nom" name="nom" required>

            <label for="tarif">Tarif (XPF) :</label>
            <input type="number" id="tarif" name="tarif" step="0.01" required>

            <label for="duree">Durée (HH:MM:SS) :</label>
            <input type="time" id="duree" name="duree" required>

            <button type="submit" class="btn">Ajouter</button>
        </form>
        <a href="admin.php" class="btn">Annuler</a>
    </div>
</body>
</html>