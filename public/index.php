<?php
// Activez la compression Gzip 
if (!ob_start("ob_gzhandler")) ob_start();  

// Chemins absolus pour Heroku
require_once('/app/vendor/autoload.php'); 
require_once('/app/config/config.php');  

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