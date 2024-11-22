<?php
require_once __DIR__ . '/../src/GestionUtilisateur.php';
require_once __DIR__ . '/../src/Calendrier.php';
require_once __DIR__ . '/../src/Creneau.php';
require_once __DIR__ . '/../src/Activite.php';
require_once __DIR__ . '/../src/Reservation.php';
require_once __DIR__ . '/../src/Personne.php';
require_once __DIR__ . '/../src/Utilisateur.php';
require_once __DIR__ . '/../src/GestionCreneauxActivite.php';

use PHPUnit\Framework\TestCase;

class GestionUtilisateurTest extends TestCase {
    private $calendrier;
    private $gestionUtilisateur;
    private $creneau;
    private $activite;
    private $utilisateur;

    protected function setUp(): void {
        $this->calendrier = $this->createMock(Calendrier::class);
        $this->gestionUtilisateur = new GestionUtilisateur($this->calendrier);
        $this->creneau = $this->createMock(Creneau::class);
        $this->activite = $this->createMock(Activite::class);
        $this->utilisateur = $this->createMock(Utilisateur::class);
        Reservation::reinitialiseIds();
    }

    public function testInitialisationGestionUtilisateur() {
        $this->assertEmpty($this->gestionUtilisateur->getReservations());
    }

    public function testAjoutReservation() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(1);
    
