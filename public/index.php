<?php
// Activez la compression Gzip 
if (!ob_start("ob_gzhandler")) ob_start();  

// Assurez-vous que Heroku utilise le bon port
$port = getenv('PORT') ?: 5000;

// Charger l'autoloader de Composer 
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/config.php';  

// Redirection vers l'index principal
require_once __DIR__ . '/../index.php';