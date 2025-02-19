<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger les dépendances essentielles
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/pdo.php';

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gérer le routage
$request_uri = $_SERVER['REQUEST_URI'];
$clean_uri = strtok($request_uri, '?');
$clean_uri = rtrim($clean_uri, '/');

// Ajouter du débogage
error_log("URI demandée : " . $clean_uri);

// Définir le fichier à charger
if (empty($clean_uri) || $clean_uri === '/') {
    $main_file = __DIR__ . '/../index.php'; // Page d'accueil
} else {
    // Cherche le fichier correspondant dans le répertoire courant
    $main_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    
    // Vérifie si le fichier existe
    if (file_exists($main_file)) {
        // Si c'est un fichier .php, on l'utilise
        if (is_file($main_file)) {
            // Assurez-vous d'inclure .php si non spécifié
            if (!str_ends_with($main_file, '.php')) {
                $main_file .= '.php';
            }
        }
    } else {
        // Si le fichier n'existe pas, retournez une 404
        http_response_code(404);
        require_once __DIR__ . '/../templates/header.php';
        require_once __DIR__ . '/../404.php'; // Page 404 personnalisée
        require_once __DIR__ . '/../templates/footer.php';
        exit;
    }
}

error_log("Fichier à charger : " . $main_file);

// Charger la page demandée avec header et footer
require_once __DIR__ . '/../templates/header.php'; // Inclure l'en-tête
require_once $main_file; // Inclure le contenu principal
require_once __DIR__ . '/../templates/footer.php'; // Inclure le pied de page
