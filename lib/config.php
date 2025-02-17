<!-- POUR TRAVAIL EN LOCAL
// Configuration des paramètres de session (AVANT session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);

// Vérification de l'existence du fichier de configuration
if (!file_exists(__DIR__ . '/config.local.php')) {
    die('Configuration file not found');
}

$config = require_once 'config.local.php';

// Configuration DB
define('DB_HOST', $config['db']['host']);
define('DB_NAME', $config['db']['name']);
define('DB_USER', $config['db']['user']);
define('DB_PASS', $config['db']['pass']);

// Configuration MongoDB simple
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
header('X-Content-Type-Options: nosniff'); --> 

<?php
// Configuration des paramètres de session (AVANT session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);

// Déterminer l'environnement
$isProduction = getenv('PRODUCTION') === 'true';

// Charger la configuration appropriée
if ($isProduction) {
    $configFile = __DIR__ . '/config.prod.php';
} else {
    $configFile = __DIR__ . '/config.local.php';
}

if (!file_exists($configFile)) {
    die('Configuration file not found: ' . $configFile);
}

$config = require_once $configFile;

// Configuration DB
define('DB_HOST', $config['db']['host']);
define('DB_NAME', $config['db']['name']);
define('DB_USER', $config['db']['user']);
define('DB_PASS', $config['db']['pass']);

// Configuration MongoDB
if ($isProduction) {
    define('MONGODB_URI', $config['mongodb']['uri']);
} else {
    define('MONGODB_HOST', $config['mongodb']['host']);
    define('MONGODB_PORT', $config['mongodb']['port']);
}
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