<?php
error_log("=== DEBUG INDEX ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

if (!ob_start("ob_gzhandler")) ob_start();

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

// Si c'est une requête pour un fichier statique (css, images, etc.)
if (preg_match('/\.(css|jpg|jpeg|png|gif|js|ico|svg|webp)$/i', $clean_uri)) {
    // Liste des dossiers où chercher les fichiers statiques
    $search_paths = [
        __DIR__ . '/../', // Racine du projet
        __DIR__ . '/../images/', // Dossier images
        __DIR__ . '/../uploads/', // Dossier uploads
        __DIR__ . '/../uploads/photos/', // Sous-dossier photos dans uploads
        __DIR__ . '/assets/', // Dossier assets dans public si vous en avez un
    ];

    $clean_path = ltrim($clean_uri, '/');
    $file_found = false;

    foreach ($search_paths as $base_path) {
        // Vérifier d'abord si le fichier existe directement
        if (file_exists($base_path . $clean_path)) {
            $static_file = $base_path . $clean_path;
            $file_found = true;
            break;
        }
        
        // Vérifier aussi juste le nom du fichier (pour les images dans les sous-dossiers)
        $filename = basename($clean_path);
        if (file_exists($base_path . $filename)) {
            $static_file = $base_path . $filename;
            $file_found = true;
            break;
        }
    }

    if ($file_found) {
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
        if (isset($mime_types[$ext])) {
            // Ajouter des headers pour le cache
            $cache_time = 604800; // Une semaine
            header('Cache-Control: public, max-age=' . $cache_time);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
            header('Content-Type: ' . $mime_types[$ext]);
            readfile($static_file);
            exit;
        }
    }
    error_log("Fichier non trouvé après recherche dans tous les dossiers : " . $clean_path);
}

// Définir le fichier à charger pour les pages PHP
if (empty($clean_uri) || $clean_uri === '/') {
    $main_file = __DIR__ . '/../index.php';
} else {
    $main_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($main_file, '.php')) {
        $main_file .= '.php';
    }
}

// Charger la page demandée avec header et footer
if (file_exists($main_file)) {
    require_once __DIR__ . '/../templates/header.php';
    require_once $main_file;
    require_once __DIR__ . '/../templates/footer.php';
} else {
    http_response_code(404);
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../404.php';
    require_once __DIR__ . '/../templates/footer.php';
}

foreach ($search_paths as $base_path) {
    error_log("Checking path: " . $base_path . $clean_path);
    if (file_exists($base_path . $clean_path)) {
        $static_file = $base_path . $clean_path;
        $file_found = true;
        error_log("File found: " . $static_file);
        break;
    }

    $filename = basename($clean_path);
    error_log("Checking filename: " . $base_path . $filename);
    if (file_exists($base_path . $filename)) {
        $static_file = $base_path . $filename;
        $file_found = true;
        error_log("File found: " . $static_file);
        break;
    }
}
