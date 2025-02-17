<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la configuration
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/pdo.php';

// Charger le header
require_once __DIR__ . '/../templates/header.php';

// Obtenir l'URI
$request_uri = $_SERVER['REQUEST_URI'];
$clean_uri = strtok($request_uri, '?');
$clean_uri = rtrim($clean_uri, '/');

// Charger la page principale
if (empty($clean_uri) || $clean_uri === '/') {
    require_once __DIR__ . '/../index.php';
} else {
    $file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
    
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        echo "Page non trouvée";
    }
}

// Charger le footer
require_once __DIR__ . '/../templates/footer.php';