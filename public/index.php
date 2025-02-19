<?php
// Ajout de logs pour le débogage
error_log("=== DEBUG INDEX ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));



// Activer la compression de sortie avec gzip si possible
if (!ob_start("ob_gzhandler")) ob_start();

// Activer l'affichage des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger les dépendances essentielles
require_once __DIR__ . '/../vendor/autoload.php'; // Chargement des dépendances via Composer
require_once __DIR__ . '/../lib/config.php'; // Fichier de configuration
require_once __DIR__ . '/../lib/pdo.php'; // Connexion à la base de données
ini_set('session.save_path', '/tmp');
session_start();

$_SESSION['test'] = 'session active';
var_dump($_SESSION);

// Initialiser la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'URI demandée par l'utilisateur
$request_uri = $_SERVER['REQUEST_URI'];
$clean_uri = strtok($request_uri, '?'); // Supprime la partie query string
$clean_uri = rtrim($clean_uri, '/'); // Supprime le slash final pour uniformiser

// Ajouter un log pour suivre les requêtes
error_log("URI demandée : " . $clean_uri);

// Définir le fichier à charger en fonction de l'URI
if (empty($clean_uri) || $clean_uri === '/') {
    $main_file = __DIR__ . '/../index.php'; // Page d'accueil par défaut
} else {
    $main_file = __DIR__ . '/../' . ltrim($clean_uri, '/'); // Génération du chemin du fichier
    if (!str_ends_with($main_file, '.php')) {
        $main_file .= '.php'; // Ajout automatique de l'extension PHP si elle est absente
    }
}

error_log("Fichier à charger : " . $main_file);

// Vérifier si l'utilisateur tente d'accéder à un fichier statique (CSS, JS, images, etc.)
if (preg_match('/\.(css|jpg|jpeg|png|gif|js|ico|svg|webp)$/i', $clean_uri)) {
    $static_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    error_log("Tentative d'accès au fichier : " . $static_file);
    
    if (file_exists($static_file)) {
        // Définition des types MIME pour les fichiers statiques
        $mime_types = [
            'css' => 'text/css',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'js' => 'application/javascript',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp'
        ];
        
        $ext = strtolower(pathinfo($static_file, PATHINFO_EXTENSION));
        error_log("Extension détectée : " . $ext);
        
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
            readfile($static_file); // Envoyer le fichier au navigateur
            exit;
        }
    }
    error_log("Fichier non trouvé : " . $static_file);
}

// Vérifier si le fichier demandé existe et l'inclure avec header et footer
if (file_exists($main_file)) {
    require_once __DIR__ . '/../templates/header.php'; // Inclusion du header
    require_once $main_file; // Inclusion du fichier principal
    require_once __DIR__ . '/../templates/footer.php'; // Inclusion du footer
} else {
    http_response_code(404); // Envoyer une erreur 404
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../404.php'; // Inclusion de la page d'erreur
    require_once __DIR__ . '/../templates/footer.php';
}
