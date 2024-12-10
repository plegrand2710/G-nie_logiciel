<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="accueil-css.css">
</head>
<body>
    <div class="header">
        <h1>Connexion</h1>
    </div>

    <div class="container login-form">
        <form action="connexion-action.php" method="POST">
            <h2>Connectez-vous</h2>
            <input type="text" name="identifiant" placeholder="Identifiant" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p>
            Pas encore inscrit ? <a href="inscription.php?type=Utilisateur">Inscrivez-vous ici</a>.
        </p>
    </div>
</body>
</html>