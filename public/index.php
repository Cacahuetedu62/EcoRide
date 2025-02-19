<?php
error_log("=== DEBUG INDEX ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

if (!ob_start("ob_gzhandler")) ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $main_file = __DIR__ . '/../index.php';
} else {
    $main_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($main_file, '.php')) {
        $main_file .= '.php';
    }
}

error_log("Fichier à charger : " . $main_file);

// Si c'est une requête pour un fichier statique (css, images, etc.)
if (preg_match('/\.(css|jpg|jpeg|png|gif|js|ico|svg|webp)$/i', $clean_uri)) {
    $static_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    error_log("Tentative d'accès au fichier : " . $static_file);
    
    if (file_exists($static_file)) {
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
            readfile($static_file);
            exit;
        }
    }
    error_log("Fichier non trouvé : " . $static_file);
}

