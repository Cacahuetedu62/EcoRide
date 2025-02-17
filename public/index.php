<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Début du script<br>";

// Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

echo "2. Autoloader chargé<br>";

// Charger la configuration
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/pdo.php';

echo "3. Configuration et PDO chargés<br>";

$index_content = file_get_contents(__DIR__ . '/../index.php');
echo "4. Contenu de index.php :<br><pre>" . htmlspecialchars($index_content) . "</pre><br>";

// Charger le fichier
require_once __DIR__ . '/../index.php';