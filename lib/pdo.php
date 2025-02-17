<?php
require_once 'config.php';

if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", 
            DB_USER, 
            DB_PASS, 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        if (DEBUG_MODE) {
            // Test de connexion
            $stmt = $pdo->query("SELECT DATABASE()");
            $dbname = $stmt->fetchColumn();
            error_log("Connecté à la base de données: " . $dbname);
        }
    } catch (PDOException $e) {
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

// MongoDB connection
function getMongoDB() {
    if (defined('MONGODB_URI')) {
        // Production (MongoDB Atlas)
        $client = new MongoDB\Client(MONGODB_URI);
    } else {
        // Local
        $mongoUri = "mongodb://" . MONGODB_HOST . ":" . MONGODB_PORT;
        $client = new MongoDB\Client($mongoUri);
    }
    return $client->selectDatabase(MONGODB_DB);
}

// POUR TRAVAIL EN LOCAL require_once 'config.php';

// if (!isset($pdo)) {  // Vérifie si une connexion existe déjà
//     try {
//         $pdo = new PDO(
//             "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", 
//             DB_USER, 
//             DB_PASS, 
//             [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
//         );
//     } catch (Exception $e) {
//         // Log l'erreur avec plus de détails
//         error_log(sprintf(
//             "Erreur PDO [%s]: %s",
//             date('Y-m-d H:i:s'),
//             $e->getMessage()
//         ));
        
//         if (DEBUG_MODE) {
//             die('Erreur de connexion : ' . $e->getMessage());
//         } else {
//             die('Une erreur technique est survenue.');
//         }
//     }
// }

