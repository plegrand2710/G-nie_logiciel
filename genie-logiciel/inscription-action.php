<?php
include 'require.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'Utilisateur';
    $nom = trim($_POST['nom'] ?? '');
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $numTel = trim($_POST['numTel'] ?? '');

    if (empty($nom) || empty($identifiant) || empty($mdp) || empty($email) || empty($numTel)) {
        echo "<script>alert('Tous les champs sont obligatoires.');</script>";
        header("Location: inscription.php?type=" . urlencode($type));
        exit;
    }

    if ($type === 'Utilisateur') {
        $numeroCompte = trim($_POST['numeroCompte'] ?? '');
        $codeGuichet = trim($_POST['codeGuichet'] ?? '');
        $cleRib = trim($_POST['cleRib'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $titulaireNom = trim($_POST['titulaireNom'] ?? '');
        $titulairePrenom = trim($_POST['titulairePrenom'] ?? '');
        $identifiantRIB = trim($_POST['identifiantRib'] ?? '');

        if (empty($numeroCompte) || empty($codeGuichet) || empty($cleRib) || empty($iban) || empty($titulaireNom) || empty($titulairePrenom) || empty($identifiantRIB)) {
            echo "<script>alert('Tous les champs, y compris le RIB et la cotisation, sont obligatoires pour les utilisateurs.');</script>";
            header("Location: inscription.php?type=" . urlencode($type));
            exit;
        }
    }

    try {
        $bdd = new BaseDeDonnees();
        $pdo = $bdd->getConnexion();

        $pdo->beginTransaction();

        if ($type === 'Utilisateur') {
            $utilisateur = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);

            $stmt = $pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
            $stmt->execute([':identifiant' => $identifiant]);
            $idUtilisateur = $stmt->fetchColumn();

            if (!$idUtilisateur) {
                throw new RuntimeException("Impossible de récupérer l'ID de l'utilisateur.");
            }

            $rib = new Rib();
            $rib->initialiseRIB((int)$numeroCompte, (int)$codeGuichet, (int)$cleRib, $iban, $titulaireNom, $titulairePrenom, $identifiantRIB, $idUtilisateur);
            $utilisateur->setRib($rib);

            $dateDebut = new DateTime();
            $cotisation = new Cotisation($dateDebut, $idUtilisateur);
            $utilisateur->addCotisation($cotisation);
        } elseif ($type === 'Moderateur') {
            $moderateur = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);
        } else {
            throw new InvalidArgumentException("Type d'inscription invalide.");
        }

        $pdo->commit();

        header('Location: index.php?reussi=oui');
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();

            if (!empty($identifiant)) {
                $stmt = $pdo->prepare("DELETE FROM Personne WHERE identifiant = :identifiant");
                $stmt->execute([':identifiant' => $identifiant]);
            }
        }

        error_log("Erreur d'inscription : " . $e->getMessage());
        header("Location: inscription.php?type=" . urlencode($type) . "&reussi=non");
    }
}
?>