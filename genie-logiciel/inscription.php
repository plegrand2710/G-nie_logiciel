<?php
session_start();
$type = $_GET['type'] ?? 'Utilisateur';
if (!in_array($type, ['Utilisateur', 'Moderateur'])) {
    die("Type d'inscription invalide.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="accueil-css.css">
</head>
<body>
    <div class="header">
        <h1>Inscription <?php echo htmlspecialchars($type); ?></h1>
    </div>
    <?php if (!empty($_SESSION['erreurs'])) : ?>
        <script>
            alert("<?php echo implode('\n', $_SESSION['erreurs']); ?>");
        </script>
        <?php unset($_SESSION['erreurs']); ?>
    <?php endif; ?>

    <div class="container login-form">
        <form action="inscription-action.php" method="POST">
            <h2>Informations <?php echo htmlspecialchars($type); ?></h2>
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <input type="text" name="nom" placeholder="Nom complet" required>
            <input type="text" name="identifiant" placeholder="Identifiant" required>
            <input type="password" name="mdp" placeholder="Mot de passe (min. 8 caractères)" required>
            <input type="email" name="email" placeholder="Adresse email" required>
            <input type="text" name="numTel" placeholder="Numéro de téléphone" required>
            <?php if ($_GET['type'] === 'Utilisateur') : ?>
                <h2>Informations bancaires</h2>
                <input type="text" name="numeroCompte" placeholder="Numéro de compte" required><br>
                
                <input type="text" name="codeGuichet" placeholder="Code guichet" required><br>
                
                <input type="text" name="cleRib" placeholder="Clé RIB" required><br>
                
                <input type="text" name="iban" placeholder="Code IBAN" required><br>
                
                <input type="text" name="titulaireNom" placeholder="Nom du titulaire" required><br>
                
                <input type="text" name="titulairePrenom" placeholder="Prénom du titulaire" required><br>
                
                <input type="text" name="identifiantRib" placeholder="Identifiant du RIB" required><br>
            <?php endif; ?>
            <button type="submit">S'inscrire</button>
        </form>
        <p style="text-align: center; margin-top: 10px;">
            Déjà inscrit ? <a href="accueil.php">Connectez-vous ici</a>.
        </p>
    </div>
</body>
</html>