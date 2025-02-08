<?php
// Charger l'autoloader de Composer en premier
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

// Imports explicites
use MongoDB\Client;

try {
    // Test MySQL
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p>Connexion à la base de données MySQL réussie!</p>";
    
    // Test MongoDB
    $mongoUri = "mongodb://" . MONGODB_HOST . ":" . MONGODB_PORT;
    $mongoClient = new Client($mongoUri);
    $db = $mongoClient->selectDatabase(MONGODB_DB);
    echo "<p>Connexion à MongoDB réussie!</p>";

} catch(PDOException $e) {
    echo "<p style='color: red'>Erreur MySQL: " . $e->getMessage() . "</p>";
} catch(Exception $e) {
    echo "<p style='color: red'>Erreur générale ou MongoDB: " . $e->getMessage() . "</p>";
}