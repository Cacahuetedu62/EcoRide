<?php
// Activez la compression Gzip
if (!ob_start("ob_gzhandler")) ob_start();

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

// Obtenir l'URI complète
$request_uri = $_SERVER['REQUEST_URI'];
echo "4. URI demandée : " . $request_uri . "<br>";

// Nettoyer l'URI
$clean_uri = strtok($request_uri, '?');
$clean_uri = rtrim($clean_uri, '/');
echo "5. URI nettoyée : " . $clean_uri . "<br>";

// Définir le chemin du fichier
if (empty($clean_uri) || $clean_uri === '/') {
    $file = __DIR__ . '/../index.php';
} else {
    $file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
}

echo "6. Fichier à charger : " . $file . "<br>";
echo "7. Le fichier existe ? : " . (file_exists($file) ? 'Oui' : 'Non') . "<br>";

if (file_exists($file)) {
    echo "8. Chargement du fichier...<br>";
    require_once $file;
    echo "9. Fichier chargé<br>";
} else {
    http_response_code(404);
    echo "8. Page non trouvée<br>";
}