<?php
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

// Définir le fichier à charger
if (empty($clean_uri) || $clean_uri === '/') {
    $main_file = __DIR__ . '/../index.php';
} else {
    $main_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (!str_ends_with($main_file, '.php')) {
        $main_file .= '.php';
    }
}

// Si c'est une requête pour un fichier statique (css, images, etc.)
if (preg_match('/\.(css|jpg|jpeg|png|gif|js)$/', $clean_uri)) {
    $static_file = __DIR__ . '/../' . ltrim($clean_uri, '/');
    if (file_exists($static_file)) {
        $mime_types = [
            'css' => 'text/css',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'js' => 'application/javascript'
        ];
        $ext = pathinfo($static_file, PATHINFO_EXTENSION);
        header('Content-Type: ' . $mime_types[$ext]);
        readfile($static_file);
        exit;
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