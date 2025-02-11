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


// if (!isset($pdo)) {  // Vérifie si une connexion existe déjà
//     try {
//         // Ajout de logs détaillés
//         error_log("Paramètres de connexion :");
//         error_log("Hôte : " . DB_HOST);
//         error_log("Base de données : " . DB_NAME);
//         error_log("Utilisateur : " . DB_USER);
//         error_log("Port : " . DB_PORT);

//         $dsn = sprintf(
//             "mysql:host=%s;dbname=%s;charset=utf8mb4;port=%s",
//             DB_HOST,
//             DB_NAME,
//             DB_PORT
//         );

//         $pdo = new PDO(
//             $dsn, 
//             DB_USER, 
//             DB_PASS, 
//             [
//                 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//                 PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
//             ]
//         );

//         // Test de connexion
//         if (DEBUG_MODE) {
//             // Vérification de la base de données
//             $stmt = $pdo->query("SELECT DATABASE()");
//             $dbname = $stmt->fetchColumn();
//             error_log("Connecté à la base de données: " . $dbname);
            
//             // Vérification de l'existence de la table
//             $stmt = $pdo->query("SHOW TABLES LIKE 'utilisateurs'");
//             $tableExists = $stmt->rowCount();
//             error_log("Nombre de tables 'utilisateurs' trouvées : " . $tableExists);

//             // Liste de toutes les tables
//             $stmt = $pdo->query("SHOW TABLES");
//             $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
//             error_log("Tables existantes : " . implode(', ', $tables));

//             // Vérification de la structure de la table utilisateurs
//             try {
//                 $stmt = $pdo->query("DESCRIBE utilisateurs");
//                 $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
//                 error_log("Colonnes de la table utilisateurs : " . implode(', ', $columns));
//             } catch (PDOException $e) {
//                 error_log("Erreur lors de la description de la table utilisateurs : " . $e->getMessage());
//             }
//         }

//     } catch (PDOException $e) {
//         error_log(sprintf(
//             "Erreur PDO [%s]: %s\nDSN: %s",
//             date('Y-m-d H:i:s'),
//             $e->getMessage(),
//             $dsn
//         ));
        
//         if (DEBUG_MODE) {
//             die('Erreur de connexion : ' . $e->getMessage());
//         } else {
//             die('Une erreur technique est survenue.');
//         }
//     }
// }

// try {
//     $pdo = new PDO("mysql:host=db;dbname=ecoride;charset=utf8mb4", 'ecoride_user', 'secure_password');
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     // Vérification de la base de données
//     $stmt = $pdo->query("SELECT DATABASE()");
//     $currentDb = $stmt->fetchColumn();
//     echo "Base de données courante : $currentDb<br>";

//     // Liste des tables
//     $stmt = $pdo->query("SHOW TABLES");
//     $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
//     if (empty($tables)) {
//         echo "AUCUNE TABLE TROUVÉE<br>";
//     } else {
//         echo "Tables trouvées : " . implode(', ', $tables) . "<br>";
//     }

// } catch (PDOException $e) {
//     echo "Erreur : " . $e->getMessage();
// }


// try {
//     $dsn = sprintf(
//         "mysql:host=%s;dbname=%s;charset=utf8mb4;port=%s",
//         DB_HOST,
//         DB_NAME,
//         DB_PORT
//     );

//     // Logs détaillés
//     error_log("Tentative de connexion avec :");
//     error_log("DSN : " . $dsn);
//     error_log("Utilisateur : " . DB_USER);

//     $pdo = new PDO(
//         $dsn, 
//         DB_USER, 
//         DB_PASS, 
//         [
//             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
//         ]
//     );

//     // Vérification des tables
//     $stmt = $pdo->query("SHOW TABLES");
//     $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
//     error_log("Tables existantes : " . implode(', ', $tables));

// } catch (PDOException $e) {
//     error_log("Erreur de connexion détaillée : " . $e->getMessage());
//     throw $e;
// }

// return $pdo;