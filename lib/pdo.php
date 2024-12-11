<?php

try {
    $pdo = new PDO("mysql:dbname="._DB_ECORIDE_.";host="._DB_SERVER_.";charset=utf8mb4", _DB_USER_, _DB_PASSWORD_);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    
    error_log("Erreur PDO : " . $e->getMessage(), 3, "/var/log/mon_log_erreurs.log");
    die('Une erreur est survenue, veuillez réessayer plus tard.');
}

