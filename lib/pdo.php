<?php

require_once 'config.php';

if (!isset($pdo)) {  // Vérifie si une connexion existe déjà
    try {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", 
            DB_USER, 
            DB_PASS, 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Exception $e) {
        // Log l'erreur avec plus de détails
        error_log(sprintf(
            "Erreur PDO [%s]: %s",
            date('Y-m-d H:i:s'),
            $e->getMessage()
        ));
        
        if (DEBUG_MODE) {
            die('Erreur de connexion : ' . $e->getMessage());
        } else {
            die('Une erreur technique est survenue.');
        }
    }
}