<?php
// Activez la compression Gzip 
if (!ob_start("ob_gzhandler")) ob_start();  

// Charger l'autoloader de Composer 
require_once __DIR__ . '/../vendor/autoload.php'; 

// Charger la configuration à partir du bon chemin
require_once __DIR__ . '/../lib/config.php';  

// Déterminer l'environnement de production pour Heroku
$isProduction = true;

// Reste de votre code...

// Votre fichier principal de routage ou de contrôleur
require_once('/app/index.php');

// Routage
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Affichez quelque chose si aucune route n'est trouvée
if (empty($uri) || $uri === '/') {
    // Chargez votre page d'accueil principale
    echo "Bienvenue sur EcoRide !";
} else {
    // Route par défaut ou page 404
    http_response_code(404);
    echo "Page non trouvée";
}