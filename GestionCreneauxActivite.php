<?php
class GestionCreneauxActivite {

    private Creneau $_creneau;
    private Activite $_activite;
    private array $_tableCreneaux;

    function __construct(){
        $this->$tableCreneaux = [];
    }

    public function createTupleCreneauActivite(Creneau $creneau, Activite $activite): void{
        $this->$_tableCreneaux = [$creneau,$activite];
    }

    public function visualisationCreneauxActivite(): array{
        return $this->$_tableCreneaux;
    }

    
    /*ajouterCreneau(Creneau $creneau)
    supprimerCreneau(Creneau $creneau)*/
}
?>