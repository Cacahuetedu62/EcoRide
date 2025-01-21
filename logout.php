<?php
require_once('lib/config.php'); // Pour avoir accès aux configurations globales

// Récupérer l'ID de session actuel pour le régénérer
$currentSessionId = session_id();

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enregistrer certaines informations pour le logging
$user_id = isset($_SESSION['utilisateur']['id']) ? $_SESSION['utilisateur']['id'] : null;
$pseudo = isset($_SESSION['utilisateur']['pseudo']) ? $_SESSION['utilisateur']['pseudo'] : 'Inconnu';

// Vérifier si l'utilisateur était effectivement connecté
if (isset($_SESSION['utilisateur'])) {
    // Nettoyer le cookie de session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Vider le tableau de session
    $_SESSION = array();

    // Détruire la session
    session_destroy();

    // Démarrer une nouvelle session pour les messages flash
    session_start();
    $_SESSION['flash_message'] = "Déconnexion réussie !";
}

// Rediriger avec un paramètre de cache-control
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Location: index.php');
exit();
?>