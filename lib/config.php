<?php
if (!file_exists(__DIR__ . '/config.local.php')) {
    die('Le fichier de configuration est manquant');
}

// Retirer les paramètres de session d'ici car ils doivent être définis avant session_start()
$config = require_once 'config.local.php';

// Configuration DB
define('DB_HOST', $config['db']['host']);
define('DB_NAME', $config['db']['name']);
define('DB_USER', $config['db']['user']);
define('DB_PASS', $config['db']['pass']);
define('DB_PORT', '3306');

// Configuration MongoDB
define('MONGODB_HOST', $config['mongodb']['host']);
define('MONGODB_PORT', $config['mongodb']['port']);
define('MONGODB_DB', $config['mongodb']['db']);

// Configuration SMTP
define('SMTP_HOST', $config['smtp']['host']);
define('SMTP_USER', $config['smtp']['user']);
define('SMTP_PASS', $config['smtp']['pass']);

// Mode debug
define('DEBUG_MODE', $config['debug']);

// Configuration des erreurs selon le mode debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Headers de sécurité basiques
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');