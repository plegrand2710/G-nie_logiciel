<?php
/*revoir accès aux différentes méthodes*/
class GestionSuperUtilisateur extends GestionUtilisateur {
    private array $_activites;

    public function __construct(Calendrier $calendrier, array $activites = []) {
        parent::__construct($calendrier);

        foreach ($activites as $activite) {
            if (!$activite instanceof Activite) {
                throw new InvalidArgumentException("Chaque élément de la liste des activités doit être une instance de Activite.");
            }
        }

        $this->_activites = $activites;
    }

    public function creer_activite(string $nom, float $tarif, string $duree): Activite {
        if (empty($nom) || !is_string($nom)) {
            throw new InvalidArgumentException("Le nom doit être une chaîne non vide.");
        }

        if ($tarif <= 0) {
            throw new InvalidArgumentException("Le tarif doit être un nombre positif.");
        }

        //dans le constructeur gérer le fait de créer un gestioncreneauxactivite automatiquement
        $nouvelleActivite = new Activite($nom, $tarif, $duree);
        $this->_activites[] = $nouvelleActivite;

        return $nouvelleActivite;
    }

    public function supprimer_activite(Activite $activite1) {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de Activite.");
        }

        foreach ($activites as $activite) {
            if ($activite->getNom() == $activite1->getNom()) {
                //supprimer l'activite
            }
        };
    }

    public function modification_creneau(array $nouveauxCreneaux, Activite $activite) {
        foreach ($nouveauxCreneaux as $creneau) {
            if (!$creneau instanceof Creneau) {
                throw new InvalidArgumentException("Tous les créneaux doivent être des instances de la classe Creneau.");
            }
        }

        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        $gestionCreneaux = $this->getGestionCreneauxPourActivite($activite);

        if ($gestionCreneaux === null) {
            throw new LogicException("Aucune gestion de créneaux trouvée pour cette activité.");
        }

        $gestionCreneaux->supprimerTousLesCreneaux();

        foreach ($nouveauxCreneaux as $creneau) {
            $gestionCreneaux->ajouterCreneauActivite($creneau);
        }
    }

    public function modifier_activite(Activite $activite, string $nouveauNom = null, float $nouveauTarif = null, string $nouvelleDuree = null) {
        if (!$activite instanceof Activite) {
            throw new InvalidArgumentException("L'activité doit être une instance de la classe Activite.");
        }

        if ($nouveauNom !== null) {
            $activite->setNom($nouveauNom);
        }

        if ($nouveauTarif !== null && $nouveauTarif > 0) {
            $activite->setTarif($nouveauTarif);
        }

        if ($nouvelleDuree !== null) {
            $activite->setDuree($nouvelleDuree);
        }
    }

    public function getActivites(): array {
        return $this->_activites;
    }

    private function getGestionCreneauxPourActivite(Activite $activite): ?GestionCreneauxActivite {
        $calendrier = $this->getCalendrier();

        $reflect = new ReflectionClass($calendrier);
        $property = $reflect->getProperty('_gestionCreneaux');
        $property->setAccessible(true);

        $gestionCreneaux = $property->getValue($calendrier);

        foreach ($gestionCreneaux as $gestion) {
            if ($gestion instanceof GestionCreneauxActivite && $gestion->getActivite() === $activite) {
                return $gestion;
            }
        }

        return null;
    }
}