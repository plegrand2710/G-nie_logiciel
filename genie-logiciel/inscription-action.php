<?php
session_start();
include 'require.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'Utilisateur';
    $nom = trim($_POST['nom'] ?? '');
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $numTel = trim($_POST['numTel'] ?? '');

    $erreurs = [];

    if (empty($nom)) {
        $erreurs[] = "Le champ Nom est obligatoire.";
    }
    if (empty($identifiant)) {
        $erreurs[] = "Le champ Identifiant est obligatoire.";
    }
    if (empty($mdp) || strlen($mdp) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email est invalide.";
    }
    if (empty($numTel) || !preg_match('/^[0-9]{8,10}$/', $numTel)) {
        $erreurs[] = "Le numéro de téléphone doit contenir entre 8 et 10 chiffres.";
    }

    if ($type === 'Utilisateur') {
        $numeroCompte = trim($_POST['numeroCompte'] ?? '');
        $codeGuichet = trim($_POST['codeGuichet'] ?? '');
        $cleRib = trim($_POST['cleRib'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $titulaireNom = trim($_POST['titulaireNom'] ?? '');
        $titulairePrenom = trim($_POST['titulairePrenom'] ?? '');
        $identifiantRIB = trim($_POST['identifiantRib'] ?? '');

        if (empty($numeroCompte)) {
            $erreurs[] = "Le champ Numéro de compte est obligatoire.";
        }
        if (empty($codeGuichet)) {
            $erreurs[] = "Le champ Code guichet est obligatoire.";
        }
        if (empty($cleRib)) {
            $erreurs[] = "Le champ Clé RIB est obligatoire.";
        }
        if (empty($iban)) {
            $erreurs[] = "Le champ IBAN est obligatoire.";
        }
        if (empty($titulaireNom)) {
            $erreurs[] = "Le champ Nom du titulaire est obligatoire.";
        }
        if (empty($titulairePrenom)) {
            $erreurs[] = "Le champ Prénom du titulaire est obligatoire.";
        }
        if (empty($identifiantRIB)) {
            $erreurs[] = "Le champ Identifiant du RIB est obligatoire.";
        }
    }

    if (!empty($erreurs)) {
        $_SESSION['erreurs'] = $erreurs;
        header("Location: inscription.php?type=" . urlencode($type));
        exit;
    }

    try {
        $bdd = new BaseDeDonnees();
        $pdo = $bdd->getConnexion();

        $pdo->beginTransaction();

        if ($type === 'Utilisateur') {
            $utilisateur = new Utilisateur($nom, $identifiant, $mdp, $email, $numTel);
            $utilisateur->ajouterDansLaBDD();
            $stmt = $pdo->prepare("SELECT idUtilisateur FROM Utilisateur WHERE idPersonne = (SELECT idPersonne FROM Personne WHERE identifiant = :identifiant)");
            $stmt->execute([':identifiant' => $identifiant]);
            $idUtilisateur = $stmt->fetchColumn();

            if (!$idUtilisateur) {
                throw new RuntimeException("Impossible de récupérer l'ID de l'utilisateur.");
            }

            $rib = new Rib((int)$numeroCompte, (int)$codeGuichet, (int)$cleRib, $iban, $titulaireNom, $titulairePrenom, $identifiantRIB, $idUtilisateur);
            $rib->ajouterDansBase();
            $utilisateur->ajouterRib($rib);
            $pdo->commit();

            $stmt = $pdo->prepare("SELECT idPersonne FROM Utilisateur WHERE idUtilisateur =  :identifiant");
            $stmt->execute([':identifiant' => $idUtilisateur]);
            $idPersonne = $stmt->fetchColumn();
            
            $_SESSION['idPersonne'] = $idPersonne;

            $_SESSION['idUtilisateur'] = $idUtilisateur;
            header("Location: paiement-cotisation.php");
            exit;
        } elseif ($type === 'Moderateur') {
            $moderateur = new Moderateur($nom, $identifiant, $mdp, $email, $numTel);
            $moderateur->ajouterDansLaBDD();
            $pdo->commit();
        } else {
            throw new InvalidArgumentException("Type d'inscription invalide.");
        }

        header('Location: index.php?reussi=oui');
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
            if (!empty($identifiant)) {
                $stmt = $pdo->prepare("DELETE FROM Personne WHERE identifiant = :identifiant");
                $stmt->execute([':identifiant' => $identifiant]);
            }
        }
        $erreurs[] = "Une erreur est survenue : " . $e->getMessage();
        $_SESSION['erreurs'] = $erreurs;
        error_log("Erreur d'inscription : " . $e->getMessage());
        header("Location: inscription.php?type=" . urlencode($type) . "&reussi=non");
    }
}
?>