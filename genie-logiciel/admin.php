<?php
session_start();
include 'require.php';

if (!isset($_SESSION['idPersonne']) || !isset($_SESSION['identifiant'])) {
    header('Location: accueil.php?pb=connexion');
    exit;
}

$bdd = new BaseDeDonnees();
$pdo = $bdd->getConnexion();

// Vérification du type de l'utilisateur
$idPersonne = $_SESSION['idPersonne'];
$stmt = $pdo->prepare("SELECT type FROM Personne WHERE idPersonne = :idPersonne");
$stmt->execute([':idPersonne' => $idPersonne]);
$userType = $stmt->fetchColumn();

if ($userType !== 'Moderateur') {
    header('Location: tableau-de-bord.php?erreur=acces');
    exit;
}

// Récupérer les informations administratives nécessaires
$utilisateurs = $pdo->query("
    SELECT idPersonne, nom, identifiant, email, numTel, type 
    FROM Personne
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Administrateur</title>
    <link rel="stylesheet" href="admin-css.css">
</head>
<body>
    <div class="container">
        <h1>Page Administrateur</h1>
        <h2>Bienvenue, <?= htmlspecialchars($_SESSION['identifiant']) ?> (Moderateur)</h2>
        
        <h3>Liste des personnes</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Identifiant</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $utilisateur): ?>
                <tr>
                    <td><?= htmlspecialchars($utilisateur['idPersonne']) ?></td>
                    <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                    <td><?= htmlspecialchars($utilisateur['identifiant']) ?></td>
                    <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                    <td><?= htmlspecialchars($utilisateur['numTel']) ?></td>
                    <td><?= htmlspecialchars($utilisateur['type']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Gestion des Activités et Créneaux</h3>
        <div class="admin-actions">
            <a href="ajouter-activite.php" class="btn">Ajouter une Activité</a>
            <a href="modifier-activite.php" class="btn">Modifier une Activité</a>
            <a href="modifier-creneau.php" class="btn">Modifier un Créneau</a>
        </div>
        
        <a href="tableau-de-bord.php" class="btn">Retour au Tableau de Bord</a>
    </div>
</body>
</html>