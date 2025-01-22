<?php
require_once('lib/config.php');

// Démarrer la session si elle n'existe pas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enregistrer certaines informations pour le logging
$user_id = $_SESSION['utilisateur']['id'] ?? null;
$pseudo = $_SESSION['utilisateur']['pseudo'] ?? 'Inconnu';

// Vérifier si l'utilisateur était effectivement connecté
if (isset($_SESSION['utilisateur'])) {
    // Nettoyer le cookie de session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Vider et détruire la session
    $_SESSION = array();
    session_destroy();

    // Démarrer une nouvelle session UNIQUEMENT pour le message flash
    session_regenerate_id(true);
    $_SESSION['flash_message'] = "Déconnexion réussie !";
}

// Rediriger avec un paramètre de cache-control
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Location: index.php');
exit();
?>