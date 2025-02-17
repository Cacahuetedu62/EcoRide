<?php
// Active la compression Gzip 
if (!ob_start("ob_gzhandler")) ob_start();  

// Charger l'autoloader de Composer en premier 
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/config.php';  

// Gestion de la réécriture d'URL
if ($_SERVER['REQUEST_URI'] != '/' && !file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'])) {
    // Routage pour les routes non existantes
    include 'index.php';
}

// Vérifie et utilise le port Heroku
$port = getenv('PORT') ?: 5000;

// Le reste de votre code...
echo "Application démarrée sur le port $port";

// Votre logique principale de routage ici
echo "Bienvenue sur EcoRide !";