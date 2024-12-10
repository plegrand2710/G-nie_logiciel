<?php

class BaseDeDonnees {
    private static $instance = null;
    private $connexion;

    public function __construct() {
        $host = 'localhost';
        $port = '8889';
        $dbname = 'gestion_salle_sport';
        $user = 'root';
        $pass = 'root';

        try {
            $this->connexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
            $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur de connexion : " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnexion(): PDO {
        return $this->connexion;
    }
}