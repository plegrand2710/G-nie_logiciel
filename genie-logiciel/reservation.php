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

        <div class="recap">
            <p><strong>Date :</strong> <span id="dateReservation"></span></p>
            <p><strong>Heure :</strong> <span id="heureReservation"></span></p>
            <p><strong>Activité :</strong> <span id="activiteReservation"></span></p>
            <p><strong>Tarif :</strong> <span id="tarifReservation"></span> €</p>
        </div>

        <h2>Paiement</h2>
        <form id="paymentForm" action="processPayment.php" method="POST">
            <input type="hidden" name="date" id="inputDate">
            <input type="hidden" name="heureDebut" id="inputHeureDebut">
            <input type="hidden" name="heureFin" id="inputHeureFin">
            <input type="hidden" name="activite" id="inputActivite">
            <input type="hidden" name="tarif" id="inputTarif">

            <label for="cardName">Nom sur la carte :</label>
            <input type="text" id="cardName" name="cardName" required>

            <label for="cardNumber">Numéro de carte :</label>
            <input type="text" id="cardNumber" name="cardNumber" required>

            <label for="expiryDate">Date d'expiration :</label>
            <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/AA" required>

            <label for="cvv">CVV :</label>
            <input type="text" id="cvv" name="cvv" required>

            <button type="submit" class="btn-pay">Payer</button>
        </form>
    </div>

    <script src="script.js"></script>
</body>
</html>