        $this->gestionUtilisateur->ajouterReservation($reservation);
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(1, $reservations);
        $this->assertSame($reservation, $reservations[0]);
    }

    public function testSupprimerReservationExistante() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(1);

        $this->gestionUtilisateur->ajouterReservation($reservation);
        $this->gestionUtilisateur->supprimerReservation($reservation);

        $this->assertEmpty($this->gestionUtilisateur->getReservations());
    }

    public function testSupprimerReservationInexistante() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(1);

        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->supprimerReservation($reservation);
    }

    public function testReserverCreneauDisponible() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(true);

        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);

        $this->creneau->expects($this->once())->method('reserverCreneau');

        $result = $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);

        $this->assertTrue($result);
        $this->assertCount(1, $this->gestionUtilisateur->getReservations());
    }

    public function testReserverCreneauIndisponible() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(false);

        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);

        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
    }

    public function testAnnulerReservationValide() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatut')->willReturn('confirmée');
        $reservation->method('getId')->willReturn(1);
        $reservation->expects($this->once())->method('setStatut')->with('annulée');
        $reservation->expects($this->once())->method('getCreneau')->willReturn($this->creneau);

        $this->creneau->expects($this->once())->method('libererCreneau');

        $this->gestionUtilisateur->ajouterReservation($reservation);
        $this->gestionUtilisateur->annulerReservation($reservation);

        $this->assertEmpty($this->gestionUtilisateur->getReservations());
    }

    public function testAnnulerReservationInvalide() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatut')->willReturn('annulée');

        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->annulerReservation($reservation);
    }

    public function testAfficherCreneauxDisponiblesParActivite() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('visualisationCreneauxActivite')->willReturn(['Creneau1', 'Creneau2']);

        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);

        $result = $this->gestionUtilisateur->afficherCreneauxDisponiblesParActivite($this->activite);

        $this->assertCount(2, $result);
        $this->assertEquals(['Creneau1', 'Creneau2'], $result);
    }

    public function testAfficherReservations() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);

        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2); 

        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);

        $reservations = $this->gestionUtilisateur->afficherReservations();
        $this->assertCount(2, $reservations);
    }

    public function testReservationsIncrementIds() {
        $this->creneau->method('reserverCreneau');
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(true);

        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);

        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);

        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertEquals(1, $reservations[0]->getId());
        $this->assertEquals(2, $reservations[1]->getId());
    }

    public function testAjouterPlusieursReservations() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);

        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(2, $reservations);
        $this->assertSame($reservation1, $reservations[0]);
        $this->assertSame($reservation2, $reservations[1]);
    }

    public function testSupprimerReservationInexistanteParId() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(99);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->supprimerReservation($reservation);
    }

    public function testReserverAvecUtilisateurNull() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(true);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, null);
    }

    public function testReserverSansGestionCreneaux() {
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn(null);
    
        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
    }

    public function testAnnulerReservationStatutInvalide() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatut')->willReturn('en attente');
    
        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->annulerReservation($reservation);
    }

    public function testAjouterReservationInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->ajouterReservation(null);
    }

    public function testAfficherCreneauxDisponiblesActiviteNull() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->afficherCreneauxDisponiblesParActivite(null);
    }

    public function testAfficherReservationsVide() {
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertEmpty($reservations);
    }

    public function testReservationsIncrementeIdsPourGrandNombre() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(true);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        for ($i = 1; $i <= 100; $i++) {
            $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
        }
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(100, $reservations);
        $this->assertEquals(100, $reservations[99]->getId());
    }

    public function testGetReservationsAvecReservations() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);

        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(2, $reservations);
        $this->assertSame($reservation1, $reservations[0]);
        $this->assertSame($reservation2, $reservations[1]);
    }

    public function testSetReservations() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation2 = $this->createMock(Reservation::class);
    
        $this->gestionUtilisateur->setReservations([$reservation1, $reservation2]);
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(2, $reservations);
        $this->assertSame($reservation1, $reservations[0]);
        $this->assertSame($reservation2, $reservations[1]);
    }

    public function testSupprimerToutesReservations() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);

        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $this->gestionUtilisateur->setReservations([]);
    
        $this->assertEmpty($this->gestionUtilisateur->getReservations());
    }

    public function testGetEtSetCalendrier() {
        $nouveauCalendrier = $this->createMock(Calendrier::class);
        $this->gestionUtilisateur->setCalendrier($nouveauCalendrier);
    
        $this->assertSame($nouveauCalendrier, $this->gestionUtilisateur->getCalendrier());
    }

    public function testReserverActiviteNonExistanteDansCalendrier() {
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn(null);
    
        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
    }

    public function testAjouterReservationIdInvalide() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(-1);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->ajouterReservation($reservation);
    }

    public function testSupprimerReservationAvecIdInvalide() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(-1);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->supprimerReservation($reservation);
    }

    public function testAfficherCreneauxDisponiblesPourActiviteSansCreneaux() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('visualisationCreneauxActivite')->willReturn([]);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        $result = $this->gestionUtilisateur->afficherCreneauxDisponiblesParActivite($this->activite);
        $this->assertEmpty($result);
    }

    public function testReserverCreneauDejaReserve() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(false);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        $this->expectException(LogicException::class);
        $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
    }

    public function testReserverAvecCreneauInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->reserver(null, $this->activite, $this->utilisateur);
    }

    public function testAjouterReservationAvecIdExistant() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(1);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    }

    public function testReserverAvecCreneauInvalideType() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->reserver('invalid', $this->activite, $this->utilisateur);
    }

    public function testAfficherCreneauxDisponiblesTypes() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('visualisationCreneauxActivite')->willReturn([
            $this->createMock(Creneau::class),
            $this->createMock(Creneau::class),
        ]);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        $result = $this->gestionUtilisateur->afficherCreneauxDisponiblesParActivite($this->activite);
    
        foreach ($result as $creneau) {
            $this->assertInstanceOf(Creneau::class, $creneau);
        }
    }

    public function testSupprimerReservationNonExistante() {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn(999);
    
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->supprimerReservation($reservation);
    }

    public function testReserverAvecActiviteInvalide() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->reserver($this->creneau, 'invalid', $this->utilisateur);
    }

    public function testSupprimerToutesReservationsApresAjout() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $this->gestionUtilisateur->setReservations([]);
    
        $this->assertEmpty($this->gestionUtilisateur->getReservations());
    }

    public function testReserverAvecNull() {
        $this->expectException(InvalidArgumentException::class);
        $this->gestionUtilisateur->reserver(null, null, null);
    }

    public function testAjouterGrandNombreDeReservations() {
        for ($i = 1; $i <= 1000; $i++) {
            $reservation = $this->createMock(Reservation::class);
            $reservation->method('getId')->willReturn($i);
            $this->gestionUtilisateur->ajouterReservation($reservation);
        }
    
        $this->assertCount(1000, $this->gestionUtilisateur->getReservations());
    }

    public function testReservationConcurrente() {
        $gestionCreneauxActivite = $this->createMock(GestionCreneauxActivite::class);
        $gestionCreneauxActivite->method('verifierDisponibilite')->willReturn(true);
    
        $this->calendrier
            ->method('trouverGestionCreneauxPourActivite')
            ->willReturn($gestionCreneauxActivite);
    
        $threads = [];
        for ($i = 0; $i < 10; $i++) {
            $threads[$i] = function () use ($i) {
                $this->gestionUtilisateur->reserver($this->creneau, $this->activite, $this->utilisateur);
            };
        }
    
        foreach ($threads as $thread) {
            $thread();
        }
    
        $reservations = $this->gestionUtilisateur->getReservations();
        $this->assertCount(10, $reservations);
    }

    public function testAfficherReservationsParUtilisateur() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);
        $reservation1->method('getPersonne')->willReturn($this->utilisateur);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
        $reservation2->method('getPersonne')->willReturn($this->utilisateur);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $result = $this->gestionUtilisateur->afficherReservationsParUtilisateur($this->utilisateur);
    
        $this->assertCount(2, $result);
        $this->assertSame([$reservation1, $reservation2], $result);
    }

    public function testAfficherReservationsUtilisateurParActivite() {
        $reservation1 = $this->createMock(Reservation::class);
        $reservation1->method('getId')->willReturn(1);
        $reservation1->method('getPersonne')->willReturn($this->utilisateur);
        $reservation1->method('getActivite')->willReturn($this->activite);
    
        $reservation2 = $this->createMock(Reservation::class);
        $reservation2->method('getId')->willReturn(2);
        $reservation2->method('getPersonne')->willReturn($this->utilisateur);
        $reservation2->method('getActivite')->willReturn($this->activite);
    
        $this->gestionUtilisateur->ajouterReservation($reservation1);
        $this->gestionUtilisateur->ajouterReservation($reservation2);
    
        $result = $this->gestionUtilisateur->afficherReservationsUtilisateurParActivite($this->utilisateur, $this->activite);
    
        $this->assertCount(2, $result);
        $this->assertSame([$reservation1, $reservation2], $result);
    }
}