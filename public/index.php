<?php
// Activez la compression Gzip
if (!ob_start("ob_gzhandler")) ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la configuration
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/pdo.php';

// Charger la page demandée
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/';

// Nettoyer l'URI
$clean_uri = strtok($request_uri, '?');
$clean_uri = rtrim($clean_uri, '/');

// Définir le chemin du fichier
if (empty($clean_uri) || $clean_uri === '/') {
    $file = __DIR__ . '/../index.php';
} else {
    // Enlever le premier slash et ajouter .php
    $file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
}

echo "URI demandée : " . $clean_uri . "<br>";
echo "Chemin du fichier : " . $file . "<br>";

if (file_exists($file)) {
    require_once $file;
} else {
    http_response_code(404);
    echo "Page non trouvée";
}