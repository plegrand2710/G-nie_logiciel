<?php
session_start();
include 'require.php';


if (!isset($_SESSION['idUtilisateur'])) {
    header('Location: index.php');
    exit;
}

$idPersonne = $_SESSION['idPersonne'];
$idUtilisateur = $_SESSION['idUtilisateur'];

$bdd = new BaseDeDonnees();
$pdo = $bdd->getConnexion();

$stmt = $pdo->prepare("SELECT * FROM Personne WHERE idPersonne = :idPersonne");
$stmt->execute([':idPersonne' => $idPersonne]);
$personne = $stmt->fetch(PDO::FETCH_ASSOC);

error_log("personne " . $idPersonne);

if (!$idUtilisateur) {
    echo "Utilisateur introuvable.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM RIB WHERE idUtilisateur = :idUtilisateur");
$stmt->execute([':idUtilisateur' => $idUtilisateur]);
$listeRIB = $stmt->fetchAll(PDO::FETCH_ASSOC);

$montant = 3000;
$validite = "1 an";

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="paiement-cotisation-css.css">
    <title>Paiement de la Cotisation</title>
</head>
<body>
    <h1>Paiement de la Cotisation</h1>
    <h2>Informations Utilisateur</h2>
    <p>Nom : <?= htmlspecialchars($personne['nom']) ?></p>
    <p>Email : <?= htmlspecialchars($personne['email']) ?></p>
    <p>Montant de la cotisation : <?= $montant ?> XPF</p>
    <p>Durée de validité : <?= $validite ?></p>

    <h2>Choisissez un RIB pour le paiement</h2>
    <form action="paiement-cotisation-action.php" method="POST">
        <table>
            <tr>
                <th></th>
                <th>Numéro de Compte</th>
                <th>Code Guichet</th>
                <th>IBAN</th>
            </tr>
            <?php foreach ($listeRIB as $rib): ?>
            <tr>
                <td><input type="radio" name="idRIB" value="<?= $rib['idRIB'] ?>" <?= $rib === reset($listeRIB) ? 'checked' : '' ?>></td>
                <td><?= htmlspecialchars($rib['numero_compte']) ?></td>
                <td><?= htmlspecialchars($rib['code_guichet']) ?></td>
                <td><?= htmlspecialchars($rib['code_iban']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit">Valider et Finaliser l'inscription</button>
    </form>
</body>
</html>