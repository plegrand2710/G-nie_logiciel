function reserverCreneau(date, heureDebut, heureFin) {
    const [year, month, day] = date.split('-');
    const formattedDate = `${day}/${month}/${year}`; 

    if (confirm(`Voulez-vous réserver ce créneau ?\nDate: ${formattedDate}\nHeure: ${heureDebut} - ${heureFin}`)) {
        window.location.href = `reservation.php?date=${encodeURIComponent(date)}&heureDebut=${encodeURIComponent(heureDebut)}&heureFin=${encodeURIComponent(heureFin)}`;
    }
}