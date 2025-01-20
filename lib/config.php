<?php
$config = require_once 'config.local.php';

// Configuration DB
define('DB_HOST', $config['db']['host']);
define('DB_NAME', $config['db']['name']);
define('DB_USER', $config['db']['user']);
define('DB_PASS', $config['db']['pass']);

// Configuration SMTP
define('SMTP_HOST', $config['smtp']['host']);
define('SMTP_USER', $config['smtp']['user']);
define('SMTP_PASS', $config['smtp']['pass']);

// Mode debug
define('DEBUG_MODE', $config['debug']);