
<?php
// Inclure l'autoloader de Composer
    require 'vendor/autoload.php';

    // Importer les classes PHPMailer
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Serveur SMTP de Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'pauline.legrand.gedimat@gmail.com'; // Votre adresse Gmail
        $mail->Password = 'ignhxceprnfxwaup'; // Mot de passe ou App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuration des destinataires
        $mail->setFrom('pauline.legrand.gedimat@gmail.com', 'Legrand Pauline');
        $mail->addAddress('pauline.legrand2710@gmail.com');
        $mail->addAddress('cl72160@gmail.com');
        $mail->addAddress('quentin.djearayer@gmail.com');

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = "Nouvelle Annonce Publiée par $pseudo";
        $mail->Body    = "Une nouvelle annonce a été publiée par $pseudo.<br><br>Titre : $titre<br>Categorie : $nom_cat<br>Date de publication : $d_pub<br>
                         Texte : $texte<br>Date d'expiration : $d_exp.<br><br><a href='https://intranet-gedimatlegrand.go.yo.fr/intranet/index.php'>Se connecter</a>";
        $mail->AltBody = "Une nouvelle annonce a été publiée par $pseudo.\n\nTitre : $titre\nCategorie : $nom_cat\nDate de publication : $d_pub\n
                         Texte : $texte\nDate d'expiration : $d_exp.\nSe connecter ici : https://intranet-gedimatlegrand.go.yo.fr/intranet/index.php";

        // Envoi de l'email
        $mail->send();

        // Redirection après l'ajout
        header("Location: visualisation-annonces.php?mail=good");
        exit();
    } catch (Exception $e) {
        header("Location: visualisation-annonces.php?mail={$mail->ErrorInfo}");
        exit();
    }